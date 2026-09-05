<?php
// Usage: drush php:script dump_pipeline_jobs.php -- <pipeline_id>[,<id>...]
$etm = \Drupal::entityTypeManager();
foreach (explode(',', $extra[0] ?? '') as $pid) {
  $p = $etm->getStorage('flowdrop_pipeline')->load($pid);
  if (!$p) { echo "no pipeline $pid\n"; continue; }
  printf("== pipeline %s status=%s\n", $pid, $p->get('status')->value ?? '?');
  foreach ($p->get('job_id') as $ref) {
    $j = $ref->entity; if (!$j) continue;
    $err = (string) $j->get('error_message')->value;
    $out = (string) $j->get('output_data')->value;
    printf("  job %s %-42s %-12s err=%s\n", $j->id(), $j->get('node_type_id')->target_id ?? '?', $j->get('status')->value, mb_substr(preg_replace('/\s+/', ' ', $err), 0, 500));
    if ($err === '' && stripos($out, 'error') !== FALSE) printf("      out=%s\n", mb_substr(preg_replace('/\s+/', ' ', $out), 0, 400));
  }
}
