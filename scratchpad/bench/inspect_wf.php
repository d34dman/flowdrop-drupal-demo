<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$wf = $s->load($extra[0]);
if (!$wf) { print "!! workflow {$extra[0]} not found\n"; exit; }
foreach ($wf->get('nodes') as $n) {
  $id = (string) ($n['id'] ?? '?');
  $cfg = $n['data']['config'] ?? [];
  if (str_contains($id, 'reason')) {
    printf("REASON %s: model=%s temp=%s maxTokens=%s systemPrompt=%d chars\n", $id,
      $cfg['model'] ?? '(unset)', var_export($cfg['temperature'] ?? NULL, TRUE),
      $cfg['maxTokens'] ?? '(unset)', strlen((string) ($cfg['systemPrompt'] ?? '')));
  }
  if (preg_match('/url_to_markdown|html_to_markdown|http_request/', $id)) {
    printf("TOOL   %s\n   %s\n", $id, json_encode($cfg, JSON_UNESCAPED_SLASHES));
  }
}
