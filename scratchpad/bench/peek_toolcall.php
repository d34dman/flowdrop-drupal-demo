<?php
$j = \Drupal::entityTypeManager()->getStorage('flowdrop_job')->load($extra[0]);
$d = json_decode((string) $j->get('output_data')->value, TRUE);
$tc = $d['tool_calls'] ?? [];
if (is_string($tc)) { $tc = json_decode($tc, TRUE); }
foreach ((array) $tc as $i => $c) {
  $name = $c['name'] ?? $c['function']['name'] ?? '?';
  $args = $c['arguments'] ?? $c['function']['arguments'] ?? [];
  if (is_string($args)) { $args = json_decode($args, TRUE) ?: ['_raw' => $args]; }
  printf("tool call %d: %s\n", $i + 1, $name);
  foreach ((array) $args as $k => $v) {
    $s = is_scalar($v) ? (string) $v : json_encode($v);
    printf("   arg '%s': %s chars\n", $k, number_format(strlen($s)));
    printf("     head: %s\n", str_replace("\n", ' ', substr($s, 0, 140)));
    printf("     tail: %s\n", str_replace("\n", ' ', substr($s, -140)));
  }
}
