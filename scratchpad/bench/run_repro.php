<?php
$launcher = \Drupal::service('flowdrop_workflow_executor.launcher');
$acct = \Drupal::service('account_switcher');
$acct->switchTo(\Drupal\user\Entity\User::load(1));
foreach (['repro_port_exposed', 'repro_port_hidden'] as $id) {
  $wf = \Drupal::entityTypeManager()->getStorage("flowdrop_workflow")->load($id);
  $res = $launcher->launch($wf, [],
    new \Drupal\flowdrop_workflow_executor\DTO\LaunchOptions(wait: TRUE));
  $p = \Drupal::entityTypeManager()->getStorage('flowdrop_pipeline')->load($res->pipelineId);
  $out = '(no job)';
  foreach ($p->get('job_id') as $ref) {
    if ($j = $ref->entity) { $out = (string) $j->get('output_data')->value; }
  }
  printf("%-22s pipeline=%-5s status=%-10s output=%s\n", $id, $res->pipelineId,
    $res->status, $out);
}
$acct->switchBack();
