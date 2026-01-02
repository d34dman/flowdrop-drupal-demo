<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\Exception;

/**
 * Exception thrown when maximum iterations limit is exceeded.
 *
 * This is typically a warning rather than a hard error, as the system
 * will truncate the input array to the maximum allowed iterations.
 */
class MaxIterationsExceededException extends IteratorException {

  /**
   * The requested number of iterations.
   */
  protected int $requestedIterations = 0;

  /**
   * The maximum allowed iterations.
   */
  protected int $maxIterations = 0;

  /**
   * Set the requested iterations count.
   *
   * @param int $count
   *   The requested iterations count.
   *
   * @return $this
   */
  public function setRequestedIterations(int $count): self {
    $this->requestedIterations = $count;
    return $this;
  }

  /**
   * Get the requested iterations count.
   *
   * @return int
   *   The requested iterations count.
   */
  public function getRequestedIterations(): int {
    return $this->requestedIterations;
  }

  /**
   * Set the maximum allowed iterations.
   *
   * @param int $max
   *   The maximum allowed iterations.
   *
   * @return $this
   */
  public function setMaxIterations(int $max): self {
    $this->maxIterations = $max;
    return $this;
  }

  /**
   * Get the maximum allowed iterations.
   *
   * @return int
   *   The maximum allowed iterations.
   */
  public function getMaxIterations(): int {
    return $this->maxIterations;
  }

  /**
   * Create exception for exceeded iterations.
   *
   * @param string $nodeId
   *   The iterator node ID.
   * @param int $requested
   *   The requested number of iterations.
   * @param int $max
   *   The maximum allowed iterations.
   *
   * @return self
   *   New exception instance.
   */
  public static function exceeded(string $nodeId, int $requested, int $max): self {
    $message = sprintf(
      "Iterator '%s' received %d items but maximum is %d. Input will be truncated.",
      $nodeId,
      $requested,
      $max
    );

    $exception = new self($message);
    $exception->setIteratorNodeId($nodeId);
    $exception->setRequestedIterations($requested);
    $exception->setMaxIterations($max);

    return $exception;
  }

}
