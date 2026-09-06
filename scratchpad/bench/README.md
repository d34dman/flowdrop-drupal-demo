# Benchmark harness

Everything needed to run a cell of the redaction benchmark and read the result. The site
runs in DDEV and `drush` is not on the host, so every script goes through `ddev exec`.
The invitation and the contribution path are in the root [README](../../README.md); this
file is the reference for the pieces.

## Cells

| Cell | Workflow id | Needs a model |
|---|---|---|
| B0 | `bench_0_floor` | no |
| B1 | `bench_1_reference` | no |
| B2 | `bench_2_raw_html_llm` | yes |
| B3 | `bench_3_markdown_llm` | yes |
| B4 | `bench_4_ai_agent_tool` | yes |
| B5 | `bench_5_react_agent` | yes |
| B6 | `bench_6_agent_autonomous` | yes |
| B7 | `bench_7_react_optimized` | yes |
| B8 | `bench_8_react_with_tools_in_parent` | yes |
| B9 | `bench_9_reflexion_with_tools_in_parent` | yes |

Pages: `small`, `medium`, `large` from the flowdrop-ai-bench corpus, read from its
`corpus/v1/manifest.json` at run time (URL and sha256 per page). B5a, the naive-prompt
control, was retired when every cell moved to the one shared prompt. Models run so far:
`claude-haiku-4-5-20251001`, `claude-sonnet-4-6`, `claude-sonnet-5`, `claude-opus-5`.
Pass the bare Anthropic model id; the provider defaults to `anthropic`.

## Run

```sh
ddev exec sh scratchpad/bench/run_cell.sh <cells> <model> [pages] [reps] [tag] \
  > scratchpad/bench/<tag>.log 2>&1
```

`run_cell.sh` does four things in order:

0. `set_prompt.php` fetches `prompt/redact.v1.md` (and `critic.v1.md` for B9) from the
   bench site and writes it into every model-calling node and agent: chat and reason
   nodes' `systemPrompt` (un-exposing the port, which otherwise shadows the value), the
   ReAct/Reflexion engines' `system_prompt` and `critic_prompt`, and the two AI Agents
   entities. The ledger records the prompt's sha256.
1. `set_model.php` points every model-calling node in every bench workflow at the model.
   There is no per-cell model, so a sweep across models must set it each time. The model
   lives in three unrelated config shapes (chat node, reason processor, agent executor);
   the script patches all three so a matrix never silently mixes models.
2. `launch.php` runs the cells and appends one ledger line per run to `results/runs.jsonl`:
   run identity, pipeline id, wall seconds and the metering context uuid. It switches to
   user 1 because the anonymous quota is far too small.
3. `collect.php` rewrites `results/metrics.jsonl` for **all** runs from stored state
   (calls, tokens, cached tokens, cost, status) and writes each output document to
   `results/outputs/<run_id>.md`. It is idempotent and free; rerun it any time.

Everything the harness needs from the bench repo is read over HTTP from
`https://d34dman.github.io/flowdrop-ai-bench/`. `BENCH_BASE` overrides that (a local
checkout served with `python3 -m http.server`, reached from the container as
`http://host.docker.internal:<port>/`); `BENCH_CORPUS` picks the corpus version.

Agent cells take 20 s to several minutes per page; B6 and B9 on `large` can take
10+ minutes. Costs are real: a single-cell single-page run is cents, a full B2–B9 sweep
on three pages is a few dollars. Never relaunch a failed cell without reading why it
failed (`results/outputs/`, the `<tag>.log`).

## Read

```sh
python3 scratchpad/bench/summarize.py B5 small sonnet-5   # every run of that cell/page/model
```

A run with `pipeline_status != completed` or a tiny `output_chars` is a failure, not a
data point. Eyeball the redaction in `results/outputs/<run_id>.md`: the marks are runs of
`▌`, competitor names should be gone, Drupal and the page's own subject should remain.

## Files

| Path | What |
|---|---|
| `run_cell.sh` | The runner. Cell table at the top; add new variants there. |
| `set_model.php`, `launch.php`, `collect.php` | The three steps above |
| `results/runs.jsonl` | Launch ledger, append-only |
| `results/metrics.jsonl` | Derived metrics, rewritten by `collect.php` |
| `results/outputs/` | One Markdown document per run |
| `results/runs/` | One JSON file per run, the unit of contribution |
| `export.py <bench-checkout> <tag-prefix>` | Copies the runs and outputs for a tag into a flowdrop-ai-bench checkout, ready for a PR |
| `bench_lib.php`, `set_prompt.php` | Manifest/prompt fetch and the prompt push |
| `fold_runs.py` | Retired with the v1 dataset; use `export.py` |
| `summarize.py`, `analyse.py`, `overhead.py`, `report_final.py` | Local analysis |
| `build*.php`, `apply_prompt.php`, `fix_bench7_ports.php` | One-off scripts that built or repaired the bench workflows; kept for the record |
| `*.log`, `*.sh` | Logs and wrappers of the sweeps behind the published dataset |

## Rules

- The dataset lives in flowdrop-ai-bench. Runs get there through `export.py` and a PR;
  nothing is hand-edited.
- `results/` is committed. Commit new runs as `bench: <what> on <model>`.
- Changing a workflow or prompt changes the experiment. Export the config
  (`ddev drush cex`) and say what changed in the commit or PR.
