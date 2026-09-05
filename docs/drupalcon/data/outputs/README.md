# Benchmark output documents

This folder holds the verbatim final output of each redaction-benchmark run, copied byte-for-byte from `scratchpad/bench/results/outputs/` on 2026-09-05 (the Opus 5 B8/B9 cells and two B5 Sonnet 5 reruns folded later the same day by `scratchpad/bench/fold_runs.py`). These are the source documents that `runs.csv` in the parent folder was scored against — kept here so the numbers in the ledger can be checked against what the run actually produced.

A missing file means the run failed before producing output (`status` != `completed` in `runs.csv`, or `paused`); the index below flags these rows as **no output**. The 31 `bench_0_floor` runs are excluded even though their tiny output files exist: every one of them is a ~28-49 byte stub containing only the literal string `https://en.wikipedia.org/wiki/Drupal`, not a redaction output, so they carry no content worth inspecting.

**`RECOVERED_bench_5_large.md`** is the one exception to "missing file = no output": run `bench_5_react_agent__large__r1__1788100397` is recorded as failed (no `outputs/<run_id>.md`), but per `scratchpad/bench/results/annotations.jsonl` the pipeline actually finished the answer (job 2453 returned 38,343 chars of Markdown, 2 redactions) before dying later, in job 2456, on an unrelated bookkeeping step — appending to the conversation buffer past the 1MB pipeline-memory cap. The finished answer was retrievable from durable job state even though the engine delivered nothing to the caller, and it's kept here under its recovered filename rather than the run's own `run_id.md` name.

Regenerate the folder and this table with `scratchpad/export_outputs.py` / `scratchpad/gen_readme.py` (workspace scratch, not committed).

## Index

Grouped by variant, then page, then model. One row per run. `flag` is `⚠️HTML` when `html_tag_density > 5`, `shadowed` when `prompt_shadowed = 1`, `no output` when the run has no output file at all (failed/paused runs, excluding the recovered one above), and `excluded (stub)` for the 31 `bench_0_floor` rows whose source file exists but was not copied here (see above).


### B0 floor (`bench_0_floor`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_0_floor__large__r1__1788101317 | overhead-probe | large |  | 1 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r10__1788101327 | overhead-probe | large |  | 10 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r2__1788101318 | overhead-probe | large |  | 2 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r3__1788101319 | overhead-probe | large |  | 3 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r4__1788101320 | overhead-probe | large |  | 4 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r5__1788101322 | overhead-probe | large |  | 5 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r6__1788101323 | overhead-probe | large |  | 6 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r7__1788101324 | overhead-probe | large |  | 7 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r8__1788101325 | overhead-probe | large |  | 8 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__large__r9__1788101326 | overhead-probe | large |  | 9 | completed | 0 | 0 | 0.1 | 0 | 0 | 1 | excluded (stub) |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_0_floor__medium__r1__1788101315 | overhead-probe | medium |  | 1 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r10__1788101327 | overhead-probe | medium |  | 10 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r2__1788101318 | overhead-probe | medium |  | 2 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r3__1788101319 | overhead-probe | medium |  | 3 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r4__1788101320 | overhead-probe | medium |  | 4 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r5__1788101321 | overhead-probe | medium |  | 5 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r6__1788101322 | overhead-probe | medium |  | 6 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r7__1788101324 | overhead-probe | medium |  | 7 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r8__1788101324 | overhead-probe | medium |  | 8 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |
| bench_0_floor__medium__r9__1788101326 | overhead-probe | medium |  | 9 | completed | 0 | 0 | 0.4 | 0 | 1 | 1 | excluded (stub) |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_0_floor__small__r1__1788099734 | smoke | small |  | 1 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r1__1788101314 | overhead-probe | small |  | 1 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r10__1788101327 | overhead-probe | small |  | 10 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r2__1788101318 | overhead-probe | small |  | 2 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r3__1788101319 | overhead-probe | small |  | 3 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r4__1788101320 | overhead-probe | small |  | 4 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r5__1788101321 | overhead-probe | small |  | 5 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r6__1788101322 | overhead-probe | small |  | 6 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r7__1788101323 | overhead-probe | small |  | 7 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r8__1788101324 | overhead-probe | small |  | 8 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |
| bench_0_floor__small__r9__1788101326 | overhead-probe | small |  | 9 | completed | 0 | 0 | 0.4 | 0 | 0 | 1 | excluded (stub) |

### B1 reference (`bench_1_reference`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_1_reference__large__r1__1788095689](bench_1_reference__large__r1__1788095689.md) |  | large |  | 1 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r1__1788101317](bench_1_reference__large__r1__1788101317.md) | overhead-probe | large |  | 1 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r10__1788101327](bench_1_reference__large__r10__1788101327.md) | overhead-probe | large |  | 10 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r2__1788101318](bench_1_reference__large__r2__1788101318.md) | overhead-probe | large |  | 2 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r3__1788101319](bench_1_reference__large__r3__1788101319.md) | overhead-probe | large |  | 3 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r4__1788101320](bench_1_reference__large__r4__1788101320.md) | overhead-probe | large |  | 4 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r5__1788101322](bench_1_reference__large__r5__1788101322.md) | overhead-probe | large |  | 5 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r6__1788101323](bench_1_reference__large__r6__1788101323.md) | overhead-probe | large |  | 6 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r7__1788101324](bench_1_reference__large__r7__1788101324.md) | overhead-probe | large |  | 7 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r8__1788101325](bench_1_reference__large__r8__1788101325.md) | overhead-probe | large |  | 8 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |
| [bench_1_reference__large__r9__1788101326](bench_1_reference__large__r9__1788101326.md) | overhead-probe | large |  | 9 | completed | 0 | 0 | 100.0 | 0 | 5 | 664 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_1_reference__medium__r1__1788095208](bench_1_reference__medium__r1__1788095208.md) |  | medium |  | 1 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r1__1788101316](bench_1_reference__medium__r1__1788101316.md) | overhead-probe | medium |  | 1 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r10__1788101327](bench_1_reference__medium__r10__1788101327.md) | overhead-probe | medium |  | 10 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r2__1788101318](bench_1_reference__medium__r2__1788101318.md) | overhead-probe | medium |  | 2 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r3__1788101319](bench_1_reference__medium__r3__1788101319.md) | overhead-probe | medium |  | 3 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r4__1788101320](bench_1_reference__medium__r4__1788101320.md) | overhead-probe | medium |  | 4 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r5__1788101321](bench_1_reference__medium__r5__1788101321.md) | overhead-probe | medium |  | 5 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r6__1788101323](bench_1_reference__medium__r6__1788101323.md) | overhead-probe | medium |  | 6 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r7__1788101324](bench_1_reference__medium__r7__1788101324.md) | overhead-probe | medium |  | 7 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r8__1788101325](bench_1_reference__medium__r8__1788101325.md) | overhead-probe | medium |  | 8 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |
| [bench_1_reference__medium__r9__1788101326](bench_1_reference__medium__r9__1788101326.md) | overhead-probe | medium |  | 9 | completed | 0 | 0 | 100.0 | 0 | 36 | 46 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_1_reference__small__r1__1788094492](bench_1_reference__small__r1__1788094492.md) |  | small |  | 1 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r1__1788101314](bench_1_reference__small__r1__1788101314.md) | overhead-probe | small |  | 1 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r10__1788101327](bench_1_reference__small__r10__1788101327.md) | overhead-probe | small |  | 10 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r2__1788101318](bench_1_reference__small__r2__1788101318.md) | overhead-probe | small |  | 2 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r3__1788101319](bench_1_reference__small__r3__1788101319.md) | overhead-probe | small |  | 3 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r4__1788101320](bench_1_reference__small__r4__1788101320.md) | overhead-probe | small |  | 4 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r5__1788101321](bench_1_reference__small__r5__1788101321.md) | overhead-probe | small |  | 5 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r6__1788101322](bench_1_reference__small__r6__1788101322.md) | overhead-probe | small |  | 6 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r7__1788101323](bench_1_reference__small__r7__1788101323.md) | overhead-probe | small |  | 7 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r8__1788101324](bench_1_reference__small__r8__1788101324.md) | overhead-probe | small |  | 8 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |
| [bench_1_reference__small__r9__1788101326](bench_1_reference__small__r9__1788101326.md) | overhead-probe | small |  | 9 | completed | 0 | 0 | 100.0 | 0 | 0 | 247 |  |

### B2 raw html llm (`bench_2_raw_html_llm`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_2_raw_html_llm__large__r1__1788117644 | matrix-claude-haiku-4-5-20251001 | large |  | 1 | failed | 0 | 0 |  | 0 | 0 | 0 | no output |
| [bench_2_raw_html_llm__large__r1__1788120838](bench_2_raw_html_llm__large__r1__1788120838.md) | matrix-claude-opus-5 | large | claude-opus-5 | 1 | completed | 1 | 1.53714 | 47.2 | 0 | 1 | 165 |  |
| [bench_2_raw_html_llm__large__r1__1788099671](bench_2_raw_html_llm__large__r1__1788099671.md) | fill-large | large | claude-sonnet-4-6 | 1 | completed | 1 | 0.744642 | 43.5 | 4 | 2 | 160 |  |
| [bench_2_raw_html_llm__large__r1__1788118850](bench_2_raw_html_llm__large__r1__1788118850.md) | matrix-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 1 | 0.658356 | 46.7 | 4 | 0 | 169 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_2_raw_html_llm__medium__r1__1788117344](bench_2_raw_html_llm__medium__r1__1788117344.md) | matrix-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 1 | 0.068971 | 67.3 | 28 | 0 | 36 |  |
| [bench_2_raw_html_llm__medium__r1__1788120523](bench_2_raw_html_llm__medium__r1__1788120523.md) | matrix-claude-opus-5 | medium | claude-opus-5 | 1 | completed | 1 | 0.434275 | 68.9 | 27 | 2 | 37 |  |
| [bench_2_raw_html_llm__medium__r1__1788095209](bench_2_raw_html_llm__medium__r1__1788095209.md) |  | medium | claude-sonnet-4-6 | 1 | completed | 1 | 0.20835 | 68.8 | 28 | 1 | 37 |  |
| [bench_2_raw_html_llm__medium__r1__1788118009](bench_2_raw_html_llm__medium__r1__1788118009.md) | matrix-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 1 | 0.93559 | 567.2 | 40 | 3 | 51 | ⚠️HTML |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_2_raw_html_llm__small__r1__1788116759](bench_2_raw_html_llm__small__r1__1788116759.md) | matrix-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 1 | 0.081935 | 408.1 | 6 | 0 | 150 | ⚠️HTML |
| [bench_2_raw_html_llm__small__r1__1788120089](bench_2_raw_html_llm__small__r1__1788120089.md) | matrix-claude-opus-5 | small | claude-opus-5 | 1 | completed | 1 | 0.506635 | 407.7 | 0 | 0 | 156 | ⚠️HTML |
| [bench_2_raw_html_llm__small__r1__1788094492](bench_2_raw_html_llm__small__r1__1788094492.md) |  | small | claude-sonnet-4-6 | 1 | completed | 1 | 0.245253 | 407.6 | 0 | 0 | 156 | ⚠️HTML |
| [bench_2_raw_html_llm__small__r1__1788117676](bench_2_raw_html_llm__small__r1__1788117676.md) | matrix-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 1 | 0.200864 | 407.9 | 0 | 0 | 156 | ⚠️HTML |

### B3 markdown llm (`bench_3_markdown_llm`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_3_markdown_llm__large__r1__1788117646](bench_3_markdown_llm__large__r1__1788117646.md) | matrix-claude-haiku-4-5-20251001 | large | claude-haiku-4-5-20251001 | 1 | completed | 1 | 0.040001 | 4.3 | 0 | 0 | 17 |  |
| [bench_3_markdown_llm__large__r1__1788120924](bench_3_markdown_llm__large__r1__1788120924.md) | matrix-claude-opus-5 | large | claude-opus-5 | 1 | completed | 1 | 1.42555 | 96.6 | 4 | 0 | 411 |  |
| [bench_3_markdown_llm__large__r1__1788099836](bench_3_markdown_llm__large__r1__1788099836.md) | fill-large | large | claude-sonnet-4-6 | 1 | completed | 1 | 0.443241 | 83.7 | 2 | 2 | 379 |  |
| [bench_3_markdown_llm__large__r1__1788118965](bench_3_markdown_llm__large__r1__1788118965.md) | matrix-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 1 | 0.56705 | 96.6 | 4 | 0 | 411 |  |
| [bench_3_markdown_llm__large__r1__1788122467](bench_3_markdown_llm__large__r1__1788122467.md) | collision-probe | large | claude-sonnet-5 | 1 | completed | 1 | 0.56829 | 96.6 | 3 | 1 | 411 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_3_markdown_llm__medium__r1__1788117375](bench_3_markdown_llm__medium__r1__1788117375.md) | matrix-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 1 | 0.013951 | 69.0 | 29 | 0 | 37 |  |
| [bench_3_markdown_llm__medium__r1__1788120554](bench_3_markdown_llm__medium__r1__1788120554.md) | matrix-claude-opus-5 | medium | claude-opus-5 | 1 | completed | 1 | 0.14297 | 94.1 | 31 | 0 | 39 |  |
| [bench_3_markdown_llm__medium__r1__1788095263](bench_3_markdown_llm__medium__r1__1788095263.md) |  | medium | claude-sonnet-4-6 | 1 | completed | 1 | 0.041796 | 69.1 | 30 | 0 | 38 |  |
| [bench_3_markdown_llm__medium__r1__1788118586](bench_3_markdown_llm__medium__r1__1788118586.md) | matrix-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 1 | 0.059298 | 98.0 | 33 | 0 | 41 |  |
| [bench_3_markdown_llm__medium__r1__1788122309](bench_3_markdown_llm__medium__r1__1788122309.md) | determinism-probe | medium | claude-sonnet-5 | 1 | completed | 1 | 0.054248 | 93.3 | 30 | 3 | 43 |  |
| [bench_3_markdown_llm__medium__r2__1788122346](bench_3_markdown_llm__medium__r2__1788122346.md) | determinism-probe | medium | claude-sonnet-5 | 2 | completed | 1 | 0.061468 | 100.1 | 33 | 0 | 41 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_3_markdown_llm__small__r1__1788116518](bench_3_markdown_llm__small__r1__1788116518.md) | smoke-haiku | small | claude-haiku-4-5-20251001 | 1 | completed | 1 | 0.024565 | 85.8 | 0 | 0 | 89 |  |
| [bench_3_markdown_llm__small__r1__1788116887](bench_3_markdown_llm__small__r1__1788116887.md) | matrix-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 1 | 0.024565 | 85.8 | 0 | 0 | 89 |  |
| [bench_3_markdown_llm__small__r1__1788120260](bench_3_markdown_llm__small__r1__1788120260.md) | matrix-claude-opus-5 | small | claude-opus-5 | 1 | completed | 1 | 0.19673 | 97.4 | 0 | 0 | 101 |  |
| [bench_3_markdown_llm__small__r1__1788094745](bench_3_markdown_llm__small__r1__1788094745.md) |  | small | claude-sonnet-4-6 | 1 | completed | 1 | 0.085353 | 94.4 | 0 | 0 | 100 |  |
| [bench_3_markdown_llm__small__r1__1788117814](bench_3_markdown_llm__small__r1__1788117814.md) | matrix-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 1 | 0.075772 | 94.0 | 0 | 0 | 101 |  |

### B4 ai agent tool (`bench_4_ai_agent_tool`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_4_ai_agent_tool__large__r1__1788117657](bench_4_ai_agent_tool__large__r1__1788117657.md) | matrix-claude-haiku-4-5-20251001 | large |  | 1 | completed | 0 | 0 | 0.0 | 0 | 0 | 0 |  |
| [bench_4_ai_agent_tool__large__r1__1788121345](bench_4_ai_agent_tool__large__r1__1788121345.md) | matrix-claude-opus-5 | large | claude-opus-5 | 1 | completed | 1 | 1.58885 | 42.9 | 0 | 1 | 148 |  |
| [bench_4_ai_agent_tool__large__r1__1788100234](bench_4_ai_agent_tool__large__r1__1788100234.md) | fill-large | large | claude-sonnet-4-6 | 1 | completed | 2 | 1.409976 | 26.1 | 0 | 14 | 103 |  |
| [bench_4_ai_agent_tool__large__r1__1788119305](bench_4_ai_agent_tool__large__r1__1788119305.md) | matrix-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 2 | 1.328222 | 43.8 | 1 | 0 | 159 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_4_ai_agent_tool__medium__r1__1788117404](bench_4_ai_agent_tool__medium__r1__1788117404.md) | matrix-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 2 | 0.146824 | 74.1 | 29 | 0 | 37 |  |
| [bench_4_ai_agent_tool__medium__r1__1788120601](bench_4_ai_agent_tool__medium__r1__1788120601.md) | matrix-claude-opus-5 | medium | claude-opus-5 | 1 | completed | 2 | 0.98402 | 93.1 | 30 | 0 | 38 |  |
| [bench_4_ai_agent_tool__medium__r1__1788095315](bench_4_ai_agent_tool__medium__r1__1788095315.md) |  | medium | claude-sonnet-4-6 | 1 | completed | 2 | 0.450414 | 80.8 | 29 | 0 | 37 |  |
| [bench_4_ai_agent_tool__medium__r1__1788118627](bench_4_ai_agent_tool__medium__r1__1788118627.md) | matrix-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.373464 | 76.0 | 70 | 0 | 0 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_4_ai_agent_tool__small__r1__1788116932](bench_4_ai_agent_tool__small__r1__1788116932.md) | matrix-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 2 | 0.100625 | 92.6 | 0 | 0 | 96 |  |
| [bench_4_ai_agent_tool__small__r1__1788120323](bench_4_ai_agent_tool__small__r1__1788120323.md) | matrix-claude-opus-5 | small | claude-opus-5 | 1 | completed | 2 | 0.44292 | 94.0 | 0 | 0 | 85 |  |
| [bench_4_ai_agent_tool__small__r1__1788094828](bench_4_ai_agent_tool__small__r1__1788094828.md) |  | small | claude-sonnet-4-6 | 1 | completed | 2 | 0.318573 | 102.4 | 0 | 6 | 91 |  |
| [bench_4_ai_agent_tool__small__r1__1788117861](bench_4_ai_agent_tool__small__r1__1788117861.md) | matrix-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 2 | 0.150018 | 59.9 | 0 | 0 | 46 |  |

### B5 react agent (`bench_5_react_agent`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_5_react_agent__large__r1__1788117659 | matrix-claude-haiku-4-5-20251001 | large | claude-haiku-4-5-20251001 | 1 | failed | 1 | 0.001253 |  | 0 | 0 | 0 | no output |
| [bench_5_react_agent__large__r1__1788121442](bench_5_react_agent__large__r1__1788121442.md) | matrix-claude-opus-5 | large | claude-opus-5 | 1 | completed | 2 | 2.427035 | 84.1 | 4 | 0 | 339 |  |
| bench_5_react_agent__large__r1__1788100397 | fill-large | large | claude-sonnet-4-6 | 1 | failed | 3 | 1.90572 |  | 0 | 0 | 0 | no output |
| [bench_5_react_agent__large__r1__1788112643](bench_5_react_agent__large__r1__1788112643.md) | post-patch-10mb | large | claude-sonnet-4-6 | 1 | completed | 3 | 1.895445 | 74.9 | 2 | 2 | 327 |  |
| [bench_5_react_agent__large__r1__1788119487](bench_5_react_agent__large__r1__1788119487.md) | matrix-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 3 | 1.545168 | 43.9 | 0 | 1 | 161 |  |
| [bench_5_react_agent__large__r1__1788128755](bench_5_react_agent__large__r1__1788128755.md) | verify-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 3 | 1.699004 | 73.1 | 4 | 0 | 328 |  |
| [bench_5_react_agent__large__r1__1788171105](bench_5_react_agent__large__r1__1788171105.md) | final-sonnet5 | large | claude-sonnet-5 | 1 | completed | 4 | 2.466502 | 74.3 | 4 | 0 | 332 |  |
| [bench_5_react_agent__large__r1__1788622695](bench_5_react_agent__large__r1__1788622695.md) | b5rerun-large-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 4 | 2.24458 | 44.5 | 1 | 0 | 160 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_5_react_agent__medium__r1__1788117463](bench_5_react_agent__medium__r1__1788117463.md) | matrix-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 3 | 0.160982 | 68.1 | 0 | 29 | 37 |  |
| [bench_5_react_agent__medium__r1__1788120686](bench_5_react_agent__medium__r1__1788120686.md) | matrix-claude-opus-5 | medium | claude-opus-5 | 1 | completed | 2 | 0.60287 | 97.0 | 30 | 0 | 38 |  |
| [bench_5_react_agent__medium__r1__1788095426](bench_5_react_agent__medium__r1__1788095426.md) |  | medium | claude-sonnet-4-6 | 1 | completed | 3 | 0.523194 | 87.2 | 28 | 2 | 38 |  |
| [bench_5_react_agent__medium__r1__1788118682](bench_5_react_agent__medium__r1__1788118682.md) | matrix-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.247036 | 94.2 | 29 | 1 | 38 |  |
| [bench_5_react_agent__medium__r1__1788128692](bench_5_react_agent__medium__r1__1788128692.md) | verify-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.255454 | 93.5 | 29 | 0 | 37 |  |
| [bench_5_react_agent__medium__r1__1788170950](bench_5_react_agent__medium__r1__1788170950.md) | final-sonnet5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.307664 | 93.4 | 29 | 1 | 38 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_5_react_agent__small__r1__1788117054](bench_5_react_agent__small__r1__1788117054.md) | matrix-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 3 | 0.091443 | 75.7 | 0 | 0 | 70 |  |
| [bench_5_react_agent__small__r1__1788120407](bench_5_react_agent__small__r1__1788120407.md) | matrix-claude-opus-5 | small | claude-opus-5 | 1 | completed | 2 | 0.23575 | 95.2 | 0 | 0 | 84 |  |
| [bench_5_react_agent__small__r1__1788095052](bench_5_react_agent__small__r1__1788095052.md) |  | small | claude-sonnet-4-6 | 1 | completed | 3 | 0.201225 | 70.6 | 0 | 0 | 56 |  |
| [bench_5_react_agent__small__r1__1788117911](bench_5_react_agent__small__r1__1788117911.md) | matrix-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 2 | 0.05995 | 50.6 | 0 | 0 | 26 |  |
| [bench_5_react_agent__small__r1__1788128672](bench_5_react_agent__small__r1__1788128672.md) | verify-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 2 | 0.061752 | 50.6 | 0 | 0 | 30 |  |
| [bench_5_react_agent__small__r1__1788167797](bench_5_react_agent__small__r1__1788167797.md) | prompt-fix-probe | small | claude-sonnet-5 | 1 | completed | 3 | 0.317246 | 92.9 | 0 | 0 | 80 |  |
| [bench_5_react_agent__small__r1__1788169455](bench_5_react_agent__small__r1__1788169455.md) | marker-b5 | small | claude-sonnet-5 | 1 | completed | 3 | 0.16165 | 69.6 | 0 | 0 | 55 |  |
| [bench_5_react_agent__small__r1__1788169567](bench_5_react_agent__small__r1__1788169567.md) | promptlog | small | claude-sonnet-5 | 1 | completed | 2 | 0.062374 | 51.5 | 0 | 0 | 31 |  |
| [bench_5_react_agent__small__r1__1788169780](bench_5_react_agent__small__r1__1788169780.md) | banana2 | small | claude-sonnet-5 | 1 | completed | 1 | 0.001298 | 0.1 | 0 | 0 | 0 |  |
| [bench_5_react_agent__small__r1__1788169627](bench_5_react_agent__small__r1__1788169627.md) | banana | small | claude-sonnet-5 | 1 | completed | 3 | 0.27333 | 95.1 | 0 | 0 | 101 |  |
| [bench_5_react_agent__small__r1__1788169881](bench_5_react_agent__small__r1__1788169881.md) | prompt-really-fixed | small | claude-sonnet-5 | 1 | completed | 3 | 0.19252 | 70.5 | 0 | 0 | 57 |  |
| [bench_5_react_agent__small__r1__1788170858](bench_5_react_agent__small__r1__1788170858.md) | final-sonnet5 | small | claude-sonnet-5 | 1 | completed | 3 | 0.134518 | 50.6 | 0 | 0 | 30 |  |
| [bench_5_react_agent__small__r2__1788170005](bench_5_react_agent__small__r2__1788170005.md) | prompt-really-fixed | small | claude-sonnet-5 | 2 | completed | 3 | 0.27124 | 95.1 | 0 | 0 | 99 |  |
| [bench_5_react_agent__small__r1__1788621924](bench_5_react_agent__small__r1__1788621924.md) | b5rerun-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 2 | 0.060894 | 50.6 | 0 | 0 | 26 |  |

### B5a react agent naive (`bench_5a_react_agent_naive`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_5a_react_agent_naive__large__r1__1788117665 | matrix-claude-haiku-4-5-20251001 | large | claude-haiku-4-5-20251001 | 1 | failed | 1 | 0.001156 |  | 0 | 0 | 0 | no output |
| [bench_5a_react_agent_naive__large__r1__1788121715](bench_5a_react_agent_naive__large__r1__1788121715.md) | matrix-claude-opus-5 | large | claude-opus-5 | 1 | completed | 2 | 1.87854 | 44.4 | 3 | 1 | 161 |  |
| [bench_5a_react_agent_naive__large__r1__1788100991](bench_5a_react_agent_naive__large__r1__1788100991.md) | fill-large | large | claude-sonnet-4-6 | 1 | completed | 3 | 1.625898 | 41.2 | 9 | 7 | 152 |  |
| [bench_5a_react_agent_naive__large__r1__1788119645](bench_5a_react_agent_naive__large__r1__1788119645.md) | matrix-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 3 | 1.455626 | 42.9 | 4 | 0 | 163 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_5a_react_agent_naive__medium__r1__1788117524](bench_5a_react_agent_naive__medium__r1__1788117524.md) | matrix-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 3 | 0.158331 | 67.1 | 26 | 2 | 36 |  |
| [bench_5a_react_agent_naive__medium__r1__1788120750](bench_5a_react_agent_naive__medium__r1__1788120750.md) | matrix-claude-opus-5 | medium | claude-opus-5 | 1 | completed | 2 | 0.54625 | 79.9 | 30 | 0 | 38 |  |
| [bench_5a_react_agent_naive__medium__r1__1788095581](bench_5a_react_agent_naive__medium__r1__1788095581.md) |  | medium | claude-sonnet-4-6 | 1 | completed | 3 | 0.493044 | 69.1 | 29 | 0 | 37 |  |
| [bench_5a_react_agent_naive__medium__r1__1788118742](bench_5a_react_agent_naive__medium__r1__1788118742.md) | matrix-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.21866 | 80.7 | 29 | 0 | 37 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_5a_react_agent_naive__small__r1__1788117160](bench_5a_react_agent_naive__small__r1__1788117160.md) | matrix-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 3 | 0.042022 | 45.2 | 3 | 0 | 21 |  |
| [bench_5a_react_agent_naive__small__r1__1788120457](bench_5a_react_agent_naive__small__r1__1788120457.md) | matrix-claude-opus-5 | small | claude-opus-5 | 1 | completed | 2 | 0.15708 | 50.7 | 0 | 0 | 26 |  |
| [bench_5a_react_agent_naive__small__r1__1788095159](bench_5a_react_agent_naive__small__r1__1788095159.md) |  | small | claude-sonnet-4-6 | 1 | completed | 3 | 0.13836 | 48.9 | 3 | 0 | 29 |  |
| [bench_5a_react_agent_naive__small__r1__1788117927](bench_5a_react_agent_naive__small__r1__1788117927.md) | matrix-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 3 | 0.130632 | 51.4 | 0 | 0 | 31 |  |

### B6 agent autonomous (`bench_6_agent_autonomous`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_6_agent_autonomous__large__r1__1788117670](bench_6_agent_autonomous__large__r1__1788117670.md) | matrix-claude-haiku-4-5-20251001 | large | claude-haiku-4-5-20251001 | 1 | completed | 1 | 0.001343 | 0.0 | 0 | 0 | 0 |  |
| [bench_6_agent_autonomous__large__r1__1788121803](bench_6_agent_autonomous__large__r1__1788121803.md) | matrix-claude-opus-5 | large | claude-opus-5 | 1 | completed | 2 | 2.08134 | 85.2 | 4 | 0 | 338 |  |
| [bench_6_agent_autonomous__large__r1__1788110513](bench_6_agent_autonomous__large__r1__1788110513.md) | bench6-fill | large | claude-sonnet-4-6 | 1 | completed | 3 | 1.770204 | 74.5 | 2 | 2 | 329 |  |
| [bench_6_agent_autonomous__large__r1__1788119755](bench_6_agent_autonomous__large__r1__1788119755.md) | matrix-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 2 | 0.951126 | 81.5 | 4 | 0 | 336 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_6_agent_autonomous__medium__r1__1788117580](bench_6_agent_autonomous__medium__r1__1788117580.md) | matrix-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 3 | 0.149482 | 69.0 | 27 | 3 | 38 |  |
| [bench_6_agent_autonomous__medium__r1__1788120791](bench_6_agent_autonomous__medium__r1__1788120791.md) | matrix-claude-opus-5 | medium | claude-opus-5 | 1 | completed | 2 | 0.48942 | 93.8 | 30 | 0 | 38 |  |
| [bench_6_agent_autonomous__medium__r1__1788110386](bench_6_agent_autonomous__medium__r1__1788110386.md) | bench6-fill | medium | claude-sonnet-4-6 | 1 | completed | 3 | 0.460179 | 86.6 | 28 | 1 | 37 |  |
| [bench_6_agent_autonomous__medium__r1__1788118776](bench_6_agent_autonomous__medium__r1__1788118776.md) | matrix-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.245412 | 97.6 | 30 | 0 | 38 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_6_agent_autonomous__small__r1__1788116565](bench_6_agent_autonomous__small__r1__1788116565.md) | smoke-haiku | small | claude-haiku-4-5-20251001 | 1 | completed | 3 | 0.118074 | 69.6 | 0 | 0 | 55 |  |
| [bench_6_agent_autonomous__small__r1__1788117179](bench_6_agent_autonomous__small__r1__1788117179.md) | matrix-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 3 | 0.118074 | 69.4 | 0 | 0 | 55 |  |
| [bench_6_agent_autonomous__small__r1__1788120479](bench_6_agent_autonomous__small__r1__1788120479.md) | matrix-claude-opus-5 | small | claude-opus-5 | 1 | completed | 2 | 0.21341 | 94.8 | 0 | 0 | 83 |  |
| [bench_6_agent_autonomous__small__r1__1788110255](bench_6_agent_autonomous__small__r1__1788110255.md) | bench6-smoke | small | claude-sonnet-4-6 | 1 | completed | 3 | 0.184242 | 70.4 | 0 | 0 | 56 |  |
| [bench_6_agent_autonomous__small__r1__1788117962](bench_6_agent_autonomous__small__r1__1788117962.md) | matrix-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 2 | 0.094082 | 93.6 | 0 | 0 | 87 |  |

### B7 react optimized (`bench_7_react_optimized`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_7_react_optimized__large__r1__1788171871](bench_7_react_optimized__large__r1__1788171871.md) | b7-sonnet46 | large | claude-sonnet-4-6 | 1 | completed | 2 | 0.362523 | 80.9 | 2 | 2 | 335 |  |
| [bench_7_react_optimized__large__r1__1788171421](bench_7_react_optimized__large__r1__1788171421.md) | final-sonnet5 | large | claude-sonnet-5 | 1 | completed | 2 | 0.40919 | 79.7 | 4 | 0 | 331 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_7_react_optimized__medium__r1__1788171786](bench_7_react_optimized__medium__r1__1788171786.md) | b7-sonnet46 | medium | claude-sonnet-4-6 | 1 | completed | 2 | 0.06657 | 93.3 | 28 | 2 | 38 |  |
| [bench_7_react_optimized__medium__r1__1788171063](bench_7_react_optimized__medium__r1__1788171063.md) | final-sonnet5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.060336 | 93.2 | 28 | 2 | 38 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_7_react_optimized__small__r1__1788171729](bench_7_react_optimized__small__r1__1788171729.md) | b7-sonnet46 | small | claude-sonnet-4-6 | 1 | completed | 2 | 0.066042 | 69.3 | 0 | 0 | 48 |  |
| [bench_7_react_optimized__small__r1__1788169139](bench_7_react_optimized__small__r1__1788169139.md) | b7-smoke | small | claude-sonnet-5 | 1 | paused | 1 | 0.00226 |  | 0 | 0 | 0 | shadowed |
| [bench_7_react_optimized__small__r1__1788169186](bench_7_react_optimized__small__r1__1788169186.md) | b7-smoke2 | small | claude-sonnet-5 | 1 | completed | 2 | 0.027432 | 36.9 | 0 | 0 | 21 | shadowed |
| [bench_7_react_optimized__small__r1__1788169274](bench_7_react_optimized__small__r1__1788169274.md) | b7-reps | small | claude-sonnet-5 | 1 | completed | 2 | 0.027422 | 30.6 | 0 | 0 | 19 | shadowed |
| [bench_7_react_optimized__small__r1__1788169375](bench_7_react_optimized__small__r1__1788169375.md) | marker-test | small | claude-sonnet-5 | 1 | completed | 2 | 0.026832 | 28.1 | 0 | 0 | 16 | shadowed |
| [bench_7_react_optimized__small__r1__1788169404](bench_7_react_optimized__small__r1__1788169404.md) | marker-test2 | small | claude-sonnet-5 | 1 | completed | 2 | 0.026746 | 27.6 | 0 | 0 | 12 | shadowed |
| [bench_7_react_optimized__small__r1__1788169828](bench_7_react_optimized__small__r1__1788169828.md) | prompt-really-fixed | small | claude-sonnet-5 | 1 | completed | 2 | 0.082018 | 94.6 | 0 | 0 | 101 |  |
| [bench_7_react_optimized__small__r1__1788170898](bench_7_react_optimized__small__r1__1788170898.md) | final-sonnet5 | small | claude-sonnet-5 | 1 | completed | 2 | 0.082588 | 94.6 | 0 | 0 | 101 |  |
| [bench_7_react_optimized__small__r2__1788169288](bench_7_react_optimized__small__r2__1788169288.md) | b7-reps | small | claude-sonnet-5 | 2 | completed | 2 | 0.026402 | 30.0 | 0 | 0 | 17 | shadowed |
| [bench_7_react_optimized__small__r2__1788169953](bench_7_react_optimized__small__r2__1788169953.md) | prompt-really-fixed | small | claude-sonnet-5 | 2 | completed | 2 | 0.082418 | 94.6 | 0 | 0 | 101 |  |
| [bench_7_react_optimized__small__r3__1788169301](bench_7_react_optimized__small__r3__1788169301.md) | b7-reps | small | claude-sonnet-5 | 3 | completed | 2 | 0.027112 | 28.0 | 0 | 0 | 17 | shadowed |

### B8 react tools in parent (`bench_8_react_with_tools_in_parent`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_8_react_with_tools_in_parent__large__r1__1788572020](bench_8_react_with_tools_in_parent__large__r1__1788572020.md) | bench89-claude-haiku-4-5-20251001 | large | claude-haiku-4-5-20251001 | 1 | completed | 2 | 0.152432 | 80.4 | 0 | 4 | 342 |  |
| [bench_8_react_with_tools_in_parent__large__r1__1788606833](bench_8_react_with_tools_in_parent__large__r1__1788606833.md) | bench89-claude-sonnet-4-6 | large | claude-sonnet-4-6 | 1 | completed | 2 | 0.369348 | 82.2 | 4 | 2 | 337 |  |
| [bench_8_react_with_tools_in_parent__large__r1__1788573268](bench_8_react_with_tools_in_parent__large__r1__1788573268.md) | bench89-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 2 | 0.335066 | 81.9 | 8 | 0 | 336 |  |
| [bench_8_react_with_tools_in_parent__large__r1__1788623258](bench_8_react_with_tools_in_parent__large__r1__1788623258.md) | bench89-opus5-large | large | claude-opus-5 | 1 | completed | 2 | 1.266465 | 89.7 | 6 | 1 | 388 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_8_react_with_tools_in_parent__medium__r1__1788571895](bench_8_react_with_tools_in_parent__medium__r1__1788571895.md) | bench89-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 2 | 0.021069 | 91.4 | 40 | 10 | 38 |  |
| [bench_8_react_with_tools_in_parent__medium__r1__1788606666](bench_8_react_with_tools_in_parent__medium__r1__1788606666.md) | bench89-claude-sonnet-4-6 | medium | claude-sonnet-4-6 | 1 | completed | 2 | 0.071049 | 97.1 | 56 | 3 | 39 |  |
| [bench_8_react_with_tools_in_parent__medium__r1__1788573068](bench_8_react_with_tools_in_parent__medium__r1__1788573068.md) | bench89-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 2 | 0.063418 | 96.5 | 56 | 2 | 38 |  |
| bench_8_react_with_tools_in_parent__medium__r1__1788625183 | bench89-opus5-sm | medium | claude-opus-5 | 1 | paused | 1 | 0.007855 |  | 0 | 0 | 0 | no output |
| bench_8_react_with_tools_in_parent__medium__r1__1788625440 | bench89-opus5-medium-retry | medium | claude-opus-5 | 1 | paused | 1 | 0.007855 |  | 0 | 0 | 0 | no output |
| bench_8_react_with_tools_in_parent__medium__r1__1788625697 | bench89-opus5-medium-retry2 | medium | claude-opus-5 | 1 | paused | 1 | 0.00788 |  | 0 | 0 | 0 | no output |
| [bench_8_react_with_tools_in_parent__medium__r1__1788626572](bench_8_react_with_tools_in_parent__medium__r1__1788626572.md) | bench89-opus5-medium-nogate | medium | claude-opus-5 | 1 | completed | 2 | 0.610715 | 97.2 | 56 | 3 | 39 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_8_react_with_tools_in_parent__small__r1__1788571775](bench_8_react_with_tools_in_parent__small__r1__1788571775.md) | bench89-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 2 | 0.031191 | 94.1 | 0 | 0 | 99 |  |
| [bench_8_react_with_tools_in_parent__small__r1__1788606552](bench_8_react_with_tools_in_parent__small__r1__1788606552.md) | bench89-claude-sonnet-4-6 | small | claude-sonnet-4-6 | 1 | completed | 2 | 0.066072 | 69.3 | 0 | 0 | 48 |  |
| [bench_8_react_with_tools_in_parent__small__r1__1788572895](bench_8_react_with_tools_in_parent__small__r1__1788572895.md) | bench89-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 2 | 0.081564 | 94.0 | 0 | 0 | 101 |  |
| [bench_8_react_with_tools_in_parent__small__r1__1788625045](bench_8_react_with_tools_in_parent__small__r1__1788625045.md) | bench89-opus5-sm | small | claude-opus-5 | 1 | completed | 2 | 0.20269 | 94.3 | 0 | 0 | 100 |  |

### B9 reflexion tools in parent (`bench_9_reflexion_with_tools_in_parent`)


#### page: large

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [bench_9_reflexion_with_tools_in_parent__large__r1__1788572256](bench_9_reflexion_with_tools_in_parent__large__r1__1788572256.md) | bench89-claude-haiku-4-5-20251001 | large | claude-haiku-4-5-20251001 | 1 | completed | 7 | 0.496865 | 75.5 | 8 | 0 | 327 |  |
| [bench_9_reflexion_with_tools_in_parent__large__r1__1788607160](bench_9_reflexion_with_tools_in_parent__large__r1__1788607160.md) | bench89-claude-sonnet-4-6 | large | claude-sonnet-4-6 | 1 | completed | 7 | 1.205433 | 79.9 | 6 | 1 | 346 |  |
| [bench_9_reflexion_with_tools_in_parent__large__r1__1788573434](bench_9_reflexion_with_tools_in_parent__large__r1__1788573434.md) | bench89-claude-sonnet-5 | large | claude-sonnet-5 | 1 | completed | 7 | 1.282254 | 76.5 | 8 | 0 | 333 |  |
| [bench_9_reflexion_with_tools_in_parent__large__r1__1788623601](bench_9_reflexion_with_tools_in_parent__large__r1__1788623601.md) | bench89-opus5-large | large | claude-opus-5 | 1 | completed | 3 | 1.46291 | 89.8 | 6 | 1 | 388 |  |

#### page: medium

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_9_reflexion_with_tools_in_parent__medium__r1__1788571939 | bench89-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | failed | 6 | 0.058786 |  | 0 | 0 | 0 | no output |
| [bench_9_reflexion_with_tools_in_parent__medium__r1__1788612447](bench_9_reflexion_with_tools_in_parent__medium__r1__1788612447.md) | b9fix-claude-haiku-4-5-20251001 | medium | claude-haiku-4-5-20251001 | 1 | completed | 9 | 0.262026 | 93.0 | 58 | 0 | 37 |  |
| [bench_9_reflexion_with_tools_in_parent__medium__r1__1788606753](bench_9_reflexion_with_tools_in_parent__medium__r1__1788606753.md) | bench89-claude-sonnet-4-6 | medium | claude-sonnet-4-6 | 1 | completed | 3 | 0.079941 | 94.5 | 56 | 3 | 39 |  |
| [bench_9_reflexion_with_tools_in_parent__medium__r1__1788573110](bench_9_reflexion_with_tools_in_parent__medium__r1__1788573110.md) | bench89-claude-sonnet-5 | medium | claude-sonnet-5 | 1 | completed | 7 | 0.267566 | 96.8 | 70 | 0 | 43 |  |
| [bench_9_reflexion_with_tools_in_parent__medium__r1__1788625189](bench_9_reflexion_with_tools_in_parent__medium__r1__1788625189.md) | bench89-opus5-sm | medium | claude-opus-5 | 1 | completed | 5 | 1.332035 | 98.0 | 62 | 0 | 39 |  |

#### page: small

| run_id | tag | page | model | rep | status | calls | cost_usd | retention_pct | redactions | leaks | drupal_mentions | flag |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| bench_9_reflexion_with_tools_in_parent__small__r1__1788571833 | bench89-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | failed | 4 | 0.04435 |  | 0 | 0 | 0 | no output |
| bench_9_reflexion_with_tools_in_parent__small__r1__1788612167 | b9fix-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | paused | 10 | 0.242727 |  | 0 | 0 | 0 | no output |
| [bench_9_reflexion_with_tools_in_parent__small__r1__1788613357](bench_9_reflexion_with_tools_in_parent__small__r1__1788613357.md) | b9fix2-claude-haiku-4-5-20251001 | small | claude-haiku-4-5-20251001 | 1 | completed | 9 | 0.230297 | 94.9 | 0 | 0 | 106 |  |
| bench_9_reflexion_with_tools_in_parent__small__r1__1788606605 | bench89-claude-sonnet-4-6 | small | claude-sonnet-4-6 | 1 | failed | 4 | 0.095421 |  | 0 | 0 | 0 | no output |
| [bench_9_reflexion_with_tools_in_parent__small__r1__1788611993](bench_9_reflexion_with_tools_in_parent__small__r1__1788611993.md) | b9fix-claude-sonnet-4-6 | small | claude-sonnet-4-6 | 1 | completed | 9 | 0.345633 | 69.3 | 0 | 0 | 49 |  |
| [bench_9_reflexion_with_tools_in_parent__small__r1__1788572945](bench_9_reflexion_with_tools_in_parent__small__r1__1788572945.md) | bench89-claude-sonnet-5 | small | claude-sonnet-5 | 1 | completed | 5 | 0.192494 | 94.4 | 0 | 0 | 100 |  |
| [bench_9_reflexion_with_tools_in_parent__small__r1__1788625106](bench_9_reflexion_with_tools_in_parent__small__r1__1788625106.md) | bench89-opus5-sm | small | claude-opus-5 | 1 | completed | 3 | 0.249045 | 94.3 | 0 | 0 | 99 |  |

### Recovered

| file | note |
|---|---|
| [RECOVERED_bench_5_large.md](RECOVERED_bench_5_large.md) | Recovered output for `bench_5_react_agent__large__r1__1788100397` (see explanation above) |

