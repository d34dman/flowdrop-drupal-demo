<?php
$db = \Drupal::database();
$pipes = $db->query("SELECT id, workflow_id, parent_pipeline_id, snapshot_id, created, input_data FROM {flowdrop_pipeline} ORDER BY id")->fetchAllAssoc('id');
$links = $db->query("SELECT entity_id AS pid, job_id_target_id AS jid FROM {flowdrop_pipeline__job_id}")->fetchAllKeyed(1, 0);
$jobsByPipe = [];
foreach ($db->query("SELECT id, node_type_id, metadata, input_data FROM {flowdrop_job} WHERE node_type_id LIKE '%reason%' ORDER BY id") as $j) { $pid = $links[$j->id] ?? NULL; if ($pid !== NULL) $jobsByPipe[$pid][] = $j; }
$roots = array_filter($pipes, fn($p) => in_array($p->workflow_id, ['bench_5_react_agent', 'bench_7_react_url_tool', 'bench_8_react_with_tools_in_parent'], TRUE));
$subs = array_filter($pipes, fn($p) => !in_array($p->workflow_id, ['bench_5_react_agent', 'bench_7_react_url_tool', 'bench_8_react_with_tools_in_parent'], TRUE) && str_contains($p->workflow_id, 'react'));
$n = 0;
foreach ($roots as $r) {
  $match = NULL;
  foreach ($subs as $s) { if ($s->parent_pipeline_id == $r->id || (!$s->parent_pipeline_id && $s->created >= $r->created && $s->created <= $r->created + 30)) { $match = $s; break; } }
  $line = sprintf("%s %-34s root=%-4s ", date('m-d H:i', (int) $r->created), $r->workflow_id, $r->id);
  if (!$match) { print $line . "sub=NONE\n"; continue; }
  $line .= sprintf("sub=%-4s (%s) input_data=%s ", $match->id, $match->workflow_id, substr(preg_replace('/\s+/', ' ', (string) $match->input_data), 0, 90));
  $jobs = $jobsByPipe[$match->id] ?? [];
  if (!$jobs) { print $line . " reasonJobs=0\n"; continue; }
  $m = json_decode($jobs[0]->metadata, TRUE) ?: []; $d = json_decode($jobs[0]->input_data, TRUE) ?: [];
  $cfg = (string) ($m['config']['systemPrompt'] ?? ''); $ex = 'n/a';
  foreach ($m['config']['ports']['inputs'] ?? [] as $pp) if (($pp['id'] ?? '') === 'systemPrompt') $ex = json_encode($pp['exposed'] ?? NULL);
  $deliv = array_key_exists('systemPrompt', $d) ? (trim((string) $d['systemPrompt']) === '' ? 'EMPTY' : strlen((string) $d['systemPrompt']) . 'ch "' . substr(preg_replace('/\s+/', ' ', (string) $d['systemPrompt']), 0, 50) . '"') : 'absent';
  printf("%s\n      reason cfgPrompt=%dch exposed=%s deliveredSystemPrompt=%s inputKeys=%s\n", $line, strlen($cfg), $ex, $deliv, implode(',', array_keys($d)));
  if (++$n > 45) break;
}
