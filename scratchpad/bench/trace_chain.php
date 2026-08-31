<?php
/**
 * Prints the length of every value entering and leaving every job of a
 * pipeline. If a FlowDrop node or edge were dropping part of the model's
 * answer, the byte count would step down somewhere along this chain.
 */
$storage = \Drupal::entityTypeManager()->getStorage('flowdrop_pipeline');
foreach (explode(',', $extra[0]) as $pid) {
  $pipeline = $storage->load(trim($pid));
  if (!$pipeline) { print "!! pipeline $pid missing\n"; continue; }
  print "=== pipeline $pid ({$pipeline->getStatus()}) ===\n";
  $fmt = function ($raw) {
    $d = json_decode((string) $raw, TRUE);
    if (!is_array($d)) { return '-'; }
    $p = [];
    foreach ($d as $k => $v) {
      $s = is_scalar($v) ? (string) $v : json_encode($v);
      $p[] = $k . '=' . number_format(strlen($s));
    }
    return implode(' ', $p);
  };
  foreach ($pipeline->get('job_id') as $ref) {
    $j = $ref->entity;
    if (!$j) { continue; }
    printf("job %-6s %-30s %-9s\n    IN  %s\n    OUT %s\n", $j->id(),
      (string) ($j->get('node_type_id')->target_id ?? '?'),
      (string) $j->get('status')->value,
      $fmt($j->get('input_data')->value), $fmt($j->get('output_data')->value));
  }
}
