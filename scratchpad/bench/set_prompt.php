<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$wf = $s->load($extra[0]);
$nodes = $wf->get('nodes');
foreach ($nodes as &$n) {
  if (!str_contains((string) $n['id'], 'reason')) { continue; }
  $n['data']['config']['systemPrompt'] = $extra[1];
  printf("%s systemPrompt set to %d chars: %s\n", $n['id'], strlen($extra[1]), $extra[1]);
}
unset($n);
$wf->set('nodes', $nodes)->save();
