<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\DTO;

/**
 * Result of a tool execution.
 */
final class ToolResult {

  /**
   * Status: tool executed successfully.
   */
  public const STATUS_SUCCESS = 'success';

  /**
   * Status: tool execution failed.
   */
  public const STATUS_ERROR = 'error';

  /**
   * Status: tool execution was skipped.
   */
  public const STATUS_SKIPPED = 'skipped';

  /**
   * Constructs a new ToolResult object.
   *
   * @param string $toolCallId
   *   The ID of the tool call this responds to.
   * @param string $toolName
   *   The name of the tool.
   * @param string $nodeId
   *   The node ID that was executed.
   * @param string $status
   *   The execution status.
   * @param mixed $output
   *   The output from the tool.
   * @param string|null $errorMessage
   *   Error message if failed.
   * @param float $executionTimeMs
   *   Execution time in milliseconds.
   * @param \DateTimeImmutable $executedAt
   *   When the tool was executed.
   */
  public function __construct(
    private readonly string $toolCallId,
    private readonly string $toolName,
    private readonly string $nodeId,
    private readonly string $status,
    private readonly mixed $output,
    private readonly ?string $errorMessage = NULL,
    private readonly float $executionTimeMs = 0.0,
    private readonly \DateTimeImmutable $executedAt = new \DateTimeImmutable(),
  ) {}

  /**
   * Creates a successful result.
   *
   * @param string $toolCallId
   *   The tool call ID.
   * @param string $toolName
   *   The tool name.
   * @param string $nodeId
   *   The node ID.
   * @param mixed $output
   *   The output.
   * @param float $executionTimeMs
   *   Execution time.
   *
   * @return self
   *   A successful ToolResult.
   */
  public static function success(
    string $toolCallId,
    string $toolName,
    string $nodeId,
    mixed $output,
    float $executionTimeMs,
  ): self {
    return new self(
      toolCallId: $toolCallId,
      toolName: $toolName,
      nodeId: $nodeId,
      status: self::STATUS_SUCCESS,
      output: $output,
      executionTimeMs: $executionTimeMs,
    );
  }

  /**
   * Creates an error result.
   *
   * @param string $toolCallId
   *   The tool call ID.
   * @param string $toolName
   *   The tool name.
   * @param string $nodeId
   *   The node ID.
   * @param string $errorMessage
   *   The error message.
   * @param float $executionTimeMs
   *   Execution time.
   *
   * @return self
   *   An error ToolResult.
   */
  public static function error(
    string $toolCallId,
    string $toolName,
    string $nodeId,
    string $errorMessage,
    float $executionTimeMs,
  ): self {
    return new self(
      toolCallId: $toolCallId,
      toolName: $toolName,
      nodeId: $nodeId,
      status: self::STATUS_ERROR,
      output: NULL,
      errorMessage: $errorMessage,
      executionTimeMs: $executionTimeMs,
    );
  }

  /**
   * Creates a skipped result.
   *
   * @param string $toolCallId
   *   The tool call ID.
   * @param string $toolName
   *   The tool name.
   * @param string $nodeId
   *   The node ID.
   * @param string $reason
   *   The reason for skipping.
   *
   * @return self
   *   A skipped ToolResult.
   */
  public static function skipped(
    string $toolCallId,
    string $toolName,
    string $nodeId,
    string $reason,
  ): self {
    return new self(
      toolCallId: $toolCallId,
      toolName: $toolName,
      nodeId: $nodeId,
      status: self::STATUS_SKIPPED,
      output: NULL,
      errorMessage: $reason,
      executionTimeMs: 0.0,
    );
  }

  /**
   * Gets the tool call ID.
   *
   * @return string
   *   The tool call ID.
   */
  public function getToolCallId(): string {
    return $this->toolCallId;
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
   * Gets the node ID.
   *
   * @return string
   *   The node ID.
   */
  public function getNodeId(): string {
    return $this->nodeId;
  }

  /**
   * Gets the status.
   *
   * @return string
   *   The status.
   */
  public function getStatus(): string {
    return $this->status;
  }

  /**
   * Gets the output.
   *
   * @return mixed
   *   The output.
   */
  public function getOutput(): mixed {
    return $this->output;
  }

  /**
   * Gets the error message.
   *
   * @return string|null
   *   The error message or NULL.
   */
  public function getErrorMessage(): ?string {
    return $this->errorMessage;
  }

  /**
   * Gets the execution time in milliseconds.
   *
   * @return float
   *   The execution time.
   */
  public function getExecutionTimeMs(): float {
    return $this->executionTimeMs;
  }

  /**
   * Gets the execution timestamp.
   *
   * @return \DateTimeImmutable
   *   The timestamp.
   */
  public function getExecutedAt(): \DateTimeImmutable {
    return $this->executedAt;
  }

  /**
   * Checks if the result was successful.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function isSuccess(): bool {
    return $this->status === self::STATUS_SUCCESS;
  }

  /**
   * Checks if the result was an error.
   *
   * @return bool
   *   TRUE if error.
   */
  public function isError(): bool {
    return $this->status === self::STATUS_ERROR;
  }

  /**
   * Checks if the result was skipped.
   *
   * @return bool
   *   TRUE if skipped.
   */
  public function isSkipped(): bool {
    return $this->status === self::STATUS_SKIPPED;
  }

  /**
   * Gets the output as string for LLM.
   *
   * @return string
   *   The output formatted for LLM.
   */
  public function getOutputForLlm(): string {
    if ($this->isError()) {
      return "Error: {$this->errorMessage}";
    }

    if ($this->isSkipped()) {
      return "Skipped: {$this->errorMessage}";
    }

    if (is_string($this->output)) {
      return $this->output;
    }

    return json_encode($this->output) ?: '';
  }

  /**
   * Converts to array format.
   *
   * @return array<string, mixed>
   *   The result as array.
   */
  public function toArray(): array {
    return [
      'tool_call_id' => $this->toolCallId,
      'tool_name' => $this->toolName,
      'node_id' => $this->nodeId,
      'status' => $this->status,
      'output' => $this->output,
      'error_message' => $this->errorMessage,
      'execution_time_ms' => $this->executionTimeMs,
      'executed_at' => $this->executedAt->format(\DateTimeInterface::RFC3339_EXTENDED),
    ];
  }

  /**
   * Creates from array.
   *
   * @param array<string, mixed> $data
   *   The array data.
   *
   * @return self
   *   A new ToolResult instance.
   */
  public static function fromArray(array $data): self {
    return new self(
      toolCallId: $data['tool_call_id'] ?? '',
      toolName: $data['tool_name'] ?? '',
      nodeId: $data['node_id'] ?? '',
      status: $data['status'] ?? self::STATUS_SUCCESS,
      output: $data['output'] ?? NULL,
      errorMessage: $data['error_message'] ?? NULL,
      executionTimeMs: $data['execution_time_ms'] ?? 0.0,
      executedAt: isset($data['executed_at'])
        ? new \DateTimeImmutable($data['executed_at'])
        : new \DateTimeImmutable(),
    );
  }

}
