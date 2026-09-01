# Benchmark & analysis — artifact index

Everything produced for the DrupalCon Rotterdam 2026 talk research
(2026-08-30 → 09-01). Interactive index of the same list:
<https://claude.ai/code/artifact/47c9cb5c-16cf-43a4-96e9-cb05836a2dd0>

## Interactive artifacts (published)

| Artifact | What it shows | Updated |
|---|---|---|
| [Why Your AI Agent Hallucinates](https://claude.ai/code/artifact/4d0184e1-547a-4dad-9060-bd81cedc913c) | Talk-companion page — failure modes and lessons, matching the session abstract | 2026-09-01 |
| [Caching the Agent Route](https://claude.ai/code/artifact/b2fa38fe-d50f-4d1a-ad2f-c90562ec2565) | Token economy; why prompt caching is unreachable for the agentic variants | 2026-09-01 |
| [Redaction Tradeoff Explorer](https://claude.ai/code/artifact/1fdd6f40-a31b-43f8-83a1-39a3d0a4c7e4) | Threshold explorer — parallel coordinates, drag to set acceptable cost/time/quality | 2026-08-31 |
| [Redaction Benchmark Plots](https://claude.ai/code/artifact/33ba2dce-38f5-4f19-b3a1-eff81053bc93) | Benchmark overview — all variants, global filters | 2026-08-31 |
| [URL-Shaped Tool Deep Dive](https://claude.ai/code/artifact/9360ca71-6e39-4526-b1c8-a9ada5f39017) | B5 vs B7 — what changes when a tool takes a URL instead of the content | 2026-08-31 |
| [How the Redaction Benchmark Works](https://claude.ai/code/artifact/418dbee8-3e92-4635-b928-454260443158) | Method explainer — the task, the variants, metric caveats | 2026-08-30 |
| [Seven Ways to Redact a Page](https://claude.ai/code/artifact/ba617bdd-e7ce-4fff-af54-2b4a481f2627) | The workflow architectures compared side by side | 2026-08-30 |
| [Redactor Model Matrix](https://claude.ai/code/artifact/f0cec580-bfd2-443f-85be-c8627940d9b8) | Variant × model results grid | 2026-08-30 |
| [ReAct Redactor Deep Dive](https://claude.ai/code/artifact/e2a1cb1f-59b2-4cf8-a383-8394b394facd) | B5 (the ReAct agent variant) across four models | 2026-08-30 |

## In this repo

### Research (`docs/drupalcon/research/`, commit 81c349b)

- [01-method.md](research/01-method.md) — benchmark design, the eight variants, metric caveats
- [02-results-matrix.md](research/02-results-matrix.md) — the full numbers, every variant × model × page
- [03-failure-gallery.md](research/03-failure-gallery.md) — four named failure modes with exact evidence
- [04-tool-shape.md](research/04-tool-shape.md) — B5 vs B7: URL-shaped vs content-shaped tools
- [05-cost-and-caching.md](research/05-cost-and-caching.md) — token economy, caching, what this cost
- [06-flowdrop-findings.md](research/06-flowdrop-findings.md) — FlowDrop bugs found; three issues filed upstream

### Talk preparation (`docs/drupalcon/ideas/`)

- [talk-outline.md](ideas/talk-outline.md) — 45-minute slide-by-slide plan
- [objective-mapping.md](ideas/objective-mapping.md) — learning objectives ↔ evidence, gaps included
- [open-questions.md](ideas/open-questions.md) — experiments still to run, with cost estimates

### Data (`docs/drupalcon/data/`)

- [runs.csv](data/runs.csv) — frozen snapshot of all 170 benchmark runs (the durable copy)
- [controls.csv](data/controls.csv) — control-run baselines

### Harness (`scratchpad/bench/`, commit a86dfa8)

- Harness: `harness.php`, `launch.php`, `collect.php`, `run_one.php`, `build*.php`
- Analysis: `analyse.py`, `overhead.py`, `report_final.py`
- Raw logs: `matrix.log`, `matrix2.log`, `b5*.log`, `b6.log`, `large.log`, `rep1.log`
- Supporting config: benchmark workflows/agents/node types in commits 6fa6184 and 2f4f216; `fd_bench` module in a86dfa8

## Status

Research complete and reproducible; slides not yet built. Biggest gap:
prompt injection (learning objective 3) has no experimental data —
see [ideas/open-questions.md](ideas/open-questions.md).
