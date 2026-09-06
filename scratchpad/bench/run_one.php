<?php
/**
 * Runs one bench workflow once and prints the metrics for that run.
 *
 * Usage: drush php:script run_one.php -- <workflow_id> <url>
 */
$id  = $extra[0] ?? 'bench_1_reference';
$url = $extra[1] ?? 'https://d34dman.github.io/flowdrop-ai-bench/corpus/v1/medium.html';

// drush php:script executes as the anonymous user, whose quota is a small
// shared ceiling meant for public traffic — a benchmark run exhausts it and
// then fails mid-matrix. Attribute the runs to user 1 instead.
$accountSwitcher = \Drupal::service('account_switcher');
$accountSwitcher->switchTo(\Drupal\user\Entity\User::load(1));

$etm = \Drupal::entityTypeManager();
$db  = \Drupal::database();

$wf = $etm->getStorage('flowdrop_workflow')->load($id);
if (!$wf) { print "no such workflow: $id\n"; return; }

$launcher = \Drupal::service('flowdrop_workflow_executor.launcher');
$opts = new \Drupal\flowdrop_workflow_executor\DTO\LaunchOptions(wait: TRUE);

// Every usage row written from here on belongs to this run: the launch is
// synchronous and the harness runs one workflow at a time.
$maxBefore = (int) $db->query('SELECT COALESCE(MAX(id),0) FROM {ai_metering_usage}')->fetchField();

$t0 = microtime(TRUE);
$res = $launcher->launch($wf, ['url' => $url], $opts);
$wall = microtime(TRUE) - $t0;

printf("workflow : %s\nurl      : %s\npipeline : %s\nstatus   : %s\nwall     : %.2fs\n\n", $id, $url, $res->pipelineId, $res->status, $wall);

// --- per-node timing ------------------------------------------------------
$pipeline = $etm->getStorage('flowdrop_pipeline')->load($res->pipelineId);
$jobs = [];
foreach ($pipeline->get('job_id') as $ref) {
  if ($j = $ref->entity) { $jobs[] = $j; }
}
print "nodes:\n";
$finalMessage = NULL;
foreach ($jobs as $j) {
  $started   = (int) $j->get('started')->value;
  $completed = (int) $j->get('completed')->value;
  $nodeType  = $j->get('node_type_id')->target_id ?? '?';
  printf("   %-46s %-10s %ss\n", $nodeType, $j->get('status')->value, $completed && $started ? $completed - $started : '?');

  if (str_contains((string) $nodeType, 'output')) {
    $d = json_decode((string) $j->get('output_data')->value, TRUE);
    if (!empty($d['message'])) { $finalMessage = $d['message']; }
  }
}

// --- tokens and cost ------------------------------------------------------
$rows = $db->query('SELECT model_id, input_tokens, output_tokens, cached_tokens, estimated_cost_usd, status
  FROM {ai_metering_usage} WHERE id > :m ORDER BY id', [':m' => $maxBefore])->fetchAll();
printf("\nllm calls: %d\n", count($rows));
$tin = $tout = $tcache = 0; $cost = 0.0;
foreach ($rows as $r) {
  printf("   %-26s in=%-7d out=%-6d cached=%-6d \$%.6f %s\n",
    $r->model_id, $r->input_tokens, $r->output_tokens, $r->cached_tokens, $r->estimated_cost_usd, $r->status);
  $tin += $r->input_tokens; $tout += $r->output_tokens; $tcache += $r->cached_tokens; $cost += (float) $r->estimated_cost_usd;
}
printf("totals   : in=%d out=%d cached=%d cost=\$%.6f\n", $tin, $tout, $tcache, $cost);

if ($finalMessage !== NULL) {
  printf("\n--- output (%d chars) ---\n%s\n", strlen($finalMessage), substr($finalMessage, 0, 500));
}
