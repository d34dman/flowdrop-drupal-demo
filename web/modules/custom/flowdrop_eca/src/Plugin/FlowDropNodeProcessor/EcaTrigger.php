<?php

declare(strict_types=1);

namespace Drupal\flowdrop_eca\Plugin\FlowDropNodeProcessor;

use Drupal\flowdrop\DTO\ParameterBagInterface;
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
      $container->get("logger.factory")
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get("flowdrop_node_processor");
  }

  /**
   * {@inheritdoc}
   */
  protected function getTriggerType(): string {
    return "eca";
  }

  /**
   * {@inheritdoc}
   */
  protected function processTriggerData(array $triggerData, array $params): array {
    $ecaData = [];

    if (!empty($params)) {
      $ecaData = [
        "eca_event" => $params["eca_event"] ?? "",
        "eca_conditions" => $params["eca_conditions"] ?? [],
        "eca_actions" => $params["eca_actions"] ?? [],
        "eca_context" => $params["eca_context"] ?? [],
        "eca_entity" => $params["eca_entity"] ?? NULL,
        "eca_user" => $params["eca_user"] ?? NULL,
        "eca_parameters" => $params["eca_parameters"] ?? [],
      ];
    }

    // Add ECA trigger specific metadata.
    $ecaData["eca_execution"] = TRUE;
    $ecaData["execution_source"] = "eca";
    $ecaData["eca_timestamp"] = time();

    // Merge with configured trigger data.
    return array_merge($triggerData, $ecaData);
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterSchema(): array {
    $baseSchema = parent::getParameterSchema();

    // Add ECA trigger specific input fields.
    $baseSchema["properties"]["eca_event"] = [
      "type" => "string",
      "title" => "ECA Event",
      "description" => "The ECA event that triggered this workflow",
      "flowdrop" => [
        "connectable" => TRUE,
        "configurable" => FALSE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["eca_conditions"] = [
      "type" => "array",
      "title" => "ECA Conditions",
      "description" => "ECA conditions that were evaluated",
      "flowdrop" => [
        "connectable" => TRUE,
        "configurable" => FALSE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["eca_actions"] = [
      "type" => "array",
      "title" => "ECA Actions",
      "description" => "ECA actions that were executed",
      "flowdrop" => [
        "connectable" => TRUE,
        "configurable" => FALSE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["eca_context"] = [
      "type" => "object",
      "title" => "ECA Context",
      "description" => "Context data from the ECA event",
      "flowdrop" => [
        "connectable" => TRUE,
        "configurable" => FALSE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["eca_entity"] = [
      "type" => "object",
      "title" => "ECA Entity",
      "description" => "The entity that triggered the ECA event",
      "flowdrop" => [
        "connectable" => TRUE,
        "configurable" => FALSE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["eca_user"] = [
      "type" => "object",
      "title" => "ECA User",
      "description" => "The user associated with the ECA event",
      "flowdrop" => [
        "connectable" => TRUE,
        "configurable" => FALSE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["eca_parameters"] = [
      "type" => "object",
      "title" => "ECA Parameters",
      "description" => "Additional parameters from the ECA event",
      "flowdrop" => [
        "connectable" => TRUE,
        "configurable" => FALSE,
        "required" => FALSE,
      ],
    ];

    // Add ECA trigger specific configuration.
    $baseSchema["properties"]["ecaEventTypes"] = [
      "type" => "array",
      "title" => "ECA Event Types",
      "description" => "Types of ECA events that should trigger this workflow",
      "items" => [
        "type" => "string",
      ],
      "default" => [],
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["ecaConditions"] = [
      "type" => "array",
      "title" => "ECA Conditions",
      "description" => "ECA conditions that must be met to trigger the workflow",
      "items" => [
        "type" => "object",
        "properties" => [
          "condition_type" => ["type" => "string"],
          "condition_config" => ["type" => "object"],
        ],
      ],
      "default" => [],
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["ecaActions"] = [
      "type" => "array",
      "title" => "ECA Actions",
      "description" => "ECA actions to execute as part of the trigger",
      "items" => [
        "type" => "object",
        "properties" => [
          "action_type" => ["type" => "string"],
          "action_config" => ["type" => "object"],
        ],
      ],
      "default" => [],
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["ecaEntityTypes"] = [
      "type" => "array",
      "title" => "ECA Entity Types",
      "description" => "Entity types that should trigger this workflow",
      "items" => [
        "type" => "string",
      ],
      "default" => [],
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["ecaUserRoles"] = [
      "type" => "array",
      "title" => "ECA User Roles",
      "description" => "User roles that should trigger this workflow",
      "items" => [
        "type" => "string",
      ],
      "default" => [],
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["ecaBundles"] = [
      "type" => "array",
      "title" => "ECA Bundles",
      "description" => "Content type bundles that should trigger this workflow",
      "items" => [
        "type" => "string",
      ],
      "default" => [],
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["ecaWorkflowId"] = [
      "type" => "string",
      "title" => "ECA Workflow ID",
      "description" => "The ECA workflow ID to integrate with",
      "default" => "",
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    $baseSchema["properties"]["ecaIntegrationMode"] = [
      "type" => "string",
      "title" => "ECA Integration Mode",
      "description" => "How to integrate with ECA workflows",
      "enum" => ["trigger", "action", "condition"],
      "default" => "trigger",
      "flowdrop" => [
        "connectable" => FALSE,
        "configurable" => TRUE,
        "required" => FALSE,
      ],
    ];

    return $baseSchema;
  }

  /**
   * Check if an ECA event matches the configured filters.
   *
   * @param array<string, mixed> $ecaData
   *   The ECA event data to check.
   * @param \Drupal\flowdrop\DTO\ParameterBagInterface $params
   *   The trigger parameters.
   *
   * @return bool
   *   TRUE if the ECA event matches the filters.
   */
  public function ecaEventMatchesFilters(array $ecaData, ParameterBagInterface $params): bool {
    // Check ECA event type filter.
    $ecaEventTypes = $params->get("ecaEventTypes", []);
    if (!empty($ecaEventTypes) && is_array($ecaEventTypes)) {
      $eventType = $ecaData["eca_event"] ?? "";
      if (!in_array($eventType, $ecaEventTypes)) {
        return FALSE;
      }
    }

    // Check entity type filter.
    $ecaEntityTypes = $params->get("ecaEntityTypes", []);
    if (!empty($ecaEntityTypes) && is_array($ecaEntityTypes)) {
      $entity = $ecaData["eca_entity"] ?? [];
      $entityType = is_array($entity) ? ($entity["type"] ?? "") : "";
      if (!in_array($entityType, $ecaEntityTypes)) {
        return FALSE;
      }
    }

    // Check user role filter.
    $ecaUserRoles = $params->get("ecaUserRoles", []);
    if (!empty($ecaUserRoles) && is_array($ecaUserRoles)) {
      $user = $ecaData["eca_user"] ?? [];
      $userRoles = is_array($user) ? ($user["roles"] ?? []) : [];
      $hasMatchingRole = FALSE;
      foreach ($ecaUserRoles as $role) {
        if (is_array($userRoles) && in_array($role, $userRoles)) {
          $hasMatchingRole = TRUE;
          break;
        }
      }
      if (!$hasMatchingRole) {
        return FALSE;
      }
    }

    // Check bundle filter.
    $ecaBundles = $params->get("ecaBundles", []);
    if (!empty($ecaBundles) && is_array($ecaBundles)) {
      $entity = $ecaData["eca_entity"] ?? [];
      $bundle = is_array($entity) ? ($entity["bundle"] ?? "") : "";
      if (!in_array($bundle, $ecaBundles)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Execute ECA conditions.
   *
   * @param array<int, array<string, mixed>> $conditions
   *   The ECA conditions to evaluate.
   * @param array<string, mixed> $context
   *   The context data for condition evaluation.
   *
   * @return bool
   *   TRUE if all conditions pass.
   */
  public function executeEcaConditions(array $conditions, array $context): bool {
    foreach ($conditions as $condition) {
      $conditionType = $condition["condition_type"] ?? "";
      $conditionConfig = $condition["condition_config"] ?? [];

      if (!$this->evaluateCondition($conditionType, $conditionConfig, $context)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Execute ECA actions.
   *
   * @param array<int, array<string, mixed>> $actions
   *   The ECA actions to execute.
   * @param array<string, mixed> $context
   *   The context data for action execution.
   *
   * @return array<int, array<string, mixed>>
   *   Results from action execution.
   */
  public function executeEcaActions(array $actions, array $context): array {
    $results = [];

    foreach ($actions as $action) {
      $actionType = $action["action_type"] ?? "";
      $actionConfig = $action["action_config"] ?? [];

      $results[] = $this->executeAction($actionType, $actionConfig, $context);
    }

    return $results;
  }

  /**
   * Evaluate a single ECA condition.
   *
   * @param string $conditionType
   *   The type of condition to evaluate.
   * @param array<string, mixed> $config
   *   The condition configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return bool
   *   TRUE if the condition passes.
   */
  protected function evaluateCondition(string $conditionType, array $config, array $context): bool {
    // This would integrate with ECA's condition evaluation system.
    // For now, return TRUE as a placeholder.
    $this->loggerFactory->get("flowdrop")->info("Evaluating ECA condition", [
      "condition_type" => $conditionType,
      "config" => $config,
    ]);

    return TRUE;
  }

  /**
   * Execute a single ECA action.
   *
   * @param string $actionType
   *   The type of action to execute.
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action execution result.
   */
  protected function executeAction(string $actionType, array $config, array $context): array {
    // This would integrate with ECA's action execution system.
    // For now, return a placeholder result.
    $this->loggerFactory->get("flowdrop")->info("Executing ECA action", [
      "action_type" => $actionType,
      "config" => $config,
    ]);

    return [
      "action_type" => $actionType,
      "success" => TRUE,
      "result" => [],
    ];
  }

}
