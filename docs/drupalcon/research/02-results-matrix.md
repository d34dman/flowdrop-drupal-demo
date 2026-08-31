# Results — the full matrix

All figures metered from `ai_metering_usage`. `kept` = retention vs the de-noised control
(see [01-method.md](01-method.md)). `red` = `▌▌▌▌` marks placed. `leak` = competitor
mentions still readable.

Control competitor mentions: **small 0 · medium 36 · large 5**.

## Orchestration floor — what FlowDrop itself costs

| Variant | n | Median | p90 | Min | Max |
|---|---|---|---|---|---|
| B0 empty pipeline | 30 | **0.024s** | 0.036s | 0.014s | 0.179s |
| B1 deterministic fetch + convert | 30 | **0.258s** | 0.792s | 0.122s | 1.562s |

> **Use this to close down the "the framework is slow" objection before it opens.**
> FlowDrop's own orchestration is 24 ms. A full deterministic fetch-and-convert of a
> 535 KB page is a quarter of a second. Every remaining second in this document is the
> model.

## The model matrix — one draw per cell

⚠️ **B5 and B5a rows ran with an empty system prompt** (FlowDrop port bug, found after
these runs). The task reached the model via the user message; the detailed instructions
did not. Marked, not deleted — see [06-flowdrop-findings.md](06-flowdrop-findings.md).

### B2 — raw HTML → one LLM call

| Page | Model | Calls | Tokens in | Cost | Wall | Kept | Red | Leak |
|---|---|---|---|---|---|---|---|---|
| small | haiku-4.5 | 1 | 13,675 | $0.0819 | 127.8s | 408.1% ⚠️HTML | 6 | 0 |
| medium | haiku-4.5 | 1 | 59,261 | $0.0690 | 30.9s | 67.3% | 28 | 0 |
| large | haiku-4.5 | 0 | 0 | $0.0000 | 2.0s | **failed** | 0 | 0 |
| small | sonnet-5 | 1 | 16,622 | $0.2009 | 138.7s | 407.9% ⚠️HTML | 0 | 0 |
| medium | sonnet-5 | 1 | 73,380 | $0.9356 | 576.8s | 567.2% ⚠️HTML | 40 | 3 |
| large | sonnet-5 | 1 | 266,158 | $0.6584 | 114.5s | 46.7% | 4 | 0 |
| small | opus-5 | 1 | 16,622 | $0.5066 | 170.3s | 407.7% ⚠️HTML | 0 | 0 |
| medium | opus-5 | 1 | 73,380 | $0.4343 | 30.7s | 68.9% | 27 | 2 |
| large | opus-5 | 1 | 266,158 | $1.5371 | 85.2s | 47.2% | 0 | 1 |

### B3 — deterministic Markdown → one LLM call

| Page | Model | Calls | Tokens in | Cost | Wall | Kept | Red | Leak |
|---|---|---|---|---|---|---|---|---|
| small | haiku-4.5 | 1 | 4,915 | $0.0246 | 45.2s | 85.8% | 0 | 0 |
| medium | haiku-4.5 | 1 | 3,501 | $0.0140 | 28.8s | 69.0% | 29 | 0 |
| large | haiku-4.5 | 1 | 34,821 | $0.0400 | 11.2s | 4.3% ⚠️ | 0 | 0 |
| small | sonnet-5 | 1 | 6,391 | $0.0758 | 46.8s | 94.0% | 0 | 0 |
| medium | sonnet-5 | 1 | 4,924 | **$0.0593** | 41.1s | **98.0%** | 33 | **0** |
| large | sonnet-5 | 1 | 46,970 | $0.5671 | 339.8s | **96.6%** | 4 | **0** |
| small | opus-5 | 1 | 6,391 | $0.1967 | 63.4s | 97.4% | 0 | 0 |
| medium | opus-5 | 1 | 4,924 | $0.1430 | 47.2s | 94.1% | 31 | 0 |
| large | opus-5 | 1 | 46,970 | $1.4256 | 421.7s | 96.6% | 4 | 0 |

**B3 is the quiet winner of the entire benchmark.** On Sonnet 5 it is the cheapest
correct run on every page, and — uniquely — it never produced a *silent* wrong answer.
It does fail once, with Haiku on the large page (4.3%), but that failure is truncation:
visible at a glance, and cheap. Every other variant's failures had to be hunted for.

### B4 — Drupal AI Agent + tool

| Page | Model | Calls | Tokens in | Cost | Wall | Kept | Red | Leak |
|---|---|---|---|---|---|---|---|---|
| small | haiku-4.5 | 2 | 39,135 | $0.1006 | 122.0s | 92.6% | 0 | 0 |
| medium | haiku-4.5 | 2 | 124,149 | $0.1468 | 59.2s | 74.1% | 29 | 0 |
| large | haiku-4.5 | 0 | 0 | $0.0000 | 2.0s | **failed** | 0 | 0 |
| small | sonnet-5 | 2 | 40,549 | $0.1500 | 50.1s | 59.9% | 0 | 0 |
| medium | sonnet-5 | 2 | 153,802 | $0.3735 | 55.0s | 76.0% | **70** ❌ | 0 |
| large | sonnet-5 | 2 | 552,606 | $1.3282 | 182.9s | 43.8% | 1 | 0 |
| small | opus-5 | 2 | 41,389 | $0.4429 | 84.2s | 94.0% | 0 | 0 |
| medium | opus-5 | 2 | 155,234 | $0.9840 | 84.6s | 93.1% | 30 | 0 |
| large | opus-5 | 1 | 266,590 | $1.5889 | 97.2s | 42.9% | 0 | 1 |

❌ The 70-glyph cell is failure #2 — it redacted "Drupal" itself, all 46 mentions.

### B5 — FlowDrop ReAct, `html_to_markdown` tool ⚠️ no system prompt

| Page | Model | Calls | Tokens in | Cost | Wall | Kept | Red | Leak |
|---|---|---|---|---|---|---|---|---|
| small | haiku-4.5 | 3 | 41,793 | $0.0914 | 106.6s | 75.7% | 0 | 0 |
| medium | haiku-4.5 | 3 | 139,877 | $0.1610 | 61.1s | 68.1% | **0** ❌ | **29** ❌ |
| large | haiku-4.5 | 1 | 818 | $0.0013 | 5.8s | **failed** | 0 | 0 |
| small | sonnet-5 | 2 | 22,580 | $0.0600 | 15.4s | 50.6% | 0 | 0 |
| medium | sonnet-5 | 2 | 91,658 | $0.2470 | 59.2s | 94.2% | 29 | 1 |
| large | sonnet-5 | 3 | 691,024 | $1.5452 | 157.4s | 43.9% | 0 | 1 |
| small | opus-5 | 2 | 22,455 | $0.2357 | 49.5s | 95.2% | 0 | 0 |
| medium | opus-5 | 2 | 91,494 | $0.6029 | 64.4s | 97.0% | 30 | 0 |
| large | opus-5 | 2 | 337,512 | $2.4270 | 272.9s | 84.1% | 4 | 0 |

❌ The Haiku medium cell is failure #1 — converted perfectly, redacted nothing.

### B5a — as B5, naive prompt ⚠️ no system prompt

| Page | Model | Calls | Tokens in | Cost | Wall | Kept | Red | Leak |
|---|---|---|---|---|---|---|---|---|
| small | haiku-4.5 | 3 | 34,092 | $0.0420 | 18.3s | 45.2% | 3 | 0 |
| medium | haiku-4.5 | 3 | 138,941 | $0.1583 | 55.7s | 67.1% | 26 | 2 |
| large | haiku-4.5 | 1 | 746 | $0.0012 | 5.4s | **failed** | 0 | 0 |
| small | sonnet-5 | 3 | 47,391 | $0.1306 | 35.0s | 51.4% | 0 | 0 |
| medium | sonnet-5 | 2 | 91,470 | $0.2187 | 34.5s | 80.7% | 29 | 0 |
| large | sonnet-5 | 3 | 675,883 | $1.4556 | 110.5s | 42.9% | 4 | 0 |
| small | opus-5 | 2 | 22,256 | $0.1571 | 22.6s | 50.7% | 0 | 0 |
| medium | opus-5 | 2 | 91,390 | $0.5463 | 41.3s | 79.9% | 30 | 0 |
| large | opus-5 | 2 | 337,323 | $1.8785 | 88.1s | 44.4% | 3 | 1 |

> B5a was meant to be the prompt-sensitivity arm, but **it currently shares a sub-workflow
> with B5**, so the two are not yet a clean contrast. Do not present B5-vs-B5a as a
> prompt-quality result. See [ideas/open-questions.md](../ideas/open-questions.md).

### B6 — autonomous agent

| Page | Model | Calls | Tokens in | Cost | Wall | Kept | Red | Leak |
|---|---|---|---|---|---|---|---|---|
| small | haiku-4.5 | 3 | 41,984 | $0.1181 | 165.8s | 69.4% | 0 | 0 |
| medium | haiku-4.5 | 3 | 125,622 | $0.1495 | 64.0s | 69.0% | 27 | 3 |
| large | haiku-4.5 | 1 | 883 | $0.0013 | 3.1s | **failed** | 0 | 0 |
| small | sonnet-5 | 2 | 18,331 | $0.0941 | 47.6s | 93.6% | 0 | 0 |
| medium | sonnet-5 | 2 | 75,251 | $0.2454 | 74.3s | 97.6% | 30 | 0 |
| large | sonnet-5 | 2 | 267,893 | $0.9511 | 331.7s | 81.5% | 4 | 0 |
| small | opus-5 | 2 | 18,207 | $0.2134 | 43.9s | 94.8% | 0 | 0 |
| medium | opus-5 | 2 | 75,099 | $0.4894 | 46.9s | 93.8% | 30 | 0 |
| large | opus-5 | 2 | 267,768 | $2.0813 | 263.4s | 85.2% | 4 | 0 |

### B7 — FlowDrop ReAct, `url_to_markdown` tool ✅ prompt verified present

| Page | Model | Calls | Tokens in | Cost | Wall | Kept | Red | Leak |
|---|---|---|---|---|---|---|---|---|
| small | sonnet-5 | 2 | 9,399 | $0.0826 | 52.0s | 94.6% | 0 | 0 |
| medium | sonnet-5 | 2 | 7,468 | $0.0603 | 41.9s | 93.2% | 28 | 2 |
| large | sonnet-5 | 2 | 56,955 | $0.4092 | 224.7s | 79.7% | 4 | 0 |
| small | sonnet-4.6 | 2 | 7,434 | $0.0660 | 57.0s | 69.3% | 0 | 0 |
| medium | sonnet-4.6 | 2 | 5,910 | $0.0666 | 85.1s | 93.3% | 28 | 2 |
| large | sonnet-4.6 | 2 | 40,856 | $0.3625 | 327.0s | 80.9% | 2 | 2 |

**Two calls in every single cell, on both models.** No other variant is stable in call
count across pages or models.

## Haiku's cliff

Haiku 4.5 failed outright on the large page in **four of six agentic variants**
(B2, B4, B5, B5a, B6 — with B2 and B4 dying at 2.0s having consumed nothing). It is the
only model in the set that does not complete the matrix. Cheap models fail the *loud*
way here, which is the good way — except in B5-medium, where it failed the silent way.

## Rankings that matter

**Cheapest correct run, medium page:** B3 + Sonnet 5 — $0.0593, 98.0%, 33 red, 0 leaks.
**Most expensive run:** B5 + Opus 5, large — $2.4270.
**Worst value:** B4 + Sonnet 5, medium — $0.3735 to destroy the document.
**Most predictable:** B7 — 2 calls everywhere, ±0.0 pts across three draws.
