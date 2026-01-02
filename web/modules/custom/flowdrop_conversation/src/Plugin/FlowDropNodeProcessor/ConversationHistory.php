<?php

declare(strict_types=1);

namespace Drupal\flowdrop_conversation\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\flowdrop_conversation\Service\ConversationManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Conversation History node processor.
 *
 * Manages conversation history for AI agents and chat interfaces.
 * Supports creating, retrieving, and updating conversation state.
 */
#[FlowDropNodeProcessor(
  id: "conversation_history",
  label: new TranslatableMarkup("Conversation History"),
  type: "conversation_history",
  supportedTypes: ["conversation_history"],
  category: "ai",
  description: "Manages conversation history for AI agents",
  version: "1.0.0",
  tags: ["conversation", "history", "memory", "ai", "chat"]
)]
class ConversationHistory extends AbstractFlowDropNodeProcessor {

  /**
   * The conversation manager service.
   *
   * @var \Drupal\flowdrop_conversation\Service\ConversationManager
   */
  protected ConversationManager $conversationManager;

  /**
   * Constructs a new ConversationHistory object.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   * @param \Drupal\flowdrop_conversation\Service\ConversationManager $conversationManager
   *   The conversation manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    ConversationManager $conversationManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->conversationManager = $conversationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get("logger.factory"),
      $container->get("flowdrop_conversation.manager"),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get("flowdrop_conversation");
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $params): bool {
    // Action is optional (defaults to 'get'), so inputs are always valid.
    // Specific action validations happen in the process method.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(ParameterBagInterface $params): array {
    $action = $params->get("action", "get");
    $conversationId = $params->get("conversationId");

    $this->getLogger()->debug("ConversationHistory processing action: @action", [
      "@action" => $action,
    ]);

    return match ($action) {
      "create" => $this->handleCreate($params),
      "get" => $this->handleGet($params),
      "add" => $this->handleAdd($params),
      "clear" => $this->handleClear($params),
      "delete" => $this->handleDelete($params),
      "get_or_create" => $this->handleGetOrCreate($params),
      default => throw new \InvalidArgumentException(
        "Unknown action: {$action}. Valid actions: create, get, add, clear, delete, get_or_create"
      ),
    };
  }

  /**
   * Handles the 'create' action.
   *
   * @param \Drupal\flowdrop\DTO\ParameterBagInterface $params
   *   The parameters.
   *
   * @return array<string, mixed>
   *   The result.
   */
  private function handleCreate(ParameterBagInterface $params): array {
    $systemPrompt = $params->get("systemPrompt");
    $metadata = $params->get("metadata", []);
    $metadataArray = is_array($metadata) ? $metadata : [];

    $conversation = $this->conversationManager->createConversation(
      $systemPrompt,
      $metadataArray
    );

    $this->getLogger()->info("Created conversation @id", [
      "@id" => $conversation->getConversationId(),
    ]);

    return [
      "conversationId" => $conversation->getConversationId(),
      "messages" => $conversation->getMessagesForLlm(),
      "messageCount" => $conversation->getMessageCount(),
      "created" => TRUE,
    ];
  }

  /**
   * Handles the 'get' action.
   *
   * @param \Drupal\flowdrop\DTO\ParameterBagInterface $params
   *   The parameters.
   *
   * @return array<string, mixed>
   *   The result.
   */
  private function handleGet(ParameterBagInterface $params): array {
    $conversationId = $params->get("conversationId");

    if (empty($conversationId)) {
      return [
        "conversationId" => NULL,
        "messages" => [],
        "messageCount" => 0,
        "found" => FALSE,
        "error" => "No conversation ID provided",
      ];
    }

    $conversation = $this->conversationManager->loadConversation((string) $conversationId);

    if ($conversation === NULL) {
      return [
        "conversationId" => $conversationId,
        "messages" => [],
        "messageCount" => 0,
        "found" => FALSE,
      ];
    }

    $strategy = $params->get("strategy", "full");
    $windowSize = (int) $params->get("windowSize", 20);

    $messages = $strategy === "window"
      ? $this->conversationManager->getRecentHistoryForLlm(
          (string) $conversationId,
          $windowSize
        )
      : $conversation->getMessagesForLlm();

    return [
      "conversationId" => $conversationId,
      "messages" => $messages,
      "messageCount" => $conversation->getMessageCount(),
      "found" => TRUE,
      "systemPrompt" => $conversation->getSystemPrompt(),
      "metadata" => $conversation->getMetadata(),
    ];
  }

  /**
   * Handles the 'add' action.
   *
   * @param \Drupal\flowdrop\DTO\ParameterBagInterface $params
   *   The parameters.
   *
   * @return array<string, mixed>
   *   The result.
   */
  private function handleAdd(ParameterBagInterface $params): array {
    $conversationId = $params->get("conversationId");

    if (empty($conversationId)) {
      throw new \InvalidArgumentException("Conversation ID is required for add action");
    }

    $role = $params->get("role", "user");
    $content = $params->get("content", "");
    $toolCallId = $params->get("toolCallId");

    $options = [];
    if ($toolCallId !== NULL) {
      $options["toolCallId"] = $toolCallId;
    }

    $conversation = $this->conversationManager->addMessage(
      (string) $conversationId,
      (string) $role,
      (string) $content,
      $options
    );

    if ($conversation === NULL) {
      return [
        "conversationId" => $conversationId,
        "messages" => [],
        "messageCount" => 0,
        "added" => FALSE,
        "error" => "Conversation not found",
      ];
    }

    return [
      "conversationId" => $conversationId,
      "messages" => $conversation->getMessagesForLlm(),
      "messageCount" => $conversation->getMessageCount(),
      "added" => TRUE,
    ];
  }

  /**
   * Handles the 'clear' action.
   *
   * @param \Drupal\flowdrop\DTO\ParameterBagInterface $params
   *   The parameters.
   *
   * @return array<string, mixed>
   *   The result.
   */
  private function handleClear(ParameterBagInterface $params): array {
    $conversationId = $params->get("conversationId");

    if (empty($conversationId)) {
      throw new \InvalidArgumentException("Conversation ID is required for clear action");
    }

    $conversation = $this->conversationManager->clearHistory((string) $conversationId);

    if ($conversation === NULL) {
      return [
        "conversationId" => $conversationId,
        "cleared" => FALSE,
        "error" => "Conversation not found",
      ];
    }

    return [
      "conversationId" => $conversationId,
      "messages" => $conversation->getMessagesForLlm(),
      "messageCount" => $conversation->getMessageCount(),
      "cleared" => TRUE,
    ];
  }

  /**
   * Handles the 'delete' action.
   *
   * @param \Drupal\flowdrop\DTO\ParameterBagInterface $params
   *   The parameters.
   *
   * @return array<string, mixed>
   *   The result.
   */
  private function handleDelete(ParameterBagInterface $params): array {
    $conversationId = $params->get("conversationId");

    if (empty($conversationId)) {
      throw new \InvalidArgumentException("Conversation ID is required for delete action");
    }

    $deleted = $this->conversationManager->deleteConversation((string) $conversationId);

    return [
      "conversationId" => $conversationId,
      "deleted" => $deleted,
    ];
  }

  /**
   * Handles the 'get_or_create' action.
   *
   * @param \Drupal\flowdrop\DTO\ParameterBagInterface $params
   *   The parameters.
   *
   * @return array<string, mixed>
   *   The result.
   */
  private function handleGetOrCreate(ParameterBagInterface $params): array {
    $conversationId = $params->get("conversationId");
    $systemPrompt = $params->get("systemPrompt");
    $metadata = $params->get("metadata", []);
    $metadataArray = is_array($metadata) ? $metadata : [];

    $conversation = $this->conversationManager->getOrCreate(
      $conversationId,
      $systemPrompt,
      $metadataArray
    );

    $wasCreated = $conversationId === NULL
      || !$this->conversationManager->conversationExists((string) $conversationId);

    return [
      "conversationId" => $conversation->getConversationId(),
      "messages" => $conversation->getMessagesForLlm(),
      "messageCount" => $conversation->getMessageCount(),
      "created" => $wasCreated,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "action" => [
          "type" => "string",
          "title" => "Action",
          "description" => "Action to perform on the conversation",
          "enum" => ["create", "get", "add", "clear", "delete", "get_or_create"],
          "default" => "get",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => TRUE,
            "required" => FALSE,
          ],
        ],
        "conversationId" => [
          "type" => "string",
          "title" => "Conversation ID",
          "description" => "ID of the conversation to operate on",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "role" => [
          "type" => "string",
          "title" => "Role",
          "description" => "Message role (for add action)",
          "enum" => ["user", "assistant", "system", "tool"],
          "default" => "user",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "content" => [
          "type" => "string",
          "title" => "Content",
          "description" => "Message content (for add action)",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "systemPrompt" => [
          "type" => "string",
          "title" => "System Prompt",
          "description" => "System prompt (for create action) or default for new conversations",
          "default" => "",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => TRUE,
            "required" => FALSE,
          ],
        ],
        "toolCallId" => [
          "type" => "string",
          "title" => "Tool Call ID",
          "description" => "Tool call ID (for tool role messages)",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "metadata" => [
          "type" => "object",
          "title" => "Metadata",
          "description" => "Additional metadata (for create action)",
          "flowdrop" => [
            "connectable" => TRUE,
            "configurable" => FALSE,
            "required" => FALSE,
          ],
        ],
        "strategy" => [
          "type" => "string",
          "title" => "History Strategy",
          "description" => "How to manage conversation history",
          "enum" => ["full", "window"],
          "default" => "full",
          "flowdrop" => [
            "connectable" => FALSE,
            "configurable" => TRUE,
            "required" => FALSE,
          ],
        ],
        "windowSize" => [
          "type" => "integer",
          "title" => "Window Size",
          "description" => "Number of recent messages to keep (for window strategy)",
          "default" => 20,
          "minimum" => 1,
          "maximum" => 100,
          "flowdrop" => [
            "connectable" => FALSE,
            "configurable" => TRUE,
            "required" => FALSE,
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "conversationId" => [
          "type" => "string",
          "title" => "Conversation ID",
          "description" => "The conversation identifier",
        ],
        "messages" => [
          "type" => "array",
          "title" => "Messages",
          "description" => "Conversation messages formatted for LLM",
        ],
        "messageCount" => [
          "type" => "integer",
          "title" => "Message Count",
          "description" => "Total number of messages in conversation",
        ],
        "found" => [
          "type" => "boolean",
          "title" => "Found",
          "description" => "Whether the conversation was found (get action)",
        ],
        "created" => [
          "type" => "boolean",
          "title" => "Created",
          "description" => "Whether a new conversation was created",
        ],
        "added" => [
          "type" => "boolean",
          "title" => "Added",
          "description" => "Whether a message was added (add action)",
        ],
        "cleared" => [
          "type" => "boolean",
          "title" => "Cleared",
          "description" => "Whether the history was cleared (clear action)",
        ],
        "deleted" => [
          "type" => "boolean",
          "title" => "Deleted",
          "description" => "Whether the conversation was deleted",
        ],
        "systemPrompt" => [
          "type" => "string",
          "title" => "System Prompt",
          "description" => "The system prompt if set",
        ],
        "metadata" => [
          "type" => "object",
          "title" => "Metadata",
          "description" => "Conversation metadata",
        ],
        "error" => [
          "type" => "string",
          "title" => "Error",
          "description" => "Error message if operation failed",
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return "conversation_history";
  }

}
