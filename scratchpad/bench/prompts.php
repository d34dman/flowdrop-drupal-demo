<?php
// The system prompt each harness actually sends, and the tool schemas offered.
$wf = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow')->load('react_agent_with_tools');
foreach ($wf->get('nodes') as $n) {
  $cfg = $n['data']['config'] ?? [];
  foreach (['systemPrompt', 'system_prompt', 'prompt'] as $k) {
    if (!empty($cfg[$k])) {
      printf("=== B5 react_agent_with_tools :: node %s :: %s ===\n%s\n\n",
        $n['id'], $k, $cfg[$k]);
    }
  }
  if (!empty($cfg['maxTokens']) || !empty($cfg['max_tokens'])) {
    printf("    [%s] maxTokens=%s\n", $n['id'], $cfg['maxTokens'] ?? $cfg['max_tokens']);
  }
}
$agent = \Drupal::entityTypeManager()->getStorage('ai_agent')->load('agent_bench_autonomous');
if ($agent) {
  $a = $agent->toArray();
  foreach (['system_prompt', 'description', 'label', 'max_loops', 'llm_model'] as $k) {
    if (isset($a[$k]) && $a[$k] !== '') {
      printf("=== B6 ai_agent :: %s ===\n%s\n\n", $k,
        is_scalar($a[$k]) ? $a[$k] : json_encode($a[$k]));
    }
  }
}
