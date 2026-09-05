<?php
/**
 * Smoke test: throwaway copy of the Reflexion engine, run once with a trivial
 * ask and no tools; then print what the critic actually received.
 * Usage: drush php:script scratchpad/reflexion/smoke.php
 */
\Drupal::service('account_switcher')->switchTo(\Drupal\user\Entity\User::load(1));
$storage = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$src = $storage->load('reflexion_agent_engine');
if ($old = $storage->load('reflexion_smoke')) { $old->delete(); }
$copy = $src->createDuplicate();
$copy->set('id', 'reflexion_smoke');
$copy->set('label', 'Reflexion smoke (throwaway)');
$nodes = $copy->get('nodes');
foreach ($nodes as &$n) {
  if ($n['id'] === 'conversation_buffer.4') { $n['data']['config']['scope'] = 'pipeline'; }
}
unset($n);
$copy->set('nodes', $nodes);
$meta = $copy->get('metadata') ?? [];
$meta['orchestrator_settings'] = ['type' => 'flowdrop_stategraph:stategraph'];
$copy->set('metadata', $meta);
$copy->save();
register_shutdown_function(static fn() => $storage->load('reflexion_smoke')?->delete());

$launcher = \Drupal::service('flowdrop_workflow_executor.launcher');
$t0 = microtime(TRUE);
$res = $launcher->launch($copy, [
  'system_prompt' => 'You are a terse assistant. Answer in one short sentence.',
  'message' => 'Which planet is closest to the Sun?',
  'critic_prompt' => "You are a reviewer. You see the user's request and a draft answer. Reply VERDICT: ACCEPT only if the draft names the planet AND gives its average distance from the Sun in kilometres. Otherwise reply VERDICT: REVISE and state exactly what is missing. Do not write the answer yourself.",
], new \Drupal\flowdrop_workflow_executor\DTO\LaunchOptions(wait: TRUE));
printf("pipeline %s status %s in %.1fs\n", $res->pipelineId, $res->status, microtime(TRUE) - $t0);

$pipeline = \Drupal::entityTypeManager()->getStorage('flowdrop_pipeline')->load($res->pipelineId);
$jobs = $pipeline->getJobs();
usort($jobs, static fn($a, $b) => (int) $a->id() <=> (int) $b->id());
foreach ($jobs as $job) {
  $node = $job->getNodeId();
  $out = $job->getOutputData();
  printf("%-40s %-10s %s\n", $node, $job->getStatus(), $job->getErrorMessage() ? 'ERR ' . substr($job->getErrorMessage(), 0, 160) : '');
  if ($node === 'flowdrop_node_processor_reason.2') {
    echo "  CRITIC INPUT MESSAGES:\n";
    foreach (($job->getInputData()['messages'] ?? []) as $m) {
      printf("    [%s] %s\n", $m['role'] ?? '?', substr(is_string($m['content']) ? $m['content'] : json_encode($m['content']), 0, 300));
    }
    echo "  CRITIC OUTPUT: ", substr((string) ($out['text'] ?? ''), 0, 300), "\n";
  }
  if ($node === 'flowdrop_node_processor_reason.1') { echo "  AGENT SAW ", count($job->getInputData()['messages'] ?? []), " msgs; DRAFT: ", substr((string) ($out['text'] ?? ''), 0, 200), "\n"; }
  if ($node === 'text_output.1') { echo "  FINAL: ", substr((string) ($out['text'] ?? ''), 0, 300), "\n"; }
}
