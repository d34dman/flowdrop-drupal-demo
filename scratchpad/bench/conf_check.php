<?php
$wf = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow')->load($extra[0]);
foreach ($wf->get('nodes') as $n) {
  $c = $n['data']['config'] ?? [];
  printf("%-34s requiresConfirmation=%s  url=%s\n", $n['id'],
    var_export($c['requiresConfirmation'] ?? NULL, TRUE),
    isset($c['url']) ? "'" . $c['url'] . "'" : '(none)');
}
printf("input_ports: %s\n", json_encode($wf->get('input_ports')));
