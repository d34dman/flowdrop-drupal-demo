<?php
$j = \Drupal::entityTypeManager()->getStorage('flowdrop_job')->load($extra[0]);
$d = json_decode((string) $j->get('output_data')->value, TRUE);
$k = $extra[1];
$v = (string) $d[$k];
printf("--- port '%s' (%d bytes) ---\nHEAD: %s\n\nTAIL: %s\n", $k, strlen($v),
  substr($v, 0, 300), substr($v, -300));
