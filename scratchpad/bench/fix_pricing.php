<?php
/**
 * Corrects the Haiku 4.5 price and adds the undated alias.
 *
 * ai_metering keys pricing by the model id the provider reports, and the usage
 * rows from this benchmark carry undated ids ("claude-sonnet-4-6"). A dated key
 * alone therefore never matches, and an unmatched model is costed at zero
 * rather than failing — a whole matrix reading $0.00 with no warning.
 */
$config = \Drupal::configFactory()->getEditable('ai_metering.settings');
$pricing = $config->get('pricing');

// Anthropic list price: $1 / $5 per MTok. Cache read 10%, cache write 125%.
$haiku = [
  'input_per_token' => 1.0E-6,
  'output_per_token' => 5.0E-6,
  'cache_read_per_token' => 1.0E-7,
  'cache_write_per_token' => 1.25E-6,
];
foreach (['anthropic:claude-haiku-4-5-20251001', 'anthropic:claude-haiku-4-5'] as $key) {
  $before = $pricing[$key]['input_per_token'] ?? NULL;
  $pricing[$key] = $haiku;
  printf("%-40s %s -> %s per input token\n", $key,
    $before === NULL ? '(absent)' : sprintf('%.3E', $before), '1.000E-6');
}

// Same trap for every other model in the matrix: make sure the undated alias
// each provider actually reports is priced too.
$aliases = [
  'anthropic:claude-sonnet-4-5' => 'anthropic:claude-sonnet-4-5-20250929',
  'anthropic:claude-opus-4-5' => 'anthropic:claude-opus-4-5-20251101',
];
foreach ($aliases as $alias => $source) {
  if (!isset($pricing[$alias]) && isset($pricing[$source])) {
    $pricing[$alias] = $pricing[$source];
    printf("%-40s added, mirroring %s\n", $alias, $source);
  }
}

$config->set('pricing', $pricing)->save();
print "\nsaved.\n";
