<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\DTO;

/**
 * Single step in agent execution trace.
 */
final class TraceStep {

  /**
   * Step type: LLM call.
   */
  public const TYPE_LLM_CALL = 'llm_call';

  /**
   * Step type: tool call.
   */
  public const TYPE_TOOL_CALL = 'tool_call';

  /**
   * Step type: tool result.
   */
  public const TYPE_TOOL_RESULT = 'tool_result';

  /**
   * Step type: final answer.
   */
  public const TYPE_FINAL_ANSWER = 'final_answer';

  /**
   * Constructs a new TraceStep object.
   *
   * @param int $stepNumber
   *   The step number in the trace.
   * @param string $type
   *   The type of step.
   * @param array<string, mixed> $input
   *   Input to this step.
   * @param array<string, mixed> $output
   *   Output from this step.
   * @param int|null $tokensUsed
   *   Token usage (for LLM calls).
   * @param float $durationMs
   *   Duration in milliseconds.
   * @param string|null $error
   *   Error information if step failed.
   * @param \DateTimeImmutable $timestamp
   *   When this step occurred.
   */
  public function __construct(
    private readonly int $stepNumber,
    private readonly string $type,
    private readonly array $input,
    private readonly array $output,
    private readonly ?int $tokensUsed = NULL,
    private readonly float $durationMs = 0.0,
    private readonly ?string $error = NULL,
    private readonly \DateTimeImmutable $timestamp = new \DateTimeImmutable(),
  ) {}

  /**
   * Creates an LLM call step.
   *
   * @param int $stepNumber
   *   The step number.
   * @param array<string, mixed> $input
   *   The input (messages).
   * @param array<string, mixed> $output
   *   The LLM response.
   * @param int $tokensUsed
   *   Tokens used.
   * @param float $durationMs
   *   Duration in ms.
   *
   * @return self
   *   An LLM call step.
   */
  public static function llmCall(
    int $stepNumber,
    array $input,
    array $output,
    int $tokensUsed,
    float $durationMs,
  ): self {
    return new self(
      stepNumber: $stepNumber,
      type: self::TYPE_LLM_CALL,
      input: $input,
      output: $output,
      tokensUsed: $tokensUsed,
      durationMs: $durationMs,
    );
  }

  /**
   * Creates a tool call step.
   *
   * @param int $stepNumber
   *   The step number.
   * @param array<string, mixed> $input
   *   The tool call details.
   * @param array<string, mixed> $output
   *   The tool result.
   * @param float $durationMs
   *   Duration in ms.
   * @param string|null $error
   *   Error if failed.
   *
   * @return self
   *   A tool call step.
   */
  public static function toolCall(
    int $stepNumber,
    array $input,
    array $output,
    float $durationMs,
    ?string $error = NULL,
  ): self {
    return new self(
      stepNumber: $stepNumber,
      type: self::TYPE_TOOL_CALL,
      input: $input,
      output: $output,
      durationMs: $durationMs,
      error: $error,
    );
  }

  /**
   * Creates a final answer step.
   *
   * @param int $stepNumber
   *   The step number.
   * @param array<string, mixed> $output
   *   The final answer.
   *
   * @return self
   *   A final answer step.
   */
  public static function finalAnswer(int $stepNumber, array $output): self {
    return new self(
      stepNumber: $stepNumber,
      type: self::TYPE_FINAL_ANSWER,
      input: [],
      output: $output,
    );
  }

  /**
   * Gets the step number.
   *
   * @return int
   *   The step number.
   */
  public function getStepNumber(): int {
    return $this->stepNumber;
  }

  /**
   * Gets the step type.
   *
   * @return string
   *   The type.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Gets the input.
   *
   * @return array<string, mixed>
   *   The input.
   */
  public function getInput(): array {
    return $this->input;
  }

  /**
   * Gets the output.
   *
   * @return array<string, mixed>
   *   The output.
   */
  public function getOutput(): array {
    return $this->output;
  }

  /**
   * Gets tokens used.
   *
   * @return int|null
   *   The tokens used or NULL.
   */
  public function getTokensUsed(): ?int {
    return $this->tokensUsed;
  }

  /**
   * Gets the duration in milliseconds.
   *
   * @return float
   *   The duration.
   */
  public function getDurationMs(): float {
    return $this->durationMs;
  }

  /**
   * Gets the error.
   *
   * @return string|null
   *   The error or NULL.
   */
  public function getError(): ?string {
    return $this->error;
  }

  /**
   * Gets the timestamp.
   *
   * @return \DateTimeImmutable
   *   The timestamp.
   */
  public function getTimestamp(): \DateTimeImmutable {
    return $this->timestamp;
  }

  /**
   * Checks if this step had an error.
   *
   * @return bool
   *   TRUE if error.
   */
  public function hasError(): bool {
    return $this->error !== NULL;
  }

  /**
   * Converts to array format.
   *
   * @return array<string, mixed>
   *   The step as array.
   */
  public function toArray(): array {
    return [
      'step_number' => $this->stepNumber,
      'type' => $this->type,
      'input' => $this->input,
      'output' => $this->output,
      'tokens_used' => $this->tokensUsed,
      'duration_ms' => $this->durationMs,
      'error' => $this->error,
      'timestamp' => $this->timestamp->format(\DateTimeInterface::RFC3339_EXTENDED),
    ];
  }

}
