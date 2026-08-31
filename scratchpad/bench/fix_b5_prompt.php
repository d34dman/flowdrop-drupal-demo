<?php
/**
 * Sets the system prompt on the Reason node itself. The parent workflow's
 * `system_prompt` config key on the sub-workflow node is read by nothing —
 * the sub-workflow node type declares no ports and no config schema — so the
 * agent has been running with no instructions at all.
 */
$prompt = "Fetch the content at the given URL and convert the ENTIRE page to Markdown.\n\n"
  . "Reproduce the full document: every heading, paragraph, list item and table, in\n"
  . "the original order and wording. Do not summarise, abridge, omit or re-word\n"
  . "anything. Redaction is the only change you may make to the text.\n\n"
  . "Redaction: replace any competitor of Drupal CMS with \"\u{258C}\u{258C}\u{258C}\u{258C}\".\n\n"
  . "Output the Markdown document and nothing else. No code fences, no preamble, no\n"
  . "commentary, no explanation of what you did.";
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
foreach (['react_agent_with_tools'] as $id) {
  $wf = $s->load($id);
  $nodes = $wf->get('nodes');
  $touched = 0;
  foreach ($nodes as &$n) {
    if (!str_contains((string) ($n['id'] ?? ''), 'reason')) { continue; }
    $before = $n['data']['config']['systemPrompt'] ?? '';
    $n['data']['config']['systemPrompt'] = $prompt;
    $touched++;
    printf("%s :: %s -> systemPrompt was %d chars, now %d\n", $id, $n['id'],
      strlen($before), strlen($prompt));
  }
  unset($n);
  if ($touched) { $wf->set('nodes', $nodes)->save(); }
}
