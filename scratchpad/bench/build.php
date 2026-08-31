<?php
/**
 * Builds the benchmark variants of the redactor workflows.
 *
 * Each bench workflow differs from its source in exactly three ways, so that
 * what the numbers compare is the pipeline shape and nothing else:
 *   - the hardcoded URL becomes a declared `url` input port, so one harness
 *     can drive every variant over the same set of pages;
 *   - execution is pinned to the synchronous orchestrator, so a run can be
 *     waited on inline and its wall-clock time measured without queue latency;
 *   - id, label and description name what the variant actually demonstrates.
 */

$storage = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');

/** Drops nodes by id, and every edge touching them. */
$without = function (array $nodes, array $edges, array $dropIds): array {
  $nodes = array_values(array_filter($nodes, fn($n) => !in_array($n['id'], $dropIds, TRUE)));
  $edges = array_values(array_filter($edges, fn($e) => !in_array($e['source'], $dropIds, TRUE)
    && !in_array($e['target'], $dropIds, TRUE)));
  return [$nodes, $edges];
};

/** Builds an edge in the id/handle shape the runtime and editor expect. */
$edge = function (string $src, string $srcPort, string $tgt, string $tgtPort, string $category): array {
  $sh = "$src-output-$srcPort";
  $th = "$tgt-input-$tgtPort";
  return [
    'id' => "xy-edge__{$src}{$sh}-{$tgt}{$th}",
    'source' => $src,
    'target' => $tgt,
    'sourceHandle' => $sh,
    'targetHandle' => $th,
    'data' => [
      'targetNodeType' => 'universalNode',
      'targetCategory' => $category,
      'metadata' => ['edgeType' => 'data', 'sourcePortDataType' => 'string'],
    ],
    'style' => 'stroke: var(--fd-edge-data);',
    'markerEnd' => ['type' => 'arrowclosed', 'width' => 16, 'height' => 16, 'color' => 'var(--fd-edge-data)'],
    'class' => 'flowdrop--edge--data',
  ];
};

$urlPort = function (string $nodeId, string $port = 'url'): array {
  return [[
    'name' => 'url',
    'node_id' => $nodeId,
    'port' => $port,
    'title' => 'URL',
    'description' => 'The page to fetch, convert to Markdown and redact.',
    'required' => TRUE,
  ]];
};

$specs = [
  'bench_1_reference' => [
    'source' => 'redactor_an_ai_workflow',
    'label' => 'Bench 1 · Deterministic Reference (no LLM)',
    'description' => 'Fetch, then convert to Markdown with the html_to_markdown node. No model is called, so this run costs nothing and its output is byte-identical every time. It is the ground truth the LLM variants are scored against for structure — and, having no redaction step, the control that shows what the redaction is actually adding.',
    'build' => function (array $nodes, array $edges) use ($without, $edge) {
      // Drop the model; wire the Markdown straight to the output.
      [$nodes, $edges] = $without($nodes, $edges, ['flowdrop_ai_provider_chat.1']);
      $edges[] = $edge('html_to_markdown.1', 'markdown', 'chat_output.3', 'message', 'processing');
      return [$nodes, $edges];
    },
    'ports' => 'http_request.1',
  ],
  'bench_2_raw_html_llm' => [
    'source' => 'redactor_lazy_ai_workflow',
    'label' => 'Bench 2 · Raw HTML → LLM',
    'description' => 'Fetch, then hand the raw HTML to the model and ask it to do both jobs at once: convert to Markdown and redact. The least build effort of any variant, and the one whose input tokens scale with the size of the page rather than the size of its content.',
    'build' => NULL,
    'ports' => 'http_request.1',
  ],
  'bench_3_markdown_llm' => [
    'source' => 'redactor_an_ai_workflow',
    'label' => 'Bench 3 · Markdown Node → LLM',
    'description' => 'Fetch, convert to Markdown deterministically, then spend the model only on the redaction. The hybrid: the graph already knows how to do the mechanical half, so the tokens buy judgement instead of transcription.',
    'build' => NULL,
    'ports' => 'http_request.1',
  ],
  'bench_4_ai_agent_tool' => [
    'source' => 'redactor_a_lazy_agent_workflow',
    'label' => 'Bench 4 · Drupal AI Agent (html_to_markdown tool)',
    'description' => 'Fetch, then pass the HTML to a Drupal AI Agent that owns an html_to_markdown tool and decides for itself whether to use it. Same two jobs as Bench 3, but the sequencing is the model\'s call rather than the graph\'s.',
    'build' => NULL,
    'ports' => 'http_request.1',
  ],
  'bench_5_react_agent' => [
    'source' => 'redactor_an_flowdrop_agent_workflow',
    'label' => 'Bench 5 · FlowDrop ReAct Agent (fetch + markdown tools)',
    'description' => 'Hand a bare URL to a FlowDrop ReAct agent whose toolbox holds both the HTTP fetch and the Markdown conversion, and let it reason its way to the answer. Nothing about the order of work is encoded in the graph — the fully autonomous end of the ladder.',
    'build' => function (array $nodes, array $edges) use ($without) {
      // The URL was baked into a prompt_template; the input port replaces it.
      return $without($nodes, $edges, ['prompt_template.1']);
    },
    'ports' => ['flowdrop_workflow_react_agent_with_tools.1', 'message'],
  ],
];

foreach ($specs as $id => $spec) {
  $src = $storage->load($spec['source']);
  if (!$src) { printf("!! source %s missing\n", $spec['source']); continue; }

  $nodes = $src->getNodes();
  $edges = $src->getEdges();
  if ($spec['build']) {
    [$nodes, $edges] = ($spec['build'])($nodes, $edges);
  }

  $portArgs = (array) $spec['ports'];
  [$portNode, $portName] = [$portArgs[0], $portArgs[1] ?? 'url'];
  $ports = $urlPort($portNode, $portName);

  // A workflow input may only target a port the node actually exposes (R10).
  // These ports were configured rather than connected in the source
  // workflows, so they are not exposed yet.
  foreach ($nodes as &$n) {
    if ($n['id'] !== $portNode) { continue; }
    // A node that was never wired has no explicit port map; its node type's
    // exposed_by_default then governs, so there is nothing to override.
    if (!isset($n['data']['config']['ports']['inputs'])) { continue; }
    foreach ($n['data']['config']['ports']['inputs'] as &$in) {
      if ($in['id'] === $portName) { $in['exposed'] = TRUE; }
    }
    unset($in);
  }
  unset($n);

  $metadata = $src->getMetadata();
  // Synchronous so the harness can wait on the run inline and time it.
  $metadata['orchestrator_settings'] = ['type' => 'flowdrop_runtime:synchronous'];

  if ($existing = $storage->load($id)) { $existing->delete(); }

  $new = $storage->create([
    'id' => $id,
    'label' => $spec['label'],
    'status' => TRUE,
  ]);
  $new->setDescription($spec['description'])
    ->setNodes($nodes)
    ->setEdges($edges)
    ->setInputPorts($ports)
    ->setMetadata($metadata)
    ->save();

  printf("  %-24s <- %-38s  %d nodes, %d edges\n", $id, $spec['source'], count($nodes), count($edges));
}
print "\nDone.\n";
