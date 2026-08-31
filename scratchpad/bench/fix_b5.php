<?php
/**
 * Bench 5's content arrives as a tool result rather than in the user message,
 * so "answer the user" reads to the model as "summarise the article" — it
 * returned a 1k-char precis where the pipeline variants returned the 8.7k-char
 * document. That is a different task, so its cost is not comparable.
 *
 * The as-authored variant is preserved as bench_5a so the drift stays a
 * reproducible finding rather than a story about a prompt we already changed.
 */
$storage = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$b5 = $storage->load('bench_5_react_agent');

// 1. Preserve the as-authored version.
if ($old = $storage->load('bench_5a_react_agent_naive')) { $old->delete(); }
$naive = $storage->create(['id' => 'bench_5a_react_agent_naive', 'status' => TRUE,
  'label' => 'Bench 5a · FlowDrop ReAct Agent (as-authored prompt)']);
$naive->setDescription('The ReAct agent exactly as it was written before benchmarking, kept as the control for prompt sensitivity. Its instruction says "get the content and replace" without demanding the whole document back, and because the page reaches the model as a tool result rather than as the user message, it summarises instead of converting — the most expensive variant returning the least faithful output. Bench 5 is the same graph with that one instruction sharpened.')
  ->setNodes($b5->getNodes())->setEdges($b5->getEdges())
  ->setInputPorts($b5->getInputPorts())->setMetadata($b5->getMetadata())->save();
print "  preserved bench_5a_react_agent_naive\n";

// 2. Sharpen Bench 5's instruction: completeness is what the tool-result
//    framing loses, so the prompt has to ask for it explicitly.
$prompt = <<<'TXT'
Fetch the content at the given URL and convert the ENTIRE page to Markdown.

Reproduce the full document: every heading, paragraph, list item and table, in
the original order and wording. Do not summarise, abridge, omit or re-word
anything. Redaction is the only change you may make to the text.

Redaction: replace any competitor of Drupal CMS with "▌▌▌▌".

Output the Markdown document and nothing else. No code fences, no preamble, no
commentary, no explanation of what you did.
TXT;

$nodes = $b5->getNodes();
foreach ($nodes as &$n) {
  if ($n['id'] !== 'flowdrop_workflow_react_agent_with_tools.1') { continue; }
  $n['data']['config']['system_prompt'] = $prompt;
}
unset($n);
$b5->setNodes($nodes)->save();
print "  sharpened bench_5_react_agent system_prompt\n";
