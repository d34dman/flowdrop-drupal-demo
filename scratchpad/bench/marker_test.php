<?php
/**
 * Puts a unique token at the front of the system prompt. If it does not appear
 * in the output, the prompt never reached the model.
 */
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$wf = $s->load($extra[0]);
$nodes = $wf->get('nodes');
foreach ($nodes as &$n) {
  if (!str_contains((string) $n['id'], 'reason')) { continue; }
  $orig = (string) ($n['data']['config']['systemPrompt'] ?? '');
  file_put_contents('/tmp/orig_prompt.txt', $orig);
  $n['data']['config']['systemPrompt'] =
    "Begin your reply with the exact token QQZZX9 and nothing before it.\n\n" . $orig;
  printf("%s: prompt %d -> %d chars (marker prepended)\n", $n['id'],
    strlen($orig), strlen($n['data']['config']['systemPrompt']));
}
unset($n);
$wf->set('nodes', $nodes)->save();
