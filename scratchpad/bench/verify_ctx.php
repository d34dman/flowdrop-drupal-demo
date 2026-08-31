<?php
$accountSwitcher = \Drupal::service('account_switcher');
$accountSwitcher->switchTo(\Drupal\user\Entity\User::load(1));

$uuid = \Drupal::service('uuid')->generate();
\Drupal::service('fd_bench.run_context')->set($uuid);

$wf = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow')->load('bench_3_markdown_llm');
$res = \Drupal::service('flowdrop_workflow_executor.launcher')->launch(
  $wf, ['url' => 'https://www.drupal.org/about'],
  new \Drupal\flowdrop_workflow_executor\DTO\LaunchOptions(wait: TRUE));
\Drupal::service('fd_bench.run_context')->set(NULL);

printf("run uuid : %s\npipeline : %s / %s\n\n", $uuid, $res->pipelineId, $res->status);
$rows = \Drupal::database()->query(
  'SELECT id, model_id, input_tokens, output_tokens, estimated_cost_usd, context_id
   FROM {ai_metering_usage} WHERE context_id = :c ORDER BY id', [':c' => $uuid])->fetchAll();
printf("rows matched by context_id: %d\n", count($rows));
foreach ($rows as $r) {
  printf("  #%d %-22s in=%-7d out=%-6d $%.6f ctx=%s\n", $r->id, $r->model_id,
    $r->input_tokens, $r->output_tokens, $r->estimated_cost_usd, $r->context_id);
}
