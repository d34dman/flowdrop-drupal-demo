<?php
// Raw metering rows for a context id: what the provider actually reported.
$db = \Drupal::database();
foreach (explode(',', $extra[0]) as $ctx) {
  $rows = $db->select('ai_metering_usage', 'u')->fields('u')
    ->condition('context_id', trim($ctx))->execute()->fetchAll();
  printf("=== %s : %d row(s) ===\n", substr(trim($ctx), 0, 8), count($rows));
  foreach ($rows as $r) {
    $a = (array) $r;
    printf("  model=%-34s in=%-8s out=%-8s cost=%-9s created=%s\n",
      $a['model_id'] ?? '?', $a['input_tokens'] ?? '?', $a['output_tokens'] ?? '?',
      $a['total_cost'] ?? ($a['cost'] ?? '?'),
      isset($a['created']) ? date('H:i:s', (int) $a['created']) : '?');
  }
}
