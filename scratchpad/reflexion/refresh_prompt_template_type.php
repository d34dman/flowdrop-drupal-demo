<?php
// Refresh the demo's prompt_template node type from the module's current default, keeping the site uuid.
use Drupal\Core\Serialization\Yaml;
$root = dirname(DRUPAL_ROOT);
$name = 'flowdrop_node_type.flowdrop_node_type.prompt_template.yml';
$module = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/modules/contrib/flowdrop/modules/flowdrop_node_processor/config/install/' . $name));
$site = Yaml::decode(file_get_contents("$root/config/sync/$name"));
echo "site keys: ", implode(',', array_keys($site)), "\nmodule keys: ", implode(',', array_keys($module)), "\n";
$new = ['uuid' => $site['uuid'], 'langcode' => $site['langcode'] ?? 'en', 'status' => $site['status'] ?? TRUE] + $module;
foreach (['uuid', 'langcode', 'status'] as $k) { unset($module[$k]); }
$new = array_merge(['uuid' => $site['uuid'], 'langcode' => $site['langcode'] ?? 'en', 'status' => $site['status'] ?? TRUE], $module);
file_put_contents("$root/config/sync/$name", Yaml::encode($new));
file_put_contents("$root/scratchpad/reflexion/import/$name", Yaml::encode($new));
echo "written; parameters: ", implode(',', array_keys($new['parameters'])), "\n";
