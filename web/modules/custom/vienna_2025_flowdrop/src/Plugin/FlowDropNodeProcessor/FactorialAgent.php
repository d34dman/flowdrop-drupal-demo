<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Drupal\key\KeyRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Executor for Factorial Agent nodes.
 */
#[FlowDropNodeProcessor(
  id: "factorial_agent",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Factorial Agent"),
  type: "simple",
  supportedTypes: ["simple", "default"],
  category: "ai",
  description: "Simple AI agent implementation",
  version: "1.0.0",
  tags: ["ai", "agent", "simple"]
)]
class FactorialAgent extends AbstractFlowDropNodeProcessor {


  /**
   * The OpenAI API key.
   */
  protected string $openaiApiKey;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly ClientInterface $httpClient,
    private readonly KeyRepository $keyRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->openaiApiKey = $this->keyRepository->getKey('openai_api_key')->getKeyValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('http_client'),
      $container->get('key.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('flowdrop_node_processor');
  }

  /**
   * {@inheritdoc}
   */
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    $systemPrompt = (string) $config->getConfig('systemPrompt', 'You are a helpful assistant.');
    $temperature = (float) $config->getConfig('temperature', 0.7);
    $maxTokens = (int) $config->getConfig('maxTokens', 1000);
    $tools = (array) $config->getConfig('tools', []);

    // Get the input message.
    // It can be a string or structured data (array) from data flow.
    $messageInput = $inputs->get('message') ?: '';
    $message = $this->normalizeMessage($messageInput);

    $tools = $inputs->get('tools') ?: $tools;

    // Process the agent request with OpenAI API.
    $response = $this->processAgentRequest($message, $systemPrompt, $temperature, $maxTokens, $tools);

    $this->getLogger()->info('Simple agent executed successfully', [
      'message_length' => strlen($message),
      'temperature' => $temperature,
      'max_tokens' => $maxTokens,
      'tools_count' => count($tools),
    ]);

    return [
      'response' => $response,
      'system_prompt' => $systemPrompt,
      'temperature' => $temperature,
      'max_tokens' => $maxTokens,
      'tools_used' => $response['tools_used'] ?? [],
      'message' => $response['content'] ?? $message,
    ];
  }

  /**
   * Normalize message input to string.
   *
   * Handles both string inputs and structured data (arrays) from data flow.
   *
   * @param mixed $messageInput
   *   The message input (string or array).
   *
   * @return string
   *   Normalized message string.
   */
  private function normalizeMessage(mixed $messageInput): string {
    // If it's already a string, return as-is.
    if (is_string($messageInput)) {
      return $messageInput;
    }

    // If it's NULL or empty, return empty string.
    if (empty($messageInput)) {
      return '';
    }

    // If it's an array, convert to JSON string for processing.
    if (is_array($messageInput)) {
      // Check if it has a 'message' field and use that.
      if (isset($messageInput['message']) && is_string($messageInput['message'])) {
        return $messageInput['message'];
      }

      // Otherwise, convert the entire structure to JSON.
      return json_encode($messageInput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // For other types, convert to string.
    return (string) $messageInput;
  }

  /**
   * Process agent request with OpenAI API.
   *
   * Makes an actual HTTP request to OpenAI's Chat Completions API.
   *
   * @param string $message
   *   The user message to send to the agent.
   * @param string $systemPrompt
   *   The system prompt to guide the agent's behavior.
   * @param float $temperature
   *   The temperature parameter for response generation (0.0 to 2.0).
   * @param int $maxTokens
   *   The maximum number of tokens to generate.
   * @param array $tools
   *   An array of tools available to the agent.
   *
   * @return array
   *   The agent response containing content, role, timestamp, and tools_used.
   */
  private function processAgentRequest(string $message, string $systemPrompt, float $temperature, int $maxTokens, array $tools): array {
    try {
      // Build the messages array for OpenAI API.
      $messages = [
        [
          "role" => "system",
          "content" => $systemPrompt,
        ],
        [
          "role" => "user",
          "content" => $message,
        ],
      ];

      // Build the request payload.
      $payload = [
        "model" => "gpt-4o-mini",
        "messages" => $messages,
        "temperature" => $temperature,
        "max_tokens" => $maxTokens,
      ];

      // Add tools if provided.
      if (!empty($tools)) {
        $payload["tools"] = $this->formatToolsForOpenAI($tools);
      }

      // Make the HTTP request to OpenAI API.
      $response = $this->httpClient->request("POST", "https://api.openai.com/v1/chat/completions", [
        "headers" => [
          "Content-Type" => "application/json",
          "Authorization" => "Bearer {$this->openaiApiKey}",
        ],
        "json" => $payload,
      ]);

      // Parse the response.
      $responseBody = json_decode($response->getBody()->getContents(), TRUE);

      if (!isset($responseBody["choices"][0]["message"])) {
        throw new \RuntimeException("Invalid response from OpenAI API");
      }

      $assistantMessage = $responseBody["choices"][0]["message"];
      $toolCalls = $assistantMessage["tool_calls"] ?? [];

      return [
        "content" => $assistantMessage["content"] ?? "",
        "role" => "assistant",
        "timestamp" => time(),
        "tools_used" => $this->extractToolsUsed($toolCalls),
        "finish_reason" => $responseBody["choices"][0]["finish_reason"] ?? "stop",
        "usage" => $responseBody["usage"] ?? [],
      ];
    }
    catch (GuzzleException $e) {
      // Log the error and return a fallback response.
      $this->getLogger()->error("OpenAI API request failed: @error", [
        "@error" => $e->getMessage(),
      ]);

      return [
        "content" => "I apologize, but I encountered an error while processing your request. Please try again later.",
        "role" => "assistant",
        "timestamp" => time(),
        "tools_used" => [],
        "error" => $e->getMessage(),
      ];
    }
    catch (\Exception $e) {
      // Handle any other exceptions.
      $this->getLogger()->error("Unexpected error in agent processing: @error", [
        "@error" => $e->getMessage(),
      ]);

      return [
        "content" => "I apologize, but an unexpected error occurred. Please try again later.",
        "role" => "assistant",
        "timestamp" => time(),
        "tools_used" => [],
        "error" => $e->getMessage(),
      ];
    }
  }

  /**
   * Format tools for OpenAI API.
   *
   * Converts the tools array to OpenAI's expected format.
   *
   * @param array $tools
   *   The tools array from configuration or input.
   *
   * @return array
   *   The formatted tools array for OpenAI API.
   */
  private function formatToolsForOpenAI(array $tools): array {
    $formattedTools = [];

    foreach ($tools as $tool) {
      // If the tool is already in OpenAI format, use it as-is.
      if (isset($tool["type"]) && $tool["type"] === "function") {
        $formattedTools[] = $tool;
        continue;
      }

      // Otherwise, convert it to OpenAI format.
      $formattedTool = [
        "type" => "function",
        "function" => [
          "name" => $tool["name"] ?? "unknown_tool",
          "description" => $tool["description"] ?? "",
        ],
      ];

      // Add parameters if available.
      if (isset($tool["parameters"])) {
        $formattedTool["function"]["parameters"] = $tool["parameters"];
      }

      $formattedTools[] = $formattedTool;
    }

    return $formattedTools;
  }

  /**
   * Extract tools used from tool calls.
   *
   * @param array $toolCalls
   *   The tool_calls array from OpenAI response.
   *
   * @return array
   *   Array of tools that were used with their arguments.
   */
  private function extractToolsUsed(array $toolCalls): array {
    $toolsUsed = [];

    foreach ($toolCalls as $toolCall) {
      if (!isset($toolCall["function"])) {
        continue;
      }

      $toolsUsed[] = [
        "id" => $toolCall["id"] ?? "",
        "name" => $toolCall["function"]["name"] ?? "",
        "arguments" => json_decode($toolCall["function"]["arguments"] ?? "{}", TRUE),
      ];
    }

    return $toolsUsed;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Simple agent nodes can accept any inputs or none.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'response' => [
          'type' => 'object',
          'description' => 'The Agent Response',
        ],
        'tools_used' => [
          'type' => 'array',
          'description' => 'The tools used by the agent',
        ],
        'message' => [
          'type' => 'string',
          'description' => 'The Agent Response as text',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'nodeType' => [
          'type' => 'select',
          'title' => 'Node Type',
          'description' => 'Choose the visual representation for this node',
          'default' => 'simple',
          'enum' => ["simple", "default"],
          'enumNames' => ["Simple", "Default"],
        ],
        'systemPrompt' => [
          'type' => 'string',
          'title' => 'System Prompt',
          'description' => 'System prompt for the agent',
          'format' => 'multiline',
          'default' => 'You are a helpful assistant.',
        ],
        'temperature' => [
          'type' => 'number',
          'title' => 'Temperature',
          'description' => 'Temperature for response generation (0.0 to 1.0)',
          'default' => 0.7,
        ],
        'maxTokens' => [
          'type' => 'integer',
          'title' => 'Max Tokens',
          'description' => 'Maximum tokens for response',
          'default' => 1000,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'message' => [
          'type' => 'string',
          'title' => 'Message',
          'description' => 'The message for the agent to process',
          'required' => FALSE,
        ],
        'tools' => [
          'type' => 'array',
          'title' => 'Tools',
          'description' => 'Tools available to the agent',
          'default' => [],
        ],
      ],
    ];
  }

}
