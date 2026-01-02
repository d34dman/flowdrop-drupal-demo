<?php

declare(strict_types=1);

namespace Drupal\flowdrop_ai\Adapter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop_ai\DTO\LlmResponse;
use Drupal\flowdrop_ai\DTO\ToolDefinition;
use Drupal\flowdrop_ai\Exception\LlmApiException;
use Drupal\flowdrop_conversation\DTO\ToolCall;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * OpenAI-compatible tool calling adapter.
 *
 * Works with OpenAI, Azure OpenAI, and other compatible APIs.
 */
final class OpenAiToolCallingAdapter implements ToolCallingAdapterInterface {

  /**
   * The OpenAI API base URL.
   */
  private const API_BASE_URL = 'https://api.openai.com/v1';

  /**
   * Available models.
   *
   * @var array<string, array{id: string, name: string, max_tokens: int}>
   */
  private const MODELS = [
    'gpt-4' => [
      'id' => 'gpt-4',
      'name' => 'GPT-4',
      'max_tokens' => 8192,
    ],
    'gpt-4-turbo' => [
      'id' => 'gpt-4-turbo',
      'name' => 'GPT-4 Turbo',
      'max_tokens' => 128000,
    ],
    'gpt-4-turbo-preview' => [
      'id' => 'gpt-4-turbo-preview',
      'name' => 'GPT-4 Turbo Preview',
      'max_tokens' => 128000,
    ],
    'gpt-4o' => [
      'id' => 'gpt-4o',
      'name' => 'GPT-4o',
      'max_tokens' => 128000,
    ],
    'gpt-4o-mini' => [
      'id' => 'gpt-4o-mini',
      'name' => 'GPT-4o Mini',
      'max_tokens' => 128000,
    ],
    'gpt-3.5-turbo' => [
      'id' => 'gpt-3.5-turbo',
      'name' => 'GPT-3.5 Turbo',
      'max_tokens' => 16385,
    ],
  ];

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * Constructs a new OpenAiToolCallingAdapter.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('flowdrop_ai');
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider(): string {
    return 'openai';
  }

  /**
   * {@inheritdoc}
   */
  public function supportsModel(string $modelId): bool {
    return str_starts_with($modelId, 'gpt-');
  }

  /**
   * {@inheritdoc}
   */
  public function callWithTools(
    array $messages,
    array $tools,
    string $model,
    float $temperature = 0.7,
    int $maxTokens = 1000,
    array $options = [],
  ): LlmResponse {
    $payload = [
      'model' => $model,
      'messages' => $this->formatMessages($messages),
      'temperature' => $temperature,
      'max_tokens' => $maxTokens,
    ];

    if (!empty($tools)) {
      $payload['tools'] = $this->formatTools($tools);
      $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
    }

    return $this->makeRequest($payload);
  }

  /**
   * {@inheritdoc}
   */
  public function call(
    array $messages,
    string $model,
    float $temperature = 0.7,
    int $maxTokens = 1000,
    array $options = [],
  ): LlmResponse {
    $payload = [
      'model' => $model,
      'messages' => $this->formatMessages($messages),
      'temperature' => $temperature,
      'max_tokens' => $maxTokens,
    ];

    return $this->makeRequest($payload);
  }

  /**
   * {@inheritdoc}
   */
  public function formatTools(array $tools): array {
    return array_map(
      fn(ToolDefinition $tool) => $tool->toOpenAiFunction(),
      $tools
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableModels(): array {
    return self::MODELS;
  }

  /**
   * Makes the API request.
   *
   * @param array<string, mixed> $payload
   *   The request payload.
   *
   * @return \Drupal\flowdrop_ai\DTO\LlmResponse
   *   The response.
   *
   * @throws \Drupal\flowdrop_ai\Exception\LlmApiException
   *   If the request fails.
   */
  private function makeRequest(array $payload): LlmResponse {
    $apiKey = $this->getApiKey();
    $baseUrl = $this->getBaseUrl();

    $this->logger->debug('OpenAI request: @model', [
      '@model' => $payload['model'],
    ]);

    try {
      $response = $this->httpClient->request('POST', "{$baseUrl}/chat/completions", [
        'headers' => [
          'Authorization' => "Bearer {$apiKey}",
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 120,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      return $this->normalizeResponse($data);
    }
    catch (GuzzleException $e) {
      $this->logger->error('OpenAI API error: @error', [
        '@error' => $e->getMessage(),
      ]);

      throw $this->handleGuzzleException($e);
    }
  }

  /**
   * Normalizes the API response.
   *
   * @param array<string, mixed> $data
   *   The raw response data.
   *
   * @return \Drupal\flowdrop_ai\DTO\LlmResponse
   *   The normalized response.
   */
  private function normalizeResponse(array $data): LlmResponse {
    $choice = $data['choices'][0] ?? [];
    $message = $choice['message'] ?? [];
    $usage = $data['usage'] ?? [];

    // Parse tool calls if present.
    $toolCalls = [];
    if (!empty($message['tool_calls'])) {
      foreach ($message['tool_calls'] as $tc) {
        $toolCalls[] = ToolCall::fromOpenAiFormat($tc);
      }
    }

    // Map finish reason.
    $finishReason = match ($choice['finish_reason'] ?? 'stop') {
      'tool_calls' => LlmResponse::FINISH_TOOL_CALLS,
      'length' => LlmResponse::FINISH_LENGTH,
      'content_filter' => LlmResponse::FINISH_CONTENT_FILTER,
      default => LlmResponse::FINISH_STOP,
    };

    return new LlmResponse(
      content: $message['content'] ?? NULL,
      toolCalls: $toolCalls,
      finishReason: $finishReason,
      promptTokens: $usage['prompt_tokens'] ?? 0,
      completionTokens: $usage['completion_tokens'] ?? 0,
      totalTokens: $usage['total_tokens'] ?? 0,
      model: $data['model'] ?? '',
      raw: $data,
    );
  }

  /**
   * Formats messages for the API.
   *
   * @param array<array{role: string, content: string}> $messages
   *   The messages.
   *
   * @return array<array<string, mixed>>
   *   Formatted messages.
   */
  private function formatMessages(array $messages): array {
    return array_map(function (array $msg) {
      $formatted = [
        'role' => $msg['role'],
        'content' => $msg['content'] ?? '',
      ];

      // Handle tool calls in assistant messages.
      if (!empty($msg['tool_calls'])) {
        $formatted['tool_calls'] = $msg['tool_calls'];
      }

      // Handle tool responses.
      if (!empty($msg['tool_call_id'])) {
        $formatted['tool_call_id'] = $msg['tool_call_id'];
      }

      return $formatted;
    }, $messages);
  }

  /**
   * Gets the API key from configuration.
   *
   * @return string
   *   The API key.
   *
   * @throws \Drupal\flowdrop_ai\Exception\LlmApiException
   *   If API key is not configured.
   */
  private function getApiKey(): string {
    $config = $this->configFactory->get('flowdrop_ai.settings');
    $apiKey = $config->get('openai_api_key');

    if (empty($apiKey)) {
      throw new LlmApiException(
        'OpenAI API key not configured.',
        'openai',
        401
      );
    }

    return $apiKey;
  }

  /**
   * Gets the API base URL.
   *
   * @return string
   *   The base URL.
   */
  private function getBaseUrl(): string {
    $config = $this->configFactory->get('flowdrop_ai.settings');
    return $config->get('openai_base_url') ?: self::API_BASE_URL;
  }

  /**
   * Handles Guzzle exceptions.
   *
   * @param \GuzzleHttp\Exception\GuzzleException $e
   *   The exception.
   *
   * @return \Drupal\flowdrop_ai\Exception\LlmApiException
   *   A converted exception.
   */
  private function handleGuzzleException(GuzzleException $e): LlmApiException {
    $statusCode = NULL;
    $errorResponse = [];

    if (method_exists($e, 'getResponse') && $e->getResponse()) {
      $response = $e->getResponse();
      $statusCode = $response->getStatusCode();
      $body = $response->getBody()->getContents();
      $errorResponse = json_decode($body, TRUE) ?? [];
    }

    return match ($statusCode) {
      401 => LlmApiException::authenticationFailed('openai'),
      429 => LlmApiException::rateLimited('openai'),
      default => new LlmApiException(
        $e->getMessage(),
        'openai',
        $statusCode,
        $errorResponse,
        $e
      ),
    };
  }

}
