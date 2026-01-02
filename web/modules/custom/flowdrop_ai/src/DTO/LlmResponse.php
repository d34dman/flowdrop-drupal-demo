<?php

declare(strict_types=1);

namespace Drupal\flowdrop_ai\DTO;

use Drupal\flowdrop_conversation\DTO\ToolCall;

/**
 * Represents a response from an LLM.
 *
 * Normalizes responses from different providers (OpenAI, Anthropic, etc.)
 * into a consistent format.
 */
final class LlmResponse {

  /**
   * Finish reason: model completed normally.
   */
  public const FINISH_STOP = 'stop';

  /**
   * Finish reason: model wants to call tools.
   */
  public const FINISH_TOOL_CALLS = 'tool_calls';

  /**
   * Finish reason: maximum tokens reached.
   */
  public const FINISH_LENGTH = 'length';

  /**
   * Finish reason: content was filtered.
   */
  public const FINISH_CONTENT_FILTER = 'content_filter';

  /**
   * Constructs a new LlmResponse object.
   *
   * @param string|null $content
   *   The text content of the response.
   * @param array<ToolCall> $toolCalls
   *   Tool calls requested by the model.
   * @param string $finishReason
   *   Why the model stopped generating.
   * @param int $promptTokens
   *   Tokens used in the prompt.
   * @param int $completionTokens
   *   Tokens generated in the completion.
   * @param int $totalTokens
   *   Total tokens used.
   * @param string $model
   *   The model that generated the response.
   * @param array<string, mixed> $raw
   *   The raw response from the provider.
   */
  public function __construct(
    private readonly ?string $content,
    private readonly array $toolCalls = [],
    private readonly string $finishReason = self::FINISH_STOP,
    private readonly int $promptTokens = 0,
    private readonly int $completionTokens = 0,
    private readonly int $totalTokens = 0,
    private readonly string $model = '',
    private readonly array $raw = [],
  ) {}

  /**
   * Gets the text content.
   *
   * @return string|null
   *   The text content or NULL.
   */
  public function getContent(): ?string {
    return $this->content;
  }

  /**
   * Gets the tool calls.
   *
   * @return array<ToolCall>
   *   The tool calls.
   */
  public function getToolCalls(): array {
    return $this->toolCalls;
  }

  /**
   * Gets the first tool call.
   *
   * @return \Drupal\flowdrop_conversation\DTO\ToolCall|null
   *   The first tool call or NULL.
   */
  public function getFirstToolCall(): ?ToolCall {
    return $this->toolCalls[0] ?? NULL;
  }

  /**
   * Checks if the response contains tool calls.
   *
   * @return bool
   *   TRUE if tool calls present.
   */
  public function hasToolCalls(): bool {
    return !empty($this->toolCalls);
  }

  /**
   * Gets the finish reason.
   *
   * @return string
   *   The finish reason.
   */
  public function getFinishReason(): string {
    return $this->finishReason;
  }

  /**
   * Checks if the model wants to call tools.
   *
   * @return bool
   *   TRUE if finish reason is tool_calls.
   */
  public function wantsToolCalls(): bool {
    return $this->finishReason === self::FINISH_TOOL_CALLS || $this->hasToolCalls();
  }

  /**
   * Checks if the model completed normally.
   *
   * @return bool
   *   TRUE if finish reason is stop.
   */
  public function isComplete(): bool {
    return $this->finishReason === self::FINISH_STOP && !$this->hasToolCalls();
  }

  /**
   * Gets prompt tokens.
   *
   * @return int
   *   The prompt tokens.
   */
  public function getPromptTokens(): int {
    return $this->promptTokens;
  }

  /**
   * Gets completion tokens.
   *
   * @return int
   *   The completion tokens.
   */
  public function getCompletionTokens(): int {
    return $this->completionTokens;
  }

  /**
   * Gets total tokens.
   *
   * @return int
   *   The total tokens.
   */
  public function getTotalTokens(): int {
    return $this->totalTokens;
  }

  /**
   * Gets the model name.
   *
   * @return string
   *   The model name.
   */
  public function getModel(): string {
    return $this->model;
  }

  /**
   * Gets the raw response.
   *
   * @return array<string, mixed>
   *   The raw response.
   */
  public function getRaw(): array {
    return $this->raw;
  }

  /**
   * Converts to array format.
   *
   * @return array<string, mixed>
   *   The response as array.
   */
  public function toArray(): array {
    return [
      'content' => $this->content,
      'tool_calls' => array_map(fn(ToolCall $tc) => $tc->toArray(), $this->toolCalls),
      'finish_reason' => $this->finishReason,
      'usage' => [
        'prompt_tokens' => $this->promptTokens,
        'completion_tokens' => $this->completionTokens,
        'total_tokens' => $this->totalTokens,
      ],
      'model' => $this->model,
    ];
  }

}
