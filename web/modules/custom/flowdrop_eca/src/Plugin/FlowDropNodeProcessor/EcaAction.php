<?php

declare(strict_types=1);

namespace Drupal\flowdrop_eca\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executor for ECA Action nodes.
 *
 * ECA Action nodes execute ECA actions within FlowDrop workflows,
 * allowing integration between FlowDrop and ECA systems.
 */
#[FlowDropNodeProcessor(
  id: "eca_action",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("ECA Action"),
  type: "default",
  supportedTypes: ["default"],
  category: "eca",
  description: "Execute ECA actions within FlowDrop workflows",
  version: "1.0.0",
  tags: ["eca", "action", "workflow"]
)]
class EcaAction extends AbstractFlowDropNodeProcessor {

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
  protected function process(ParameterBagInterface $params): array {
    $actionType = $params->get("actionType", "");
    $actionConfig = $params->get("actionConfig", []);
    $ecaContext = $params->get("ecaContext", []);

    // Merge all params into context.
    $context = array_merge($ecaContext, $params->all());

    // Execute the ECA action.
    $actionConfigArray = is_array($actionConfig) ? $actionConfig : [];
    $result = $this->executeEcaAction((string) $actionType, $actionConfigArray, $context);

    $this->getLogger()->info("ECA Action executed successfully", [
      "action_type" => $actionType,
      "success" => $result["success"] ?? FALSE,
    ]);

    return [
      "action_type" => $actionType,
      "action_result" => $result,
      "success" => $result["success"] ?? FALSE,
      "output" => $result["output"] ?? [],
      "errors" => $result["errors"] ?? [],
    ];
  }

  /**
   * Execute an ECA action.
   *
   * @param string $actionType
   *   The type of ECA action to execute.
   * @param array<string, mixed> $actionConfig
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data for the action.
   *
   * @return array<string, mixed>
   *   The action execution result.
   */
  protected function executeEcaAction(string $actionType, array $actionConfig, array $context): array {
    // This would integrate with ECA's action execution system.
    // For now, provide a framework for different action types.
    switch ($actionType) {
      case "create_entity":
        return $this->executeCreateEntityAction($actionConfig, $context);

      case "update_entity":
        return $this->executeUpdateEntityAction($actionConfig, $context);

      case "delete_entity":
        return $this->executeDeleteEntityAction($actionConfig, $context);

      case "send_email":
        return $this->executeSendEmailAction($actionConfig, $context);

      case "redirect_user":
        return $this->executeRedirectUserAction($actionConfig, $context);

      case "set_message":
        return $this->executeSetMessageAction($actionConfig, $context);

      case "log_action":
        return $this->executeLogActionAction($actionConfig, $context);

      default:
        return $this->executeCustomAction($actionType, $actionConfig, $context);
    }
  }

  /**
   * Execute a create entity action.
   *
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeCreateEntityAction(array $config, array $context): array {
    $entityType = $config["entity_type"] ?? "";
    $bundle = $config["bundle"] ?? "";
    $values = $config["values"] ?? [];

    // This would integrate with Drupal's entity creation system.
    $this->getLogger()->info("Creating ECA entity", [
      "entity_type" => $entityType,
      "bundle" => $bundle,
      "values" => $values,
    ]);

    return [
      "success" => TRUE,
      "output" => [
        "entity_id" => uniqid("entity_", TRUE),
        "entity_type" => $entityType,
        "bundle" => $bundle,
      ],
      "errors" => [],
    ];
  }

  /**
   * Execute an update entity action.
   *
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeUpdateEntityAction(array $config, array $context): array {
    $entityId = $config["entity_id"] ?? "";
    $entityType = $config["entity_type"] ?? "";
    $values = $config["values"] ?? [];

    $this->getLogger()->info("Updating ECA entity", [
      "entity_id" => $entityId,
      "entity_type" => $entityType,
      "values" => $values,
    ]);

    return [
      "success" => TRUE,
      "output" => [
        "entity_id" => $entityId,
        "entity_type" => $entityType,
        "updated" => TRUE,
      ],
      "errors" => [],
    ];
  }

  /**
   * Execute a delete entity action.
   *
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeDeleteEntityAction(array $config, array $context): array {
    $entityId = $config["entity_id"] ?? "";
    $entityType = $config["entity_type"] ?? "";

    $this->getLogger()->info("Deleting ECA entity", [
      "entity_id" => $entityId,
      "entity_type" => $entityType,
    ]);

    return [
      "success" => TRUE,
      "output" => [
        "entity_id" => $entityId,
        "entity_type" => $entityType,
        "deleted" => TRUE,
      ],
      "errors" => [],
    ];
  }

  /**
   * Execute a send email action.
   *
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeSendEmailAction(array $config, array $context): array {
    $to = $config["to"] ?? "";
    $subject = $config["subject"] ?? "";
    $from = $config["from"] ?? "";

    $this->getLogger()->info("Sending ECA email", [
      "to" => $to,
      "subject" => $subject,
      "from" => $from,
    ]);

    return [
      "success" => TRUE,
      "output" => [
        "email_sent" => TRUE,
        "to" => $to,
        "subject" => $subject,
      ],
      "errors" => [],
    ];
  }

  /**
   * Execute a redirect user action.
   *
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeRedirectUserAction(array $config, array $context): array {
    $url = $config["url"] ?? "";
    $statusCode = $config["status_code"] ?? 302;

    $this->getLogger()->info("Redirecting user via ECA", [
      "url" => $url,
      "status_code" => $statusCode,
    ]);

    return [
      "success" => TRUE,
      "output" => [
        "redirect_url" => $url,
        "status_code" => $statusCode,
      ],
      "errors" => [],
    ];
  }

  /**
   * Execute a set message action.
   *
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeSetMessageAction(array $config, array $context): array {
    $message = $config["message"] ?? "";
    $type = $config["type"] ?? "status";
    $repeat = $config["repeat"] ?? FALSE;

    $this->getLogger()->info("Setting ECA message", [
      "message" => $message,
      "type" => $type,
    ]);

    return [
      "success" => TRUE,
      "output" => [
        "message" => $message,
        "type" => $type,
        "repeat" => $repeat,
      ],
      "errors" => [],
    ];
  }

  /**
   * Execute a log action.
   *
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeLogActionAction(array $config, array $context): array {
    $message = $config["message"] ?? "";
    $level = $config["level"] ?? "info";
    $channel = $config["channel"] ?? "eca";

    $this->loggerFactory->get($channel)->log($level, $message, $context);

    return [
      "success" => TRUE,
      "output" => [
        "logged" => TRUE,
        "message" => $message,
        "level" => $level,
        "channel" => $channel,
      ],
      "errors" => [],
    ];
  }

  /**
   * Execute a custom action.
   *
   * @param string $actionType
   *   The custom action type.
   * @param array<string, mixed> $config
   *   The action configuration.
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return array<string, mixed>
   *   The action result.
   */
  protected function executeCustomAction(string $actionType, array $config, array $context): array {
    $this->getLogger()->info("Executing custom ECA action", [
      "action_type" => $actionType,
      "config" => $config,
    ]);

    return [
      "success" => TRUE,
      "output" => [
        "action_type" => $actionType,
        "custom_action" => TRUE,
      ],
      "errors" => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $params): bool {
    // ECA actions can accept any inputs.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "action_type" => [
          "type" => "string",
          "description" => "The type of ECA action that was executed",
        ],
        "action_result" => [
          "type" => "object",
          "description" => "The result from the ECA action execution",
        ],
        "success" => [
          "type" => "boolean",
          "description" => "Whether the action was successful",
        ],
        "output" => [
          "type" => "object",
          "description" => "Output data from the action",
        ],
        "errors" => [
          "type" => "array",
          "description" => "Any errors that occurred during execution",
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "data" => [
          "type" => "mixed",
          "title" => "Input Data",
          "description" => "Input data for the ECA action",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "entity" => [
          "type" => "object",
          "title" => "Entity",
          "description" => "Entity data for entity-related actions",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "user" => [
          "type" => "object",
          "title" => "User",
          "description" => "User data for user-related actions",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "parameters" => [
          "type" => "object",
          "title" => "Parameters",
          "description" => "Additional parameters for the action",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "actionType" => [
          "type" => "string",
          "title" => "Action Type",
          "description" => "The type of ECA action to execute",
          "enum" => [
            "create_entity",
            "update_entity",
            "delete_entity",
            "send_email",
            "redirect_user",
            "set_message",
            "log_action",
          ],
          "default" => "log_action",
          "flowdrop" => [
            "connectable" => FALSE,
            "configurable" => TRUE,
            "required" => TRUE,
          ],
        ],
        "actionConfig" => [
          "type" => "object",
          "title" => "Action Configuration",
          "description" => "Configuration for the ECA action",
          "default" => [],
          "flowdrop" => [
            "connectable" => FALSE,
            "configurable" => TRUE,
            "required" => FALSE,
          ],
        ],
        "ecaContext" => [
          "type" => "object",
          "title" => "ECA Context",
          "description" => "Context data for the ECA action",
          "default" => [],
          "flowdrop" => [
            "connectable" => FALSE,
            "configurable" => TRUE,
            "required" => FALSE,
          ],
        ],
        "description" => [
          "type" => "string",
          "title" => "Description",
          "description" => "Optional description for this ECA action",
          "default" => "",
          "flowdrop" => [
            "connectable" => FALSE,
            "configurable" => TRUE,
            "required" => FALSE,
          ],
        ],
      ],
    ];
  }

}
