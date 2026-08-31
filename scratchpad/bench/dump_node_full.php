<?php
$wf = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow')->load($extra[0]);
foreach ($wf->get('nodes') as $n) {
  if (($n['id'] ?? '') !== $extra[1]) { continue; }
  print json_encode($n, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
// What does the node type itself declare?
$nt = \Drupal::entityTypeManager()->getStorage('flowdrop_node_type')
  ->load('flowdrop_workflow_react_agent_with_tools');
if ($nt) {
  $a = $nt->toArray();
  printf("\n=== node type declares ===\ninputs: %s\nconfig schema keys: %s\n",
    json_encode($a['input_ports'] ?? $a['inputs'] ?? [], JSON_PRETTY_PRINT),
    json_encode(array_keys($a['configuration'] ?? $a['config'] ?? []), JSON_PRETTY_PRINT));
}
