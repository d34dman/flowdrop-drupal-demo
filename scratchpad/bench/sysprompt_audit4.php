<?php
$db = \Drupal::database();
$pipes = $db->query("SELECT id, workflow_id, parent_pipeline_id, root_pipeline_id, snapshot_id, created FROM {flowdrop_pipeline} ORDER BY id")->fetchAllAssoc('id');
$rootWf = function ($id) use ($pipes) { $p = $pipes[$id] ?? NULL; if (!$p) return '?'; $r = $p->root_pipeline_id ?: ($p->parent_pipeline_id ?: $id); return $pipes[$r]->workflow_id ?? '?'; };
$links = $db->query("SELECT entity_id AS pid, job_id_target_id AS jid FROM {flowdrop_pipeline__job_id}")->fetchAllKeyed(1, 0);
$jobsByPipe = [];
foreach ($db->query("SELECT id, node_type_id, metadata FROM {flowdrop_job} ORDER BY id") as $j) { $pid = $links[$j->id] ?? NULL; if ($pid !== NULL) $jobsByPipe[$pid][] = $j; }
$snapCols = array_keys($db->query("SELECT * FROM {flowdrop_workflow_snapshot} LIMIT 1")->fetchAssoc() ?: []);
print "snapshot cols: " . implode(',', $snapCols) . "\n\n";
$count = 0;
foreach ($pipes as $pid => $p) {
  $root = $rootWf($pid);
  if (!preg_match('/bench_5_react|bench_8/', (string) $root)) continue;
  $jobs = $jobsByPipe[$pid] ?? [];
  $reason = [];
  foreach ($jobs as $j) { if (str_contains($j->node_type_id, 'reason')) { $m = json_decode($j->metadata, TRUE); $nid = $m['node_id'] ?? '?'; $sp = strlen((string) ($m['config']['systemPrompt'] ?? '')); $ex = 'n/a'; foreach ($m['config']['ports']['inputs'] ?? [] as $pp) if (($pp['id'] ?? '') === 'systemPrompt') $ex = json_encode($pp['exposed'] ?? NULL); $reason[$nid] = "$nid cfg={$sp}ch exposed=$ex"; } }
  $nodeTypes = []; foreach ($jobs as $j) $nodeTypes[$j->node_type_id] = ($nodeTypes[$j->node_type_id] ?? 0) + 1;
  printf("%s pipe=%-4s wf=%-38s parent=%-4s root=%-30s snap=%s\n   jobs: %s\n   reason: %s\n", date('m-d H:i', (int) $p->created), $pid, $p->workflow_id, $p->parent_pipeline_id ?: '-', $root, $p->snapshot_id ?: '-',
    implode(', ', array_map(fn($k, $v) => "$k×$v", array_keys($nodeTypes), $nodeTypes)), implode(' | ', $reason) ?: 'none');
  if (++$count > 40) break;
}
