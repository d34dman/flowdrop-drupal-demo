<?php

declare(strict_types=1);

namespace Drupal\flowdrop_conversation\DTO;

/**
 * Represents a single message in a conversation.
 *
 * Messages can be from different roles: system, user, assistant, or tool.
 * This DTO is immutable - all modifications return new instances.
 */
final class Message {

  /**
   * System message role - sets context for the conversation.
   */
  public const ROLE_SYSTEM = 'system';

  /**
   * User message role - input from the user.
   */
  public const ROLE_USER = 'user';

  /**
   * Assistant message role - response from the AI.
   */
  public const ROLE_ASSISTANT = 'assistant';

  /**
   * Tool message role - result from a tool execution.
   */
  public const ROLE_TOOL = 'tool';

  /**
   * Constructs a new Message object.
   *
   * @param string $id
   *   Unique message identifier.
   * @param string $role
   *   The role of the message sender.
   * @param string $content
   *   The message content.
   * @param \Drupal\flowdrop_conversation\DTO\ToolCall|null $toolCall
   *   Tool call request (for assistant messages).
   * @param string|null $toolCallId
   *   ID of the tool call this message responds to (for tool messages).
   * @param \DateTimeImmutable $timestamp
   *   When the message was created.
   * @param array<string, mixed> $metadata
   *   Additional metadata.
   */
  public function __construct(
    private readonly string $id,
    private readonly string $role,
    private readonly string $content,
    private readonly ?ToolCall $toolCall = NULL,
    private readonly ?string $toolCallId = NULL,
    private readonly \DateTimeImmutable $timestamp = new \DateTimeImmutable(),
    private readonly array $metadata = [],
  ) {}

  /**
   * Creates a user message.
   *
   * @param string $content
   *   The message content.
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   A new user message.
   */
  public static function user(string $content, array $metadata = []): self {
    return new self(
      id: self::generateId(),
      role: self::ROLE_USER,
      content: $content,
      metadata: $metadata,
    );
  }

  /**
   * Creates an assistant message.
   *
   * @param string $content
   *   The message content.
   * @param \Drupal\flowdrop_conversation\DTO\ToolCall|null $toolCall
   *   Optional tool call request.
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   A new assistant message.
   */
  public static function assistant(
    string $content,
    ?ToolCall $toolCall = NULL,
    array $metadata = [],
  ): self {
    return new self(
      id: self::generateId(),
      role: self::ROLE_ASSISTANT,
      content: $content,
      toolCall: $toolCall,
      metadata: $metadata,
    );
  }

  /**
   * Creates a tool result message.
   *
   * @param string $toolCallId
   *   The ID of the tool call this responds to.
   * @param string $content
   *   The tool result content (usually JSON).
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   A new tool message.
   */
  public static function tool(
    string $toolCallId,
    string $content,
    array $metadata = [],
  ): self {
    return new self(
      id: self::generateId(),
      role: self::ROLE_TOOL,
      content: $content,
      toolCallId: $toolCallId,
      metadata: $metadata,
    );
  }

  /**
   * Creates a system message.
   *
   * @param string $content
   *   The system prompt content.
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   A new system message.
   */
  public static function system(string $content, array $metadata = []): self {
    return new self(
      id: self::generateId(),
      role: self::ROLE_SYSTEM,
      content: $content,
      metadata: $metadata,
    );
  }

  /**
   * Gets the message ID.
   *
   * @return string
   *   The message ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Gets the message role.
   *
   * @return string
   *   The role (system, user, assistant, or tool).
   */
  public function getRole(): string {
    return $this->role;
  }

  /**
   * Gets the message content.
   *
   * @return string
   *   The message content.
   */
  public function getContent(): string {
    return $this->content;
  }

  /**
   * Gets the tool call if present.
   *
   * @return \Drupal\flowdrop_conversation\DTO\ToolCall|null
   *   The tool call or NULL.
   */
  public function getToolCall(): ?ToolCall {
    return $this->toolCall;
  }

  /**
   * Gets the tool call ID this message responds to.
   *
   * @return string|null
   *   The tool call ID or NULL.
   */
  public function getToolCallId(): ?string {
    return $this->toolCallId;
  }

  /**
   * Gets the message timestamp.
   *
   * @return \DateTimeImmutable
   *   The timestamp.
   */
  public function getTimestamp(): \DateTimeImmutable {
    return $this->timestamp;
  }

  /**
   * Gets the message metadata.
   *
   * @return array<string, mixed>
   *   The metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Checks if this is a user message.
   *
   * @return bool
   *   TRUE if user message.
   */
  public function isUser(): bool {
    return $this->role === self::ROLE_USER;
  }

  /**
   * Checks if this is an assistant message.
   *
   * @return bool
   *   TRUE if assistant message.
   */
  public function isAssistant(): bool {
    return $this->role === self::ROLE_ASSISTANT;
  }

  /**
   * Checks if this is a tool message.
   *
   * @return bool
   *   TRUE if tool message.
   */
  public function isTool(): bool {
    return $this->role === self::ROLE_TOOL;
  }

  /**
   * Checks if this is a system message.
   *
   * @return bool
   *   TRUE if system message.
   */
  public function isSystem(): bool {
    return $this->role === self::ROLE_SYSTEM;
  }

  /**
   * Checks if this message contains a tool call.
   *
   * @return bool
   *   TRUE if contains tool call.
   */
  public function hasToolCall(): bool {
    return $this->toolCall !== NULL;
  }

  /**
   * Converts the message to array format for LLM.
   *
   * @return array<string, mixed>
   *   The message in LLM-compatible format.
   */
  public function toArrayForLlm(): array {
    $result = [
      'role' => $this->role,
      'content' => $this->content,
    ];

    if ($this->toolCall !== NULL) {
      $result['tool_calls'] = [$this->toolCall->toArrayForLlm()];
    }

    if ($this->toolCallId !== NULL) {
      $result['tool_call_id'] = $this->toolCallId;
    }

    return $result;
  }

  /**
   * Converts the message to array format for storage.
   *
   * @return array<string, mixed>
   *   The message as array.
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'role' => $this->role,
      'content' => $this->content,
      'tool_call' => $this->toolCall?->toArray(),
      'tool_call_id' => $this->toolCallId,
      'timestamp' => $this->timestamp->format(\DateTimeInterface::RFC3339_EXTENDED),
      'metadata' => $this->metadata,
    ];
  }

  /**
   * Creates a Message from array data.
   *
   * @param array<string, mixed> $data
   *   The array data.
   *
   * @return self
   *   A new Message instance.
   */
  public static function fromArray(array $data): self {
    return new self(
      id: $data['id'] ?? self::generateId(),
      role: $data['role'],
      content: $data['content'] ?? '',
      toolCall: isset($data['tool_call']) ? ToolCall::fromArray($data['tool_call']) : NULL,
      toolCallId: $data['tool_call_id'] ?? NULL,
      timestamp: isset($data['timestamp'])
        ? new \DateTimeImmutable($data['timestamp'])
        : new \DateTimeImmutable(),
      metadata: $data['metadata'] ?? [],
    );
  }

  /**
   * Generates a unique message ID.
   *
   * @return string
   *   A unique ID.
   */
  private static function generateId(): string {
    return 'msg_' . bin2hex(random_bytes(12));
  }

}
