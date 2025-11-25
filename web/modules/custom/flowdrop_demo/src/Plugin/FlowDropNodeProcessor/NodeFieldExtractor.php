<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extracts field values from Drupal nodes.
 */
#[FlowDropNodeProcessor(
  id: "node_field_extractor",
  label: new TranslatableMarkup("Node Field Extractor"),
  type: "default",
  supportedTypes: ["default"],
  category: "content",
  description: "Extract field values from Drupal nodes",
  version: "1.0.0",
  tags: ["node", "fields", "drupal", "content"]
)]
class NodeFieldExtractor extends AbstractFlowDropNodeProcessor {

  /**
   * Constructs a NodeFieldExtractor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
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
    // Get node ID from input (from ECA trigger).
    $node_id = $inputs->get('entity_id') ?? $inputs->get('node_id');

    // Allow manual node ID input if not from trigger.
    if (!$node_id) {
      $node_id = $config->getConfig('nodeId');
    }

    if (!$node_id) {
      throw new \Exception('No node ID provided');
    }

    // Load the node.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($node_id);

    if (!$node) {
      throw new \Exception("Node {$node_id} not found");
    }

    // Get field name to extract.
    $field_name = $config->getConfig('fieldName', 'body');
    $extract_all = $config->getConfig('extractAll', FALSE);

    $output = [
      'node_id' => $node->id(),
      'node_title' => $node->getTitle(),
      'node_type' => $node->bundle(),
      'node_status' => $node->isPublished() ? 'published' : 'unpublished',
    ];

    if ($extract_all) {
      // Extract all field values.
      $fields = [];
      foreach ($node->getFields() as $field_name => $field) {
        if (!$field->isEmpty() && !in_array($field_name, ['uuid', 'vid', 'revision_timestamp'])) {
          $fields[$field_name] = $this->extractFieldValue($field);
        }
      }
      $output['fields'] = $fields;
    }
    else {
      // Extract specific field.
      if (!$node->hasField($field_name)) {
        throw new \Exception("Node does not have field '{$field_name}'");
      }

      $field = $node->get($field_name);
      if ($field->isEmpty()) {
        $this->getLogger()->warning('Field @field is empty for node @nid', [
          '@field' => $field_name,
          '@nid' => $node->id(),
        ]);
        $output['field_value'] = '';
        $output['field_name'] = $field_name;
      }
      else {
        $output['field_value'] = $this->extractFieldValue($field);
        $output['field_name'] = $field_name;
      }
    }

    $this->getLogger()->info('Extracted fields from node @nid (@title)', [
      '@nid' => $node->id(),
      '@title' => $node->getTitle(),
    ]);

    return $output;
  }

  /**
   * Extracts value from a field based on its type.
   */
  protected function extractFieldValue($field) {
    $field_type = $field->getFieldDefinition()->getType();

    switch ($field_type) {
      case 'text_long':
      case 'text_with_summary':
        // Body field - get processed value.
        $value = $field->value ?? '';
        // Strip HTML tags for clean text.
        return strip_tags($value);

      case 'string':
      case 'string_long':
      case 'text':
        return $field->value ?? '';

      case 'entity_reference':
        // Return referenced entity IDs.
        $values = [];
        foreach ($field as $item) {
          if ($item->entity) {
            $values[] = [
              'id' => $item->target_id,
              'label' => $item->entity->label(),
            ];
          }
        }
        return $values;

      case 'image':
      case 'file':
        $values = [];
        foreach ($field as $item) {
          if ($item->entity) {
            $values[] = [
              'url' => $item->entity->createFileUrl(),
              'filename' => $item->entity->getFilename(),
            ];
          }
        }
        return $values;

      default:
        // Generic handling - return raw value.
        return $field->value ?? json_encode($field->getValue());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Either entity_id or node_id must be provided, or nodeId in config.
    return !empty($inputs['entity_id']) || !empty($inputs['node_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'entity_id' => [
          'type' => 'integer',
          'title' => 'Entity ID',
          'description' => 'Node ID from ECA trigger',
          'required' => FALSE,
        ],
        'node_id' => [
          'type' => 'integer',
          'title' => 'Node ID',
          'description' => 'Alternative node ID input',
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
        'node_id' => [
          'type' => 'integer',
          'description' => 'The node ID',
        ],
        'node_title' => [
          'type' => 'string',
          'description' => 'The node title',
        ],
        'node_type' => [
          'type' => 'string',
          'description' => 'The node content type',
        ],
        'node_status' => [
          'type' => 'string',
          'description' => 'Node publication status',
        ],
        'field_value' => [
          'type' => 'string',
          'description' => 'Extracted field value',
        ],
        'field_name' => [
          'type' => 'string',
          'description' => 'Name of the extracted field',
        ],
        'fields' => [
          'type' => 'object',
          'description' => 'All extracted fields (if extractAll enabled)',
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
          'default' => 'default',
          'enum' => ["default"],
        ],
        'fieldName' => [
          'type' => 'string',
          'title' => 'Field Name',
          'description' => 'Machine name of the field to extract (e.g., body, field_description)',
          'default' => 'body',
        ],
        'extractAll' => [
          'type' => 'boolean',
          'title' => 'Extract All Fields',
          'description' => 'Extract all fields instead of just one',
          'default' => FALSE,
        ],
        'nodeId' => [
          'type' => 'integer',
          'title' => 'Node ID (Manual)',
          'description' => 'Manual node ID if not from trigger',
          'required' => FALSE,
        ],
      ],
    ];
  }

}
