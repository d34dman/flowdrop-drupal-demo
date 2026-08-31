<?php
/**
 * Compares each stored run's output against the deterministic reference,
 * to see how much of the document each variant actually reproduced.
 */
$runs = [
  126 => 'bench_1_reference (ground truth)',
  128 => 'bench_2_raw_html_llm',
  127 => 'bench_3_markdown_llm',
  129 => 'bench_4_ai_agent_tool',
  132 => 'bench_5a_react_naive',
];
$etm = \Drupal::entityTypeManager();
$texts = [];
foreach ($runs as $pid => $label) {
  $p = $etm->getStorage('flowdrop_pipeline')->load($pid);
  if (!$p) { printf("%-32s  (pipeline %d gone)\n", $label, $pid); continue; }
  $msg = '';
  foreach ($p->get('job_id') as $r) {
    $j = $r->entity; if (!$j) continue;
    if (!str_contains((string) ($j->get('node_type_id')->target_id ?? ''), 'output')) continue;
    $d = json_decode((string) $j->get('output_data')->value, TRUE);
    if (!empty($d['message'])) { $msg = $d['message']; }
  }
  $texts[$label] = $msg;
}
$refLen = strlen($texts['bench_1_reference (ground truth)'] ?? '');
printf("%-32s %8s %7s %7s %7s  %s\n", 'variant', 'chars', '%ref', 'h2+', 'words', 'leaks "WordPress"');
foreach ($texts as $label => $t) {
  $h = preg_match_all('/^#{1,6}\s/m', $t);
  $w = str_word_count(strip_tags($t));
  $leak = substr_count($t, 'WordPress');
  printf("%-32s %8d %6.0f%% %7d %7d  %s\n", $label, strlen($t),
    $refLen ? strlen($t) / $refLen * 100 : 0, $h, $w, $leak ? "YES ($leak)" : 'no');
}
