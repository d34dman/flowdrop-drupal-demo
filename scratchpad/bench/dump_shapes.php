<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach (['bench_0_floor','bench_1_reference','bench_2_raw_html_llm','bench_3_markdown_llm',
          'bench_4_ai_agent_tool','bench_5_react_agent','bench_6_agent_autonomous',
          'react_agent_with_tools'] as $id) {
  $w = $s->load($id);
  if (!$w) { printf("%-28s MISSING\n", $id); continue; }
  $a = $w->toArray();
  printf("\n=== %s ===\n", $id);
  foreach ($a['nodes'] as $n) {
    $nt = $n['data']['metadata']['node_type_id'] ?? '?';
    $extra = '';
    foreach (['agent_id','workflow_id','model','provider'] as $k) {
      if (!empty($n['data']['config'][$k])) { $extra .= " $k=" . $n['data']['config'][$k]; }
    }
    printf("  %-42s %s%s\n", $n['id'], $nt, $extra);
  }
  foreach (($a['edges'] ?? []) as $e) {
    printf("    %s  ->  %s\n", $e['sourceHandle'] ?? $e['source'], $e['targetHandle'] ?? $e['target']);
  }
  printf("  IN : %s\n", json_encode($a['input_ports'] ?? NULL));
  printf("  OUT: %s\n", json_encode($a['output_ports'] ?? NULL));
}
