<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\Exception;

/**
 * Base exception for Iterator-related errors.
 *
 * This exception is thrown when general iterator execution fails.
 */
class IteratorException extends \RuntimeException {

  /**
   * The iterator node ID that caused the exception.
   */
  protected ?string $iteratorNodeId = NULL;

  /**
   * Additional context data for the exception.
   *
   * @var array<string, mixed>
   */
  protected array $context = [];

  /**
   * Set the iterator node ID.
   *
   * @param string $nodeId
   *   The iterator node ID.
   *
   * @return $this
   */
  public function setIteratorNodeId(string $nodeId): self {
    $this->iteratorNodeId = $nodeId;
    return $this;
  }

  /**
   * Get the iterator node ID.
   *
   * @return string|null
   *   The iterator node ID, or NULL if not set.
   */
  public function getIteratorNodeId(): ?string {
    return $this->iteratorNodeId;
  }

  /**
   * Set additional context data.
   *
   * @param array<string, mixed> $context
   *   The context data.
   *
   * @return $this
   */
  public function setContext(array $context): self {
    $this->context = $context;
    return $this;
  }

  /**
   * Get additional context data.
   *
   * @return array<string, mixed>
   *   The context data.
   */
  public function getContext(): array {
    return $this->context;
  }

  /**
   * Create exception with iterator node ID.
   *
   * @param string $message
   *   The exception message.
   * @param string $nodeId
   *   The iterator node ID.
   * @param \Throwable|null $previous
   *   Previous exception.
   *
   * @return self
   *   New exception instance.
   */
  public static function forNode(string $message, string $nodeId, ?\Throwable $previous = NULL): self {
    $exception = new self($message, 0, $previous);
    $exception->setIteratorNodeId($nodeId);
    return $exception;
  }

}
