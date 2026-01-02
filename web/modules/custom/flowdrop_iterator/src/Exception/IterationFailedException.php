<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\Exception;

/**
 * Exception thrown when a single iteration fails.
 *
 * This exception contains information about which iteration failed
 * and the item that was being processed.
 */
class IterationFailedException extends IteratorException {

  /**
   * The iteration index that failed.
   */
  protected int $iterationIndex = 0;

  /**
   * The item that was being processed when failure occurred.
   */
  protected mixed $failedItem = NULL;

  /**
   * Set the iteration index.
   *
   * @param int $index
   *   The iteration index (0-based).
   *
   * @return $this
   */
  public function setIterationIndex(int $index): self {
    $this->iterationIndex = $index;
    return $this;
  }

  /**
   * Get the iteration index.
   *
   * @return int
   *   The iteration index.
   */
  public function getIterationIndex(): int {
    return $this->iterationIndex;
  }

  /**
   * Set the failed item.
   *
   * @param mixed $item
   *   The item that was being processed.
   *
   * @return $this
   */
  public function setFailedItem(mixed $item): self {
    $this->failedItem = $item;
    return $this;
  }

  /**
   * Get the failed item.
   *
   * @return mixed
   *   The item that was being processed.
   */
  public function getFailedItem(): mixed {
    return $this->failedItem;
  }

  /**
   * Create exception for a specific iteration.
   *
   * @param string $message
   *   The exception message.
   * @param string $nodeId
   *   The iterator node ID.
   * @param int $index
   *   The iteration index.
   * @param mixed $item
   *   The item being processed.
   * @param \Throwable|null $previous
   *   Previous exception.
   *
   * @return self
   *   New exception instance.
   */
  public static function forIteration(
    string $message,
    string $nodeId,
    int $index,
    mixed $item = NULL,
    ?\Throwable $previous = NULL,
  ): self {
    $fullMessage = sprintf(
      "%s (iteration %d of iterator %s)",
      $message,
      $index,
      $nodeId
    );

    $exception = new self($fullMessage, 0, $previous);
    $exception->setIteratorNodeId($nodeId);
    $exception->setIterationIndex($index);
    $exception->setFailedItem($item);

    return $exception;
  }

}
