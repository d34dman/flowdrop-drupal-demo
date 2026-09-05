#!/bin/sh
# B8 + B9 sweep, Haiku then Sonnet 5.
set -u
WF=bench_8_react_with_tools_in_parent,bench_9_reflexion_with_tools_in_parent
for M in claude-haiku-4-5-20251001 claude-sonnet-5; do
  echo "=================== $M ==================="
  drush php:script scratchpad/bench/set_model.php -- "$M" || exit 1
  drush php:script scratchpad/bench/launch.php -- scratchpad/bench/results "$WF" small,medium,large 1 "bench89-$M"
  echo "--- $M done ---"
done
echo "=================== B8/B9 COMPLETE ==================="
