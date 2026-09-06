# FlowDrop Drupal Demo

A demonstration project showcasing FlowDrop workflows in Drupal 11.

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
- An Anthropic API key for the workflows that call a model. The site reads it from the
  `ANTHROPIC_KEY` environment variable through the Key module:

  ```bash
  echo 'ANTHROPIC_KEY=sk-ant-...' >> .ddev/.env
  ddev restart
  ```

## Benchmark

The FlowDrop redaction benchmark that was developed in this repository in August and
September 2026 now has its own home, runner included:
<https://github.com/d34dman/flowdrop-ai-bench> (results: <https://d34dman.github.io/flowdrop-ai-bench/>).
Nothing of it remains here.
