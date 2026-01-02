<?php

declare(strict_types=1);

namespace Drupal\flowdrop_conversation\DTO;

/**
 * Represents a tool call request from the LLM.
 *
 * This DTO captures the details of a tool invocation requested by an AI model,
 * including the tool name, arguments, and timing information.
 */
final class ToolCall {

  /**
   * Constructs a new ToolCall object.
   *
   * @param string $id
   *   Unique identifier for this tool call.
   * @param string $toolName
   *   The name of the tool to call.
   * @param array<string, mixed> $arguments
   *   Arguments to pass to the tool.
   * @param \DateTimeImmutable $requestedAt
   *   When the tool call was requested.
   */
  public function __construct(
    private readonly string $id,
    private readonly string $toolName,
    private readonly array $arguments,
    private readonly \DateTimeImmutable $requestedAt = new \DateTimeImmutable(),
  ) {}

  /**
   * Creates a new ToolCall.
   *
   * @param string $toolName
   *   The name of the tool to call.
   * @param array<string, mixed> $arguments
   *   Arguments to pass to the tool.
   *
   * @return self
   *   A new ToolCall instance.
   */
  public static function create(string $toolName, array $arguments = []): self {
    return new self(
      id: self::generateId(),
      toolName: $toolName,
      arguments: $arguments,
    );
  }

  /**
   * Gets the tool call ID.
   *
   * @return string
   *   The tool call ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Gets the tool name.
   *
   * @return string
   *   The tool name.
   */
  public function getToolName(): string {
    return $this->toolName;
  }

  /**
   * Gets the tool arguments.
   *
   * @return array<string, mixed>
   *   The arguments.
   */
  public function getArguments(): array {
    return $this->arguments;
  }

  /**
   * Gets a specific argument value.
   *
   * @param string $key
   *   The argument key.
   * @param mixed $default
   *   Default value if not found.
   *
   * @return mixed
   *   The argument value.
   */
  public function getArgument(string $key, mixed $default = NULL): mixed {
    return $this->arguments[$key] ?? $default;
  }

  /**
   * Gets the timestamp when this call was requested.
   *
   * @return \DateTimeImmutable
   *   The timestamp.
   */
  public function getRequestedAt(): \DateTimeImmutable {
    return $this->requestedAt;
  }

  /**
   * Converts to array format for LLM (OpenAI-compatible).
   *
   * @return array<string, mixed>
   *   The tool call in LLM format.
   */
  public function toArrayForLlm(): array {
    return [
      'id' => $this->id,
      'type' => 'function',
      'function' => [
        'name' => $this->toolName,
        'arguments' => json_encode($this->arguments),
      ],
    ];
  }

  /**
   * Converts to array format for storage.
   *
   * @return array<string, mixed>
   *   The tool call as array.
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'tool_name' => $this->toolName,
      'arguments' => $this->arguments,
      'requested_at' => $this->requestedAt->format(\DateTimeInterface::RFC3339_EXTENDED),
    ];
  }

  /**
   * Creates a ToolCall from array data.
   *
   * @param array<string, mixed> $data
   *   The array data.
   *
   * @return self
   *   A new ToolCall instance.
   */
  public static function fromArray(array $data): self {
    return new self(
      id: $data['id'] ?? self::generateId(),
      toolName: $data['tool_name'] ?? $data['toolName'] ?? '',
      arguments: $data['arguments'] ?? [],
      requestedAt: isset($data['requested_at'])
        ? new \DateTimeImmutable($data['requested_at'])
        : new \DateTimeImmutable(),
    );
  }

  /**
   * Creates from OpenAI function call format.
   *
   * @param array<string, mixed> $data
   *   OpenAI tool call data.
   *
   * @return self
   *   A new ToolCall instance.
   */
  public static function fromOpenAiFormat(array $data): self {
    $function = $data['function'] ?? [];
    $arguments = [];

    if (isset($function['arguments'])) {
      $arguments = is_string($function['arguments'])
        ? json_decode($function['arguments'], TRUE) ?? []
        : $function['arguments'];
    }

    return new self(
      id: $data['id'] ?? self::generateId(),
      toolName: $function['name'] ?? '',
      arguments: $arguments,
    );
  }

  /**
   * Creates from Anthropic tool use format.
   *
   * @param array<string, mixed> $data
   *   Anthropic tool use data.
   *
   * @return self
   *   A new ToolCall instance.
   */
  public static function fromAnthropicFormat(array $data): self {
    return new self(
      id: $data['id'] ?? self::generateId(),
      toolName: $data['name'] ?? '',
      arguments: $data['input'] ?? [],
    );
  }

  /**
   * Generates a unique tool call ID.
   *
   * @return string
   *   A unique ID.
   */
  private static function generateId(): string {
    return 'call_' . bin2hex(random_bytes(12));
  }

}
