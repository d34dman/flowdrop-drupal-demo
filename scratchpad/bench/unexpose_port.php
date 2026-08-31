<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$wf = $s->load($extra[0]);
$nodes = $wf->get('nodes');
foreach ($nodes as &$n) {
  if (!str_contains((string) $n['id'], 'reason')) { continue; }
  foreach ($n['data']['config']['ports']['inputs'] as &$p) {
    if (($p['id'] ?? '') === 'systemPrompt') {
      printf("port systemPrompt exposed=%s -> false\n", var_export($p['exposed'] ?? NULL, TRUE));
      $p['exposed'] = FALSE;
    }
  }
  unset($p);
}
unset($n);
$wf->set('nodes', $nodes)->save();
print "saved\n";
