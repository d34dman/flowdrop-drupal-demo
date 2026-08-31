<?php
// Add current Anthropic rates for the models the bench matrix uses.
// Rates: Anthropic first-party API list price, USD per token.
$rates = [
  // $3 / $15 per MTok
  'anthropic:claude-sonnet-4-6'            => [3.0E-6,  1.5E-5],
  // $2 / $10 per MTok
  'anthropic:claude-sonnet-5'              => [2.0E-6,  1.0E-5],
  // $5 / $25 per MTok
  'anthropic:claude-opus-5'                => [5.0E-6,  2.5E-5],
  // $15 / $75 per MTok. The existing config keyed this as ...-20250929,
  // which is not a model id the provider serves; the real id is -20251101.
  'anthropic:claude-opus-4-5-20251101'     => [1.5E-5,  7.5E-5],
];

$config = \Drupal::configFactory()->getEditable('ai_metering.settings');
$pricing = $config->get('pricing') ?? [];
foreach ($rates as $key => [$in, $out]) {
  $pricing[$key] = [
    'input_per_token'      => $in,
    'output_per_token'     => $out,
    // Anthropic cache read = 10% of input; cache write = 125% of input.
    'cache_read_per_token'  => $in * 0.1,
    'cache_write_per_token' => $in * 1.25,
  ];
  printf("  priced %-40s in=%s out=%s\n", $key, $in, $out);
}
$config->set('pricing', $pricing)->save();
print "\nDone.\n";
