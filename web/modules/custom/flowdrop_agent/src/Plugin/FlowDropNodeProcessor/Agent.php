<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\flowdrop_agent\Service\AgentExecutor;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Agent node processor for LLM-driven tool calling.
 *
 * This node type orchestrates the ReAct loop where an LLM decides
 * which tools to call based on user prompts and available tools.
 */
#[FlowDropNodeProcessor(
  id: "agent",
  label: new TranslatableMarkup("Agent"),
  type: "agent",
  supportedTypes: ["agent"],
  category: "ai",
  description: "LLM-powered agent that can call tools dynamically",
  version: "1.0.0",
  tags: ["agent", "ai", "tool-calling", "llm", "autonomous", "react"]
)]
class Agent extends AbstractFlowDropNodeProcessor {

  /**
   * The agent executor service.
   *
   * @var \Drupal\flowdrop_agent\Service\AgentExecutor
   */
  protected AgentExecutor $agentExecutor;

  /**
   * Constructs a new Agent object.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   * @param \Drupal\flowdrop_agent\Service\AgentExecutor $agentExecutor
   *   The agent executor service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    AgentExecutor $agentExecutor,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->agentExecutor = $agentExecutor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('flowdrop_agent.executor'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('flowdrop_agent');
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $inputs): bool {
    // At minimum, we need a prompt.
    if (empty($inputs['prompt']) && !is_string($inputs['prompt'] ?? NULL)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresSpecialOrchestration(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(ParameterBagInterface $params): array {
    // This is a fallback implementation.
    // The orchestrator should intercept agent nodes and use AgentExecutor.
    // If we reach here, it means the orchestrator didn't handle this.
    $this->getLogger()->warning(
      'Agent process() called directly - orchestrator should handle this'
    );

    return [
      'answer' => NULL,
      'status' => 'error',
      'iterations' => 0,
      'tokensUsed' => 0,
      'executionTimeMs' => 0,
      'steps' => [],
      'availableTools' => [],
      'error' => 'Agent should be executed via AgentExecutor service',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'prompt' => [
          'type' => 'string',
          'title' => 'Prompt',
          'description' => 'The user prompt/question for the agent',
          'required' => TRUE,
        ],
        'context' => [
          'type' => 'object',
          'title' => 'Context',
          'description' => 'Additional context data for the agent',
          'required' => FALSE,
        ],
        'conversationId' => [
          'type' => 'string',
          'title' => 'Conversation ID',
          'description' => 'ID of existing conversation to continue',
          'required' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'answer' => [
          'type' => 'string',
          'title' => 'Answer',
          'description' => 'The final answer from the agent',
        ],
        'status' => [
          'type' => 'string',
          'title' => 'Status',
          'description' => 'Execution status',
          'enum' => ['completed', 'max_iterations', 'failed'],
        ],
        'iterations' => [
          'type' => 'integer',
          'title' => 'Iterations',
          'description' => 'Number of iterations executed',
        ],
        'tokensUsed' => [
          'type' => 'integer',
          'title' => 'Tokens Used',
          'description' => 'Total tokens consumed',
        ],
        'executionTimeMs' => [
          'type' => 'number',
          'title' => 'Execution Time',
          'description' => 'Total execution time in milliseconds',
        ],
        'steps' => [
          'type' => 'array',
          'title' => 'Steps',
          'description' => 'Full execution trace steps',
        ],
        'availableTools' => [
          'type' => 'array',
          'title' => 'Available Tools',
          'description' => 'Tools that were available to the agent',
        ],
        'error' => [
          'type' => 'string',
          'title' => 'Error',
          'description' => 'Error message if execution failed',
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
        'model' => [
          'type' => 'string',
          'title' => 'Model',
          'description' => 'LLM model to use',
          'default' => 'gpt-4',
          'enum' => [
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-3.5-turbo',
            'claude-3-opus-20240229',
            'claude-3-sonnet-20240229',
            'claude-3-5-sonnet-20241022',
          ],
        ],
        'systemPrompt' => [
          'type' => 'string',
          'title' => 'System Prompt',
          'description' => 'System prompt to guide agent behavior',
          'default' => 'You are a helpful assistant with access to tools. Use tools when needed to answer questions accurately.',
        ],
        'maxIterations' => [
          'type' => 'integer',
          'title' => 'Max Iterations',
          'description' => 'Maximum tool-calling iterations',
          'default' => 10,
          'minimum' => 1,
          'maximum' => 50,
        ],
        'temperature' => [
          'type' => 'number',
          'title' => 'Temperature',
          'description' => 'LLM temperature setting',
          'default' => 0.7,
          'minimum' => 0,
          'maximum' => 2,
          'step' => '0.01',
          'format' => 'range',
        ],
        'maxTokens' => [
          'type' => 'integer',
          'title' => 'Max Tokens',
          'description' => 'Maximum tokens per LLM call',
          'default' => 1000,
          'minimum' => 1,
          'maximum' => 128000,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return 'agent';
  }

}
