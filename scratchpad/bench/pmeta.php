<?php
$ps = \Drupal::entityTypeManager()->getStorage('flowdrop_pipeline');
foreach (explode(',', $extra[0]) as $pid) {
  $p = $ps->load(trim($pid));
  if (!$p) { print "missing $pid\n"; continue; }
  printf("pipeline %s status=%s\n  fields: %s\n  metadata: %s\n", $p->id(), $p->getStatus(),
    implode(', ', array_keys($p->toArray())),
    json_encode(method_exists($p, 'getMetadata') ? $p->getMetadata() : [], JSON_PRETTY_PRINT));
}
