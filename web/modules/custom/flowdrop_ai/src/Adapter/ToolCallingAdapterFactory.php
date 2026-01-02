<?php

declare(strict_types=1);

namespace Drupal\flowdrop_ai\Adapter;

use Drupal\flowdrop_ai\Exception\LlmApiException;

/**
 * Factory for creating tool calling adapters.
 *
 * Automatically selects the appropriate adapter based on model ID.
 */
final class ToolCallingAdapterFactory {

  /**
   * Registered adapters.
   *
   * @var array<ToolCallingAdapterInterface>
   */
  private array $adapters = [];

  /**
   * Constructs a new ToolCallingAdapterFactory.
   *
   * @param \Drupal\flowdrop_ai\Adapter\OpenAiToolCallingAdapter $openAiAdapter
   *   The OpenAI adapter.
   * @param \Drupal\flowdrop_ai\Adapter\AnthropicToolCallingAdapter $anthropicAdapter
   *   The Anthropic adapter.
   */
  public function __construct(
    OpenAiToolCallingAdapter $openAiAdapter,
    AnthropicToolCallingAdapter $anthropicAdapter,
  ) {
    $this->adapters = [
      'openai' => $openAiAdapter,
      'anthropic' => $anthropicAdapter,
    ];
  }

  /**
   * Gets an adapter for a specific model.
   *
   * @param string $modelId
   *   The model ID (e.g., 'gpt-4', 'claude-3-sonnet').
   *
   * @return \Drupal\flowdrop_ai\Adapter\ToolCallingAdapterInterface
   *   The appropriate adapter.
   *
   * @throws \Drupal\flowdrop_ai\Exception\LlmApiException
   *   If no adapter supports the model.
   */
  public function getAdapter(string $modelId): ToolCallingAdapterInterface {
    foreach ($this->adapters as $adapter) {
      if ($adapter->supportsModel($modelId)) {
        return $adapter;
      }
    }

    throw LlmApiException::invalidModel('unknown', $modelId);
  }

  /**
   * Gets an adapter by provider name.
   *
   * @param string $provider
   *   The provider name (e.g., 'openai', 'anthropic').
   *
   * @return \Drupal\flowdrop_ai\Adapter\ToolCallingAdapterInterface
   *   The adapter.
   *
   * @throws \Drupal\flowdrop_ai\Exception\LlmApiException
   *   If provider is not found.
   */
  public function getAdapterByProvider(string $provider): ToolCallingAdapterInterface {
    if (!isset($this->adapters[$provider])) {
      throw new LlmApiException(
        "Provider '{$provider}' is not supported.",
        $provider,
        400
      );
    }

    return $this->adapters[$provider];
  }

  /**
   * Gets all registered adapters.
   *
   * @return array<string, ToolCallingAdapterInterface>
   *   All adapters keyed by provider name.
   */
  public function getAdapters(): array {
    return $this->adapters;
  }

  /**
   * Gets all available models across all providers.
   *
   * @return array<string, array{id: string, name: string, provider: string, max_tokens: int}>
   *   All available models.
   */
  public function getAllModels(): array {
    $models = [];

    foreach ($this->adapters as $provider => $adapter) {
      foreach ($adapter->getAvailableModels() as $id => $model) {
        $models[$id] = array_merge($model, ['provider' => $provider]);
      }
    }

    return $models;
  }

  /**
   * Checks if a model is supported.
   *
   * @param string $modelId
   *   The model ID.
   *
   * @return bool
   *   TRUE if supported.
   */
  public function supportsModel(string $modelId): bool {
    foreach ($this->adapters as $adapter) {
      if ($adapter->supportsModel($modelId)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Gets the provider for a model.
   *
   * @param string $modelId
   *   The model ID.
   *
   * @return string|null
   *   The provider name or NULL if not found.
   */
  public function getProviderForModel(string $modelId): ?string {
    foreach ($this->adapters as $provider => $adapter) {
      if ($adapter->supportsModel($modelId)) {
        return $provider;
      }
    }

    return NULL;
  }

}
