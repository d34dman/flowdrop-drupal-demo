<?php
$wf = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow')->load('react_agent_with_tools');
foreach ($wf->get('nodes') as $n) {
  $id = (string) ($n['id'] ?? '?');
  if (!str_contains($id, 'reason')) { continue; }
  print "node id: $id\n";
  print json_encode($n, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
