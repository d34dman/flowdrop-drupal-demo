<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\DTO;

/**
 * Represents the state of an Iterator during execution.
 *
 * This immutable DTO tracks:
 * - The items being iterated
 * - Current iteration index
 * - Accumulated results from each iteration
 * - Execution status
 * - Child pipeline reference
 * - Errors encountered during iteration.
 */
final class IteratorState {

  /**
   * Status: Iterator is pending execution.
   */
  public const STATUS_PENDING = "pending";

  /**
   * Status: Iterator is currently iterating.
   */
  public const STATUS_ITERATING = "iterating";

  /**
   * Status: Iterator completed successfully.
   */
  public const STATUS_COMPLETED = "completed";

  /**
   * Status: Iterator failed.
   */
  public const STATUS_FAILED = "failed";

  /**
   * Constructs an IteratorState instance.
   *
   * @param string $iteratorNodeId
   *   The iterator node ID.
   * @param array<int, mixed> $items
   *   The items to iterate over.
   * @param int $currentIndex
   *   The current iteration index (0-based).
   * @param array<int, mixed> $accumulatedResults
   *   Results accumulated from completed iterations.
   * @param string $status
   *   The current status.
   * @param string|null $childPipelineId
   *   The child pipeline ID, if created.
   * @param array<int, string> $errors
   *   Errors encountered during iteration.
   */
  public function __construct(
    private readonly string $iteratorNodeId,
    private readonly array $items,
    private readonly int $currentIndex = 0,
    private readonly array $accumulatedResults = [],
    private readonly string $status = self::STATUS_PENDING,
    private readonly ?string $childPipelineId = NULL,
    private readonly array $errors = [],
  ) {}

  /**
   * Get iterator node ID.
   *
   * @return string
   *   The iterator node ID.
   */
  public function getIteratorNodeId(): string {
    return $this->iteratorNodeId;
  }

  /**
   * Get all items to iterate.
   *
   * @return array<int, mixed>
   *   The items array.
   */
  public function getItems(): array {
    return $this->items;
  }

  /**
   * Get current iteration index.
   *
   * @return int
   *   The current index (0-based).
   */
  public function getCurrentIndex(): int {
    return $this->currentIndex;
  }

  /**
   * Get current item being processed.
   *
   * @return mixed
   *   The current item, or NULL if index is out of bounds.
   */
  public function getCurrentItem(): mixed {
    return $this->items[$this->currentIndex] ?? NULL;
  }

  /**
   * Get total item count.
   *
   * @return int
   *   The total number of items.
   */
  public function getTotalCount(): int {
    return count($this->items);
  }

  /**
   * Check if there are more items to process.
   *
   * @return bool
   *   TRUE if there are more items.
   */
  public function hasMoreItems(): bool {
    return $this->currentIndex < count($this->items);
  }

  /**
   * Get accumulated results.
   *
   * @return array<int, mixed>
   *   The accumulated results array.
   */
  public function getAccumulatedResults(): array {
    return $this->accumulatedResults;
  }

  /**
   * Get current status.
   *
   * @return string
   *   The status constant.
   */
  public function getStatus(): string {
    return $this->status;
  }

  /**
   * Get child pipeline ID.
   *
   * @return string|null
   *   The child pipeline ID, or NULL if not created.
   */
  public function getChildPipelineId(): ?string {
    return $this->childPipelineId;
  }

  /**
   * Get errors encountered during iteration.
   *
   * @return array<int, string>
   *   The errors array.
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * Check if iteration is complete.
   *
   * @return bool
   *   TRUE if completed or failed.
   */
  public function isComplete(): bool {
    return $this->status === self::STATUS_COMPLETED ||
           $this->status === self::STATUS_FAILED;
  }

  /**
   * Check if iteration has errors.
   *
   * @return bool
   *   TRUE if errors exist.
   */
  public function hasErrors(): bool {
    return !empty($this->errors);
  }

  /**
   * Create state for next iteration with result.
   *
   * @param mixed $result
   *   The result from current iteration.
   *
   * @return self
   *   New state instance with incremented index and accumulated result.
   */
  public function withNextIteration(mixed $result): self {
    return new self(
      iteratorNodeId: $this->iteratorNodeId,
      items: $this->items,
      currentIndex: $this->currentIndex + 1,
      accumulatedResults: [...$this->accumulatedResults, $result],
      status: self::STATUS_ITERATING,
      childPipelineId: $this->childPipelineId,
      errors: $this->errors,
    );
  }

  /**
   * Create completed state.
   *
   * @return self
   *   New state instance with completed status.
   */
  public function withCompleted(): self {
    return new self(
      iteratorNodeId: $this->iteratorNodeId,
      items: $this->items,
      currentIndex: $this->currentIndex,
      accumulatedResults: $this->accumulatedResults,
      status: self::STATUS_COMPLETED,
      childPipelineId: $this->childPipelineId,
      errors: $this->errors,
    );
  }

  /**
   * Create state with error (fails the iteration).
   *
   * @param string $error
   *   The error message.
   *
   * @return self
   *   New state instance with failed status.
   */
  public function withError(string $error): self {
    return new self(
      iteratorNodeId: $this->iteratorNodeId,
      items: $this->items,
      currentIndex: $this->currentIndex,
      accumulatedResults: $this->accumulatedResults,
      status: self::STATUS_FAILED,
      childPipelineId: $this->childPipelineId,
      errors: [...$this->errors, $error],
    );
  }

  /**
   * Create state with skipped item (continues iteration).
   *
   * @param string $error
   *   The error message for the skipped item.
   *
   * @return self
   *   New state instance with incremented index and skipped marker.
   */
  public function withSkippedItem(string $error): self {
    $skippedResult = [
      "_skipped" => TRUE,
      "_error" => $error,
      "_index" => $this->currentIndex,
    ];

    return new self(
      iteratorNodeId: $this->iteratorNodeId,
      items: $this->items,
      currentIndex: $this->currentIndex + 1,
      accumulatedResults: [...$this->accumulatedResults, $skippedResult],
      status: self::STATUS_ITERATING,
      childPipelineId: $this->childPipelineId,
      errors: [...$this->errors, $error],
    );
  }

  /**
   * Create state with child pipeline.
   *
   * @param string $pipelineId
   *   The child pipeline ID.
   *
   * @return self
   *   New state instance with child pipeline ID.
   */
  public function withChildPipeline(string $pipelineId): self {
    return new self(
      iteratorNodeId: $this->iteratorNodeId,
      items: $this->items,
      currentIndex: $this->currentIndex,
      accumulatedResults: $this->accumulatedResults,
      status: $this->status,
      childPipelineId: $pipelineId,
      errors: $this->errors,
    );
  }

  /**
   * Create state with iterating status.
   *
   * @return self
   *   New state instance with iterating status.
   */
  public function withIterating(): self {
    return new self(
      iteratorNodeId: $this->iteratorNodeId,
      items: $this->items,
      currentIndex: $this->currentIndex,
      accumulatedResults: $this->accumulatedResults,
      status: self::STATUS_ITERATING,
      childPipelineId: $this->childPipelineId,
      errors: $this->errors,
    );
  }

  /**
   * Convert state to array representation.
   *
   * @return array<string, mixed>
   *   The state as an array.
   */
  public function toArray(): array {
    return [
      "iteratorNodeId" => $this->iteratorNodeId,
      "items" => $this->items,
      "currentIndex" => $this->currentIndex,
      "accumulatedResults" => $this->accumulatedResults,
      "status" => $this->status,
      "childPipelineId" => $this->childPipelineId,
      "errors" => $this->errors,
      "totalCount" => $this->getTotalCount(),
      "hasMoreItems" => $this->hasMoreItems(),
      "isComplete" => $this->isComplete(),
      "hasErrors" => $this->hasErrors(),
    ];
  }

  /**
   * Create state from array representation.
   *
   * @param array<string, mixed> $data
   *   The state data array.
   *
   * @return self
   *   New IteratorState instance.
   */
  public static function fromArray(array $data): self {
    return new self(
      iteratorNodeId: (string) ($data["iteratorNodeId"] ?? ""),
      items: $data["items"] ?? [],
      currentIndex: (int) ($data["currentIndex"] ?? 0),
      accumulatedResults: $data["accumulatedResults"] ?? [],
      status: (string) ($data["status"] ?? self::STATUS_PENDING),
      childPipelineId: isset($data["childPipelineId"]) ? (string) $data["childPipelineId"] : NULL,
      errors: $data["errors"] ?? [],
    );
  }

}
