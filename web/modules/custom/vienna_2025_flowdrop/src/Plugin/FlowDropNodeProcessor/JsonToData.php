<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts JSON string to structured data.
 *
 * Takes a JSON string as input and outputs a decoded PHP array/object.
 */
#[FlowDropNodeProcessor(
  id: "json_to_data",
  label: new TranslatableMarkup("JSON to Data"),
  type: "default",
  supportedTypes: ["default"],
  category: "utility",
  description: "Convert JSON string to structured data (array/object)",
  version: "1.0.0",
  tags: ["json", "converter", "utility", "decode"]
)]
class JsonToData extends AbstractFlowDropNodeProcessor {

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
    $input = $inputs->get("json");

    // Convert JSON string to structured data.
    $result = $this->convertToData($input);

    $this->getLogger()->info("JSON to Data conversion completed", [
      "input_type" => gettype($input),
      "output_type" => gettype($result),
      "success" => $result !== NULL,
    ]);

    return [
      "data" => $result,
      "success" => $result !== NULL,
    ];
  }

  /**
   * Convert JSON string to structured data.
   *
   * @param mixed $input
   *   The input (JSON string or already-decoded data).
   *
   * @return mixed
   *   The decoded data.
   */
  private function convertToData(mixed $input): mixed {
    // If already an array/object, return as-is.
    if (is_array($input) || is_object($input)) {
      return $input;
    }

    // If not a string, return empty array.
    if (!is_string($input)) {
      $this->getLogger()->warning("Input is not a string, returning empty array");
      return [];
    }

    // If empty string, return empty array.
    if (trim($input) === "") {
      return [];
    }

    // Decode JSON string.
    $result = json_decode($input, TRUE);

    if ($result === NULL && json_last_error() !== JSON_ERROR_NONE) {
      $this->getLogger()->error("Failed to decode JSON: @error", [
        "@error" => json_last_error_msg(),
      ]);
      return [];
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
        "data" => [
          "type" => "mixed",
          "description" => "The decoded structured data (array/object)",
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
        "json" => [
          "type" => "string",
          "title" => "JSON String",
          "description" => "The JSON string to decode",
          "required" => TRUE,
        ],
      ],
    ];
  }

}

