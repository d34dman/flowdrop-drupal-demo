#!/bin/sh
# B8 + B9 on Sonnet 4.6, to match the model every other cell of the plots page uses.
set -u
WF=bench_8_react_with_tools_in_parent,bench_9_reflexion_with_tools_in_parent
M=claude-sonnet-4-6
echo "=================== $M ==================="
drush php:script scratchpad/bench/set_model.php -- "$M" || exit 1
drush php:script scratchpad/bench/launch.php -- scratchpad/bench/results "$WF" small,medium,large 1 "bench89-$M"
echo "=================== B8/B9 SONNET 4.6 COMPLETE ==================="
