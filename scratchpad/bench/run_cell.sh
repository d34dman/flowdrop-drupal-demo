#!/bin/sh
# Runs one or more benchmark cells on one model and re-collects metrics.
# Corpus pages and the prompt come from https://d34dman.github.io/flowdrop-ai-bench/
# (override with BENCH_BASE / BENCH_CORPUS); the ledger records their hashes.
# Runs INSIDE the ddev web container:  ddev exec sh scratchpad/bench/run_cell.sh ...
#
# Usage: run_cell.sh <cells> <model> [pages] [reps] [tag]
#   cells  comma list of B0..B9 (or raw workflow ids)   e.g. B5  or  B8,B9  (B5a retired: one prompt for all)
#   model  bare Anthropic model id                     e.g. claude-sonnet-5
#   pages  comma list of small,medium,large            default: small,medium,large
#   reps   repetitions                                 default: 1
#   tag    ledger tag written to runs.jsonl            default: <cells>-<model>
set -u
CELLS=${1:?cells}; MODEL=${2:?model}; PAGES=${3:-small,medium,large}; REPS=${4:-1}; TAG=${5:-"$CELLS-$MODEL"}
OUT=scratchpad/bench/results

wf_of() {
  case "$1" in
    B0) echo bench_0_floor ;;                       B1) echo bench_1_reference ;;
    B2) echo bench_2_raw_html_llm ;;                B3) echo bench_3_markdown_llm ;;
    B4) echo bench_4_ai_agent_tool ;;               B5) echo bench_5_react_agent ;;
    B6) echo bench_6_agent_autonomous ;;
    B7) echo bench_7_react_optimized ;;             B8) echo bench_8_react_with_tools_in_parent ;;
    B9) echo bench_9_reflexion_with_tools_in_parent ;; *) echo "$1" ;;
  esac
}
WF=$(echo "$CELLS" | tr ',' '\n' | while read -r c; do wf_of "$c"; done | paste -sd, -)

echo "=== cells=$CELLS ($WF) model=$MODEL pages=$PAGES reps=$REPS tag=$TAG ==="
drush php:script scratchpad/bench/set_prompt.php -- "$OUT/.bench-cache" || exit 1
drush php:script scratchpad/bench/set_model.php -- "$MODEL" || exit 1
drush php:script scratchpad/bench/launch.php -- "$OUT" "$WF" "$PAGES" "$REPS" "$TAG" || exit 1
drush php:script scratchpad/bench/collect.php -- "$OUT" || exit 1
echo "=== COMPLETE tag=$TAG ==="
