<?php
// For every B5 row frozen in runs.csv: which prompt text did the parent forward into the sub-pipeline?
$db = \Drupal::database();
$pipes = $db->query("SELECT id, workflow_id, parent_pipeline_id, created, input_data FROM {flowdrop_pipeline} ORDER BY id")->fetchAllAssoc('id');
$subs = array_filter($pipes, fn($p) => $p->workflow_id === 'react_agent_with_tools');
$ledger = []; foreach (array_map('json_decode', array_filter(file('/var/www/html/scratchpad/bench/results/runs.jsonl', FILE_IGNORE_NEW_LINES))) as $r) $ledger[$r->run_id] = $r;
$csv = array_map('str_getcsv', file('/var/www/html/docs/drupalcon/data/runs.csv'));
$h = array_shift($csv);
foreach ($csv as $row) {
  $r = array_combine($h, $row);
  if ($r['variant'] !== 'bench_5a_react_agent_naive') continue;
  $l = $ledger[$r['run_id']] ?? NULL; $root = $l ? ($pipes[$l->pipeline_id] ?? NULL) : NULL;
  $sub = NULL; if ($root) foreach ($subs as $s) { if ($s->parent_pipeline_id == $root->id || (!$s->parent_pipeline_id && $s->created >= $root->created && $s->created <= $root->created + 30)) { $sub = $s; break; } }
  $fwd = $sub ? (json_decode($sub->input_data, TRUE)['flowdrop_node_processor_reason.1']['systemPrompt'] ?? NULL) : NULL;
  printf("%-46s %-7s %-26s shadowed=%s forwardedPrompt=%s\n", $r['run_id'], $r['page'], $r['model'], $r['prompt_shadowed'],
    $fwd === NULL ? 'NONE' : strlen($fwd) . 'ch "' . substr(preg_replace('/\s+/', ' ', $fwd), 0, 45) . '"');
}
