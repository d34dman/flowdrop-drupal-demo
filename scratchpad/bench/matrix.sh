#!/bin/sh
# Runs the full variant x page sweep once per model.
# Cheapest model first, so an interrupted matrix loses the least money.
set -u
WF=bench_2_raw_html_llm,bench_3_markdown_llm,bench_4_ai_agent_tool,bench_5_react_agent,bench_5a_react_agent_naive,bench_6_agent_autonomous
for M in claude-haiku-4-5-20251001 claude-sonnet-5 claude-opus-5; do
  echo "=================== $M ==================="
  drush php:script scratchpad/bench/set_model.php -- "$M" || exit 1
  drush php:script scratchpad/bench/launch.php -- scratchpad/bench/results "$WF" small,medium,large 1 "matrix-$M"
  echo "--- $M done ---"
done
echo "=================== MATRIX COMPLETE ==================="
