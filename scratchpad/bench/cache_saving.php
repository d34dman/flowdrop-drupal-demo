<?php
/**
 * Estimates what Anthropic prompt caching would have saved, from the real
 * per-call token sequence of every multi-call run.
 *
 * The repeated prefix of call N is taken as the input of call N-1: an agent
 * loop appends to the conversation, so everything the previous call saw is
 * re-sent verbatim. Reads bill at 10% of input, writes at 125%, so a prefix
 * reused at least once already pays for its own write.
 */
$db = \Drupal::database();
$price = ['claude-opus-5' => 5.0, 'claude-sonnet-5' => 2.0,
          'claude-haiku-4-5-20251001' => 1.0, 'claude-sonnet-4-6' => 3.0];
$lines = file(dirname(\Drupal::root()) . '/scratchpad/bench/results/metrics.jsonl');
$tot = ['billed' => 0.0, 'saved' => 0.0, 'runs' => 0, 'repeat' => 0];
$per = [];
foreach ($lines as $line) {
  $r = json_decode($line, TRUE);
  if (!str_starts_with((string) ($r['tag'] ?? ''), 'matrix-')) { continue; }
  if ($r['pipeline_status'] !== 'completed' || $r['llm_calls'] < 2) { continue; }
  $model = $r['models'][0] ?? NULL;
  if (!$model || !isset($price[$model])) { continue; }
  $q = $db->select('ai_metering_usage', 'u')->fields('u')
    ->condition('context_id', $r['context_uuid']);
  if ($db->schema()->fieldExists('ai_metering_usage', 'id')) { $q->orderBy('id'); }
  $rows = array_map(fn($x) => (array) $x, $q->execute()->fetchAll());
  if (count($rows) < 2) { continue; }
  $p = $price[$model] / 1e6;
  $saved = 0.0; $repeat = 0;
  for ($i = 1; $i < count($rows); $i++) {
    $prefix = (int) $rows[$i - 1]['input_tokens'];
    $cur = (int) $rows[$i]['input_tokens'];
    $prefix = min($prefix, $cur);
    $repeat += $prefix;
    // Pay 125% once to write, then 10% to read instead of 100%.
    $saved += $prefix * $p * (1 - 0.10) - ($i === 1 ? $prefix * $p * 0.25 : 0);
  }
  $tot['billed'] += $r['cost_usd']; $tot['saved'] += max($saved, 0);
  $tot['runs']++; $tot['repeat'] += $repeat;
  $per[] = [$model, $r['workflow'], $r['url_key'], count($rows), $repeat,
            $r['input_tokens'], $r['cost_usd'], max($saved, 0)];
}
usort($per, fn($a, $b) => $b[7] <=> $a[7]);
printf("%-9s %-34s %5s %11s %11s %8s %9s\n",
  'model', 'cell', 'calls', 'repeated', 'total in', 'billed', 'saveable');
foreach (array_slice($per, 0, 10) as $x) {
  printf("%-9s %-34s %5d %11s %11s %8s %9s\n",
    str_replace(['claude-', '-20251001'], '', $x[0]), substr($x[1], 0, 24) . ' ' . $x[2],
    $x[3], number_format($x[4]), number_format($x[5]),
    '$' . number_format($x[6], 3), '$' . number_format($x[7], 3));
}
printf("\n%d multi-call runs: %s of %s input tokens are verbatim repeats (%.0f%%)\n",
  $tot['runs'], number_format($tot['repeat']),
  number_format(array_sum(array_column($per, 5))),
  100 * $tot['repeat'] / max(array_sum(array_column($per, 5)), 1));
printf("billed \$%.2f, recoverable \$%.2f (%.0f%%)\n",
  $tot['billed'], $tot['saved'], 100 * $tot['saved'] / max($tot['billed'], .0001));
