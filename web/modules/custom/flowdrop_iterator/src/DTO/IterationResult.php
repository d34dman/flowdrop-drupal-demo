<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\DTO;

/**
 * Represents the result of a single iteration execution.
 *
 * This DTO captures:
 * - The iteration index
 * - The input item that was processed
 * - The output result from the sub-workflow
 * - Execution metadata (timing, job IDs, etc.)
 * - Any errors encountered.
 */
final class IterationResult {

  /**
   * Constructs an IterationResult instance.
   *
   * @param int $index
   *   The iteration index (0-based).
   * @param mixed $inputItem
   *   The input item that was processed.
   * @param mixed $outputResult
   *   The output result from sub-workflow.
   * @param bool $success
   *   Whether the iteration succeeded.
   * @param string|null $error
   *   Error message if iteration failed.
   * @param array<string, mixed> $metadata
   *   Additional execution metadata.
   */
  public function __construct(
    private readonly int $index,
    private readonly mixed $inputItem,
    private readonly mixed $outputResult,
    private readonly bool $success = TRUE,
    private readonly ?string $error = NULL,
    private readonly array $metadata = [],
  ) {}

  /**
   * Get iteration index.
   *
   * @return int
   *   The iteration index (0-based).
   */
  public function getIndex(): int {
    return $this->index;
  }

  /**
   * Get the input item that was processed.
   *
   * @return mixed
   *   The input item.
   */
  public function getInputItem(): mixed {
    return $this->inputItem;
  }

  /**
   * Get the output result from sub-workflow.
   *
   * @return mixed
   *   The output result.
   */
  public function getOutputResult(): mixed {
    return $this->outputResult;
  }

  /**
   * Check if iteration succeeded.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function isSuccess(): bool {
    return $this->success;
  }

  /**
   * Get error message if iteration failed.
   *
   * @return string|null
   *   The error message, or NULL if successful.
   */
  public function getError(): ?string {
    return $this->error;
  }

  /**
   * Get execution metadata.
   *
   * @return array<string, mixed>
   *   The metadata array.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Get a specific metadata value.
   *
   * @param string $key
   *   The metadata key.
   * @param mixed $default
   *   Default value if key not found.
   *
   * @return mixed
   *   The metadata value.
   */
  public function getMetadataValue(string $key, mixed $default = NULL): mixed {
    return $this->metadata[$key] ?? $default;
  }

  /**
   * Create a successful iteration result.
   *
   * @param int $index
   *   The iteration index.
   * @param mixed $inputItem
   *   The input item.
   * @param mixed $outputResult
   *   The output result.
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   New successful result instance.
   */
  public static function success(
    int $index,
    mixed $inputItem,
    mixed $outputResult,
    array $metadata = [],
  ): self {
    return new self(
      index: $index,
      inputItem: $inputItem,
      outputResult: $outputResult,
      success: TRUE,
      error: NULL,
      metadata: $metadata,
    );
  }

  /**
   * Create a failed iteration result.
   *
   * @param int $index
   *   The iteration index.
   * @param mixed $inputItem
   *   The input item.
   * @param string $error
   *   The error message.
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   New failed result instance.
   */
  public static function failure(
    int $index,
    mixed $inputItem,
    string $error,
    array $metadata = [],
  ): self {
    return new self(
      index: $index,
      inputItem: $inputItem,
      outputResult: NULL,
      success: FALSE,
      error: $error,
      metadata: $metadata,
    );
  }

  /**
   * Create a skipped iteration result.
   *
   * @param int $index
   *   The iteration index.
   * @param mixed $inputItem
   *   The input item.
   * @param string $reason
   *   The reason for skipping.
   * @param array<string, mixed> $metadata
   *   Optional metadata.
   *
   * @return self
   *   New skipped result instance.
   */
  public static function skipped(
    int $index,
    mixed $inputItem,
    string $reason,
    array $metadata = [],
  ): self {
    return new self(
      index: $index,
      inputItem: $inputItem,
      outputResult: [
        "_skipped" => TRUE,
        "_reason" => $reason,
      ],
      success: TRUE,
      error: NULL,
      metadata: array_merge($metadata, ["skipped" => TRUE]),
    );
  }

  /**
   * Convert result to array representation.
   *
   * @return array<string, mixed>
   *   The result as an array.
   */
  public function toArray(): array {
    return [
      "index" => $this->index,
      "inputItem" => $this->inputItem,
      "outputResult" => $this->outputResult,
      "success" => $this->success,
      "error" => $this->error,
      "metadata" => $this->metadata,
    ];
  }

  /**
   * Create result from array representation.
   *
   * @param array<string, mixed> $data
   *   The result data array.
   *
   * @return self
   *   New IterationResult instance.
   */
  public static function fromArray(array $data): self {
    return new self(
      index: (int) ($data["index"] ?? 0),
      inputItem: $data["inputItem"] ?? NULL,
      outputResult: $data["outputResult"] ?? NULL,
      success: (bool) ($data["success"] ?? TRUE),
      error: isset($data["error"]) ? (string) $data["error"] : NULL,
      metadata: $data["metadata"] ?? [],
    );
  }

}
