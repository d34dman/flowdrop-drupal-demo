<?php
$j = \Drupal::entityTypeManager()->getStorage('flowdrop_job')->load($extra[0]);
$d = json_decode((string) $j->get('output_data')->value, TRUE);
$raw = $d[$extra[1]] ?? NULL;
$s = is_string($raw) ? $raw : json_encode($raw);
printf("field '%s': %s chars\n\nHEAD:\n%s\n\nTAIL:\n%s\n", $extra[1],
  number_format(strlen($s)), substr($s, 0, 500), substr($s, -400));
