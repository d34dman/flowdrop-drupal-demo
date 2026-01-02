<?php

declare(strict_types=1);

namespace Drupal\flowdrop_conversation\DTO;

/**
 * Manages the state of a conversation.
 *
 * This DTO stores the complete state of a conversation including all messages,
 * metadata, and timestamps. It provides methods for adding messages and
 * retrieving conversation history in various formats.
 *
 * The class is immutable - all modification methods return new instances.
 */
final class ConversationState {

  /**
   * Constructs a new ConversationState object.
   *
   * @param string $conversationId
   *   Unique conversation identifier.
   * @param array<Message> $messages
   *   List of messages in the conversation.
   * @param array<string, mixed> $metadata
   *   Additional metadata about the conversation.
   * @param \DateTimeImmutable $createdAt
   *   When the conversation was created.
   * @param \DateTimeImmutable $updatedAt
   *   When the conversation was last updated.
   */
  public function __construct(
    private readonly string $conversationId,
    private readonly array $messages = [],
    private readonly array $metadata = [],
    private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    private readonly \DateTimeImmutable $updatedAt = new \DateTimeImmutable(),
  ) {}

  /**
   * Creates a new conversation.
   *
   * @param string|null $systemPrompt
   *   Optional system prompt to start the conversation.
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   A new ConversationState instance.
   */
  public static function create(?string $systemPrompt = NULL, array $metadata = []): self {
    $messages = [];
    if ($systemPrompt !== NULL && $systemPrompt !== '') {
      $messages[] = Message::system($systemPrompt);
    }

    return new self(
      conversationId: self::generateId(),
      messages: $messages,
      metadata: $metadata,
    );
  }

  /**
   * Gets the conversation ID.
   *
   * @return string
   *   The conversation ID.
   */
  public function getConversationId(): string {
    return $this->conversationId;
  }

  /**
   * Gets all messages.
   *
   * @return array<Message>
   *   The messages.
   */
  public function getMessages(): array {
    return $this->messages;
  }

  /**
   * Gets the message count.
   *
   * @return int
   *   Number of messages.
   */
  public function getMessageCount(): int {
    return count($this->messages);
  }

  /**
   * Gets the metadata.
   *
   * @return array<string, mixed>
   *   The metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Gets a specific metadata value.
   *
   * @param string $key
   *   The metadata key.
   * @param mixed $default
   *   Default value if not found.
   *
   * @return mixed
   *   The metadata value.
   */
  public function getMetadataValue(string $key, mixed $default = NULL): mixed {
    return $this->metadata[$key] ?? $default;
  }

  /**
   * Gets the creation timestamp.
   *
   * @return \DateTimeImmutable
   *   The creation timestamp.
   */
  public function getCreatedAt(): \DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Gets the last update timestamp.
   *
   * @return \DateTimeImmutable
   *   The update timestamp.
   */
  public function getUpdatedAt(): \DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Gets the last message.
   *
   * @return \Drupal\flowdrop_conversation\DTO\Message|null
   *   The last message or NULL if empty.
   */
  public function getLastMessage(): ?Message {
    if (empty($this->messages)) {
      return NULL;
    }
    return $this->messages[count($this->messages) - 1];
  }

  /**
   * Gets the system prompt if present.
   *
   * @return string|null
   *   The system prompt or NULL.
   */
  public function getSystemPrompt(): ?string {
    foreach ($this->messages as $message) {
      if ($message->isSystem()) {
        return $message->getContent();
      }
    }
    return NULL;
  }

  /**
   * Adds a user message.
   *
   * @param string $content
   *   The message content.
   *
   * @return self
   *   A new ConversationState with the message added.
   */
  public function addUserMessage(string $content): self {
    return $this->addMessage(Message::user($content));
  }

  /**
   * Adds an assistant message.
   *
   * @param string $content
   *   The message content.
   * @param \Drupal\flowdrop_conversation\DTO\ToolCall|null $toolCall
   *   Optional tool call.
   *
   * @return self
   *   A new ConversationState with the message added.
   */
  public function addAssistantMessage(string $content, ?ToolCall $toolCall = NULL): self {
    return $this->addMessage(Message::assistant($content, $toolCall));
  }

  /**
   * Adds a tool call message.
   *
   * @param \Drupal\flowdrop_conversation\DTO\ToolCall $toolCall
   *   The tool call.
   *
   * @return self
   *   A new ConversationState with the message added.
   */
  public function addToolCall(ToolCall $toolCall): self {
    return $this->addMessage(Message::assistant('', $toolCall));
  }

  /**
   * Adds a tool result message.
   *
   * @param string $toolCallId
   *   The ID of the tool call this responds to.
   * @param mixed $result
   *   The tool result (will be JSON encoded if not string).
   *
   * @return self
   *   A new ConversationState with the message added.
   */
  public function addToolResult(string $toolCallId, mixed $result): self {
    $content = is_string($result) ? $result : json_encode($result);
    return $this->addMessage(Message::tool($toolCallId, $content));
  }

  /**
   * Adds a message to the conversation.
   *
   * @param \Drupal\flowdrop_conversation\DTO\Message $message
   *   The message to add.
   *
   * @return self
   *   A new ConversationState with the message added.
   */
  public function addMessage(Message $message): self {
    $newMessages = $this->messages;
    $newMessages[] = $message;

    return new self(
      conversationId: $this->conversationId,
      messages: $newMessages,
      metadata: $this->metadata,
      createdAt: $this->createdAt,
      updatedAt: new \DateTimeImmutable(),
    );
  }

  /**
   * Updates metadata.
   *
   * @param array<string, mixed> $metadata
   *   Metadata to merge.
   *
   * @return self
   *   A new ConversationState with updated metadata.
   */
  public function withMetadata(array $metadata): self {
    return new self(
      conversationId: $this->conversationId,
      messages: $this->messages,
      metadata: array_merge($this->metadata, $metadata),
      createdAt: $this->createdAt,
      updatedAt: new \DateTimeImmutable(),
    );
  }

  /**
   * Gets messages formatted for LLM.
   *
   * @return array<array{role: string, content: string}>
   *   Messages in LLM-compatible format.
   */
  public function getMessagesForLlm(): array {
    return array_map(
      fn(Message $msg) => $msg->toArrayForLlm(),
      $this->messages
    );
  }

  /**
   * Gets recent messages with a window.
   *
   * @param int $windowSize
   *   Number of recent messages to return.
   * @param bool $keepSystem
   *   Whether to always include system message.
   *
   * @return array<Message>
   *   The recent messages.
   */
  public function getRecentMessages(int $windowSize, bool $keepSystem = TRUE): array {
    if (count($this->messages) <= $windowSize) {
      return $this->messages;
    }

    $result = [];

    // Always include system message if present and requested.
    if ($keepSystem) {
      foreach ($this->messages as $message) {
        if ($message->isSystem()) {
          $result[] = $message;
          break;
        }
      }
    }

    // Get the most recent messages.
    $recentStart = max(0, count($this->messages) - $windowSize);
    $recentMessages = array_slice($this->messages, $recentStart);

    // Filter out system message from recent if already included.
    if ($keepSystem && !empty($result)) {
      $recentMessages = array_filter(
        $recentMessages,
        fn(Message $msg) => !$msg->isSystem()
      );
    }

    return array_merge($result, array_values($recentMessages));
  }

  /**
   * Checks if the conversation is empty (no non-system messages).
   *
   * @return bool
   *   TRUE if empty.
   */
  public function isEmpty(): bool {
    foreach ($this->messages as $message) {
      if (!$message->isSystem()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Converts to array format for storage.
   *
   * @return array<string, mixed>
   *   The conversation state as array.
   */
  public function toArray(): array {
    return [
      'conversation_id' => $this->conversationId,
      'messages' => array_map(fn(Message $msg) => $msg->toArray(), $this->messages),
      'metadata' => $this->metadata,
      'created_at' => $this->createdAt->format(\DateTimeInterface::RFC3339_EXTENDED),
      'updated_at' => $this->updatedAt->format(\DateTimeInterface::RFC3339_EXTENDED),
    ];
  }

  /**
   * Creates a ConversationState from array data.
   *
   * @param array<string, mixed> $data
   *   The array data.
   *
   * @return self
   *   A new ConversationState instance.
   */
  public static function fromArray(array $data): self {
    $messages = array_map(
      fn(array $msgData) => Message::fromArray($msgData),
      $data['messages'] ?? []
    );

    return new self(
      conversationId: $data['conversation_id'] ?? self::generateId(),
      messages: $messages,
      metadata: $data['metadata'] ?? [],
      createdAt: isset($data['created_at'])
        ? new \DateTimeImmutable($data['created_at'])
        : new \DateTimeImmutable(),
      updatedAt: isset($data['updated_at'])
        ? new \DateTimeImmutable($data['updated_at'])
        : new \DateTimeImmutable(),
    );
  }

  /**
   * Generates a unique conversation ID.
   *
   * @return string
   *   A unique ID.
   */
  private static function generateId(): string {
    return 'conv_' . bin2hex(random_bytes(12));
  }

}
