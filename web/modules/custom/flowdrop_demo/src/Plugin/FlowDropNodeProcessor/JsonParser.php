<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parses JSON strings into structured data.
 *
 * Extracts JSON from various formats (plain JSON, Markdown code blocks)
 * and outputs all fields or specific configured fields.
 */
#[FlowDropNodeProcessor(
  id: "json_parser",
  label: new TranslatableMarkup("JSON Parser"),
  type: "default",
  supportedTypes: ["default"],
  category: "data",
  description: "Parse JSON strings and dynamically extract any fields (title, description, or any other fields)",
  version: "1.0.0",
  tags: ["json", "parse", "data", "transform"]
)]
class JsonParser extends AbstractFlowDropNodeProcessor {

  /**
   * Constructs a JsonParser.
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('flowdrop_demo');
  }

  /**
   * {@inheritdoc}
   */
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    // Get JSON string from input (supports multiple input field names).
    $json_string = $inputs->get('json') ?? $inputs->get('response') ?? $inputs->get('text', '');

    if (empty($json_string)) {
      throw new \Exception('No JSON string provided');
    }

    // Try to extract JSON from Markdown code blocks if present.
    // This handles LLM responses that wrap JSON in ```json...``` blocks.
    $json_string = $this->extractJsonFromMarkdown($json_string);

    // Parse JSON.
    $data = JSON::decode($json_string);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception('Invalid JSON: ' . json_last_error_msg() . "\n\nReceived: " . substr($json_string, 0, 500));
    }

    // Get specific fields to extract from configuration.
    $extract_fields = $config->getConfig('extractFields', []);

    // Ensure extractFields is always an array (config might return string or null).
    if (!is_array($extract_fields)) {
      $extract_fields = [];
    }

    $output = [
      'parsed_data' => $data,
      'json_valid' => TRUE,
    ];

    // Extract fields based on configuration.
    if (!empty($extract_fields)) {
      // Extract only the specified fields (e.g., ["meta_title", "meta_description"]).
      foreach ($extract_fields as $field) {
        if (isset($data[$field])) {
          $output[$field] = $data[$field];
        }
      }
    }
    else {
      // No specific fields configured - flatten all top-level fields.
      // This makes any field in the JSON directly accessible in downstream nodes.
      foreach ($data as $key => $value) {
        $output[$key] = $value;
      }
    }

    $this->getLogger()->info('Parsed JSON successfully with @count fields', [
      '@count' => count($data),
    ]);

    return $output;
  }

  /**
   * Extracts JSON from Markdown code blocks.
   *
   * LLMs often return JSON wrapped in ```json ... ``` blocks.
   *
   * @param string $text
   *   The text that may contain JSON.
   *
   * @return string
   *   The extracted JSON string.
   */
  protected function extractJsonFromMarkdown(string $text): string {
    // Try to extract from Markdown code block.
    if (preg_match('/```(?:json)?\s*\n(.*?)\n```/s', $text, $matches)) {
      return trim($matches[1]);
    }

    // Try to find JSON object/array in the text.
    if (preg_match('/(\{.*\}|\[.*\])/s', $text, $matches)) {
      return trim($matches[1]);
    }

    return trim($text);
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    return !empty($inputs['json']) || !empty($inputs['response']) || !empty($inputs['text']);
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'json' => [
          'type' => 'string',
          'title' => 'JSON String',
          'description' => 'JSON string to parse',
          'required' => FALSE,
        ],
        'response' => [
          'type' => 'string',
          'title' => 'Response (alternative)',
          'description' => 'Alternative input field name',
          'required' => FALSE,
        ],
        'text' => [
          'type' => 'string',
          'title' => 'Text (alternative)',
          'description' => 'Alternative input field name',
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
        'parsed_data' => [
          'type' => 'object',
          'description' => 'The complete parsed JSON data as an associative array',
        ],
        'json_valid' => [
          'type' => 'boolean',
          'description' => 'Whether JSON parsing was successful',
        ],
      ],
      'description' => 'All fields from the parsed JSON are dynamically added to the output. If extractFields is configured, only specified fields are included. Otherwise, all top-level fields are flattened into the output (e.g., meta_title, meta_description, title, description, etc.).',
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
          'default' => 'default',
          'enum' => ["default"],
        ],
        'extractFields' => [
          'type' => 'array',
          'title' => 'Fields to Extract',
          'description' => 'Specific JSON field names to extract (e.g., ["meta_title", "meta_description"]). Leave empty to extract all top-level fields from the JSON.',
          'items' => [
            'type' => 'string',
          ],
          'default' => [],
        ],
      ],
    ];
  }

}
