#!/bin/sh
set -u
M=claude-haiku-4-5-20251001
drush php:script scratchpad/bench/set_model.php -- "$M" || exit 1
drush php:script scratchpad/bench/launch.php -- scratchpad/bench/results bench_9_reflexion_with_tools_in_parent small 1 "b9fix2-$M"
echo "=================== B9 HAIKU SMALL RETRY COMPLETE ==================="
