<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach (['bench_5_react_agent', 'bench_5a_react_agent_naive'] as $id) {
  $wf = $s->load($id); if (!$wf) { print "$id: missing\n"; continue; }
  foreach ($wf->get('nodes') as $n) { $sp = $n['data']['config']['system_prompt'] ?? NULL; if ($sp !== NULL) printf("%s :: %s system_prompt=%dch \"%s\"\n", $id, $n['id'], strlen($sp), substr(preg_replace('/\s+/', ' ', $sp), 0, 80)); }
}
