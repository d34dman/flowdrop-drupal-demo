<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\DTO;

use Drupal\flowdrop_conversation\DTO\ToolCall;

/**
 * Manages the state of an Agent during execution.
 *
 * Tracks iterations, tool calls, results, and completion status.
 * This DTO is immutable - all modifications return new instances.
 */
final class AgentState {

  /**
   * Constructs a new AgentState object.
   *
   * @param string $executionId
   *   Unique execution identifier.
   * @param string $conversationId
   *   Reference to conversation state.
   * @param int $currentIteration
   *   Current iteration number (0-based).
   * @param int $maxIterations
   *   Maximum allowed iterations.
   * @param bool $isComplete
   *   Whether the agent has signaled completion.
   * @param string|null $finalAnswer
   *   The final answer (if complete).
   * @param array<ToolCall> $toolCalls
   *   List of tool calls made.
   * @param array<ToolResult> $toolResults
   *   List of tool results received.
   * @param string|null $childPipelineId
   *   Child pipeline ID for tool executions.
   * @param int $totalTokensUsed
   *   Total tokens consumed across all LLM calls.
   * @param \DateTimeImmutable $startedAt
   *   When the agent started.
   */
  public function __construct(
    private readonly string $executionId,
    private readonly string $conversationId,
    private readonly int $currentIteration = 0,
    private readonly int $maxIterations = 10,
    private readonly bool $isComplete = FALSE,
    private readonly ?string $finalAnswer = NULL,
    private readonly array $toolCalls = [],
    private readonly array $toolResults = [],
    private readonly ?string $childPipelineId = NULL,
    private readonly int $totalTokensUsed = 0,
    private readonly \DateTimeImmutable $startedAt = new \DateTimeImmutable(),
  ) {}

  /**
   * Initializes a new agent state.
   *
   * @param string $executionId
   *   The execution ID.
   * @param string $conversationId
   *   The conversation ID.
   * @param int $maxIterations
   *   Maximum iterations allowed.
   *
   * @return self
   *   A new AgentState instance.
   */
  public static function initialize(
    string $executionId,
    string $conversationId,
    int $maxIterations = 10,
  ): self {
    return new self(
      executionId: $executionId,
      conversationId: $conversationId,
      currentIteration: 0,
      maxIterations: $maxIterations,
    );
  }

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
   * Gets the conversation ID.
   *
   * @return string
   *   The conversation ID.
   */
  public function getConversationId(): string {
    return $this->conversationId;
  }

  /**
   * Gets the current iteration number.
   *
   * @return int
   *   The current iteration (0-based).
   */
  public function getCurrentIteration(): int {
    return $this->currentIteration;
  }

  /**
   * Gets the maximum iterations.
   *
   * @return int
   *   The max iterations.
   */
  public function getMaxIterations(): int {
    return $this->maxIterations;
  }

  /**
   * Checks if the agent is complete.
   *
   * @return bool
   *   TRUE if complete.
   */
  public function isComplete(): bool {
    return $this->isComplete;
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
   * Gets all tool calls.
   *
   * @return array<ToolCall>
   *   The tool calls.
   */
  public function getToolCalls(): array {
    return $this->toolCalls;
  }

  /**
   * Gets all tool results.
   *
   * @return array<ToolResult>
   *   The tool results.
   */
  public function getToolResults(): array {
    return $this->toolResults;
  }

  /**
   * Gets the child pipeline ID.
   *
   * @return string|null
   *   The child pipeline ID or NULL.
   */
  public function getChildPipelineId(): ?string {
    return $this->childPipelineId;
  }

  /**
   * Gets total tokens used.
   *
   * @return int
   *   The total tokens.
   */
  public function getTotalTokensUsed(): int {
    return $this->totalTokensUsed;
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
   * Checks if max iterations has been reached.
   *
   * @return bool
   *   TRUE if max reached.
   */
  public function hasReachedMaxIterations(): bool {
    return $this->currentIteration >= $this->maxIterations;
  }

  /**
   * Records a tool call.
   *
   * @param \Drupal\flowdrop_conversation\DTO\ToolCall $toolCall
   *   The tool call.
   *
   * @return self
   *   A new AgentState with the tool call added.
   */
  public function recordToolCall(ToolCall $toolCall): self {
    $newToolCalls = $this->toolCalls;
    $newToolCalls[] = $toolCall;

    return new self(
      executionId: $this->executionId,
      conversationId: $this->conversationId,
      currentIteration: $this->currentIteration,
      maxIterations: $this->maxIterations,
      isComplete: $this->isComplete,
      finalAnswer: $this->finalAnswer,
      toolCalls: $newToolCalls,
      toolResults: $this->toolResults,
      childPipelineId: $this->childPipelineId,
      totalTokensUsed: $this->totalTokensUsed,
      startedAt: $this->startedAt,
    );
  }

  /**
   * Records a tool result.
   *
   * @param \Drupal\flowdrop_agent\DTO\ToolResult $result
   *   The tool result.
   *
   * @return self
   *   A new AgentState with the result added.
   */
  public function recordToolResult(ToolResult $result): self {
    $newResults = $this->toolResults;
    $newResults[] = $result;

    return new self(
      executionId: $this->executionId,
      conversationId: $this->conversationId,
      currentIteration: $this->currentIteration,
      maxIterations: $this->maxIterations,
      isComplete: $this->isComplete,
      finalAnswer: $this->finalAnswer,
      toolCalls: $this->toolCalls,
      toolResults: $newResults,
      childPipelineId: $this->childPipelineId,
      totalTokensUsed: $this->totalTokensUsed,
      startedAt: $this->startedAt,
    );
  }

  /**
   * Marks the agent as complete.
   *
   * @param string $finalAnswer
   *   The final answer.
   *
   * @return self
   *   A new completed AgentState.
   */
  public function markComplete(string $finalAnswer): self {
    return new self(
      executionId: $this->executionId,
      conversationId: $this->conversationId,
      currentIteration: $this->currentIteration,
      maxIterations: $this->maxIterations,
      isComplete: TRUE,
      finalAnswer: $finalAnswer,
      toolCalls: $this->toolCalls,
      toolResults: $this->toolResults,
      childPipelineId: $this->childPipelineId,
      totalTokensUsed: $this->totalTokensUsed,
      startedAt: $this->startedAt,
    );
  }

  /**
   * Advances to the next iteration.
   *
   * @return self
   *   A new AgentState with incremented iteration.
   */
  public function advanceIteration(): self {
    return new self(
      executionId: $this->executionId,
      conversationId: $this->conversationId,
      currentIteration: $this->currentIteration + 1,
      maxIterations: $this->maxIterations,
      isComplete: $this->isComplete,
      finalAnswer: $this->finalAnswer,
      toolCalls: $this->toolCalls,
      toolResults: $this->toolResults,
      childPipelineId: $this->childPipelineId,
      totalTokensUsed: $this->totalTokensUsed,
      startedAt: $this->startedAt,
    );
  }

  /**
   * Sets the child pipeline ID.
   *
   * @param string $childPipelineId
   *   The pipeline ID.
   *
   * @return self
   *   A new AgentState with the pipeline ID.
   */
  public function withChildPipelineId(string $childPipelineId): self {
    return new self(
      executionId: $this->executionId,
      conversationId: $this->conversationId,
      currentIteration: $this->currentIteration,
      maxIterations: $this->maxIterations,
      isComplete: $this->isComplete,
      finalAnswer: $this->finalAnswer,
      toolCalls: $this->toolCalls,
      toolResults: $this->toolResults,
      childPipelineId: $childPipelineId,
      totalTokensUsed: $this->totalTokensUsed,
      startedAt: $this->startedAt,
    );
  }

  /**
   * Adds tokens to the total count.
   *
   * @param int $tokens
   *   Tokens to add.
   *
   * @return self
   *   A new AgentState with updated token count.
   */
  public function addTokensUsed(int $tokens): self {
    return new self(
      executionId: $this->executionId,
      conversationId: $this->conversationId,
      currentIteration: $this->currentIteration,
      maxIterations: $this->maxIterations,
      isComplete: $this->isComplete,
      finalAnswer: $this->finalAnswer,
      toolCalls: $this->toolCalls,
      toolResults: $this->toolResults,
      childPipelineId: $this->childPipelineId,
      totalTokensUsed: $this->totalTokensUsed + $tokens,
      startedAt: $this->startedAt,
    );
  }

  /**
   * Converts to array format.
   *
   * @return array<string, mixed>
   *   The state as array.
   */
  public function toArray(): array {
    return [
      'execution_id' => $this->executionId,
      'conversation_id' => $this->conversationId,
      'current_iteration' => $this->currentIteration,
      'max_iterations' => $this->maxIterations,
      'is_complete' => $this->isComplete,
      'final_answer' => $this->finalAnswer,
      'tool_calls' => array_map(fn(ToolCall $tc) => $tc->toArray(), $this->toolCalls),
      'tool_results' => array_map(fn(ToolResult $tr) => $tr->toArray(), $this->toolResults),
      'child_pipeline_id' => $this->childPipelineId,
      'total_tokens_used' => $this->totalTokensUsed,
      'started_at' => $this->startedAt->format(\DateTimeInterface::RFC3339_EXTENDED),
    ];
  }

}
