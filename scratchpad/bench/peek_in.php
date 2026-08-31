<?php
$j = \Drupal::entityTypeManager()->getStorage('flowdrop_job')->load($extra[0]);
$d = json_decode((string) $j->get('input_data')->value, TRUE);
foreach ((array) $d as $k => $v) {
  $s = is_scalar($v) ? (string) $v : json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  printf("--- input port '%s' (%s chars) ---\n%s\n\n", $k, number_format(strlen($s)),
    strlen($s) > 2600 ? substr($s, 0, 2600) . "\n…[truncated]" : $s);
}
