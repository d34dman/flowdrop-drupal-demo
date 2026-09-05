#!/bin/sh
set -u
M=claude-sonnet-5
drush php:script scratchpad/bench/set_model.php -- "$M" || exit 1
drush php:script scratchpad/bench/launch.php -- scratchpad/bench/results bench_5_react_agent small 1 "b5rerun-$M"
drush php:script scratchpad/bench/collect.php -- scratchpad/bench/results
echo "=================== B5 SONNET5 SMALL COMPLETE ==================="
