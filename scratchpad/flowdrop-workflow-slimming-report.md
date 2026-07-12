# FlowDrop Workflow Config Slimming — Update Report

**Date:** 2026-07-12
**Repo:** `fd-drupal-demo`
**Trigger:** `chore: update dependencies` (8a88128) — FlowDrop `1.4.1` → `2.0.0-alpha6`

## Summary

A recent FlowDrop update introduced a database `post_update` hook that migrates
stored workflows to a **slim node-identity format**. On the next `drush config:export`,
every exported workflow shed the large embedded node-type `metadata` blob and the
transient canvas state that used to be duplicated into each node. The net effect on
the exported config in `config/sync/`:

| Metric | Before | After | Reduction |
|--------|-------:|------:|----------:|
| Total lines | 6,930 | 2,045 | **4,885 (70%)** |
| Total size | 197 KiB | 52 KiB | **148 KiB (73%)** |
| Files changed | 11 workflows | — | — |

## Per-workflow reduction

| Workflow | Lines before | Lines after | Reduction |
|----------|-------------:|------------:|----------:|
| `flowdrop_chat_processor` | 1,345 | 467 | 65% |
| `level_0_1_echo` | 257 | 78 | 69% |
| `level_0_2_echo_but_lowercase` | 375 | 112 | 70% |
| `level_1_3_pick_your_transform` | 599 | 178 | 70% |
| `level_1_4_polite_greeter_templating` | 372 | 114 | 69% |
| `level_1_5_conditional_reply` | 661 | 216 | 67% |
| `level_2_6_simple_ai_chat` | 474 | 115 | 75% |
| `level_2_7_style_controlled_ai_reply` | 801 | 205 | 74% |
| `level_2_8_translator` | 109 | 38 | 65% |
| `level_3_9_url_summariser` | 848 | 199 | 76% |
| `rote_flora` | 1,089 | 323 | 70% |

## What caused it

The migration is **`flowdrop_workflow_post_update_slim_node_identity()`**
(`web/modules/contrib/flowdrop/modules/flowdrop_workflow/flowdrop_workflow.post_update.php`).

It loads every workflow and re-saves it **without** `setSyncing(TRUE)`, so
`FlowDropWorkflow::preSave()` runs its `slimNodesForStorage()` path
(`.../src/Entity/FlowDropWorkflow.php:404`, `:448`). That method rewrites each
stored node so that only genuine, non-derivable state is persisted.

A companion hook, `flowdrop_workflow_post_update_add_schema_fields()`, backfills
new schema defaults (`input_ports`, `output_ports`, etc.) but does not affect size.

### What gets dropped from every node
Each node in the old format embedded a full copy of its node-type definition. The
slimming removes:

- **The entire `data.metadata.*` node-type definition** — `name`, `type`,
  `description`, `category`, `icon`, `executor_plugin`, `inputs`, `outputs`,
  `config`, and the full `configSchema`. This is the bulk of the savings. It is
  now replaced by a single anchor: `data.metadata.node_type_id: <id>`.
- **The constant node-level `type: universalNode`** field.
- **Transient xyflow canvas state** — `selected`, `dragging`, `deletable`.
- **`data.nodeId`** — it only ever duplicated the top-level node `id`.

### What replaces it
- Each node keeps only its identity, position, `measured`, and real user input
  (`data.config`, `data.label`, `data.extensions`).
- The node-type definition now lives once in the `flowdrop_node_type.*` config
  entities and is referenced by ID. Accordingly, each workflow gained explicit
  **config dependencies**, e.g.:

  ```yaml
  dependencies:
    config:
      - flowdrop_node_type.flowdrop_node_type.chat_input
      - flowdrop_node_type.flowdrop_node_type.chat_output
  ```

### Before → after (node excerpt, `level_0_1_echo`)
```yaml
# BEFORE — ~90 lines of embedded definition per node
- id: chat_output.1
  type: universalNode
  data:
    metadata:
      id: chat_output
      name: 'Chat Output'
      executor_plugin: 'flowdrop_node_processor:chat_output'
      inputs: [ ... ]
      outputs: [ ... ]
      configSchema: { ... }   # dozens of lines
  deletable: true

# AFTER — the node-type is referenced, not inlined
- id: chat_output.1
  data:
    metadata:
      node_type_id: chat_output
```

## Notes / caveats

- The old `metadata.id` anchor is tolerated on read for back-compat: an
  old-format config import will slim (and rename `id` → `node_type_id`) on its
  next save. The migration is **idempotent** — an already-slim workflow re-saves
  unchanged.
- Related but separate namespacing hooks shipped in the same release:
  `flowdrop_update_10001/10002` (executor-plugin ID namespacing) and
  `flowdrop_node_type_update_10001/10002` (unified ports + dependency rebuild).
  These are not the source of the size reduction.
- **Action required:** these are working-tree changes only. Review and commit the
  slimmed `config/sync/*.yml` so the repository reflects the migrated format.
