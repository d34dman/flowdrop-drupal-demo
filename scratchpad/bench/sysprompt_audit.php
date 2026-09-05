<?php
$db = \Drupal::database();
// Shape check.
$j = $db->query("SELECT * FROM {flowdrop_job} WHERE node_type_id LIKE '%reason%' ORDER BY id DESC LIMIT 1")->fetchAssoc();
printf("sample job node_type_id=%s\nmetadata=%s\ninput_data keys=%s\n\n", $j['node_type_id'] ?? '?', substr((string) ($j['metadata'] ?? ''), 0, 300),
  implode(',', array_keys(json_decode((string) ($j['input_data'] ?? ''), TRUE) ?: [])));
// Pipelines by workflow.
$pipes = $db->query("SELECT id, workflow_id, parent_pipeline_id, root_pipeline_id, created FROM {flowdrop_pipeline} ORDER BY id")->fetchAllAssoc('id');
$rootWf = function ($id) use ($pipes) { $p = $pipes[$id] ?? NULL; if (!$p) return '?'; $r = $p->root_pipeline_id ?: $id; return ($pipes[$r]->workflow_id ?? '?'); };
$jobs = $db->query("SELECT id, node_type_id, metadata, input_data, created FROM {flowdrop_job} WHERE node_type_id LIKE '%reason%' ORDER BY id")->fetchAll();
$seen = [];
foreach ($jobs as $job) {
  $m = json_decode((string) $job->metadata, TRUE) ?: [];
  $pid = $m['pipeline_id'] ?? ($m['pipeline'] ?? NULL);
  if ($pid === NULL) { foreach ($m as $k => $v) { if (stripos($k, 'pipeline') !== FALSE) { $pid = $v; break; } } }
  $root = $pid ? $rootWf($pid) : '?';
  if (!preg_match('/bench_(5|7|8)/', (string) $root)) continue;
  if (isset($seen[$pid])) continue; $seen[$pid] = 1;
  $d = json_decode((string) $job->input_data, TRUE) ?: [];
  $sp = $d['systemPrompt'] ?? ($d['inputs']['systemPrompt'] ?? ($d['parameters']['systemPrompt'] ?? NULL));
  printf("%s  %-36s pipe=%-6s job=%-6s systemPrompt=%s\n", date('m-d H:i', (int) $job->created), $root, $pid, $job->id,
    $sp === NULL ? 'ABSENT keys=' . implode(',', array_keys($d)) : (trim((string) $sp) === '' ? 'EMPTY' : strlen((string) $sp) . 'ch "' . substr(str_replace("\n", ' ', (string) $sp), 0, 60) . '"'));
}
