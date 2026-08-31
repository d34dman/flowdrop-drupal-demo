<?php
/**
 * Builds Bench 6: a Drupal AI Agent that fetches the page itself.
 *
 * Bench 4 hands the agent a page body that a FlowDrop http_request node already
 * retrieved, so it compares an agent that is given its input against a graph
 * that goes and gets it. Bench 6 removes the HTTP node and gives the agent an
 * http_fetch tool instead, making it the true peer of the FlowDrop ReAct agent
 * in Bench 5: same task, same autonomy, different engine.
 */
$etm = \Drupal::entityTypeManager();

// --- the agent ------------------------------------------------------------
$agentStorage = $etm->getStorage('ai_agent');
$src = $agentStorage->load('agent_w81pomww');
if (!$src) { throw new \RuntimeException('source agent agent_w81pomww missing'); }
$a = $src->toArray();

unset($a['uuid'], $a['third_party_settings']);
$a['id'] = 'agent_bench_autonomous';
$a['label'] = 'Bench 6 · Autonomous Fetch + Markdown + Redaction';
$a['description'] = 'Given only a URL, retrieves the page itself with the http_fetch tool, converts it with html_to_markdown and redacts competitor names. The Drupal AI Agents peer of the FlowDrop ReAct agent.';

// Deliberately the same wording as the sharpened Bench 5 prompt: the point of
// the comparison is the engine, so the instruction must not differ.
$a['system_prompt'] = <<<'TXT'
You are given a URL. Fetch the content at that URL with the http_fetch tool, then
convert the ENTIRE page to Markdown.

Reproduce the full document: every heading, paragraph, list item and table, in
the original order and wording. Do not summarise, abridge, omit or re-word
anything. Redaction is the only change you may make to the text.

Redaction: replace any competitor of Drupal CMS with "▌▌▌▌".

Output the Markdown document and nothing else. No code fences, no preamble, no
commentary, no explanation of what you did.
TXT;

$a['tools'] = [
  'ai_agent:http_fetch' => TRUE,
  'ai_agent:html_to_markdown' => TRUE,
];
$defaults = [
  'return_directly' => FALSE,
  'require_usage' => FALSE,
  'use_artifacts' => FALSE,
  'description_override' => '',
  'progress_message' => '',
  'restrict_multiple_calls' => FALSE,
  'multiple_call_error_message' => '',
];
$a['tool_settings'] = [
  'ai_agent:http_fetch' => $defaults,
  'ai_agent:html_to_markdown' => $defaults,
];
$a['tool_usage_limits'] = [];

// Two tool calls plus the answer is the happy path; the headroom is for the
// retries a fetch failure would cost. Bench 5 needed three reasoning turns.
$a['max_loops'] = 25;

if ($old = $agentStorage->load('agent_bench_autonomous')) { $old->delete(); }
$agentStorage->create($a)->save();
printf("created agent agent_bench_autonomous (tools: %s, max_loops: %d)\n",
  implode(', ', array_keys($a['tools'])), $a['max_loops']);

// --- the workflow ---------------------------------------------------------
$wfStorage = $etm->getStorage('flowdrop_workflow');
$w = $wfStorage->load('bench_4_ai_agent_tool')->toArray();

$nodes = [];
foreach ($w['nodes'] as $n) {
  // The whole point: no HTTP node. The agent fetches for itself.
  if (($n['data']['metadata']['node_type_id'] ?? '') === 'http_request') { continue; }
  if (str_contains($n['id'], 'ai_agents_executor')) {
    $n['data']['config']['agent_id'] = 'agent_bench_autonomous';
    $n['data']['config']['task_title'] = 'Fetch, convert and redact';
    $n['data']['config']['task_description'] = 'Given a URL, fetch it, convert to Markdown and redact competitors.';
    $n['position'] = ['x' => 0, 'y' => 60];
  }
  $nodes[] = $n;
}

// Only the executor -> output edge survives; the HTTP feed is gone.
$edges = array_values(array_filter($w['edges'],
  fn ($e) => !str_contains($e['source'], 'http_request')));

$executorId = 'flowdrop_agents_ai_agents_executor.1';
if ($old = $wfStorage->load('bench_6_agent_autonomous')) { $old->delete(); }
$wfStorage->create([
  'id' => 'bench_6_agent_autonomous',
  'label' => 'Bench 6 · Drupal AI Agent, Autonomous Fetch',
  'description' => 'The Drupal AI Agents peer of Bench 5. The workflow passes only a URL; the agent retrieves the page with its own http_fetch tool, converts it and redacts. No FlowDrop HTTP node is involved, so what is measured is the agent doing the whole job.',
  'status' => TRUE,
  'nodes' => $nodes,
  'edges' => $edges,
  'input_ports' => [[
    'name' => 'url',
    'node_id' => $executorId,
    'port' => 'additional_context',
    'title' => 'URL',
    'description' => 'The page the agent should fetch, convert to Markdown and redact.',
    'required' => TRUE,
  ]],
  'metadata' => [
    'orchestrator_settings' => ['type' => 'flowdrop_runtime:synchronous'],
  ],
])->save();

printf("created workflow bench_6_agent_autonomous (%d nodes, %d edges)\n", count($nodes), count($edges));
