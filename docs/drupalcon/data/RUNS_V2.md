# `runs_v2.csv` — rubric v2 scores

Produced by `score_v2.py` from `runs.csv` (203 runs after the 2026-09-05 fold of the Opus 5 B8/B9 cells and two B5 Sonnet 5 reruns by `scratchpad/bench/fold_runs.py`), the verbatim outputs in `outputs/`, and the gold
bodies in `gold/`. `runs.csv` is untouched; this file re-grades the same runs. Method:
[../ideas/report-v2-rubric.md](../ideas/report-v2-rubric.md). Regenerate with

```sh
python3 docs/drupalcon/data/score_v2.py                # writes runs_v2.csv, prints summary
python3 docs/drupalcon/data/score_v2.py --explain <run_id-prefix>   # why one run scored as it did
python3 docs/drupalcon/data/tables_v2.py                # the Markdown tables below, from runs_v2.csv
```

Scoring is deterministic and takes about two seconds. No LLM is involved.

## Columns

| Column | Meaning |
|---|---|
| `run_id` … `output_chars` | identity and metering, copied from `runs.csv` |
| `v1_retention` `v1_redactions` `v1_leaks` `v1_drupal` | the v1 metrics, kept for comparison |
| `g0_delivered` | status `completed` and ≥ 500 chars of output |
| `g1_format` | HTML tag density ≤ 5/1 000 and at least one Markdown heading |
| `g2_scope` | ≥ 50 % of the gold headings present (`structure` ≥ 0.5) |
| `recall` | correct glyphs ÷ (correct glyphs + leaks). Leaks are competitor names still readable in gold or invented text; names left in retained chrome are `leaks_chrome` and do not count |
| `precision` | correct glyphs ÷ glyphs placed on gold or invented text. Glyphs on retained chrome (`glyph_chrome`, e.g. Wikipedia citations) are excluded |
| `subject` | protected names kept ÷ (kept + glyphs placed on protected names) |
| `fidelity` | gold sentences (≥ 4 words) found in the output at ≥ 0.90 similarity, redaction-tolerant ÷ gold sentences |
| `fabrication` | output sentences found neither in the gold nor anywhere in the full page ÷ output sentences |
| `structure` | gold headings found ÷ gold headings |
| `glyphs` `glyph_correct` `glyph_protected` `glyph_other` `glyph_chrome` `glyph_ambiguous` | where every redaction mark landed. `glyph_ambiguous` is Backdrop CMS: neutral, excluded from precision. `glyphs` counts **runs** of `▌`, so a nine-bar mark is one redaction (v1 counted `▌▌▌▌` substrings and double-counted B8/B9) |
| `out_in_gold` `out_chrome` `out_fabricated` | how the output's sentences were classified |
| `v2_class` | `correct` · `degraded` · `silent` · `format` · `loud` — thresholds in `score_v2.py` (`THRESH`, `DEGRADED`) |

`silent` means every gate passed and at least one axis is below 0.75: the run completed
and the document is wrong. `format` is G1 failing (HTML came back). A G2 failure (right
format, not the document — B3 Haiku's 7 % truncation) is classed `silent`, because it
completed.

## Outcome class per cell

One letter per run: **C**orrect · **D**egraded · **S**ilent · **F**ormat · **L**oud.
Shadowed-prompt B7 draws, the dropped B5a arm and 5 `paused` runs (stalled before any
answer existed, see below) are excluded.

| Variant | Page | haiku 4.5 | sonnet 4.6 | sonnet 5 | opus 5 |
|---|---|---|---|---|---|
| B2 | small | F | F | F | F |
| B2 | medium | C | C | F | D |
| B2 | large | L | S | C | C |
| B3 | small | CC | C | C | C |
| B3 | medium | C | C | CCC | C |
| B3 | large | S | C | CC | C |
| B4 | small | C | S | C | C |
| B4 | medium | C | C | S | C |
| B4 | large | L | S | C | C |
| B5 | small | C | C | CCCCCLCCCCC | C |
| B5 | medium | S | D | CCC | C |
| B5 | large | L | LC | CCCC | C |
| B6 | small | CC | C | C | C |
| B6 | medium | D | C | C | C |
| B6 | large | L | C | C | C |
| B7 | small | · | C | CCC | · |
| B7 | medium | · | D | D | · |
| B7 | large | · | C | C | · |
| B8 | small | C | C | C | C |
| B8 | medium | S | D | D | D |
| B8 | large | C | C | C | C |
| B9 | small | LC | LC | C | C |
| B9 | medium | LC | D | C | C |
| B9 | large | C | C | C | C |

## Per variant

| Variant | Runs | Correct | Degraded | Silent | Format | Loud |
|---|---|---|---|---|---|---|
| B2 | 12 | 4 | 1 | 1 | 5 | 1 |
| B3 | 16 | 15 | 0 | 1 | 0 | 0 |
| B4 | 12 | 8 | 0 | 3 | 0 | 1 |
| B5 | 28 | 23 | 1 | 1 | 0 | 3 |
| B6 | 13 | 11 | 1 | 0 | 0 | 1 |
| B7 | 8 | 6 | 2 | 0 | 0 | 0 |
| B8 | 12 | 8 | 3 | 1 | 0 | 0 |
| B9 | 15 | 11 | 1 | 0 | 0 | 3 |
| **all** | 116 | **86** | **9** | **7** | **5** | **9** |

## Per model

| Model | Runs | Correct | Degraded | Silent | Format | Loud |
|---|---|---|---|---|---|---|
| haiku 4.5 | 25 | 14 | 1 | 3 | 1 | 6 |
| sonnet 4.6 | 26 | 16 | 4 | 3 | 1 | 2 |
| sonnet 5 | 44 | 38 | 2 | 1 | 2 | 1 |
| opus 5 | 21 | 18 | 2 | 0 | 1 | 0 |

## Every silent failure

| Page | Variant | Model | What went wrong |
|---|---|---|---|
| small | B4 | sonnet 4.6 | recall 0.0: 6 leak(s), 0 correct glyph(s) |
| medium | B4 | sonnet 5 | precision 0.414: 4 glyph(s) on invented text, 37 on protected names; subject 0.26: 37 glyphs on protected names |
| medium | B5 | haiku 4.5 | recall 0.0: 29 leak(s), 0 correct glyph(s) |
| medium | B8 | haiku 4.5 | recall 0.667: 10 leak(s), 20 correct glyph(s) |
| large | B2 | sonnet 4.6 | recall 0.0: 1 leak(s), 0 correct glyph(s); precision 0.0: 4 glyph(s) on invented text, 0 on protected names |
| large | B3 | haiku 4.5 | fidelity 0.076: truncated; structure 0.0: not the same document |
| large | B4 | sonnet 4.6 | fidelity 0.541: truncated; recall 0.0: 13 leak(s), 0 correct glyph(s) |

## Every degraded run

| Page | Variant | Model | Lowest axis |
|---|---|---|---|
| medium | B2 | opus 5 | recall 0.931 (2 of 29 target mentions left readable) |
| medium | B5 | sonnet 4.6 | recall 0.933 (2 of 30 target mentions left readable) |
| medium | B6 | haiku 4.5 | recall 0.9 (3 of 30 target mentions left readable) |
| medium | B7 | sonnet 4.6 | recall 0.933 (2 of 30 target mentions left readable) |
| medium | B7 | sonnet 5 | recall 0.933 (2 of 30 target mentions left readable) |
| medium | B8 | opus 5 | recall 0.903 (3 of 31 target mentions left readable) |
| medium | B8 | sonnet 4.6 | recall 0.903 (3 of 31 target mentions left readable) |
| medium | B8 | sonnet 5 | recall 0.933 (2 of 30 target mentions left readable) |
| medium | B9 | sonnet 4.6 | recall 0.903 (3 of 31 target mentions left readable) |

## Paused runs (excluded)

| Page | Variant | Model | Tag | Cost |
|---|---|---|---|---|
| small | B7 | sonnet 5 | `b7-smoke` | $0.0023 |
| small | B9 | haiku 4.5 | `b9fix-claude-haiku-4-5-20251001` | $0.2427 |
| medium | B8 | opus 5 | `bench89-opus5-sm` | $0.0079 |
| medium | B8 | opus 5 | `bench89-opus5-medium-retry` | $0.0079 |
| medium | B8 | opus 5 | `bench89-opus5-medium-retry2` | $0.0079 |

## Cost per correct run

Cell spend ÷ correct draws, per variant × model, all pages pooled. `∞` = money spent, nothing correct.
Spend includes the failed and paused draws of the cell.

| Variant | haiku 4.5 | sonnet 4.6 | sonnet 5 | opus 5 |
|---|---|---|---|---|
| B2 | $0.15 (1/3) | $1.20 (1/3) | $1.79 (1/3) | $2.48 (1/3) |
| B3 | $0.03 (3/4) | $0.19 (3/3) | $0.23 (6/6) | $0.59 (3/3) |
| B4 | $0.12 (2/3) | $2.18 (1/3) | $0.93 (2/3) | $1.01 (3/3) |
| B5 | $0.25 (1/3) | $2.26 (2/4) | $0.61 (17/18) | $1.09 (3/3) |
| B6 | $0.19 (2/4) | $0.80 (3/3) | $0.43 (3/3) | $0.93 (3/3) |
| B7 | · | $0.25 (2/3) | $0.18 (4/5) | · |
| B8 | $0.10 (2/3) | $0.25 (2/3) | $0.24 (2/3) | $1.05 (2/6) |
| B9 | $0.45 (3/6) | $0.86 (2/4) | $0.58 (3/3) | $1.01 (3/3) |

## Cheapest correct run per page

| Page | Variant | Model | Cost | Calls | Wall |
|---|---|---|---|---|---|
| small | B3 | haiku 4.5 | $0.0246 | 1 | 47s |
| small | B3 | haiku 4.5 | $0.0246 | 1 | 45s |
| small | B8 | haiku 4.5 | $0.0312 | 2 | 57s |
| medium | B3 | haiku 4.5 | $0.0140 | 1 | 29s |
| medium | B3 | sonnet 4.6 | $0.0418 | 1 | — |
| medium | B3 | sonnet 5 | $0.0542 | 1 | 37s |
| large | B8 | haiku 4.5 | $0.1524 | 2 | 236s |
| large | B8 | sonnet 5 | $0.3351 | 2 | 166s |
| large | B7 | sonnet 4.6 | $0.3625 | 2 | 327s |

## Medium page, correct runs by cost

The only page with both redaction targets and a protected subject.

| Variant | Model | Cost | Recall | Precision | Subject | Fidelity | Fabrication |
|---|---|---|---|---|---|---|---|
| B3 | haiku 4.5 | $0.0140 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B3 | sonnet 4.6 | $0.0418 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B3 | sonnet 5 | $0.0542 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B3 | sonnet 5 | $0.0593 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B3 | sonnet 5 | $0.0615 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B2 | haiku 4.5 | $0.0690 | 1.0 | 1.0 | 1.0 | 0.985 | 0.0 |
| B3 | opus 5 | $0.1430 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B4 | haiku 4.5 | $0.1468 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B2 | sonnet 4.6 | $0.2084 | 0.966 | 1.0 | 1.0 | 1.0 | 0.0 |
| B6 | sonnet 5 | $0.2454 | 1.0 | 1.0 | 1.0 | 1.0 | 0.022 |
| B5 | sonnet 5 | $0.2470 | 0.967 | 1.0 | 1.0 | 1.0 | 0.011 |
| B5 | sonnet 5 | $0.2555 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B9 | haiku 4.5 | $0.2620 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B9 | sonnet 5 | $0.2676 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B5 | sonnet 5 | $0.3077 | 0.967 | 1.0 | 1.0 | 1.0 | 0.0 |
| B4 | sonnet 4.6 | $0.4504 | 1.0 | 1.0 | 1.0 | 1.0 | 0.013 |
| B6 | sonnet 4.6 | $0.4602 | 0.966 | 1.0 | 1.0 | 1.0 | 0.0 |
| B6 | opus 5 | $0.4894 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |
| B5 | opus 5 | $0.6029 | 1.0 | 1.0 | 1.0 | 1.0 | 0.011 |
| B4 | opus 5 | $0.9840 | 1.0 | 1.0 | 1.0 | 1.0 | 0.011 |
| B9 | opus 5 | $1.3320 | 1.0 | 1.0 | 1.0 | 1.0 | 0.0 |

## Spend by class

| Class | Runs | Spend |
|---|---|---|
| correct | 86 | $44.91 |
| degraded | 9 | $2.06 |
| silent | 7 | $3.07 |
| format | 5 | $1.97 |
| loud | 9 | $2.11 |
| paused (excluded) | 5 | $0.27 |

## What changed against v1

- **B5's fidelity variance on the small page was chrome, not content.** The three draws v1
  reported at 51 / 71 / 95 % retention all score fidelity 1.0. They differ in how many
  Drupal.org menu sentences they reproduced (0, 11, 25). Failure #4 in the v1 gallery is a
  metric artefact. B7's stability in call count still stands; its stability in retention
  was measuring the same chrome.
- **v1's retention penalised the runs that did the sensible thing.** B2/B3/B4 on Haiku and
  Sonnet 4.6 sat at 67–69 % on the medium page because they dropped IBM's header and
  promos. They are `correct` here.
- **B8/B9's "56 and 70 glyphs on 36 mentions" was a counting artefact.** Those models
  emit nine-bar marks; v1 counted two `▌▌▌▌` per mark. Actual redactions: 28 and 35.
- **Failure #2 is the one v1 case that gets worse, correctly.** B4 · Sonnet 5 · medium:
  37 of its 70 glyphs sit on protected names, almost all "Drupal"; subject preservation 0.26. Last place, as it should be.
- **Failure #1 stands.** B5 · Haiku · medium: 0 glyphs, 29 leaks, recall 0.
- **Opus 5 on B8/B9 (folded 2026-09-05) is correct on five of six cells.** The sixth, B8 medium, is
  `degraded`: 28 marks, 3 target mentions left readable, all three in the title lines
  `# Drupal versus WordPress`. Sonnet 4.6 and Sonnet 5 leave the same title readable on B8, so the
  variant's whole medium row is `degraded` for one reason. It also took four launches to get that
  draw: three paused on the `http_request` confirmation gate before the gate was waived. Paused
  rows are in `runs.csv` and excluded from the class counts (no answer exists to grade).
- **The large page does not test redaction.** Its body mentions no competitor. v1 counted
  five: two were one "Backdrop CMS" link, three sit in the References. Backdrop, a 2013
  Drupal fork, was a v1 target and is **neutral** in v2 (`AMBIGUOUS` in `score_v2.py`): the
  models split evenly on whether a fork is a competitor (Sonnet 5 and Opus redact it,
  Sonnet 4.6 and Haiku do not), which is a definition dispute, not a failure. A glyph on it
  is neither correct nor wrong and the name left readable is not a leak. The large page
  therefore grades fidelity, format and cost only, as the small page already did.
- **A new failure mode surfaced, twice:** the agent's reasoning ends up inside the
  document. B4 · Sonnet 4.6 · small wrote "Drupal CMS competitors include platforms like
  WordPress, Joomla, Wix…" into a page that had none (six leaks). B2 · Sonnet 4.6 · large
  appended a note about the article's references that names WordPress and Joomla, and put
  four glyphs in it. Neither is caught by any v1 metric.

## Spot-check (2026-09-05)

Twelve `correct` runs across B2–B9 and all three pages were read through `--explain`. No
invented prose was found in any of them. Every "fabricated" sentence on the large page is
the Wikipedia infobox or release table, which the gold excludes and the control flattens
beyond n-gram recognition; on the medium page it is one reformatted IBM promo line. That
puts a floor of about 1 % under `fabrication` for runs that keep chrome, well inside the
0.05 threshold. The check also found duplicated link text in the gold (a converter
artefact, now collapsed in `build_gold.py`).

## Caveats

- Sentence matching is fuzzy; a heavily paraphrased sentence counts as missing (fidelity)
  or invented (fabrication). Spot-check with `--explain` before quoting a single cell.
- Thresholds are the proposal from the rubric doc, unchanged. `degraded` on the medium
  page is almost always recall 0.90–0.93: two or three of 30 WordPress mentions left readable.
- n is still 1 for most cells. The class matrix shows draws, not rates.
