<?php
/**
 * Sets the agent system prompt AND un-exposes the systemPrompt input port.
 *
 * Both steps are required: an exposed-but-unconnected input port resolves to an
 * empty string that shadows the node's configured value, so setting the config
 * alone has no effect at all.
 */
$prompt = rtrim(file_get_contents('/tmp/prompt.txt'), "\n");
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach (explode(',', $extra[0]) as $id) {
  $wf = $s->load(trim($id));
  if (!$wf) { print "!! missing $id\n"; continue; }
  $nodes = $wf->get('nodes');
  foreach ($nodes as &$n) {
    if (!str_contains((string) $n['id'], 'reason')) { continue; }
    $n['data']['config']['systemPrompt'] = $prompt;
    foreach ($n['data']['config']['ports']['inputs'] as &$p) {
      if (($p['id'] ?? '') === 'systemPrompt') { $p['exposed'] = FALSE; }
    }
    unset($p);
    printf("%-34s %s: prompt=%d chars, systemPrompt port un-exposed\n",
      trim($id), $n['id'], strlen($prompt));
  }
  unset($n);
  $wf->set('nodes', $nodes)->save();
}
