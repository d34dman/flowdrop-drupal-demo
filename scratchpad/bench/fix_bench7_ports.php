<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$old = 'flowdrop_workflow_react_agent_with_tools.1';
$new = 'flowdrop_workflow_react_agent_with_optimized_tools.1';
$b5 = $s->load('bench_5_react_agent');
printf("B5 input_ports:  %s\n", json_encode($b5->get('input_ports')));
printf("B5 output_ports: %s\n", json_encode($b5->get('output_ports')));
$wf = $s->load('bench_7_react_optimized');
foreach (['input_ports', 'output_ports'] as $f) {
  $ports = $b5->get($f);
  $fixed = json_decode(str_replace($old, $new, json_encode($ports)), TRUE);
  $wf->set($f, $fixed);
}
$wf->save();
printf("\nB7 input_ports:  %s\n", json_encode($wf->get('input_ports')));
printf("B7 output_ports: %s\n", json_encode($wf->get('output_ports')));
