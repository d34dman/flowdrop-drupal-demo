<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_job');
foreach (explode(',', $extra[0]) as $id) {
  $j = $s->load(trim($id));
  $d = json_decode((string) $j->get('output_data')->value, TRUE);
  $v = (string) ($d['response'] ?? $d['message'] ?? '');
  printf("job %-6s len=%-8d md5=%s  created=%s\n", $id, strlen($v), md5($v),
    date('c', (int) $j->get('created')->value));
}
