<?php
// First LLM call input tokens per B5 run, joined with the exposed flag of its sub-pipeline reason node.
$db = \Drupal::database();
$pipes = $db->query("SELECT id, workflow_id, parent_pipeline_id, created FROM {flowdrop_pipeline} ORDER BY id")->fetchAllAssoc('id');
$links = $db->query("SELECT entity_id AS pid, job_id_target_id AS jid FROM {flowdrop_pipeline__job_id}")->fetchAllKeyed(1, 0);
$reasonByPipe = [];
foreach ($db->query("SELECT id, metadata FROM {flowdrop_job} WHERE node_type_id LIKE '%reason%' ORDER BY id") as $j) { $pid = $links[$j->id] ?? NULL; if ($pid !== NULL && !isset($reasonByPipe[$pid])) $reasonByPipe[$pid] = $j; }
$subs = array_filter($pipes, fn($p) => $p->workflow_id === 'react_agent_with_tools');
$ledger = array_map('json_decode', array_filter(file('/var/www/html/scratchpad/bench/results/runs.jsonl', FILE_IGNORE_NEW_LINES)));
$rows = [];
foreach ($ledger as $r) {
  if (($r->workflow ?? '') !== 'bench_5_react_agent') continue;
  $root = $pipes[$r->pipeline_id] ?? NULL; if (!$root) continue;
  $sub = NULL; foreach ($subs as $s) { if ($s->parent_pipeline_id == $root->id || (!$s->parent_pipeline_id && $s->created >= $root->created && $s->created <= $root->created + 30)) { $sub = $s; break; } }
  $ex = '?'; $cfg = '?';
  if ($sub && isset($reasonByPipe[$sub->id])) { $m = json_decode($reasonByPipe[$sub->id]->metadata, TRUE); $cfg = strlen((string) ($m['config']['systemPrompt'] ?? '')); $ex = 'true'; foreach ($m['config']['ports']['inputs'] ?? [] as $pp) if (($pp['id'] ?? '') === 'systemPrompt') $ex = json_encode($pp['exposed'] ?? NULL); }
  $calls = $db->query("SELECT model_id, input_tokens, output_tokens FROM {ai_metering_usage} WHERE context_id = :c ORDER BY id", [':c' => $r->context_uuid])->fetchAll();
  $first = $calls[0] ?? NULL;
  $rows[] = sprintf("%-12s %-7s %-28s exposed=%-5s cfg=%-4s calls=%d firstCall in=%-6s out=%-5s tag=%s", substr($r->run_id, -10), $r->url_key, $first->model_id ?? '-', $ex, $cfg, count($calls), $first->input_tokens ?? '-', $first->output_tokens ?? '-', $r->tag ?? '');
}
print implode("\n", $rows) . "\n";
