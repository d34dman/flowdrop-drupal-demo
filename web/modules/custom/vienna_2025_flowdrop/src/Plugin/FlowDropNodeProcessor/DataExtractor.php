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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Executor for Data Extractor nodes.
 *
 * This processor takes JSON input, decodes it, and extracts data
 * using a property path (e.g., "[users][0][name]" or "data.user.email").
 */
#[FlowDropNodeProcessor(
  id: "data_extractor",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Data Extractor"),
  type: "default",
  supportedTypes: ["default"],
  category: "processing",
  description: "Extract data from JSON using property path notation.",
  version: "2.0.0",
  tags: ["data", "json", "extract", "property-access"]
)]
class DataExtractor extends AbstractFlowDropNodeProcessor {

  /**
   * The property accessor service.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessorInterface
   */
  protected PropertyAccessorInterface $propertyAccessor;

  /**
   * Constructs a DataExtractor object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param mixed $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Initialize the property accessor
    $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
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
  public function validateInputs(array $inputs): bool {
    // Validate that we have either JSON string input or data input
    return isset($inputs["json"]) || isset($inputs["data"]);
  }

  /**
   * {@inheritdoc}
   */
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    // Get the property path from config (e.g., "[users][0][name]" or "data.user.email")
    $propertyPath = $config->getConfig("path", "");

    // Log all available inputs for debugging
    $all_inputs = $inputs->toArray();
    $this->getLogger()->debug("DataExtractor received inputs: @inputs", [
      "@inputs" => json_encode(array_keys($all_inputs)),
    ]);

    // Determine the input source: prioritize 'data' input, fallback to 'json'
    $decodedData = NULL;
    if ($inputs->has("data")) {
      // Direct structured data input
      $decodedData = $inputs->get("data");
      $dataType = gettype($decodedData);
      $dataValue = is_string($decodedData) ? substr($decodedData, 0, 200) : (is_array($decodedData) || is_object($decodedData) ? json_encode($decodedData) : (string) $decodedData);

      $this->getLogger()->debug("Using structured 'data' input. Type: @type, Value preview: @value", [
        "@type" => $dataType,
        "@value" => substr($dataValue, 0, 200),
      ]);
    }
    elseif ($inputs->has("json")) {
      // JSON string input - needs decoding
      $jsonInput = $inputs->get("json", "");
      $inputType = gettype($jsonInput);
      $inputPreview = is_string($jsonInput) ? substr($jsonInput, 0, 200) : json_encode($jsonInput);

      $this->getLogger()->debug("Using 'json' input. Input type: @input_type, Input preview: @input_preview", [
        "@input_type" => $inputType,
        "@input_preview" => substr($inputPreview, 0, 200),
      ]);

      $decodedData = $this->normalizeJsonInput($jsonInput);

      $this->getLogger()->debug("After normalization. Type: @type, Value preview: @value", [
        "@type" => gettype($decodedData),
        "@value" => is_array($decodedData) ? json_encode($decodedData) : substr((string) $decodedData, 0, 200),
      ]);
    }
    else {
      $this->getLogger()->warning("No input provided (neither 'data' nor 'json')");
      $decodedData = [];
    }

    // Extract data using the property path
    $extractedData = $this->extractDataByPath($decodedData, $propertyPath);

    // Log the extraction
    $this->getLogger()->info("Data extracted successfully", [
      "path" => $propertyPath,
      "has_result" => $extractedData !== NULL,
    ]);

    return [
      "data" => $extractedData,
      "path" => $propertyPath,
      "success" => $extractedData !== NULL,
    ];
  }

  /**
   * Normalize JSON input to decoded data.
   *
   * Handles both JSON strings and already-decoded arrays from data flow.
   *
   * @param mixed $jsonInput
   *   The JSON input (string or array).
   *
   * @return mixed
   *   The decoded data.
   */
  private function normalizeJsonInput(mixed $jsonInput): mixed {
    // If already an array, return as-is.
    if (is_array($jsonInput)) {
      return $jsonInput;
    }

    // If string, decode it.
    if (is_string($jsonInput)) {
      return $this->decodeJson($jsonInput);
    }

    // For other types, return empty array.
    return [];
  }

  /**
   * Decodes a JSON string to a PHP variable.
   *
   * @param string $jsonString
   *   The JSON string to decode.
   *
   * @return mixed
   *   The decoded data as an associative array or NULL on error.
   *
   * @throws \RuntimeException
   *   If the JSON cannot be decoded.
   */
  private function decodeJson(string $jsonString): mixed {
    if (empty($jsonString)) {
      $this->getLogger()->warning("Empty JSON string provided");
      return [];
    }

    // Decode JSON to associative array
    $decoded = json_decode($jsonString, TRUE);

    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
      $errorMessage = json_last_error_msg();
      $this->getLogger()->error("JSON decode error: {error}", [
        "error" => $errorMessage,
      ]);
      throw new \RuntimeException("Failed to decode JSON: {$errorMessage}");
    }

    return $decoded;
  }

  /**
   * Extracts data from the decoded data using a property path.
   *
   * @param mixed $data
   *   The decoded data structure (array or object).
   * @param string $path
   *   The property path (e.g., "[users][0][name]" or "data.user.email").
   *
   * @return mixed
   *   The extracted data or NULL if the path doesn't exist.
   */
  private function extractDataByPath(mixed $data, string $path): mixed {
    // If no path is provided, return the entire data
    if (empty($path)) {
      return $data;
    }

    try {
      // Use Symfony PropertyAccess to extract data
      // This supports both array notation [key] and object notation .property
      if ($this->propertyAccessor->isReadable($data, $path)) {
        return $this->propertyAccessor->getValue($data, $path);
      }
      else {
        $this->getLogger()->warning("Property path not readable: {path}", [
          "path" => $path,
        ]);
        return NULL;
      }
    }
    catch (\Exception $e) {
      $this->getLogger()->error("Error extracting data: {error}", [
        "error" => $e->getMessage(),
        "path" => $path,
      ]);
      return NULL;
    }
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
          "description" => "The extracted data from the JSON using the property path",
        ],
        "path" => [
          "type" => "string",
          "description" => "The property path used for extraction",
        ],
        "success" => [
          "type" => "boolean",
          "description" => "Whether the extraction was successful",
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
        "path" => [
          "type" => "string",
          "title" => "Property Path",
          "description" => "Property path to extract data (e.g., \"[users][0][name]\" or \"data.user.email\"). Leave empty to return entire JSON.",
          "default" => "",
        ],
      ],
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
          "title" => "JSON Input",
          "description" => "JSON string to extract data from (alternative to 'data' input)",
          "required" => FALSE,
        ],
        "data" => [
          "type" => "mixed",
          "title" => "Data Input",
          "description" => "Structured data (array/object) to extract from (alternative to 'json' input)",
          "required" => FALSE,
        ],
      ],
    ];
  }

}
