<?php
/**
 * Points every model call in the benchmark at one model.
 *
 * The model is configured in three unrelated shapes, and missing any one of
 * them produces a matrix that silently mixes models:
 *   - flowdrop_ai_provider_chat  -> config.model            (bare model id)
 *   - flowdrop_node_processor_reason -> config.model        (bare model id)
 *   - ai_agents_executor         -> config.llm_model        ("provider__model")
 *
 * The ReAct sub-workflow is shared by bench_5 and bench_5a, so patching it once
 * covers both.
 *
 * Usage: drush php:script set_model.php -- <model_id> [provider]
 */
$model = $extra[0] ?? NULL;
$provider = $extra[1] ?? 'anthropic';
if (!$model) { throw new \RuntimeException('usage: set_model.php -- <model_id> [provider]'); }

$storage = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$targets = [
  'bench_2_raw_html_llm', 'bench_3_markdown_llm', 'bench_4_ai_agent_tool',
  'bench_5_react_agent', 'bench_5a_react_agent_naive', 'bench_6_agent_autonomous',
  'react_agent_with_tools', 'react_agent_with_optimized_tools', 'bench_7_react_optimized',
  'react_agent_engine', 'reflexion_agent_engine', 'react_agent_with_optimized_tools_v2',
  'bench_8_react_with_tools_in_parent', 'bench_9_reflexion_with_tools_in_parent',
];

$touched = 0;
foreach ($targets as $id) {
  $wf = $storage->load($id);
  if (!$wf) { printf("  !! missing %s\n", $id); continue; }
  $nodes = $wf->get('nodes');
  $changed = FALSE;
  foreach ($nodes as &$n) {
    $nt = $n['data']['metadata']['node_type_id'] ?? '';
    if (preg_match('/ai_provider_chat|processor_reason/', $nt)) {
      if (($n['data']['config']['model'] ?? NULL) !== $model) {
        $n['data']['config']['model'] = $model;
        $changed = TRUE;
        printf("  %-28s %-42s model      = %s\n", $id, $n['id'], $model);
      }
    }
    if (str_contains($nt, 'ai_agents_executor')) {
      $simple = $provider . '__' . $model;
      if (($n['data']['config']['llm_model'] ?? NULL) !== $simple) {
        $n['data']['config']['llm_model'] = $simple;
        $changed = TRUE;
        printf("  %-28s %-42s llm_model  = %s\n", $id, $n['id'], $simple);
      }
    }
  }
  unset($n);
  if ($changed) { $wf->set('nodes', $nodes)->save(); $touched++; }
}
printf("\nupdated %d workflows -> %s:%s\n", $touched, $provider, $model);
