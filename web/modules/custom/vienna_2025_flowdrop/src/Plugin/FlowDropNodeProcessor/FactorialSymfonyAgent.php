<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\key\KeyRepository;
use Drupal\vienna_2025_flowdrop\Tool\DrupalOrgLookup;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Tool\Tool;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Agent\Toolbox\ToolFactory\MemoryToolFactory;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\Component\Clock\Clock;

/**
 * Executor for Factorial Agent nodes.
 */
#[FlowDropNodeProcessor(
  id: "factorial_symfony_agent",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Factorial Symfony Agent"),
  description: "Simple AI agent implementation based on Symfony AI",
  version: "1.0.0",
)]
class FactorialSymfonyAgent extends AbstractFlowDropNodeProcessor {


  /**
   * The OpenAI API key.
   */
  protected string $openaiApiKey;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
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
  public function process(ParameterBagInterface $params): array {
    $systemPrompt = (string) $params->get('systemPrompt', 'You are a helpful assistant.');
    $temperature = (float) $params->get('temperature', 0.7);
    $maxTokens = (int) $params->get('maxTokens', 1000);
    $tools = (array) $params->get('tools', []);
    $message = (string) $params->get('message');

    $platform = PlatformFactory::create($this->openaiApiKey);
    $model = (string) $params->get('model');

    $lookupTool = new DrupalOrgLookup();
    $toolbox = new Toolbox([$lookupTool]);
    $toolProcessor = new AgentProcessor($toolbox);
    $agent = new Agent($platform, $model, inputProcessors: [$toolProcessor], outputProcessors: [$toolProcessor]);

    $messages = new MessageBag(
      Message::forSystem($systemPrompt),
      Message::ofUser($message),
    );
    try {
      $response = $agent->call($messages);
    }
    catch (\Exception $exception) {
      return [
        'response' => NULL,
        'system_prompt' => $systemPrompt,
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'tools_used' => $response['tools_used'] ?? [],
        'message' => "",
      ];
    }

    $this->getLogger()->info('Simple agent executed successfully', [
      'message_length' => strlen($message),
      'temperature' => $temperature,
      'max_tokens' => $maxTokens,
      'tools_count' => count($tools),
    ]);

    $output = [
      'tools_used' => [],
      'message' => $response->getContent(),
    ];
    return $output;
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
  public function getParameterSchema(): array {
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
        'model' => [
          'type' => 'string',
          'title' => 'OpenAI Model',
          'default' => 'gpt-4o-mini',
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
          'step' => 0.01,
          'minimum' => 0,
          'maximum' => 1,
          'format' => 'range',
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

}
