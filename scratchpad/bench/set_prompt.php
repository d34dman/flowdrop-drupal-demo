<?php
/**
 * Writes the one benchmark prompt into every model-calling cell.
 *
 * The prompt lives in three unrelated config shapes, and a cell that keeps an
 * old prompt silently turns a workflow comparison into a prompt comparison:
 *   - flowdrop_ai_provider_chat / flowdrop_node_processor_reason -> config.systemPrompt
 *     (and the systemPrompt input port must be un-exposed: an exposed, unconnected
 *     port resolves to '' and shadows the configured value)
 *   - react / reflexion engine nodes                            -> config.system_prompt
 *     (reflexion also has config.critic_prompt, from critic.v1.md)
 *   - ai_agents.ai_agent entities                               -> system_prompt
 *
 * Usage: drush php:script set_prompt.php -- <cache_dir> [prompt_rel] [critic_rel]
 */
require_once __DIR__ . '/bench_lib.php';
$cache  = $extra[0] ?? 'scratchpad/bench/results/.bench-cache';
if ($cache[0] !== '/') { $cache = dirname(DRUPAL_ROOT) . '/' . $cache; }
$promptRel = $extra[1] ?? 'prompt/redact.v1.md';
$criticRel = $extra[2] ?? 'prompt/critic.v1.md';

[$meta, $prompt, $promptSha] = bench_prompt_file($promptRel, $cache);
[, $criticTpl, $criticSha] = bench_prompt_file($criticRel, $cache);
$comps = $meta['competitors'];
$compList = implode(', ', array_slice($comps, 0, -1)) . ' and ' . end($comps);
$critic = str_replace(['{{competitors}}', '{{glyph}}'], [$compList, $meta['glyph']], $criticTpl);

$workflows = [
  'bench_2_raw_html_llm', 'bench_3_markdown_llm', 'bench_5_react_agent',
  'bench_7_react_optimized', 'bench_8_react_with_tools_in_parent',
  'bench_9_reflexion_with_tools_in_parent',
  // Sub-workflows whose reason node carries its own systemPrompt.
  'react_agent_with_tools', 'react_agent_with_optimized_tools', 'react_agent_with_optimized_tools_v2',
];
$agents = ['agent_w81pomww', 'agent_bench_autonomous'];

$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach ($workflows as $id) {
  $wf = $s->load($id);
  if (!$wf) { print "  !! missing workflow $id\n"; continue; }
  $nodes = $wf->get('nodes');
  $changed = FALSE;
  foreach ($nodes as &$n) {
    $nt = $n['data']['metadata']['node_type_id'] ?? '';
    $c =& $n['data']['config'];
    if (preg_match('/ai_provider_chat|processor_reason/', $nt)) {
      $c['systemPrompt'] = $prompt;
      foreach ($c['ports']['inputs'] ?? [] as &$p) {
        if (($p['id'] ?? '') === 'systemPrompt') { $p['exposed'] = FALSE; }
      }
      unset($p);
      $changed = TRUE; printf("  %-40s %-30s systemPrompt\n", $id, $n['id']);
    }
    if (preg_match('/react_agent|reflexion_agent/', $nt) && array_key_exists('system_prompt', $c)) {
      $c['system_prompt'] = $prompt; $changed = TRUE; printf("  %-40s %-30s system_prompt\n", $id, $n['id']);
      if (array_key_exists('critic_prompt', $c)) { $c['critic_prompt'] = $critic; printf("  %-40s %-30s critic_prompt\n", $id, $n['id']); }
    }
    unset($c);
  }
  unset($n);
  if ($changed) { $wf->set('nodes', $nodes)->save(); }
}
$as = \Drupal::entityTypeManager()->getStorage('ai_agent');
foreach ($agents as $id) {
  $a = $as->load($id);
  if (!$a) { print "  !! missing agent $id\n"; continue; }
  $a->set('system_prompt', $prompt)->save();
  printf("  %-40s %-30s system_prompt\n", "ai_agent:$id", '');
}
printf("\nprompt %s (%d chars, sha256 %s); critic %s (sha256 %s); glyph %s\n",
  $promptRel, strlen($prompt), substr($promptSha, 0, 12), $criticRel, substr($criticSha, 0, 12), $meta['glyph']);
