<?php
/**
 * The sub-workflow's "message" output reaches the caller through text_output.1
 * and arrives truncated to ~1k characters; its "reason" output is bound
 * straight to the reason node and carries the model's complete answer. Read
 * the intact port so the benchmark measures what the model produced rather
 * than what the output wiring dropped.
 */
$storage = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach (['bench_5_react_agent', 'bench_5a_react_agent_naive'] as $id) {
  $wf = $storage->load($id);
  $edges = $wf->getEdges();
  $changed = 0;
  foreach ($edges as &$e) {
    if (($e['source'] ?? '') !== 'flowdrop_workflow_react_agent_with_tools.1') { continue; }
    if (!str_ends_with((string) ($e['sourceHandle'] ?? ''), '-output-message')) { continue; }
    $e['sourceHandle'] = 'flowdrop_workflow_react_agent_with_tools.1-output-reason';
    $e['id'] = str_replace('-output-message', '-output-reason', $e['id']);
    $changed++;
  }
  unset($e);
  $wf->setEdges($edges)->save();
  printf("  %-30s rewired %d edge(s) message -> reason\n", $id, $changed);
}

// The preserved control's description asserted summarisation, which the job
// trail disproved: both variants were truncated by the same output wiring.
$naive = $storage->load('bench_5a_react_agent_naive');
$naive->setDescription('The ReAct agent with its original instruction, which says "get the content and replace" without demanding the whole document back. Kept as the control for prompt sensitivity against Bench 5, which sharpens exactly that one sentence. Both variants read the sub-workflow\'s intact "reason" output: its documented "message" output arrives truncated to roughly a thousand characters, which masked the difference between them entirely.')->save();
print "  updated bench_5a description\n";
