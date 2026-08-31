<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_job');
$in  = json_decode((string) $s->load($extra[0])->get('output_data')->value, TRUE)['markdown'];
$out = json_decode((string) $s->load($extra[1])->get('output_data')->value, TRUE)['response'];
$li = preg_split('/\n/', $in); $lo = preg_split('/\n/', $out);
printf("input lines %d, output lines %d\n", count($li), count($lo));
$si = array_map('trim', $li); $so = array_map('trim', $lo);
$setI = array_count_values(array_filter($si)); $setO = array_count_values(array_filter($so));
$shared = 0; foreach ($setO as $l => $n) { if (isset($setI[$l])) { $shared += min($n, $setI[$l]); } }
printf("output non-blank lines present verbatim in input: %d of %d (%.1f%%)\n",
  $shared, array_sum($setO), 100 * $shared / max(array_sum($setO), 1));
// where do they diverge, line-wise?
$k = 0; while ($k < min(count($si), count($so)) && $si[$k] === $so[$k]) { $k++; }
printf("identical for the first %d lines; then:\n  IN  %s\n  OUT %s\n", $k,
  substr($si[$k] ?? '', 0, 120), substr($so[$k] ?? '', 0, 120));
printf("redaction glyphs: input=%d output=%d\n",
  substr_count($in, "\u{258C}\u{258C}\u{258C}\u{258C}"), substr_count($out, "\u{258C}\u{258C}\u{258C}\u{258C}"));
