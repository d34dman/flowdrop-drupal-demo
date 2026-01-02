<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\Exception;

/**
 * Exception thrown when sub-workflow detection fails.
 *
 * This exception is thrown when the system cannot properly detect
 * or validate the sub-workflow for an iterator node.
 */
class SubWorkflowDetectionException extends IteratorException {

  /**
   * The detection errors encountered.
   *
   * @var array<int, string>
   */
  protected array $detectionErrors = [];

  /**
   * Set detection errors.
   *
   * @param array<int, string> $errors
   *   The detection errors.
   *
   * @return $this
   */
  public function setDetectionErrors(array $errors): self {
    $this->detectionErrors = $errors;
    return $this;
  }

  /**
   * Get detection errors.
   *
   * @return array<int, string>
   *   The detection errors.
   */
  public function getDetectionErrors(): array {
    return $this->detectionErrors;
  }

  /**
   * Create exception for missing item port connection.
   *
   * @param string $nodeId
   *   The iterator node ID.
   *
   * @return self
   *   New exception instance.
   */
  public static function noItemPortConnection(string $nodeId): self {
    $message = sprintf(
      "Iterator node '%s' has no connections from its 'item' output port",
      $nodeId
    );

    $exception = new self($message);
    $exception->setIteratorNodeId($nodeId);
    $exception->setDetectionErrors(["No item port connections"]);

    return $exception;
  }

  /**
   * Create exception for missing loopback edge.
   *
   * @param string $nodeId
   *   The iterator node ID.
   *
   * @return self
   *   New exception instance.
   */
  public static function noLoopbackEdge(string $nodeId): self {
    $message = sprintf(
      "Iterator node '%s' has no loopback edge to its 'loopback' input port",
      $nodeId
    );

    $exception = new self($message);
    $exception->setIteratorNodeId($nodeId);
    $exception->setDetectionErrors(["No loopback edge found"]);

    return $exception;
  }

  /**
   * Create exception for empty sub-workflow.
   *
   * @param string $nodeId
   *   The iterator node ID.
   *
   * @return self
   *   New exception instance.
   */
  public static function emptySubWorkflow(string $nodeId): self {
    $message = sprintf(
      "Iterator node '%s' has no nodes in its sub-workflow",
      $nodeId
    );

    $exception = new self($message);
    $exception->setIteratorNodeId($nodeId);
    $exception->setDetectionErrors(["Empty sub-workflow"]);

    return $exception;
  }

  /**
   * Create exception with multiple detection errors.
   *
   * @param string $nodeId
   *   The iterator node ID.
   * @param array<int, string> $errors
   *   The detection errors.
   *
   * @return self
   *   New exception instance.
   */
  public static function withErrors(string $nodeId, array $errors): self {
    $message = sprintf(
      "Sub-workflow detection failed for iterator '%s': %s",
      $nodeId,
      implode("; ", $errors)
    );

    $exception = new self($message);
    $exception->setIteratorNodeId($nodeId);
    $exception->setDetectionErrors($errors);

    return $exception;
  }

}
