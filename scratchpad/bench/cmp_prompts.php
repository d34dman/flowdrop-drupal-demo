<?php
$s = \Drupal::entityTypeManager()->getStorage('flowdrop_workflow');
$get = function ($id) use ($s) {
  foreach ($s->load($id)->get('nodes') as $n) {
    if (str_contains((string) $n['id'], 'reason')) {
      return (string) ($n['data']['config']['systemPrompt'] ?? '');
    }
  }
  return '';
};
$a = $get('react_agent_with_tools');
$b = $get('react_agent_with_optimized_tools');
printf("react_agent_with_tools           : %d chars md5=%s\n", strlen($a), md5($a));
printf("react_agent_with_optimized_tools : %d chars md5=%s\n", strlen($b), md5($b));
printf("identical: %s\n", $a === $b ? 'YES' : 'NO');
if ($a !== $b) { printf("\n--- optimized prompt ---\n%s\n", $b); }
