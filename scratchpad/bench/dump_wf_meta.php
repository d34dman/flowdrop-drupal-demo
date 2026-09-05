<?php
// Usage: drush php:script dump_wf_meta.php -- <wf_id,...>  prints non-graph top-level fields of each workflow.
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach (explode(',', $extra[0]) as $id) {
  $wf = $s->load($id); if (!$wf) { echo "!! $id missing\n"; continue; }
  echo "== $id\n";
  foreach ($wf->toArray() as $k => $v) {
    if (in_array($k, ['nodes','edges','uuid','langcode','_core','dependencies'])) continue;
    echo "  $k: ", mb_substr(json_encode($v, JSON_UNESCAPED_SLASHES), 0, 200), "\n";
  }
}
