<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Iterator node processor for looping over data collections.
 *
 * The Iterator node implements a Langflow-style loop pattern where:
 * - Accepts an array of items via the "data" input port
 * - Outputs individual items through the "item" port for processing
 * - Receives processed results through the "loopback" port
 * - Outputs aggregated results through the "done" port when complete.
 *
 * The actual iteration execution is handled by the IteratorExecutor service,
 * which is called by the orchestrator when it detects an Iterator node.
 * This processor serves as the plugin definition and provides schema info.
 *
 * @see \Drupal\flowdrop_iterator\Service\IteratorExecutor
 */
#[FlowDropNodeProcessor(
  id: "iterator",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Iterator"),
  type: "iterator",
  supportedTypes: ["iterator"],
  category: "logic",
  description: "Iterate over an array of items, processing each through a sub-workflow",
  version: "1.0.0",
  tags: ["iterator", "loop", "foreach", "collection", "aggregate"]
)]
class Iterator extends AbstractFlowDropNodeProcessor {

  /**
   * Constructs an Iterator processor instance.
   *
   * @param array<string, mixed> $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array<string, mixed> $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get("logger.factory")
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get("flowdrop_iterator");
  }

  /**
   * {@inheritdoc}
   *
   * Note: This method should not be called directly for Iterator nodes.
   * The orchestrator detects Iterator nodes and delegates to IteratorExecutor.
   * This implementation exists as a fallback for edge cases.
   */
  protected function process(ParameterBagInterface $params): array {
    // This is a fallback implementation.
    // The orchestrator should intercept iterator nodes and use
    // IteratorExecutor. If we reach here, the orchestrator didn't handle it.
    $this->getLogger()->warning(
      "Iterator process() called directly - orchestrator should handle this"
    );

    $data = $params->get("data", []);

    // Ensure data is an array.
    if (!is_array($data)) {
      $data = [$data];
    }

    // Simple pass-through for fallback - just return the data.
    return [
      "done" => $data,
      "isComplete" => TRUE,
      "index" => count($data),
      "total" => count($data),
      "_fallback" => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $inputs): bool {
    // Iterator accepts data array or empty (will output empty array).
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "data" => [
          "type" => "array",
          "title" => "Input Data",
          "description" => "Array of items to iterate over",
          "required" => TRUE,
        ],
        "loopback" => [
          "type" => "mixed",
          "title" => "Loopback",
          "description" => "Receives processed item from sub-workflow (connects from last node in loop)",
          "required" => FALSE,
          "portType" => "loopback",
          "isSpecialPort" => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "item" => [
          "type" => "mixed",
          "title" => "Current Item",
          "description" => "Current item being processed (connects to first node in loop)",
          "portType" => "item",
        ],
        "done" => [
          "type" => "array",
          "title" => "Done",
          "description" => "Aggregated results after all iterations complete",
          "portType" => "done",
        ],
        "index" => [
          "type" => "integer",
          "title" => "Index",
          "description" => "Current iteration index (0-based)",
        ],
        "total" => [
          "type" => "integer",
          "title" => "Total",
          "description" => "Total number of items",
        ],
        "isComplete" => [
          "type" => "boolean",
          "title" => "Is Complete",
          "description" => "Whether all iterations are complete",
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "maxIterations" => [
          "type" => "integer",
          "title" => "Max Iterations",
          "description" => "Maximum number of iterations (safety limit to prevent runaway loops)",
          "default" => 1000,
          "minimum" => 1,
          "maximum" => 10000,
        ],
        "onError" => [
          "type" => "string",
          "title" => "On Error",
          "description" => "Behavior when an iteration fails",
          "default" => "fail",
          "enum" => ["fail", "skip", "retry"],
          "enumLabels" => [
            "fail" => "Fail entire iteration",
            "skip" => "Skip failed item and continue",
            "retry" => "Retry failed item",
          ],
        ],
        "maxRetries" => [
          "type" => "integer",
          "title" => "Max Retries",
          "description" => "Maximum retry attempts when onError is 'retry'",
          "default" => 3,
          "minimum" => 1,
          "maximum" => 10,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return "iterator";
  }

  /**
   * Check if this node type requires special handling by the orchestrator.
   *
   * @return bool
   *   TRUE if this node requires special orchestrator handling.
   */
  public function requiresSpecialOrchestration(): bool {
    return TRUE;
  }

  /**
   * Get the list of special port names for this node.
   *
   * Special ports are used for creating special edges (like loopback).
   *
   * @return array<string, string>
   *   Map of port name to port type (input/output).
   */
  public function getSpecialPorts(): array {
    return [
      "loopback" => "input",
      "item" => "output",
    ];
  }

}
