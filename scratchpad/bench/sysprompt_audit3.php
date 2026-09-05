<?php
$db = \Drupal::database();
print "ai tables: " . implode(', ', $db->query("SHOW TABLES LIKE '%ai_%'")->fetchCol()) . "\n\n";
$pipes = $db->query("SELECT id, workflow_id, parent_pipeline_id, root_pipeline_id FROM {flowdrop_pipeline}")->fetchAllAssoc('id');
$rootWf = function ($id) use ($pipes) { $p = $pipes[$id] ?? NULL; if (!$p) return '?'; $r = $p->root_pipeline_id ?: ($p->parent_pipeline_id ?: $id); return $pipes[$r]->workflow_id ?? '?'; };
$links = $db->query("SELECT entity_id AS pid, job_id_target_id AS jid FROM {flowdrop_pipeline__job_id}")->fetchAllKeyed(1, 0);
$jobs = $db->query("SELECT id, metadata, input_data, created FROM {flowdrop_job} WHERE node_type_id LIKE '%reason%' ORDER BY id")->fetchAll();
$seen = [];
foreach ($jobs as $job) {
  $pid = $links[$job->id] ?? NULL; if ($pid === NULL) continue;
  $root = $rootWf($pid);
  if (!preg_match('/bench_(5|7|8)/', (string) $root)) continue;
  if (isset($seen[$pid])) continue; $seen[$pid] = 1;
  $m = json_decode((string) $job->metadata, TRUE) ?: [];
  $d = json_decode((string) $job->input_data, TRUE) ?: [];
  $cfgSp = (string) ($m['config']['systemPrompt'] ?? '');
  $exposed = 'n/a';
  foreach ($m['config']['ports']['inputs'] ?? [] as $p) { if (($p['id'] ?? '') === 'systemPrompt') $exposed = var_export($p['exposed'] ?? NULL, TRUE); }
  $edgeIn = 0; foreach ($m['incoming_edges'] ?? [] as $e) { if (($e['targetHandle'] ?? '') === 'systemPrompt') $edgeIn++; }
  $delivered = array_key_exists('systemPrompt', $d) ? (trim((string) $d['systemPrompt']) === '' ? 'EMPTY' : strlen((string) $d['systemPrompt']) . 'ch') : 'absent';
  $user = ''; foreach ($d['messages'] ?? [] as $msg) { if (($msg['role'] ?? '') === 'user') { $user = substr(str_replace("\n", ' ', (string) $msg['content']), 0, 50); break; } }
  printf("%s %-36s pipe=%-5s cfgPrompt=%3dch exposed=%-5s edges=%d delivered=%-7s user=\"%s\"\n",
    date('m-d H:i', (int) $job->created), $root, $pid, strlen($cfgSp), $exposed, $edgeIn, $delivered, $user);
}
