<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\DTO;

use Drupal\flowdrop_ai\DTO\ToolDefinition;

/**
 * Complete execution trace of an Agent run.
 *
 * Provides full visibility into the agent's decision-making process,
 * including all LLM calls, tool executions, and results.
 */
final class AgentTrace {

  /**
   * Status: agent is still running.
   */
  public const STATUS_RUNNING = 'running';

  /**
   * Status: agent completed successfully.
   */
  public const STATUS_COMPLETED = 'completed';

  /**
   * Status: agent failed.
   */
  public const STATUS_FAILED = 'failed';

  /**
   * Status: agent hit max iterations.
   */
  public const STATUS_MAX_ITERATIONS = 'max_iterations';

  /**
   * Detailed steps in the execution.
   *
   * @var array<TraceStep>
   */
  private array $steps = [];

  /**
   * Constructs a new AgentTrace object.
   *
   * @param string $executionId
   *   The execution ID.
   * @param string $agentNodeId
   *   The agent node ID.
   * @param array<ToolDefinition> $availableTools
   *   Available tools.
   * @param \DateTimeImmutable $startedAt
   *   When the agent started.
   * @param \DateTimeImmutable|null $completedAt
   *   When the agent completed.
   * @param string $status
   *   The current status.
   * @param string|null $finalAnswer
   *   The final answer.
   * @param int $totalIterations
   *   Total iterations executed.
   * @param int $totalTokensUsed
   *   Total tokens used.
   * @param float $totalExecutionTimeMs
   *   Total execution time in ms.
   * @param string|null $errorMessage
   *   Error message if failed.
   */
  public function __construct(
    private readonly string $executionId,
    private readonly string $agentNodeId,
    private readonly array $availableTools = [],
    private readonly \DateTimeImmutable $startedAt = new \DateTimeImmutable(),
    private ?\DateTimeImmutable $completedAt = NULL,
    private string $status = self::STATUS_RUNNING,
    private ?string $finalAnswer = NULL,
    private int $totalIterations = 0,
    private int $totalTokensUsed = 0,
    private float $totalExecutionTimeMs = 0.0,
    private ?string $errorMessage = NULL,
  ) {}

  /**
   * Gets the execution ID.
   *
   * @return string
   *   The execution ID.
   */
  public function getExecutionId(): string {
    return $this->executionId;
  }

  /**
   * Gets the agent node ID.
   *
   * @return string
   *   The node ID.
   */
  public function getAgentNodeId(): string {
    return $this->agentNodeId;
  }

  /**
   * Gets available tools.
   *
   * @return array<ToolDefinition>
   *   The tools.
   */
  public function getAvailableTools(): array {
    return $this->availableTools;
  }

  /**
   * Gets the start time.
   *
   * @return \DateTimeImmutable
   *   The start time.
   */
  public function getStartedAt(): \DateTimeImmutable {
    return $this->startedAt;
  }

  /**
   * Gets the completion time.
   *
   * @return \DateTimeImmutable|null
   *   The completion time or NULL.
   */
  public function getCompletedAt(): ?\DateTimeImmutable {
    return $this->completedAt;
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
   * Gets the final answer.
   *
   * @return string|null
   *   The final answer or NULL.
   */
  public function getFinalAnswer(): ?string {
    return $this->finalAnswer;
  }

  /**
   * Gets total iterations.
   *
   * @return int
   *   The iterations.
   */
  public function getTotalIterations(): int {
    return $this->totalIterations;
  }

  /**
   * Gets total tokens used.
   *
   * @return int
   *   The tokens.
   */
  public function getTotalTokensUsed(): int {
    return $this->totalTokensUsed;
  }

  /**
   * Gets total execution time in milliseconds.
   *
   * @return float
   *   The time.
   */
  public function getTotalExecutionTimeMs(): float {
    return $this->totalExecutionTimeMs;
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
   * Gets all trace steps.
   *
   * @return array<TraceStep>
   *   The steps.
   */
  public function getSteps(): array {
    return $this->steps;
  }

  /**
   * Adds a step to the trace.
   *
   * @param \Drupal\flowdrop_agent\DTO\TraceStep $step
   *   The step to add.
   *
   * @return self
   *   This trace for chaining.
   */
  public function addStep(TraceStep $step): self {
    $this->steps[] = $step;

    if ($step->getTokensUsed() !== NULL) {
      $this->totalTokensUsed += $step->getTokensUsed();
    }

    return $this;
  }

  /**
   * Marks the trace as completed.
   *
   * @param string $status
   *   The final status.
   * @param string|null $finalAnswer
   *   The final answer.
   * @param int $totalIterations
   *   Total iterations.
   * @param float $totalExecutionTimeMs
   *   Total execution time.
   *
   * @return self
   *   This trace for chaining.
   */
  public function complete(
    string $status,
    ?string $finalAnswer,
    int $totalIterations,
    float $totalExecutionTimeMs,
  ): self {
    $this->status = $status;
    $this->finalAnswer = $finalAnswer;
    $this->totalIterations = $totalIterations;
    $this->totalExecutionTimeMs = $totalExecutionTimeMs;
    $this->completedAt = new \DateTimeImmutable();

    return $this;
  }

  /**
   * Marks the trace as failed.
   *
   * @param string $errorMessage
   *   The error message.
   * @param int $totalIterations
   *   Iterations completed.
   * @param float $totalExecutionTimeMs
   *   Time elapsed.
   *
   * @return self
   *   This trace for chaining.
   */
  public function fail(
    string $errorMessage,
    int $totalIterations,
    float $totalExecutionTimeMs,
  ): self {
    $this->status = self::STATUS_FAILED;
    $this->errorMessage = $errorMessage;
    $this->totalIterations = $totalIterations;
    $this->totalExecutionTimeMs = $totalExecutionTimeMs;
    $this->completedAt = new \DateTimeImmutable();

    return $this;
  }

  /**
   * Converts to output format for node result.
   *
   * @return array<string, mixed>
   *   The trace as output.
   */
  public function toOutput(): array {
    return [
      'answer' => $this->finalAnswer,
      'status' => $this->status,
      'iterations' => $this->totalIterations,
      'tokensUsed' => $this->totalTokensUsed,
      'executionTimeMs' => $this->totalExecutionTimeMs,
      'steps' => array_map(fn(TraceStep $s) => $s->toArray(), $this->steps),
      'availableTools' => array_map(
        fn(ToolDefinition $t) => $t->toArray(),
        $this->availableTools
      ),
      'error' => $this->errorMessage,
    ];
  }

  /**
   * Converts to array format.
   *
   * @return array<string, mixed>
   *   The trace as array.
   */
  public function toArray(): array {
    return [
      'execution_id' => $this->executionId,
      'agent_node_id' => $this->agentNodeId,
      'started_at' => $this->startedAt->format(\DateTimeInterface::RFC3339_EXTENDED),
      'completed_at' => $this->completedAt?->format(\DateTimeInterface::RFC3339_EXTENDED),
      'status' => $this->status,
      'final_answer' => $this->finalAnswer,
      'total_iterations' => $this->totalIterations,
      'total_tokens_used' => $this->totalTokensUsed,
      'total_execution_time_ms' => $this->totalExecutionTimeMs,
      'error_message' => $this->errorMessage,
      'steps' => array_map(fn(TraceStep $s) => $s->toArray(), $this->steps),
      'available_tools' => array_map(
        fn(ToolDefinition $t) => $t->toArray(),
        $this->availableTools
      ),
    ];
  }

}
