<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts structured data to JSON string.
 *
 * Takes structured data (array/object) as input and outputs a JSON string.
 */
#[FlowDropNodeProcessor(
  id: "data_to_json",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Data to JSON"),
  type: "default",
  supportedTypes: ["default"],
  description: "Convert structured data (array/object) to JSON string",
  category: "utility",
  version: "1.0.0",
  tags: ["json", "converter", "utility", "encode"]
)]
class DataToJson extends AbstractFlowDropNodeProcessor {

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
      $container->get("logger.factory")
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get("flowdrop_node_processor");
  }

  /**
   * {@inheritdoc}
   */
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    $input = $inputs->get("data");

    // Convert structured data to JSON string.
    $result = $this->convertToString($input);

    $this->getLogger()->info("Data to JSON conversion completed", [
      "input_type" => gettype($input),
      "output_length" => strlen($result),
      "success" => $result !== "",
    ]);

    return [
      "json" => $result,
      "success" => $result !== "",
    ];
  }

  /**
   * Convert input to JSON string.
   *
   * @param mixed $input
   *   The input data.
   *
   * @return string
   *   The JSON string.
   */
  private function convertToString(mixed $input): string {
    // If already a string, return as-is.
    if (is_string($input)) {
      return $input;
    }

    // Convert to JSON string.
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    $result = json_encode($input, $flags);

    if ($result === FALSE) {
      $this->getLogger()->error("Failed to encode JSON: @error", [
        "@error" => json_last_error_msg(),
      ]);
      return "{}";
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Accept any input.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "json" => [
          "type" => "string",
          "description" => "The JSON string representation",
        ],
        "success" => [
          "type" => "boolean",
          "description" => "Whether the conversion was successful",
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
      "properties" => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      "type" => "object",
      "properties" => [
        "data" => [
          "type" => "mixed",
          "title" => "Data",
          "description" => "The structured data to encode as JSON (array/object)",
          "required" => TRUE,
        ],
      ],
    ];
  }

}

