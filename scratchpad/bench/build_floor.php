<?php
/**
 * Builds bench_0_floor: the smallest workflow that can run at all — one output
 * node fed straight from the workflow input.
 *
 * Its whole runtime is orchestration: validation, pipeline creation, job
 * generation, one dispatch, persistence. Subtracting it from a real workflow's
 * overhead isolates the per-node and per-payload cost from the fixed cost of
 * starting a run at all.
 */
$storage = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$src = $storage->load('bench_1_reference');
$a = $src->toArray();

$outNode = NULL;
foreach ($a['nodes'] as $n) {
  if (str_starts_with($n['id'], 'chat_output')) { $outNode = $n; }
}
if (!$outNode) { throw new \RuntimeException('no chat_output node in bench_1_reference'); }

// The message input arrives from the workflow input rather than an edge, so it
// has to be exposed explicitly — an unwired port defaults to the node type's
// exposed_by_default, which is not necessarily true.
foreach ($outNode['data']['config']['ports']['inputs'] as &$in) {
  if ($in['id'] === 'message') { $in['exposed'] = TRUE; }
}
unset($in);
$outNode['position'] = ['x' => 0, 'y' => 0];

if ($existing = $storage->load('bench_0_floor')) { $existing->delete(); }

$storage->create([
  'id' => 'bench_0_floor',
  'label' => 'Bench 0 · Orchestration Floor',
  'description' => 'A single output node wired to the workflow input. It does no work, so its entire runtime is FlowDrop overhead: validation, pipeline creation, job generation, one dispatch and persistence. The baseline every other run is measured against.',
  'status' => TRUE,
  'nodes' => [$outNode],
  'edges' => [],
  'input_ports' => [[
    'name' => 'url',
    'node_id' => $outNode['id'],
    'port' => 'message',
    'title' => 'Text',
    'description' => 'Passed straight through; the content is irrelevant.',
    'required' => TRUE,
  ]],
  'metadata' => [
    'orchestrator_settings' => ['type' => 'flowdrop_runtime:synchronous'],
  ],
])->save();

printf("created bench_0_floor (node %s)\n", $outNode['id']);
