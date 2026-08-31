<?php
// Per-call token counts for one run: shows how much of each later call is a
// verbatim repeat of the earlier conversation.
$db = \Drupal::database();
$q = $db->select('ai_metering_usage', 'u')->fields('u')
  ->condition('context_id', trim($extra[0]));
if ($db->schema()->fieldExists('ai_metering_usage', 'id')) { $q->orderBy('id'); }
$rows = $q->execute()->fetchAll();
$prev = 0;
foreach ($rows as $i => $r) {
  $a = (array) $r;
  $in = (int) ($a['input_tokens'] ?? 0);
  printf("call %d  in=%-9s out=%-8s  growth over previous call: %+d\n",
    $i + 1, number_format($in), number_format((int) ($a['output_tokens'] ?? 0)),
    $i ? $in - $prev : 0);
  $prev = $in;
}
