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
 * Anthropic Claude tool calling adapter.
 */
final class AnthropicToolCallingAdapter implements ToolCallingAdapterInterface {

  /**
   * The Anthropic API base URL.
   */
  private const API_BASE_URL = 'https://api.anthropic.com/v1';

  /**
   * The API version header.
   */
  private const API_VERSION = '2023-06-01';

  /**
   * Available models.
   *
   * @var array<string, array{id: string, name: string, max_tokens: int}>
   */
  private const MODELS = [
    'claude-3-opus-20240229' => [
      'id' => 'claude-3-opus-20240229',
      'name' => 'Claude 3 Opus',
      'max_tokens' => 4096,
    ],
    'claude-3-sonnet-20240229' => [
      'id' => 'claude-3-sonnet-20240229',
      'name' => 'Claude 3 Sonnet',
      'max_tokens' => 4096,
    ],
    'claude-3-haiku-20240307' => [
      'id' => 'claude-3-haiku-20240307',
      'name' => 'Claude 3 Haiku',
      'max_tokens' => 4096,
    ],
    'claude-3-5-sonnet-20241022' => [
      'id' => 'claude-3-5-sonnet-20241022',
      'name' => 'Claude 3.5 Sonnet',
      'max_tokens' => 8192,
    ],
  ];

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * Constructs a new AnthropicToolCallingAdapter.
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
    return 'anthropic';
  }

  /**
   * {@inheritdoc}
   */
  public function supportsModel(string $modelId): bool {
    return str_starts_with($modelId, 'claude-');
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
    // Extract system message from messages.
    $systemPrompt = NULL;
    $filteredMessages = [];
    foreach ($messages as $msg) {
      if ($msg['role'] === 'system') {
        $systemPrompt = $msg['content'];
      }
      else {
        $filteredMessages[] = $msg;
      }
    }

    $payload = [
      'model' => $model,
      'messages' => $this->formatMessages($filteredMessages),
      'max_tokens' => $maxTokens,
    ];

    if ($systemPrompt !== NULL) {
      $payload['system'] = $systemPrompt;
    }

    if (!empty($tools)) {
      $payload['tools'] = $this->formatTools($tools);
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
    // Extract system message.
    $systemPrompt = NULL;
    $filteredMessages = [];
    foreach ($messages as $msg) {
      if ($msg['role'] === 'system') {
        $systemPrompt = $msg['content'];
      }
      else {
        $filteredMessages[] = $msg;
      }
    }

    $payload = [
      'model' => $model,
      'messages' => $this->formatMessages($filteredMessages),
      'max_tokens' => $maxTokens,
    ];

    if ($systemPrompt !== NULL) {
      $payload['system'] = $systemPrompt;
    }

    return $this->makeRequest($payload);
  }

  /**
   * {@inheritdoc}
   */
  public function formatTools(array $tools): array {
    return array_map(
      fn(ToolDefinition $tool) => $tool->toAnthropicTool(),
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

    $this->logger->debug('Anthropic request: @model', [
      '@model' => $payload['model'],
    ]);

    try {
      $response = $this->httpClient->request('POST', "{$baseUrl}/messages", [
        'headers' => [
          'x-api-key' => $apiKey,
          'anthropic-version' => self::API_VERSION,
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 120,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      return $this->normalizeResponse($data);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Anthropic API error: @error', [
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
    $content = $data['content'] ?? [];
    $usage = $data['usage'] ?? [];

    // Extract text content and tool calls.
    $textContent = NULL;
    $toolCalls = [];

    foreach ($content as $block) {
      if ($block['type'] === 'text') {
        $textContent = ($textContent ?? '') . $block['text'];
      }
      elseif ($block['type'] === 'tool_use') {
        $toolCalls[] = ToolCall::fromAnthropicFormat($block);
      }
    }

    // Map stop reason to finish reason.
    $stopReason = $data['stop_reason'] ?? 'end_turn';
    $finishReason = match ($stopReason) {
      'tool_use' => LlmResponse::FINISH_TOOL_CALLS,
      'max_tokens' => LlmResponse::FINISH_LENGTH,
      default => LlmResponse::FINISH_STOP,
    };

    return new LlmResponse(
      content: $textContent,
      toolCalls: $toolCalls,
      finishReason: $finishReason,
      promptTokens: $usage['input_tokens'] ?? 0,
      completionTokens: $usage['output_tokens'] ?? 0,
      totalTokens: ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0),
      model: $data['model'] ?? '',
      raw: $data,
    );
  }

  /**
   * Formats messages for the Anthropic API.
   *
   * @param array<array{role: string, content: string}> $messages
   *   The messages.
   *
   * @return array<array<string, mixed>>
   *   Formatted messages.
   */
  private function formatMessages(array $messages): array {
    $formatted = [];

    foreach ($messages as $msg) {
      $role = $msg['role'];

      // Anthropic only supports 'user' and 'assistant' roles.
      if ($role === 'tool') {
        // Tool results go in user messages with tool_result content.
        $formatted[] = [
          'role' => 'user',
          'content' => [
            [
              'type' => 'tool_result',
              'tool_use_id' => $msg['tool_call_id'] ?? '',
              'content' => $msg['content'] ?? '',
            ],
          ],
        ];
      }
      elseif ($role === 'assistant' && !empty($msg['tool_calls'])) {
        // Assistant messages with tool calls.
        $content = [];
        if (!empty($msg['content'])) {
          $content[] = ['type' => 'text', 'text' => $msg['content']];
        }
        foreach ($msg['tool_calls'] as $tc) {
          $content[] = [
            'type' => 'tool_use',
            'id' => $tc['id'] ?? '',
            'name' => $tc['function']['name'] ?? $tc['name'] ?? '',
            'input' => is_string($tc['function']['arguments'] ?? NULL)
              ? json_decode($tc['function']['arguments'], TRUE)
              : ($tc['input'] ?? $tc['arguments'] ?? []),
          ];
        }
        $formatted[] = [
          'role' => 'assistant',
          'content' => $content,
        ];
      }
      else {
        // Regular user or assistant messages.
        $formatted[] = [
          'role' => $role,
          'content' => $msg['content'] ?? '',
        ];
      }
    }

    return $formatted;
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
    $apiKey = $config->get('anthropic_api_key');

    if (empty($apiKey)) {
      throw new LlmApiException(
        'Anthropic API key not configured.',
        'anthropic',
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
    return $config->get('anthropic_base_url') ?: self::API_BASE_URL;
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
      401 => LlmApiException::authenticationFailed('anthropic'),
      429 => LlmApiException::rateLimited('anthropic'),
      default => new LlmApiException(
        $e->getMessage(),
        'anthropic',
        $statusCode,
        $errorResponse,
        $e
      ),
    };
  }

}
