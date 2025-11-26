<?php

declare(strict_types=1);

namespace Drupal\flowdrop_node_processor\Plugin\FlowDropNodeProcessor;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executor for Chat Model nodes.
 */
#[FlowDropNodeProcessor(
  id: "chat_model",
  label: new TranslatableMarkup("Chat Model"),
  type: "default",
  supportedTypes: ["default"],
  category: "ai",
  description: "AI chat model integration",
  version: "1.0.0",
  tags: ["ai", "chat", "model"]
)]
class ChatModel extends AbstractFlowDropNodeProcessor {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected ConfigFactoryInterface $configFactory,
    protected AiProviderPluginManager $aiProviderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('config.factory'),
      $container->get('ai.provider')
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
    // Get configuration - if model is empty, AI module will use its default.
    $model = $config->getConfig('model', '');
    $temperature = (float) $config->getConfig('temperature', 0.7);
    $max_tokens = (int) $config->getConfig('maxTokens', 1000);
    $system_prompt = $config->getConfig('systemPrompt', '');
    $prompt = $config->getConfig('prompt', '');

    // Get the input message.
    $message = $inputs->get('message', '');

    // Combine prompt prefix with message if prompt is configured.
    if (!empty($prompt)) {
      $message = $prompt . ' ' . $message;
    }

    if (empty($message)) {
      throw new \RuntimeException('No message provided to chat model');
    }

    $this->getLogger()->info('Chat model executing with message length: @length', [
      'length' => strlen($message),
    ]);

    try {
      // Build the messages array using ChatMessage objects.
      $messages = [
        new ChatMessage('user', $message),
      ];

      // Create ChatInput object.
      $chat_input = new ChatInput($messages);

      // Set system prompt if provided.
      if (!empty($system_prompt)) {
        $chat_input->setSystemPrompt($system_prompt);
      }

      // Get the actual provider and model to use.
      $ai_settings = $this->configFactory->get('ai.settings');
      $default_providers = $ai_settings->get('default_providers');
      $provider_id = $default_providers['chat']['provider_id'] ?? '';

      if (empty($provider_id)) {
        throw new \RuntimeException('No AI provider configured. Please configure a default chat provider in AI settings.');
      }

      // If no model specified, use default from ai.settings.
      $model_used = $model ?: ($default_providers['chat']['model_id'] ?? '');

      if (empty($model_used)) {
        throw new \RuntimeException('No model configured. Please configure a default chat model in AI settings or specify one in the node configuration.');
      }

      // Create provider instance using the plugin manager.
      $provider = $this->aiProviderManager->createInstance($provider_id);

      // Configure the provider with temperature and max_tokens.
      $provider->setConfiguration(array_merge(
        $provider->getConfiguration(),
        [
          'temperature' => $temperature,
          'max_tokens' => $max_tokens,
        ]
      ));

      // Use the AI module's provider abstraction - works with any provider.
      // Signature: chat(ChatInput $input, string $model_id, array $tags = [])
      $response = $provider->chat(
        $chat_input,
        $model_used,
        [] // Tags
      );

      // Extract content from response.
      $content = $this->extractContentFromResponse($response);

      $this->getLogger()->info('Chat model executed successfully (@provider/@model)', [
        '@provider' => $provider_id,
        '@model' => $model_used,
      ]);

      return [
        'response' => $content,
        'model' => $model_used,
        'provider' => $provider_id,
        'temperature' => $temperature,
        'tokens_used' => 0,
      ];
    }
    catch (\Exception $e) {
      $this->getLogger()->error('Chat model execution failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Extracts content from AI provider response.
   *
   * @param mixed $response
   *   The response from the AI provider.
   *
   * @return string
   *   The extracted content.
   */
  protected function extractContentFromResponse($response): string {
    // Handle response object with getNormalized method.
    if (is_object($response) && method_exists($response, 'getNormalized')) {
      $normalized = $response->getNormalized();

      // If normalized is a ChatMessage object.
      if ($normalized instanceof ChatMessage) {
        return $normalized->getText();
      }

      // If normalized is an array.
      if (is_array($normalized)) {
        return $normalized['text'] ?? '';
      }

      // If normalized is a string.
      if (is_string($normalized)) {
        return $normalized;
      }
    }

    // Handle array response (OpenAI-style).
    if (is_array($response) && isset($response['choices'][0]['message']['content'])) {
      return $response['choices'][0]['message']['content'];
    }

    // Handle string response.
    if (is_string($response)) {
      return $response;
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Chat model nodes can accept any inputs or none.
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
          'type' => 'string',
          'description' => 'The model response',
        ],
        'model' => [
          'type' => 'string',
          'description' => 'The model used',
        ],
        'temperature' => [
          'type' => 'number',
          'description' => 'The temperature setting',
        ],
        'tokens_used' => [
          'type' => 'integer',
          'description' => 'Number of tokens used',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSchema(): array {
    // Get the default chat model from AI settings.
    $ai_settings = $this->configFactory->get('ai.settings');
    $default_providers = $ai_settings->get('default_providers');
    $provider_id = $default_providers['chat']['provider_id'] ?? '';
    $default_model = $default_providers['chat']['model_id'] ?? '';

    // Build the properties array.
    $properties = [];

    // Only show model field if provider is configured.
    if (!empty($provider_id)) {
      // Get available models from the provider.
      $available_models = [];
      $model_description = 'Select a chat model';

      try {
        $provider = $this->aiProviderManager->createInstance($provider_id);
        // Get configured models for the chat operation.
        $models = $provider->getConfiguredModels('chat', []);

        if (!empty($models)) {
          // Build enum array from available models.
          $available_models = array_keys($models);

          if (!empty($default_model)) {
            $model_description = sprintf('Select a model (default: %s)', $default_model);
          }
        }
      }
      catch (\Exception $e) {
        // If we can't get models, fall back to text input.
        $model_description = 'Enter model ID or leave empty for default';
      }

      // Build the model field based on available models.
      $model_field = [
        'title' => 'Model',
        'description' => $model_description,
        'default' => $default_model,
      ];

      if (!empty($available_models)) {
        // Use select dropdown if we have models.
        $model_field['type'] = 'select';
        $model_field['enum'] = $available_models;
      }
      else {
        // Fall back to text input if no models available.
        $model_field['type'] = 'string';
      }

      $properties['model'] = $model_field;
    }
    else {
      // Show warning message when no provider configured.
      $properties['_warning'] = [
        'type' => 'info',
        'title' => '⚠️ Configuration Required',
        'description' => 'No AI provider configured for chat. Please configure a default chat provider at /admin/config/ai/settings before using this node.',
      ];
    }

    return [
      'type' => 'object',
      'properties' => array_merge($properties, [
        'temperature' => [
          'type' => 'number',
          'title' => 'Temperature',
          'description' => 'Model temperature (0.0 to 2.0)',
          'default' => 0.7,
        ],
        'maxTokens' => [
          'type' => 'integer',
          'title' => 'Max Tokens',
          'description' => 'Maximum tokens in response',
          'default' => 1000,
        ],
        'systemPrompt' => [
          'type' => 'string',
          'title' => 'System Prompt',
          'description' => 'System prompt for the model',
          'format' => 'multiline',
          'default' => '',
        ],
      ]),
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
          'description' => 'The message to send to the model',
          'required' => FALSE,
        ],
      ],
    ];
  }

}
