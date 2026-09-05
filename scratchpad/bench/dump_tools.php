<?php
// Usage: drush php:script dump_tools.php -- <workflow_id>   lists nodes and, for toolbox nodes, what is wired in.
$wf = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow')->load($extra[0] ?? '');
if (!$wf) { echo "no workflow\n"; return; }
$g = $wf->get('workflow_data')->getValue()[0] ?? $wf->toArray();
$g = is_string($g) ? json_decode($g, TRUE) : $g;
$nodes = $g['nodes'] ?? ($g[0]['nodes'] ?? []);
foreach ($nodes as $n) {
  $id = $n['id'] ?? '?'; $t = $n['data']['nodeType'] ?? $n['type'] ?? '?';
  $cfg = $n['data']['config'] ?? [];
  printf("%-45s %s\n", $id, $t);
  foreach (['tools','tool_ids','confirm','requires_confirmation','external','gate'] as $k) if (isset($cfg[$k])) printf("    %s=%s\n", $k, json_encode($cfg[$k]));
}
foreach ($g['edges'] ?? [] as $e) if (str_contains(json_encode($e), 'toolbox')) printf("  edge %s -> %s\n", $e['source'] ?? '?', $e['target'] ?? '?');
