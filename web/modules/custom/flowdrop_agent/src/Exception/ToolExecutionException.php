<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\Exception;

/**
 * Exception thrown when tool execution fails.
 */
class ToolExecutionException extends AgentException {

  /**
   * The tool name that failed.
   *
   * @var string
   */
  protected string $toolName;

  /**
   * The node ID that failed.
   *
   * @var string
   */
  protected string $nodeId;

  /**
   * Constructs a new ToolExecutionException.
   *
   * @param string $message
   *   The error message.
   * @param string $toolName
   *   The tool name.
   * @param string $nodeId
   *   The node ID.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(
    string $message,
    string $toolName = '',
    string $nodeId = '',
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, 0, $previous);
    $this->toolName = $toolName;
    $this->nodeId = $nodeId;
  }

  /**
   * Creates exception for tool execution failure.
   *
   * @param string $toolName
   *   The tool name.
   * @param string $nodeId
   *   The node ID.
   * @param string $error
   *   The error message.
   * @param \Throwable|null $previous
   *   The previous exception.
   *
   * @return self
   *   A new exception instance.
   */
  public static function executionFailed(
    string $toolName,
    string $nodeId,
    string $error,
    ?\Throwable $previous = NULL,
  ): self {
    return new self(
      "Tool '{$toolName}' (node: {$nodeId}) execution failed: {$error}",
      $toolName,
      $nodeId,
      $previous
    );
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

}
