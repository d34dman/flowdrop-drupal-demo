# Data snapshot

Frozen 2026-08-31 from `scratchpad/bench/results/metrics.jsonl` plus the output documents,
so this folder stays reproducible if the scratchpad is lost. **The scratchpad is not
committed; this is the durable copy.**

## `runs.csv` — 170 runs, one row each

| Column | Notes |
|---|---|
| `run_id` `tag` `variant` `page` `model` `rep` | identity |
| `status` `calls` | `pipeline_status`; LLM round-trips |
| `input_tokens` `output_tokens` `cached_tokens` `cost_usd` | metered from `ai_metering_usage` via the run's `aim_context` tag. `cached_tokens` is **0 on every row** — see research/05 |
| `wall_seconds` `output_chars` | measured |
| `denoised_bytes` `retention_pct` | vs the control in `controls.csv`; identical de-noising both sides |
| `redactions` | count of `▌▌▌▌` |
| `leaks` | competitor names still readable (word-boundary, case-insensitive) |
| `drupal_mentions` | **the over-redaction detector.** Control has 46 on the medium page; a low number here means the run redacted its own subject |
| `html_tag_density` | tags per 1,000 chars. **> 5 means the run returned HTML, not Markdown** — any `retention_pct` above ~110 is this, not quality |
| `prompt_shadowed` | 1 = ran with an erased system prompt (flowdrop#3592438). **Exclude these from quality comparisons** |

## `controls.csv`

The deterministic B1 reference document per page — the denominator for `retention_pct`,
and the source of the competitor and Drupal mention counts.

## Reproducing

Regeneration script is inline in the session history; the inputs are
`scratchpad/bench/results/metrics.jsonl` and `scratchpad/bench/results/outputs/*.md`.
Metric definitions live in `scratchpad/bench/report_final.py`.

## Quick queries

```bash
# the four failure-gallery runs
awk -F, '$19==0 && $17>50' data/runs.csv          # redacted its own subject
awk -F, '$20>5' data/runs.csv                     # returned HTML instead of Markdown
awk -F, '$21==1' data/runs.csv | wc -l            # runs with a shadowed prompt

# total metered spend
awk -F, 'NR>1{s+=$12} END{printf "$%.2f\n", s}' data/runs.csv
```
