# What broke in FlowDrop itself

Three bugs found while running the benchmark, all filed upstream. These are the most
*Drupal-specific* material in the research and the best fit for a DrupalCon audience —
they are ordinary Drupal plugin/config bugs whose symptom is "the AI is unreliable."

---

## 1. An exposed-but-unconnected port silently erases your system prompt

**[flowdrop#3592438](https://git.drupalcode.org/project/flowdrop/-/work_items/3592438)**

### Symptom

The ReAct agent ran with **no system prompt at all**. Nothing errored. The runs completed,
produced clean Markdown, cost normal money, and returned ~30% of the document instead of
~95%. It looked exactly like "the model isn't good enough at this task."

### Cause

A FlowDrop node port can be `exposed` (connectable from another node). `ParameterResolver`
resolves precedence as:

```
runtime input (connectable + exposed)  →  workflow config  →  schema default
```

…gated on **`array_key_exists`** — presence, not emptiness. An exposed port with **no
incoming connection** resolves to an empty value, which is *present*, so it wins over the
node's configured `systemPrompt` and shadows it.

The node type does declare `system_prompt` under `parameters` — so this is a genuine
resolution bug, not a wiring mistake by the user.

### Fix

Both steps are required — setting the config alone does nothing:

```php
$n['data']['config']['systemPrompt'] = $prompt;
foreach ($n['data']['config']['ports']['inputs'] as &$p) {
  if (($p['id'] ?? '') === 'systemPrompt') { $p['exposed'] = FALSE; }
}
```

Suggested upstream fix at `WorkflowNode.php:523` — test `$params->get($name) !== NULL`
rather than `has()`.

### Measured impact

Identical workflow, same model, same page:

| | Input tokens | Retention |
|---|---|---|
| Prompt shadowed | 9,061 | **30.6 / 30.0 / 28.0%** |
| Prompt applied | 9,399 | **94.6 / 94.6 / 94.6%** |

**338 tokens of system prompt were worth 64 points of fidelity.** The 338-token delta is
also the only externally visible sign that anything was wrong.

### Why this belongs in the talk

It is a perfect worked example for **learning objective 6** (encode failure patterns) and
a cautionary tale for the whole thesis: *the research nearly concluded that an
architecture didn't work, when in fact the config layer had eaten the instructions.*
When an agent underperforms, verify the prompt physically arrived before you tune it.

**Diagnostic technique worth showing:** inject a nonsense marker (`QQZZX9`, `BANANA`)
into the system prompt and assert it appears in the model's behaviour. A prompt you cannot
prove arrived is a prompt you are not testing. An earlier conclusion in this research —
"setting `systemPrompt` fixed it, 51% → 93%" — was **wrong**, and only the marker test
exposed it; the 93% was ordinary variance.

---

## 2. The ReAct loop re-sends its transcript uncached

**[flowdrop#3592437](https://git.drupalcode.org/project/flowdrop/-/work_items/3592437)**

Each iteration re-sends the whole conversation. 30% of all input tokens in the dataset are
verbatim repeats. Combined with finding #3 in
[05-cost-and-caching.md](05-cost-and-caching.md), those repeats cannot be cached at all
through the current provider.

---

## 3. Pipeline memory ceiling — a non-atomic failure

**Patch: `flowdrop-memory-configurable-max-value-bytes.patch` (1 MB → 10 MB)**

On the large page, a model that produces a long faithful answer overflows FlowDrop's
pipeline-memory buffer **after the answer is complete**. The run then delivers nothing.

This is the abstract's *"half-applied state changes"* pattern, in the wild: the expensive
work succeeded, and the result was lost at the storage boundary. It is also an argument
for **learning objective 5** (atomic and reversible) that costs $1.90 to demonstrate —
Sonnet 4.6's large-page cell simply does not exist without the patch.

Any model pushing a long answer through this loop sits near the same cliff. **The fix is
the patch, not the model choice.**

---

## Also worth a mention

- **`url_to_markdown` raises a `schema_form` interrupt** unless the node sets
  `requiresConfirmation: 'waive'`, which pauses headless runs indefinitely
  (`scratchpad/bench/waive_utm.php`). A human-in-the-loop default that becomes a hang in
  automation.
- **Bench workflows exist only in the database** and have not been exported to
  `config/sync` — see [ideas/open-questions.md](../ideas/open-questions.md).
