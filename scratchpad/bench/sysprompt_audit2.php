<?php
$db = \Drupal::database();
$tables = $db->query("SHOW TABLES LIKE '%flowdrop%'")->fetchCol();
print implode("\n", $tables) . "\n\n";
$j = $db->query("SELECT * FROM {flowdrop_job} WHERE node_type_id LIKE '%reason%' ORDER BY id DESC LIMIT 1")->fetchAssoc();
$m = json_decode((string) $j['metadata'], TRUE) ?: [];
print "metadata keys: " . implode(',', array_keys($m)) . "\n";
foreach ($m as $k => $v) { if (!is_array($v)) printf("  %s = %s\n", $k, substr((string) $v, 0, 80)); }
$d = json_decode((string) $j['input_data'], TRUE) ?: [];
print "input_data: " . substr(json_encode($d), 0, 400) . "\n";
