<?php
/**
 * Walks a parent pipeline into the sub-workflow pipeline the ReAct node spawned,
 * printing every job so an extra loop pass or an early exit is visible.
 */
$ps = \Drupal::entityTypeManager()->getStorage('flowdrop_pipeline');
$size = function ($raw) {
  $d = json_decode((string) $raw, TRUE);
  if (!is_array($d)) { return '-'; }
  $p = [];
  foreach ($d as $k => $v) {
    $s = is_scalar($v) ? (string) $v : json_encode($v);
    $p[] = $k . '=' . number_format(strlen($s));
  }
  return implode(' ', $p);
};
foreach (explode(',', $extra[0]) as $pid) {
  $ids = \Drupal::entityQuery('flowdrop_pipeline')->accessCheck(FALSE)
    ->condition('parent_pipeline_id', (int) trim($pid))->sort('id')->execute();
  printf("\n########## parent %s -> sub-pipelines %s ##########\n", trim($pid),
    implode(',', $ids) ?: '(none)');
  foreach ($ps->loadMultiple($ids) as $p) {
    printf("--- sub %s (%s) ---\n", $p->id(), $p->getStatus());
    $i = 0;
    foreach ($p->get('job_id') as $ref) {
      $j = $ref->entity;
      if (!$j) { continue; }
      printf("  %2d job %-6s %-34s %-9s  %s\n", ++$i, $j->id(),
        (string) ($j->get('node_type_id')->target_id ?? '?'),
        (string) $j->get('status')->value, $size($j->get('output_data')->value));
    }
  }
}
