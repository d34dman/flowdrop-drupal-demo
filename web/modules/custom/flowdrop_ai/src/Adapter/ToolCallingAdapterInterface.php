<?php

declare(strict_types=1);

namespace Drupal\flowdrop_ai\Adapter;

use Drupal\flowdrop_ai\DTO\LlmResponse;

/**
 * Interface for LLM tool calling adapters.
 *
 * Adapters translate between FlowDrop's tool format and provider-specific
 * formats (OpenAI, Anthropic, etc.). This allows the Agent to work with
 * any supported LLM provider.
 */
interface ToolCallingAdapterInterface {

  /**
   * Gets the provider name this adapter handles.
   *
   * @return string
   *   The provider name (e.g., 'openai', 'anthropic').
   */
  public function getProvider(): string;

  /**
   * Checks if this adapter supports a given model.
   *
   * @param string $modelId
   *   The model ID to check.
   *
   * @return bool
   *   TRUE if supported.
   */
  public function supportsModel(string $modelId): bool;

  /**
   * Calls the LLM with tools available.
   *
   * @param array<array{role: string, content: string}> $messages
   *   Conversation messages in standard format.
   * @param array<ToolDefinition> $tools
   *   Available tool definitions.
   * @param string $model
   *   Model ID to use.
   * @param float $temperature
   *   Temperature setting (0-2).
   * @param int $maxTokens
   *   Maximum tokens to generate.
   * @param array<string, mixed> $options
   *   Additional provider-specific options.
   *
   * @return \Drupal\flowdrop_ai\DTO\LlmResponse
   *   The normalized response.
   *
   * @throws \Drupal\flowdrop_ai\Exception\LlmApiException
   *   If the API call fails.
   */
  public function callWithTools(
    array $messages,
    array $tools,
    string $model,
    float $temperature = 0.7,
    int $maxTokens = 1000,
    array $options = [],
  ): LlmResponse;

  /**
   * Calls the LLM without tools (simple completion).
   *
   * @param array<array{role: string, content: string}> $messages
   *   Conversation messages.
   * @param string $model
   *   Model ID to use.
   * @param float $temperature
   *   Temperature setting.
   * @param int $maxTokens
   *   Maximum tokens.
   * @param array<string, mixed> $options
   *   Additional options.
   *
   * @return \Drupal\flowdrop_ai\DTO\LlmResponse
   *   The response.
   *
   * @throws \Drupal\flowdrop_ai\Exception\LlmApiException
   *   If the API call fails.
   */
  public function call(
    array $messages,
    string $model,
    float $temperature = 0.7,
    int $maxTokens = 1000,
    array $options = [],
  ): LlmResponse;

  /**
   * Converts tool definitions to provider-specific format.
   *
   * @param array<ToolDefinition> $tools
   *   Tool definitions.
   *
   * @return array<array<string, mixed>>
   *   Provider-specific tool format.
   */
  public function formatTools(array $tools): array;

  /**
   * Gets available models for this provider.
   *
   * @return array<string, array{id: string, name: string, max_tokens: int}>
   *   Available models.
   */
  public function getAvailableModels(): array;

}
