<?php

declare(strict_types=1);

namespace Drupal\flowdrop_eca\Plugin\FlowDropNodeProcessor;

use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop_node_processor\Plugin\FlowDropNodeProcessor\AbstractTrigger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executor for ECA Trigger nodes.
 *
 * ECA triggers integrate with Drupal's ECA module to start workflow execution
 * based on ECA events, conditions, and actions.
 */
#[FlowDropNodeProcessor(
  id: "eca_trigger",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("ECA Trigger"),
  type: "trigger",
  supportedTypes: ["trigger"],
  category: "eca",
  description: "Trigger workflows based on ECA events",
  version: "1.0.0",
  tags: ["eca", "trigger", "workflow"]
)]
class EcaTrigger extends AbstractTrigger {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('flowdrop_node_processor');
  }

  /**
   * {@inheritdoc}
   */
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    // This method is required by AbstractFlowDropNodeProcessor
    // but triggers use their own execute method, so we delegate to the parent.
    return parent::execute($inputs, $config)->toArray();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTriggerType(): string {
    return 'eca';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $inputs, ConfigInterface $config): \Drupal\flowdrop\DTO\OutputInterface {
    // Call parent execute first.
    $output = parent::execute($inputs, $config);

    // Extract node data from inputs and add as separate output fields.
    $input_array = $inputs->toArray();

    // Extract node title from various possible input sources.
    $node_title = $input_array['node_title'] ??
                  ($input_array['eca_entity']['title'] ?? '') ??
                  ($input_array['entity']['title'] ?? '') ??
                  '';

    // Extract entity data.
    $entity = $input_array['eca_entity'] ?? $input_array['entity'] ?? [];

    // Extract entity_id and node_id for easy connection to downstream nodes.
    $entity_id = $input_array['entity_id'] ??
                 $input_array['node_id'] ??
                 ($entity['id'] ?? NULL);

    $node_id = $input_array['node_id'] ??
               $input_array['entity_id'] ??
               ($entity['id'] ?? NULL);

    // Add all the expected output fields to the output.
    $output_data = $output->toArray();
    $output_data['node_title'] = $node_title;
    $output_data['entity'] = $entity;
    $output_data['entity_id'] = $entity_id;
    $output_data['node_id'] = $node_id;
    $output_data['trigger_data'] = $input_array['trigger_data'] ?? [];

    // Rebuild output with all fields.
    $new_output = new \Drupal\flowdrop\DTO\Output();
    $new_output->fromArray($output_data);
    $new_output->setStatus('success');

    $this->getLogger()->info('ECA Trigger executed with node_title: @title, entity_id: @id', [
      '@title' => $node_title,
      '@id' => $entity_id,
    ]);

    return $new_output;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    $base_schema = parent::getOutputSchema();

    // Ensure the schema has the correct structure.
    if (!isset($base_schema['type'])) {
      $base_schema['type'] = 'object';
    }
    if (!isset($base_schema['properties'])) {
      $base_schema['properties'] = [];
    }

    // Add entity-specific outputs for easy downstream connection.
    $base_schema['properties']['entity_id'] = [
      'type' => 'integer',
      'description' => 'The entity ID',
    ];

    $base_schema['properties']['node_id'] = [
      'type' => 'integer',
      'description' => 'The node ID (alias for entity_id)',
    ];

    $base_schema['properties']['node_title'] = [
      'type' => 'string',
      'description' => 'The node title',
    ];

    $base_schema['properties']['entity'] = [
      'type' => 'object',
      'description' => 'The full entity data',
    ];

    return $base_schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function processTriggerData(mixed $trigger_data, array $inputs): array {
    // Handle both array and non-array trigger_data (UI may pass string).
    if (!is_array($trigger_data)) {
      $trigger_data = [];
    }

    $eca_data = [];

    if (!empty($inputs)) {
      $eca_data = [
        'eca_event' => $inputs['eca_event'] ?? '',
        'eca_conditions' => $inputs['eca_conditions'] ?? [],
        'eca_actions' => $inputs['eca_actions'] ?? [],
        'eca_context' => $inputs['eca_context'] ?? [],
        'eca_entity' => $inputs['eca_entity'] ?? NULL,
        'eca_user' => $inputs['eca_user'] ?? NULL,
        'eca_parameters' => $inputs['eca_parameters'] ?? [],
      ];
    }

    // Add ECA trigger specific metadata.
    $eca_data['eca_execution'] = TRUE;
    $eca_data['execution_source'] = 'eca';
    $eca_data['eca_timestamp'] = time();

    // Merge with configured trigger data.
    return array_merge($trigger_data, $eca_data);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSchema(): array {
    $base_schema = parent::getConfigSchema();

    // Add ECA trigger specific configuration.
    $base_schema['properties']['ecaEventTypes'] = [
      'type' => 'array',
      'title' => 'ECA Event Types',
      'description' => 'Types of ECA events that should trigger this workflow',
      'items' => [
        'type' => 'string',
      ],
      'default' => [],
    ];

    $base_schema['properties']['ecaConditions'] = [
      'type' => 'array',
      'title' => 'ECA Conditions',
      'description' => 'ECA conditions that must be met to trigger the workflow',
      'items' => [
        'type' => 'object',
        'properties' => [
          'condition_type' => ['type' => 'string'],
          'condition_config' => ['type' => 'object'],
        ],
      ],
      'default' => [],
    ];

    $base_schema['properties']['ecaActions'] = [
      'type' => 'array',
      'title' => 'ECA Actions',
      'description' => 'ECA actions to execute as part of the trigger',
      'items' => [
        'type' => 'object',
        'properties' => [
          'action_type' => ['type' => 'string'],
          'action_config' => ['type' => 'object'],
        ],
      ],
      'default' => [],
    ];

    $base_schema['properties']['ecaEntityTypes'] = [
      'type' => 'array',
      'title' => 'ECA Entity Types',
      'description' => 'Entity types that should trigger this workflow',
      'items' => [
        'type' => 'string',
      ],
      'default' => [],
    ];

    $base_schema['properties']['ecaUserRoles'] = [
      'type' => 'array',
      'title' => 'ECA User Roles',
      'description' => 'User roles that should trigger this workflow',
      'items' => [
        'type' => 'string',
      ],
      'default' => [],
    ];

    $base_schema['properties']['ecaBundles'] = [
      'type' => 'array',
      'title' => 'ECA Bundles',
      'description' => 'Content type bundles that should trigger this workflow',
      'items' => [
        'type' => 'string',
      ],
      'default' => [],
    ];

    $base_schema['properties']['ecaWorkflowId'] = [
      'type' => 'string',
      'title' => 'ECA Workflow ID',
      'description' => 'The ECA workflow ID to integrate with',
      'default' => '',
    ];

    $base_schema['properties']['ecaIntegrationMode'] = [
      'type' => 'string',
      'title' => 'ECA Integration Mode',
      'description' => 'How to integrate with ECA workflows',
      'enum' => ['trigger', 'action', 'condition'],
      'default' => 'trigger',
    ];

    return $base_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    $base_schema = parent::getInputSchema();

    // Add ECA trigger specific input fields.
    $base_schema['properties']['eca_event'] = [
      'type' => 'string',
      'title' => 'ECA Event',
      'description' => 'The ECA event that triggered this workflow',
      'required' => FALSE,
    ];

    $base_schema['properties']['eca_conditions'] = [
      'type' => 'array',
      'title' => 'ECA Conditions',
      'description' => 'ECA conditions that were evaluated',
      'required' => FALSE,
    ];

    $base_schema['properties']['eca_actions'] = [
      'type' => 'array',
      'title' => 'ECA Actions',
      'description' => 'ECA actions that were executed',
      'required' => FALSE,
    ];

    $base_schema['properties']['eca_context'] = [
      'type' => 'object',
      'title' => 'ECA Context',
      'description' => 'Context data from the ECA event',
      'required' => FALSE,
    ];

    $base_schema['properties']['eca_entity'] = [
      'type' => 'object',
      'title' => 'ECA Entity',
      'description' => 'The entity that triggered the ECA event',
      'required' => FALSE,
    ];

    $base_schema['properties']['eca_user'] = [
      'type' => 'object',
      'title' => 'ECA User',
      'description' => 'The user associated with the ECA event',
      'required' => FALSE,
    ];

    $base_schema['properties']['eca_parameters'] = [
      'type' => 'object',
      'title' => 'ECA Parameters',
      'description' => 'Additional parameters from the ECA event',
      'required' => FALSE,
    ];

    return $base_schema;
  }

  /**
   * Check if an ECA event matches the configured filters.
   *
   * @param array $eca_data
   *   The ECA event data to check.
   * @param \Drupal\flowdrop\DTO\ConfigInterface $config
   *   The trigger configuration.
   *
   * @return bool
   *   TRUE if the ECA event matches the filters.
   */
  public function ecaEventMatchesFilters(array $eca_data, ConfigInterface $config): bool {
    // Check ECA event type filter.
    $eca_event_types = $config->getConfig('ecaEventTypes', []);
    if (!empty($eca_event_types)) {
      $event_type = $eca_data['eca_event'] ?? '';
      if (!in_array($event_type, $eca_event_types)) {
        return FALSE;
      }
    }

    // Check entity type filter.
    $eca_entity_types = $config->getConfig('ecaEntityTypes', []);
    if (!empty($eca_entity_types)) {
      $entity = $eca_data['eca_entity'] ?? [];
      $entity_type = $entity['type'] ?? '';
      if (!in_array($entity_type, $eca_entity_types)) {
        return FALSE;
      }
    }

    // Check user role filter.
    $eca_user_roles = $config->getConfig('ecaUserRoles', []);
    if (!empty($eca_user_roles)) {
      $user = $eca_data['eca_user'] ?? [];
      $user_roles = $user['roles'] ?? [];
      $has_matching_role = FALSE;
      foreach ($eca_user_roles as $role) {
        if (in_array($role, $user_roles)) {
          $has_matching_role = TRUE;
          break;
        }
      }
      if (!$has_matching_role) {
        return FALSE;
      }
    }

    // Check bundle filter.
    $eca_bundles = $config->getConfig('ecaBundles', []);
    if (!empty($eca_bundles)) {
      $entity = $eca_data['eca_entity'] ?? [];
      $bundle = $entity['bundle'] ?? '';
      if (!in_array($bundle, $eca_bundles)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Execute ECA conditions.
   *
   * @param array $conditions
   *   The ECA conditions to evaluate.
   * @param array $context
   *   The context data for condition evaluation.
   *
   * @return bool
   *   TRUE if all conditions pass.
   */
  public function executeEcaConditions(array $conditions, array $context): bool {
    foreach ($conditions as $condition) {
      $condition_type = $condition['condition_type'] ?? '';
      $condition_config = $condition['condition_config'] ?? [];

      if (!$this->evaluateCondition($condition_type, $condition_config, $context)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Execute ECA actions.
   *
   * @param array $actions
   *   The ECA actions to execute.
   * @param array $context
   *   The context data for action execution.
   *
   * @return array
   *   Results from action execution.
   */
  public function executeEcaActions(array $actions, array $context): array {
    $results = [];

    foreach ($actions as $action) {
      $action_type = $action['action_type'] ?? '';
      $action_config = $action['action_config'] ?? [];

      $results[] = $this->executeAction($action_type, $action_config, $context);
    }

    return $results;
  }

  /**
   * Evaluate a single ECA condition.
   *
   * @param string $condition_type
   *   The type of condition to evaluate.
   * @param array $config
   *   The condition configuration.
   * @param array $context
   *   The context data.
   *
   * @return bool
   *   TRUE if the condition passes.
   */
  protected function evaluateCondition(string $condition_type, array $config, array $context): bool {
    // This would integrate with ECA's condition evaluation system
    // For now, return TRUE as a placeholder.
    $this->loggerFactory->get('flowdrop')->info('Evaluating ECA condition', [
      'condition_type' => $condition_type,
      'config' => $config,
    ]);

    return TRUE;
  }

  /**
   * Execute a single ECA action.
   *
   * @param string $action_type
   *   The type of action to execute.
   * @param array $config
   *   The action configuration.
   * @param array $context
   *   The context data.
   *
   * @return array
   *   The action execution result.
   */
  protected function executeAction(string $action_type, array $config, array $context): array {
    // This would integrate with ECA's action execution system
    // For now, return a placeholder result.
    $this->loggerFactory->get('flowdrop')->info('Executing ECA action', [
      'action_type' => $action_type,
      'config' => $config,
    ]);

    return [
      'action_type' => $action_type,
      'success' => TRUE,
      'result' => [],
    ];
  }

}
