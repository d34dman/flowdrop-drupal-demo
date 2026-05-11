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


