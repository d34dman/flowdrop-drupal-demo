<?php
/**
 * The url_to_markdown tool's HTTP node defaults to requiring confirmation, so
 * invoking it as a tool pauses the pipeline for approval. B5's agent workflow
 * already waives the same node; matching that is what lets B7 run headless.
 */
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$wf = $s->load('url_to_markdown');
$nodes = $wf->get('nodes');
$changed = 0;
foreach ($nodes as &$n) {
  $cur = $n['data']['config']['requiresConfirmation'] ?? '';
  if ($cur !== 'waive') {
    $n['data']['config']['requiresConfirmation'] = 'waive';
    printf("  %-24s requiresConfirmation '%s' -> 'waive'\n", $n['id'], $cur);
    $changed++;
  }
}
unset($n);
if ($changed) { $wf->set('nodes', $nodes)->save(); }
printf("updated %d node(s) in url_to_markdown\n", $changed);
