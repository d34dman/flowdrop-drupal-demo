# FlowDrop Drupal Demo

A demonstration project showcasing FlowDrop workflows in Drupal 11, and the home of the
**redaction benchmark**: ten workflow architectures, from a fixed pipeline to a fully
autonomous agent, doing one identical task on one identical page so their cost, speed and
failure modes can be compared. Results so far live on
<https://d34dman.github.io/flowdrop-drupal-demo/> — we would like yours next to them.

## Quick Start

Install and run the project locally using DDEV:

```bash
ddev start
ddev composer install
ddev drush si --existing-config
```

## Access the Site

Get a one-time login link:

```bash
ddev drush uli
```

Visit the workflow management page:

https://flowdrop-drupal-demo.ddev.site/admin/structure/flowdrop-workflow

## Requirements

- [DDEV](https://ddev.readthedocs.io/)
- An Anthropic API key, for anything that calls a model (see below)
- Python 3 on the host for the scoring and reporting scripts (standard library only)

## Run the benchmark

The task: fetch a public web page, redact every competitor CMS name, keep everything else
faithful, return Markdown. Every variant B0–B9 gets the same three pages. The floor (B0)
and the reference pipeline (B1) need no model; the rest do.

| Cell | What it is |
|---|---|
| B0 / B1 | Deterministic floor and reference pipeline, no model |
| B2 / B3 | Single LLM call on raw HTML / on converted Markdown |
| B4 | Drupal AI Agent with a fetch tool |
| B5 / B5a / B7 | ReAct agent: standard, naive prompt, URL-shaped tool |
| B6 | Fully autonomous agent |
| B8 / B9 | ReAct and Reflexion engines with the tools held by the parent workflow |

### 1. Put your API key where Drupal reads it

The site reads the key from the `ANTHROPIC_KEY` environment variable through the Key
module. With DDEV, one line in a gitignored file is enough:

```bash
echo 'ANTHROPIC_KEY=sk-ant-...' >> .ddev/.env
ddev restart
```

### 2. Run a cell

```bash
ddev exec sh scratchpad/bench/run_cell.sh B5 claude-sonnet-5 small 1 my-first-run
```

Arguments are `<cells> <model> [pages] [reps] [tag]`. Cells and pages take comma lists
(`B8,B9`, `small,medium,large`). The tag is the only hand-written field in the ledger and
is how your runs are found later, so make it say who and why. Agent cells on `large` can
run for ten minutes, so background it and log it:

```bash
ddev exec sh scratchpad/bench/run_cell.sh B2,B3,B5 claude-haiku-4-5-20251001 small,medium,large 1 jane-haiku-sweep \
  > scratchpad/bench/jane-haiku-sweep.log 2>&1 &
```

A single cell on one page costs cents. A full B2–B9 sweep on three pages costs a few
dollars. Runs land in `scratchpad/bench/results/` and never touch the published dataset.

### 3. Look at what you got

```bash
python3 scratchpad/bench/summarize.py B5 small sonnet-5
```

prints seconds, calls, tokens, cost and output size next to every earlier run of the same
cell. The output document is `scratchpad/bench/results/outputs/<run_id>.md`; open it. A run
whose status is not `completed`, or whose output is tiny, is a failure and not a data point.

Details of the runner, the ledger files and how model selection works:
[scratchpad/bench/README.md](scratchpad/bench/README.md).

## Contribute your runs

New models, new prompts, a variant we have not thought of, or simply more repetitions of
an existing cell: all of it is welcome as a pull request. The path from a finished run to
a PR is three commands.

```bash
# 1. Fold your tagged runs into the frozen dataset and copy their outputs
python3 scratchpad/bench/fold_runs.py jane-

# 2. Re-grade every run against the gold documents (deterministic, no LLM, ~2 s)
python3 docs/drupalcon/data/score_v2.py

# 3. Rebuild the published pages (optional; we can do it on merge)
python3 docs/drupalcon/data/build_site_v2.py
python3 docs/drupalcon/data/build_matrix_v2.py
python3 docs/drupalcon/data/build_plots_v2.py
```

Commit the result and open a PR against `main`. A good PR contains:

- the new rows in `docs/drupalcon/data/runs.csv` and `runs_v2.csv`, plus their
  `docs/drupalcon/data/outputs/<run_id>.md` documents;
- your `scratchpad/bench/results/*.jsonl` additions and the run log, so a failure can be
  read later;
- if you changed a workflow, prompt or model wiring, the exported config in
  `config/sync/` and a line in the PR saying what changed and why.

Please do not hand-edit `runs.csv`; the fold script computes its columns the same way
the existing rows were computed, and the scorer regrades everything from the outputs.
If you want to add a variant, copy one of the `bench_*` workflows in `config/sync/`,
give it the next number, add it to the cell table in `run_cell.sh`, and say so in the PR.

The reporting layer (rubric, gold documents, page generators) is likely to be refactored.
Runs are the durable part: a completed run with its output document and metering can
always be re-graded, so contribute the runs and do not worry about the pages.

## Read the research

- `docs/drupalcon/README.md`: what was measured, the ten variants and the findings so far
- `docs/drupalcon/v2/`: the current report, graded against gold documents
- `docs/drupalcon/data/RUNS_V2.md`: what every column in the dataset means
- Published pages: <https://d34dman.github.io/flowdrop-drupal-demo/>
