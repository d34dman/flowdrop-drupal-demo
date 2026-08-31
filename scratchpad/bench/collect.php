<?php
/**
 * Derives every benchmark metric from what FlowDrop and ai_metering already
 * stored, using only the launch ledger (run -> pipeline id + context uuid).
 *
 * Nothing here observes a run as it happens, so runs may be launched in any
 * order, in parallel, or days earlier, and re-collected as often as wanted
 * without spending anything. Timing comes from each job's execution_time_us
 * (microseconds) rather than the second-resolution started/completed stamps.
 *
 * Usage: drush php:script collect.php -- <results_dir>
 */
$dir = $extra[0] ?? 'scratchpad/bench/results';
if ($dir[0] !== '/') { $dir = dirname(DRUPAL_ROOT) . '/' . $dir; }

$etm = \Drupal::entityTypeManager();
$db  = \Drupal::database();
$pipelineStorage = $etm->getStorage('flowdrop_pipeline');

// Node types whose time is model time; everything else is the graph's own work.
$isAiNode = static fn (string $nt): bool => (bool) preg_match(
  '/ai_provider_chat|processor_reason|ai_agents_executor|react_agent/', $nt);

// Hand-written notes about individual runs, keyed by run id. They record what
// the stored state cannot: chiefly that a failed run's work was retrievable
// from job output even though the engine delivered nothing. Kept out of the
// derived record so re-collection never erases them.
$annotations = [];
if (is_file("$dir/annotations.jsonl")) {
  foreach (file("$dir/annotations.jsonl", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    $a = json_decode($l, TRUE);
    if (isset($a['run_id'])) { $annotations[$a['run_id']][] = $a; }
  }
}

if (!is_dir("$dir/outputs")) { mkdir("$dir/outputs", 0777, TRUE); }
$out = fopen("$dir/metrics.jsonl", 'w');
$n = 0;

foreach (file("$dir/runs.jsonl", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
  $run = json_decode($line, TRUE);
  $pipeline = $pipelineStorage->load($run['pipeline_id']);
  if (!$pipeline) {
    printf("  !! pipeline %s missing for %s\n", $run['pipeline_id'], $run['run_id']);
    continue;
  }

  // --- timing and per-node trail, from stored job metadata ---------------
  $nodes = [];
  $aiUs = $deterministicUs = 0;
  $retries = 0;
  // Bytes FlowDrop had to serialise and store for this run. Orchestrator
  // overhead tracks this far more closely than it tracks node count.
  $payloadBytes = 0;
  $output = NULL;
  $failed = [];
  foreach ($pipeline->get('job_id') as $ref) {
    $job = $ref->entity;
    if (!$job) { continue; }
    $nt  = (string) ($job->get('node_type_id')->target_id ?? '?');
    $meta = $job->getMetadata();
    $us = isset($meta['execution_time_us']) ? (int) $meta['execution_time_us'] : NULL;
    $status = (string) $job->get('status')->value;
    $retries += (int) $job->get('retry_count')->value;
    $payloadBytes += strlen((string) $job->get('input_data')->value)
      + strlen((string) $job->get('output_data')->value);

    if ($us !== NULL) {
      if ($isAiNode($nt)) { $aiUs += $us; } else { $deterministicUs += $us; }
    }
    if ($status !== 'completed') {
      $failed[] = $nt . ':' . $status;
    }
    $nodes[] = [
      'node_type' => $nt,
      'status' => $status,
      'seconds' => $us !== NULL ? round($us / 1e6, 3) : NULL,
      'is_ai' => $isAiNode($nt),
      'error' => $job->get('error_message')->value ?: NULL,
    ];
    if (str_contains($nt, 'output')) {
      $d = json_decode((string) $job->get('output_data')->value, TRUE);
      foreach (['message', 'text'] as $k) {
        if (!empty($d[$k])) { $output = $d[$k]; }
      }
    }
  }

  // --- tokens and cost, by the tag each call carried ---------------------
  $rows = $db->query('SELECT model_id, input_tokens, output_tokens, cached_tokens,
    estimated_cost_usd, status FROM {ai_metering_usage} WHERE context_id = :c ORDER BY id',
    [':c' => $run['context_uuid']])->fetchAll();
  $tin = $tout = $tcache = 0; $cost = 0.0; $models = [];
  foreach ($rows as $r) {
    $tin += (int) $r->input_tokens; $tout += (int) $r->output_tokens;
    $tcache += (int) $r->cached_tokens; $cost += (float) $r->estimated_cost_usd;
    $models[$r->model_id] = TRUE;
  }

  if ($output !== NULL) { file_put_contents("$dir/outputs/{$run['run_id']}.md", $output); }

  fwrite($out, json_encode($run + [
    'pipeline_status' => $pipeline->getStatus(),
    'failed_nodes' => $failed,
    'job_count' => count($nodes),
    'payload_bytes' => $payloadBytes,
    'total_seconds' => round(($aiUs + $deterministicUs) / 1e6, 3),
    'ai_seconds' => round($aiUs / 1e6, 3),
    'deterministic_seconds' => round($deterministicUs / 1e6, 3),
    'llm_calls' => count($rows),
    'models' => array_keys($models),
    'input_tokens' => $tin,
    'output_tokens' => $tout,
    'cached_tokens' => $tcache,
    'cost_usd' => round($cost, 6),
    'retries' => $retries,
    'output_chars' => $output !== NULL ? strlen($output) : NULL,
    'nodes' => $nodes,
    // The status stays whatever the engine reported; an annotation never
    // upgrades a failed run into a successful one.
    'annotations' => $annotations[$run['run_id']] ?? [],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
  $n++;
}
fclose($out);
printf("collected %d runs -> %s/metrics.jsonl\n", $n, $dir);
