<?php
// Is the stored LLM response actually a slice of its own input?
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_job');
[$inJob, $outJob] = [$s->load($extra[0]), $s->load($extra[1])];
$in  = json_decode((string) $inJob->get('output_data')->value, TRUE)['markdown'];
$out = json_decode((string) $outJob->get('output_data')->value, TRUE)['response'];
printf("input  %d bytes\noutput %d bytes\n", strlen($in), strlen($out));
printf("output is a PREFIX of input: %s\n", str_starts_with($in, $out) ? 'YES' : 'no');
printf("output appears verbatim in input: %s\n", str_contains($in, $out) ? 'YES' : 'no');
$common = 0;
$n = min(strlen($in), strlen($out));
while ($common < $n && $in[$common] === $out[$common]) { $common++; }
printf("shared leading bytes: %d\n", $common);
printf("--- output HEAD ---\n%s\n", substr($out, 0, 200));
printf("--- output TAIL ---\n%s\n", substr($out, -200));
printf("--- input  HEAD ---\n%s\n", substr($in, 0, 200));
