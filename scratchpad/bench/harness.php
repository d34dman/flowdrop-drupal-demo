<?php
/**
 * Benchmark harness: runs every (workflow x model x url) cell N times and
 * appends one JSON record per run to results.jsonl.
 *
 * Usage: drush php:script harness.php -- <out_dir> [reps]
 *
 * Correlation of AI usage rows to a run relies on the harness being serial:
 * launches are synchronous and one at a time, so every ai_metering_usage row
 * written between the before/after id snapshots belongs to the run in between.
 */
$outDir = $extra[0] ?? 'scratchpad/bench/results';
$reps   = (int) ($extra[1] ?? 3);

// drush runs with getcwd() at the Drupal root, so a relative path lands
// inside web/. Anchor it at the project root instead, and fail loudly:
// a silently unwritable directory means an entire matrix is lost.
if ($outDir[0] !== '/') { $outDir = dirname(DRUPAL_ROOT) . '/' . $outDir; }
foreach ([$outDir, "$outDir/outputs"] as $dir) {
  if (!is_dir($dir) && !mkdir($dir, 0777, TRUE) && !is_dir($dir)) {
    throw new \RuntimeException("cannot create output directory: $dir");
  }
}
printf("writing to %s\n", $outDir);

$WORKFLOWS = [
  'bench_1_reference',
  'bench_2_raw_html_llm',
  'bench_3_markdown_llm',
  'bench_4_ai_agent_tool',
  'bench_5_react_agent',
  'bench_5a_react_agent_naive',
];

// Deliberately spans a size range: the input-token cost of the variants that
// feed raw HTML to the model scales with the page, not with its content.
$URLS = [
  // Ascending raw-HTML size (~38KB / ~164KB / ~535KB). The span is the point:
  // the variants that feed raw HTML to the model pay for the page, not for
  // the content in it.
  'small'  => 'https://www.drupal.org/about',
  'medium' => 'https://www.ibm.com/think/topics/drupal-wordpress',
  'large'  => 'https://en.wikipedia.org/wiki/Drupal',
];

// drush php:script executes as the anonymous user, whose quota is a small
// shared ceiling meant for public traffic — a benchmark run exhausts it and
// then fails mid-matrix. Attribute the runs to user 1 instead.
$accountSwitcher = \Drupal::service('account_switcher');
$accountSwitcher->switchTo(\Drupal\user\Entity\User::load(1));

$etm = \Drupal::entityTypeManager();
$db  = \Drupal::database();
$launcher = \Drupal::service('flowdrop_workflow_executor.launcher');
$runContext = \Drupal::service('fd_bench.run_context');
$wfStorage = $etm->getStorage('flowdrop_workflow');

// Correlating usage rows to a run by id window is only sound while this is
// the only thing executing workflows. Two concurrent harnesses interleave
// their rows and silently attribute each other's tokens — which is how the
// first matrix produced single-LLM-node runs recording four model calls.
$lockPath = "$outDir/harness.lock";
$lock = fopen($lockPath, 'c');
if ($lock === FALSE || !flock($lock, LOCK_EX | LOCK_NB)) {
  throw new \RuntimeException("another harness run holds $lockPath — refusing to run concurrently");
}
ftruncate($lock, 0);
fwrite($lock, (string) getmypid());

$fh = fopen("$outDir/results.jsonl", 'a');
if ($fh === FALSE) { throw new \RuntimeException("cannot open $outDir/results.jsonl for append"); }

for ($rep = 1; $rep <= $reps; $rep++) {
  printf("\n=== repetition %d/%d ===\n", $rep, $reps);
  foreach ($URLS as $urlKey => $url) {
    foreach ($WORKFLOWS as $wfId) {
      $wf = $wfStorage->load($wfId);
      if (!$wf) { printf("!! missing %s\n", $wfId); continue; }
      $runId = sprintf('%s__%s__r%d__%d', $wfId, $urlKey, $rep, time());

      // Stamp this run so its AI calls identify themselves in the usage table.
      $runUuid = \Drupal::service('uuid')->generate();
      $runContext->set($runUuid);
      $t0 = microtime(TRUE);
      $status = 'error'; $error = NULL; $pipelineId = NULL;
      try {
        $res = $launcher->launch($wf, ['url' => $url], new \Drupal\flowdrop_workflow_executor\DTO\LaunchOptions(wait: TRUE));
        $pipelineId = $res->pipelineId;
        $status = $res->status;
      }
      catch (\Throwable $e) {
        $error = $e->getMessage();
      }
      $wall = microtime(TRUE) - $t0;
      $runContext->set(NULL);

      // Usage rows for this run.
      $rows = $db->query('SELECT model_id, provider_id, input_tokens, output_tokens, cached_tokens,
        estimated_cost_usd, status FROM {ai_metering_usage} WHERE context_id = :ctx ORDER BY id',
        [':ctx' => $runUuid])->fetchAll();
      $tin = $tout = $tcache = 0; $cost = 0.0; $models = [];
      foreach ($rows as $r) {
        $tin += (int) $r->input_tokens; $tout += (int) $r->output_tokens;
        $tcache += (int) $r->cached_tokens; $cost += (float) $r->estimated_cost_usd;
        $models[$r->model_id] = TRUE;
      }

      // Per-node trail and the final output artifact.
      $nodes = []; $output = NULL; $retries = 0;
      if ($pipelineId && ($pipeline = $etm->getStorage('flowdrop_pipeline')->load($pipelineId))) {
        foreach ($pipeline->get('job_id') as $ref) {
          $j = $ref->entity;
          if (!$j) { continue; }
          $nt = (string) ($j->get('node_type_id')->target_id ?? '?');
          $started = (int) $j->get('started')->value;
          $completed = (int) $j->get('completed')->value;
          $retries += (int) $j->get('retry_count')->value;
          $nodes[] = [
            'node_type' => $nt,
            'status' => $j->get('status')->value,
            'seconds' => ($started && $completed) ? $completed - $started : NULL,
            'error' => $j->get('error_message')->value ?: NULL,
          ];
          if (str_contains($nt, 'output')) {
            $d = json_decode((string) $j->get('output_data')->value, TRUE);
            if (!empty($d['message'])) { $output = $d['message']; }
          }
        }
      }
      if ($output !== NULL) { file_put_contents("$outDir/outputs/$runId.md", $output); }

      $record = [
        'run_id' => $runId,
        'context_uuid' => $runUuid,
        'workflow' => $wfId,
        'url_key' => $urlKey,
        'url' => $url,
        'rep' => $rep,
        'status' => $status,
        'error' => $error,
        'pipeline_id' => $pipelineId,
        'wall_seconds' => round($wall, 3),
        'llm_calls' => count($rows),
        // Single-model variants must record exactly one call; anything else
        // means the usage window caught rows this run did not make.
        'suspect_correlation' => in_array($wfId, ['bench_2_raw_html_llm', 'bench_3_markdown_llm'], TRUE)
          ? count($rows) !== 1
          : ($wfId === 'bench_1_reference' ? count($rows) !== 0 : FALSE),
        'models' => array_keys($models),
        'input_tokens' => $tin,
        'output_tokens' => $tout,
        'cached_tokens' => $tcache,
        'cost_usd' => round($cost, 6),
        'retries' => $retries,
        'output_chars' => $output !== NULL ? strlen($output) : NULL,
        'nodes' => $nodes,
        'ts' => gmdate('c'),
      ];
      fwrite($fh, json_encode($record, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n");
      fflush($fh);

      printf("  %-24s %-5s r%d  %-10s %6.1fs  %2d calls  in=%-7d out=%-6d \$%.5f\n",
        $wfId, $urlKey, $rep, $status, $wall, count($rows), $tin, $tout, $cost);
    }
  }
}
fclose($fh);
printf("\nWrote %s/results.jsonl\n", $outDir);
