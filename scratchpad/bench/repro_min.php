<?php
/**
 * Minimal reproduction on a node type unrelated to the agent stack.
 *
 * Builds two one-node workflows that differ ONLY in whether an optional input
 * port is exposed. Both configure the same value. If the exposed variant loses
 * its configured value, the defect is general and not specific to Reason.
 */
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach ([['repro_port_exposed', TRUE], ['repro_port_hidden', FALSE]] as [$id, $exposed]) {
  if ($old = $s->load($id)) { $old->delete(); }
  $s->create([
    'id' => $id,
    'label' => 'Repro: systemPrompt port ' . ($exposed ? 'exposed' : 'hidden'),
    'nodes' => [[
      'id' => 'text_output.1',
      'position' => ['x' => 100, 'y' => 100],
      'data' => [
        'label' => 'Text Output',
        'config' => [
          'text' => 'CONFIGURED-VALUE',
          'ports' => [
            'inputs' => [['id' => 'text', 'exposed' => $exposed]],
            'outputs' => [['id' => 'text']],
          ],
        ],
        'metadata' => ['node_type_id' => 'text_output'],
      ],
    ]],
    'edges' => [],
  ])->save();
  printf("built %-22s (text port exposed=%s, config text='CONFIGURED-VALUE')\n",
    $id, $exposed ? 'true' : 'false');
}
