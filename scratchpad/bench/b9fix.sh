#!/bin/sh
# Rerun the three B9 cells that failed on FlowDrop 41779a34ee (#3592443), on a1095dbadf which carries the fix.
set -u
WF=bench_9_reflexion_with_tools_in_parent
for M in claude-sonnet-4-6 claude-haiku-4-5-20251001; do
  case $M in claude-sonnet-4-6) P=small;; *) P=small,medium;; esac
  echo "=================== $M ($P) ==================="
  drush php:script scratchpad/bench/set_model.php -- "$M" || exit 1
  drush php:script scratchpad/bench/launch.php -- scratchpad/bench/results "$WF" "$P" 1 "b9fix-$M"
done
echo "=================== B9 FIX RERUN COMPLETE ==================="
