<?php
// Usage: drush php:script set_sync_orchestrator.php -- <wf_id,...>
// Restores metadata.orchestrator_settings = flowdrop_runtime:synchronous, which the editor drops on save
// and which launch.php's wait:TRUE needs.
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach (explode(',', $extra[0]) as $id) {
  $wf = $s->load($id); if (!$wf) { echo "!! $id missing\n"; continue; }
  $meta = $wf->get('metadata') ?? [];
  $before = json_encode($meta['orchestrator_settings'] ?? NULL);
  $meta['orchestrator_settings'] = ['type' => 'flowdrop_runtime:synchronous'];
  $wf->set('metadata', $meta); $wf->save();
  printf("%s: orchestrator_settings %s -> %s\n", $id, $before, json_encode($meta['orchestrator_settings']));
}
