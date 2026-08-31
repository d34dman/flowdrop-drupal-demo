<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$wf = $s->load($extra[0]);
$orig = file_get_contents('/tmp/orig_prompt.txt');
$nodes = $wf->get('nodes');
foreach ($nodes as &$n) {
  if (!str_contains((string) $n['id'], 'reason')) { continue; }
  $n['data']['config']['systemPrompt'] = $orig;
  printf("restored %s to %d chars\n", $n['id'], strlen($orig));
}
unset($n);
$wf->set('nodes', $nodes)->save();
