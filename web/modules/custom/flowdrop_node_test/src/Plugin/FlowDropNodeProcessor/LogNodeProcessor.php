<?php

declare(strict_types=1);

namespace Drupal\flowdrop_node_test\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Log Node Processor for testing FlowDrop system.
 *
 * Logs input payload to watchdog for debugging purposes.
 */
#[FlowDropNodeProcessor(
  id: "log_node_processor",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Log Node Processor"),
  type: "log",
  supportedTypes: ["log"],
  category: "testing",
  description: "Logs input payload to watchdog for debugging",
  version: "1.0.0",
  inputs: [
    "data" => [
      "type" => "string",
      "required" => TRUE,
      "description" => "Data to log",
    ],
  ],
  outputs: [
    "data" => [
      "type" => "string",
      "required" => TRUE,
      "description" => "Passed through data",
    ],
    "logged" => [
      "type" => "boolean",
      "required" => TRUE,
      "description" => "Whether data was logged successfully",
    ],
  ],
  config: [
    "log_level" => [
      "type" => "string",
      "default" => "info",
      "description" => "Log level (debug, info, warning, error)",
    ],
    "include_timestamp" => [
      "type" => "boolean",
      "default" => TRUE,
      "description" => "Include timestamp in log message",
    ],
  ],
  tags: ["test", "log", "debug"]
)]
final class LogNodeProcessor extends AbstractFlowDropNodeProcessor {

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('flowdrop_node_test');
  }

  /**
   * {@inheritdoc}
   */
  protected function process(ParameterBagInterface $params): array {
    $data = $params->get("data", "");
    $logLevel = $params->get("log_level", "info");
    $includeTimestamp = $params->get("include_timestamp", TRUE);

    $message = (string) $data;
    if ($includeTimestamp) {
      $message = "[" . date("Y-m-d H:i:s") . "] " . $message;
    }

    // Log to watchdog using injected logger.
    $logger = $this->getLogger();
    $logged = FALSE;

    try {
      switch ($logLevel) {
        case "debug":
          $logger->debug($message);
          break;

        case "warning":
          $logger->warning($message);
          break;

        case "error":
          $logger->error($message);
          break;

        default:
          $logger->info($message);
      }
      $logged = TRUE;
    }
    catch (\Exception $e) {
      $logger->error("Failed to log message: @error", ["@error" => $e->getMessage()]);
    }

    return [
      "data" => $data,
      "logged" => $logged,
      "log_level" => $logLevel,
      "timestamp" => time(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $inputs): bool {
    if (!isset($inputs["data"])) {
      return FALSE;
    }

    $data = $inputs["data"];
    if (!is_string($data) && !is_numeric($data)) {
      return FALSE;
    }

    return TRUE;
  }

}
