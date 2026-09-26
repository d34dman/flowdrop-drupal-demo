# FlowDrop Drupal Demo

A Drupal 11 site with FlowDrop and a set of example workflows, arranged as a learning
path: start with an echo, end with an agent that calls tools over MCP. Each workflow
opens in the visual editor and runs in the Playground, where you chat with it and watch
every node execute.

## Requirements

- [DDEV](https://ddev.readthedocs.io/) 1.24 or newer, with Docker running.
- About 2 GB of free disk space for the images and dependencies.
- Optional: an [Anthropic API key](https://console.anthropic.com/) for the workflows
  that call a model. Everything marked *offline* on the start page works without one.

## Quick start

```bash
git clone https://github.com/d34dman/flowdrop-drupal-demo.git
cd flowdrop-drupal-demo

# Optional, but do it now if you have a key: it saves a restart later.
echo 'ANTHROPIC_KEY=sk-ant-...' >> .ddev/.env

ddev start
ddev install-demo
```

`ddev install-demo` installs the dependencies, installs the site from the configuration in
`config/sync`, and prints a one-time login link. The link lands on the **start page**,
which lists every workflow in learning order with *Try it* (Playground) and
*Open editor* buttons, and tells you whether the API key was found.

Lost the link? `ddev drush uli start` prints a new one.

## Adding the API key later

```bash
echo 'ANTHROPIC_KEY=sk-ant-...' >> .ddev/.env
ddev restart
ddev drush cr
```

The `drush cr` matters. If you tried an AI workflow before the key was there, the empty
model list is cached, and those workflows fail with
*"Parameter 'model' received invalid value. Allowed values:"* until the cache is cleared.

## What's inside

| Section | Workflows | Needs |
|---|---|---|
| Learn the canvas | Levels 0.1 to 1.5: echo, a text processor, a runtime choice, templating, routing | nothing |
| | Levels 2.6, 2.7, 3.9: first AI call, style control, a URL summariser | API key (3.9 also internet) |
| Build an LLM chat | Your first LLM call, then giving it a memory | API key |
| Agents | A hand-built reasoning loop that calls a calculator tool | API key |
| Showcase | dri.es photo finder (MCP, no AI) and Chat with dri.es (MCP agent) | internet; the chat also needs the key |
| Building blocks | The ReAct loops the agents call as sub-workflows (editor only), an HTTP fetcher, the chat processor | varies |

The workflow list is also at `/admin/flowdrop/workflows`.

## Troubleshooting

- **`ddev start` asks for your password.** DDEV adds the hostname to `/etc/hosts` when
  your network's DNS won't resolve `*.ddev.site`, which is common on conference and
  corporate wifi. Accept it, or avoid it with
  `printf 'project_tld: localhost\n' > .ddev/config.local.yaml` and `ddev restart`. The site
  is then at `https://flowdrop-drupal-demo.localhost`.
- **The site shows stale or missing configuration after reinstalling.** Run
  `ddev drush cr`. Redis keeps the previous site's cache across a reinstall.
  `ddev install-demo` already does this for you.
- **The editor is a blank white page for a few seconds.** It is loading; give it a moment.
- **Anything else hangs.** `ddev restart` fixes most of it. `ddev logs` shows why.

## Learn more

- FlowDrop documentation: <https://flowdrop.io/docs/>
- Issues and feature requests: the [FlowDrop issue queue](https://git.drupalcode.org/project/flowdrop/-/work_items)
  on drupal.org's GitLab, not this repository.

## Benchmark

The FlowDrop redaction benchmark that was developed in this repository in August and
September 2026 now has its own home, runner included:
<https://github.com/d34dman/flowdrop-ai-bench> (results: <https://d34dman.github.io/flowdrop-ai-bench/>).
