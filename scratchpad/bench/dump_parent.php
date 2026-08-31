<?php
$wf = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow')->load($extra[0]);
foreach ($wf->get('nodes') as $n) {
  printf("--- node %s ---\n%s\n", $n['id'],
    json_encode($n['data']['config'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
print "=== EDGES ===\n";
foreach ($wf->get('edges') as $e) {
  printf("  %s.%s -> %s.%s\n", $e['source'] ?? '?', $e['sourceHandle'] ?? '?',
    $e['target'] ?? '?', $e['targetHandle'] ?? '?');
}
