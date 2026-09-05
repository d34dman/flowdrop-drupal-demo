<?php
/**
 * Rewire the Reflexion engine critic: it judges the user's ask against the
 * agent's draft only — no chat history, no scratchpad, no tool results.
 *
 * Usage: drush php:script scratchpad/reflexion/rewire_critic.php
 */
use Drupal\Core\Serialization\Yaml;

$file = dirname(DRUPAL_ROOT) . '/config/sync/flowdrop_workflow.flowdrop_workflow.reflexion_agent_engine.yml';
$cfg = Yaml::decode(file_get_contents($file));
$wd =& $cfg;
$nodes =& $wd['nodes'];
$edges =& $wd['edges'];

$byId = function (string $id) use (&$nodes) { $i = array_search($id, array_column($nodes, 'id'), TRUE); if ($i === FALSE) { throw new \RuntimeException("no node $id"); } return $i; };
$edge = function (string $src, string $sp, string $tgt, string $tp, string $type) {
  $sh = "$src-output-$sp"; $th = "$tgt-input-$tp";
  return [
    'id' => "xy-edge__{$src}{$sh}-{$tgt}{$th}",
    'source' => $src, 'target' => $tgt,
    'type' => 'default',
    'data' => ['edgeType' => $type],
    'sourceHandle' => $sh, 'targetHandle' => $th,
    'markerEnd' => ['type' => 'arrowclosed', 'width' => 16, 'height' => 16, 'color' => "var(--fd-edge-$type)"],
    'class' => "flowdrop--edge--$type",
  ];
};

// 1. Drop the scratchpad reader that fed the critic and every edge touching it.
$i = $byId('conversation_buffer.5');
if ($i !== FALSE) { array_splice($nodes, $i, 1); }
$edges = array_values(array_filter($edges, fn($e) =>
  $e['source'] !== 'conversation_buffer.5' && $e['target'] !== 'conversation_buffer.5'
  // Chat history no longer reaches the critic either.
  && !($e['source'] === 'conversation_buffer.4' && $e['target'] === 'message_assemble.2')
));

// 2. The ask enters through a Text Input so it can fan out to the chat
//    history buffer (as before) and to the critic.
$nodes[] = [
  'id' => 'text_input.1',
  'position' => ['x' => -200, 'y' => 220],
  'data' => [
    'label' => 'Text Input',
    'config' => ['defaultValue' => '', 'nodeType' => 'simple', 'instanceTitle' => 'User ask'],
    'metadata' => ['node_type_id' => 'text_input'],
  ],
  'measured' => ['width' => 290, 'height' => 100],
];
foreach ($cfg['input_ports'] as $k => $p) {
  if ($p['name'] === 'message') {
    $cfg['input_ports'][$k]['node_id'] = 'text_input.1';
    $cfg['input_ports'][$k]['port'] = 'data';
  }
}
$edges[] = $edge('text_input.1', 'text', 'conversation_buffer.4', 'content', 'data');

$mft = function (string $id, string $title, string $role, string $text, int $x, int $y) {
  return [
    'id' => $id,
    'position' => ['x' => $x, 'y' => $y],
    'data' => [
      'label' => 'Message From Text',
      'config' => [
        'role' => $role, 'text' => $text, 'tool_call_id' => '',
        'maxRetries' => 0, 'requiresConfirmation' => '', 'instanceTitle' => $title,
        'ports' => ['outputs' => [
          ['id' => 'message', 'exposed' => FALSE],
          ['id' => 'messages', 'exposed' => TRUE],
          ['id' => 'trigger'], ['id' => 'error'],
        ]],
      ],
      'metadata' => ['node_type_id' => 'message_from_text'],
    ],
    'measured' => ['width' => 280, 'height' => 262],
  ];
};
$nodes[] = $mft('message_from_text.2', 'Ask as message', 'user', '', 1900, 800);
$edges[] = $edge('text_input.1', 'text', 'message_from_text.2', 'text', 'data');

// 3. The draft: the agent's final text, only on the no-tool-calls branch.
$nodes[] = $mft('message_from_text.3', 'Draft as message', 'assistant', '', 1900, 1100);
$edges[] = $edge('flowdrop_node_processor_reason.1', 'text', 'message_from_text.3', 'text', 'data');
$edges[] = $edge('boolean_gateway.1', 'False', 'message_from_text.3', 'trigger', 'trigger');

// 4. Critic sees: [user ask][assistant draft][user review request].
$a = $byId('message_assemble.2');
$nodes[$a]['data']['config']['dynamicInputs'] = [
  ['name' => 'ask', 'label' => 'ask', 'description' => '', 'dataType' => 'messages', 'required' => FALSE],
  ['name' => 'draft', 'label' => 'draft', 'description' => '', 'dataType' => 'messages', 'required' => FALSE],
  ['name' => 'review_request', 'label' => 'review_request', 'description' => '', 'dataType' => 'messages', 'required' => FALSE],
];
$edges[] = $edge('message_from_text.2', 'messages', 'message_assemble.2', 'ask', 'data');
$edges[] = $edge('message_from_text.3', 'messages', 'message_assemble.2', 'draft', 'data');

$r = $byId('message_from_text.1');
$nodes[$r]['data']['config']['text'] = 'Review the draft answer above against my request above. Begin your reply with VERDICT: ACCEPT or VERDICT: REVISE, then explain.';

$critic = "You are a strict reviewer. You see exactly two things: the user's request and a draft answer to it. Judge the draft against the request alone: does it fully and correctly answer what was asked? Your reply must begin with exactly one line: VERDICT: ACCEPT if it does, or VERDICT: REVISE if it does not. After a REVISE verdict, list concretely what is wrong or missing and what the next draft must change. Do not write the answer yourself.";
$c = $byId('flowdrop_node_processor_reason.2');
$nodes[$c]['data']['config']['systemPrompt'] = $critic;
$cfg['parameter_schema']['properties']['critic_prompt']['default'] = $critic;

$cfg['metadata']['updatedAt'] = gmdate('Y-m-d\TH:i:s.v\Z');
foreach (['message_from_text', 'text_input', 'if_else', 'prompt_template'] as $dep) {
  $cfg['dependencies']['config'][] = "flowdrop_node_type.flowdrop_node_type.$dep";
}
$cfg['dependencies']['config'] = array_values(array_unique($cfg['dependencies']['config']));
sort($cfg['dependencies']['config']);
file_put_contents($file, Yaml::encode($cfg));
printf("nodes %d, edges %d\n", count($nodes), count($edges));
