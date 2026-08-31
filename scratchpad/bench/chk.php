<?php
$id = NULL;
foreach (['flowdrop_memory.memory_manager', 'flowdrop_memory.manager'] as $s) {
  if (\Drupal::hasService($s)) { $id = $s; break; }
}
printf("service id      : %s\n", $id ?? 'NOT FOUND');
printf("config value    : %s\n", var_export(\Drupal::config('flowdrop_memory.settings')->get('max_value_bytes'), TRUE));
if ($id) {
  $m = \Drupal::service($id);
  printf("effective limit : %s bytes (%.1f MB)\n", number_format($m->getMaxValueBytes()), $m->getMaxValueBytes() / 1048576);
}
