<?php
// Lists interrupt entities (if the interrupt module is on) newest first, with pipeline/job/kind/status.
$etm = \Drupal::entityTypeManager();
if (!$etm->hasDefinition('flowdrop_interrupt')) { echo "no flowdrop_interrupt entity type\n"; foreach (array_keys($etm->getDefinitions()) as $k) if (str_starts_with($k,'flowdrop')) echo " $k\n"; return; }
$ids = $etm->getStorage('flowdrop_interrupt')->getQuery()->accessCheck(FALSE)->sort('id','DESC')->range(0,8)->execute();
foreach ($etm->getStorage('flowdrop_interrupt')->loadMultiple($ids) as $i) {
  $row = [];
  foreach ($i->getFields() as $k => $f) { if (in_array($k,['uuid','langcode'])) continue; $v = $f->getString(); if ($v!=='') $row[] = "$k=" . mb_substr(preg_replace('/\s+/',' ',$v),0,160); }
  echo implode(' | ', $row), "\n\n";
}
