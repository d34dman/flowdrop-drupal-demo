<?php
// Usage: drush php:script dump_jobs.php -- <job_id>[,<id>...]   prints output_data + error, trimmed
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_job');
foreach (explode(',', $extra[0] ?? '') as $id) {
  $j = $s->load($id); if (!$j) { echo "no job $id\n"; continue; }
  printf("== job %s %s %s\n", $id, $j->get('node_type_id')->target_id, $j->get('status')->value);
  printf("  error: %s\n", mb_substr((string) $j->get('error_message')->value, 0, 600));
  $o = (string) $j->get('output_data')->value;
  $d = json_decode($o, TRUE);
  if (is_array($d)) { foreach ($d as $k => $v) { $v = is_string($v) ? $v : json_encode($v); printf("  %s: %s\n", $k, mb_substr(preg_replace('/\s+/', ' ', $v), 0, 700)); } }
  else printf("  out: %s\n", mb_substr($o, 0, 700));
  $i = (string) $j->get('input_data')->value; if ($i !== '') printf("  input: %s\n", mb_substr(preg_replace('/\s+/', ' ', $i), 0, 500));
}
