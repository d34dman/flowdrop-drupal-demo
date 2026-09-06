# FlowDrop Drupal Demo

A demonstration project showcasing FlowDrop workflows in Drupal 11. It is also the current
**runner for [FlowDrop AI Bench](https://github.com/d34dman/flowdrop-ai-bench)**, the
benchmark that compares ten workflow architectures on one redaction task. Results live
there, not here: <https://d34dman.github.io/flowdrop-ai-bench/>.

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
- An Anthropic API key for anything that calls a model. The site reads it from the
  `ANTHROPIC_KEY` environment variable through the Key module:

  ```bash
  echo 'ANTHROPIC_KEY=sk-ant-...' >> .ddev/.env
  ddev restart
  ```

## Run a benchmark cell

```bash
ddev exec sh scratchpad/bench/run_cell.sh B3 claude-sonnet-5 small 1 yourname-first-run
```

Arguments are `<cells> <model> [pages] [reps] [tag]`. The runner fetches the corpus pages
and the prompt from the bench repo's site and records their hashes, sets the prompt and
model in every cell, runs them, and collects metrics into `scratchpad/bench/results/`.
Then look, export, and open a pull request on the bench repo:

```bash
python3 scratchpad/bench/summarize.py B3 small sonnet-5
python3 scratchpad/bench/export.py /path/to/flowdrop-ai-bench yourname-
```

A single cell on one page costs cents; a full B2–B9 sweep on three pages a few dollars.
The cells, the runner's moving parts and its rules: [scratchpad/bench/README.md](scratchpad/bench/README.md).
How to contribute and what is welcome: the bench repo's
[CONTRIBUTING.md](https://github.com/d34dman/flowdrop-ai-bench/blob/main/CONTRIBUTING.md).

## History

`docs/archive/v1-external-corpus/` holds the research and ledgers of the first benchmark
round (August to early September 2026), which ran against live third-party pages. Its
figures are not comparable with the current corpus and the fetched text has been removed.
