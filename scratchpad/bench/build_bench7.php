<?php
/**
 * Bench 7: the ReAct agent given a url_to_markdown tool.
 *
 * Structurally identical to Bench 5 — chat_output fed from the agent's `reason`
 * port — so the only difference against B5 is the toolbox. B5's agent has to
 * pass page content *into* html_to_markdown as an argument, which forces the
 * document through the model's own output channel; here it passes a URL and
 * gets Markdown back, so bulk content never crosses the model.
 *
 * Two entities are created: a node type wrapping the workflow (cloned from the
 * B5 wrapper so the port names match), and the parent bench workflow.
 */
$ntStorage = \Drupal::entityTypeManager()->getStorage('flowdrop_node_type');
$src = $ntStorage->load('flowdrop_workflow_react_agent_with_tools');
$ntId = 'flowdrop_workflow_react_agent_with_optimized_tools';
if (!$ntStorage->load($ntId)) {
  $vals = $src->toArray();
  unset($vals['uuid'], $vals['_core']);
  $vals['id'] = $ntId;
  $vals['label'] = 'ReAct Agent with Optimized Tools';
  $vals['description'] = 'ReAct agent whose toolbox includes url_to_markdown.';
  $vals['executor_plugin'] =
    'flowdrop_workflow_executor:flowdrop_workflow:react_agent_with_optimized_tools';
  $ntStorage->create($vals)->save();
  print "created node type $ntId\n";
}
else { print "node type $ntId already exists\n"; }

$wfStorage = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$b5 = $wfStorage->load('bench_5_react_agent');
$id = 'bench_7_react_optimized';
if ($wfStorage->load($id)) { $wfStorage->load($id)->delete(); print "replaced existing $id\n"; }
$vals = $b5->toArray();
unset($vals['uuid'], $vals['_core']);
$vals['id'] = $id;
$vals['label'] = 'Bench 7 · ReAct with url_to_markdown tool';
$vals['description'] = 'Peer of Bench 5. Same shape, same prompt; the agent is '
  . 'additionally given a url_to_markdown tool so page content never has to pass '
  . 'through the model to reach the converter.';

$old = 'flowdrop_workflow_react_agent_with_tools.1';
$new = 'flowdrop_workflow_react_agent_with_optimized_tools.1';
$nodes = [];
foreach ($vals['nodes'] as $n) {
  if (($n['id'] ?? '') === $old) {
    $n['id'] = $new;
    $n['data']['label'] = 'ReAct Agent with Optimized Tools';
    $n['data']['metadata']['node_type_id'] = $ntId;
    // The dead `system_prompt` key is deliberately not carried over: the prompt
    // now lives on the sub-workflow's Reason node, which is where it is read.
    unset($n['data']['config']['system_prompt']);
  }
  $nodes[] = $n;
}
$vals['nodes'] = $nodes;
$edges = [];
foreach ($vals['edges'] as $e) {
  foreach (['id', 'source', 'target', 'sourceHandle', 'targetHandle'] as $k) {
    if (isset($e[$k])) { $e[$k] = str_replace($old, $new, $e[$k]); }
  }
  $edges[] = $e;
}
$vals['edges'] = $edges;
$wfStorage->create($vals)->save();
printf("created workflow %s: %d nodes, %d edges\n", $id, count($nodes), count($edges));
foreach ($edges as $e) {
  printf("  %s -> %s\n", $e['sourceHandle'] ?? '?', $e['targetHandle'] ?? '?');
}
