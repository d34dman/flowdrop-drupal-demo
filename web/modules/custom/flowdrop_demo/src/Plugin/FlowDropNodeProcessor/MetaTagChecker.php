<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Component\Serialization\Json;
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
 * Checks if node metatags need generation.
 *
 * Detects empty fields or token-only placeholders like [node:title].
 */
#[FlowDropNodeProcessor(
  id: "metatag_checker",
  label: new TranslatableMarkup("MetaTag Checker"),
  type: "default",
  supportedTypes: ["default"],
  category: "content",
  description: "Check if node metatags need generation (detects empty fields or token-only placeholders)",
  version: "1.0.0",
  tags: ["metatag", "seo", "validation", "checker"]
)]
class MetaTagChecker extends AbstractFlowDropNodeProcessor {

  /**
   * Constructs a MetaTagChecker.
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
    // Get node ID from input or config.
    // Check entity_id first (from triggers), then node_id, then config.
    $node_id = $inputs->get('entity_id') ?? $inputs->get('node_id') ?? $config->getConfig('nodeId');

    if (!$node_id) {
      throw new \Exception('No node ID provided');
    }

    // Load the node.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($node_id);

    if (!$node) {
      throw new \Exception("Node {$node_id} not found");
    }

    // Check if the node has a metatag field.
    if (!$node->hasField('field_metatag')) {
      return [
        'needs_generation' => FALSE,
        'reason' => 'no_metatag_field',
        'node_id' => $node_id,
        'node_title' => $node->getTitle(),
        'meta_title' => NULL,
        'meta_description' => NULL,
      ];
    }

    // Get current metatag values.
    $metatag_value = $node->get('field_metatag')->value;

    // Try to decode metatag value (handles both serialized and JSON formats).
    $metatags = [];
    if (!empty($metatag_value)) {
      // First, try JSON decode (modern Drupal format).
      $json_decoded = Json::decode($metatag_value, TRUE);
      if (json_last_error() === JSON_ERROR_NONE && is_array($json_decoded)) {
        $metatags = $json_decoded;
      }
      else {
        // Fall back to unserialize for legacy serialized PHP format.
        // Use @ to suppress warnings for invalid serialized data.
        $unserialized = @unserialize($metatag_value);
        if ($unserialized !== FALSE && is_array($unserialized)) {
          $metatags = $unserialized;
        }
        else {
          // Log the issue for debugging.
          $this->getLogger()->warning('Could not decode metatag value for node @nid. Value: @value', [
            '@nid' => $node_id,
            '@value' => substr($metatag_value, 0, 100),
          ]);
        }
      }
    }

    $meta_title = $metatags['title'] ?? '';
    $meta_description = $metatags['description'] ?? '';

    // Check if metatags need generation.
    $needs_generation = $this->needsGeneration($meta_title, $meta_description, $node);

    // Determine the reason.
    $reason = $this->determineReason($meta_title, $meta_description);

    $this->getLogger()->info('MetaTag check for node @nid: needs_generation=@needs, reason=@reason', [
      '@nid' => $node_id,
      '@needs' => $needs_generation ? 'yes' : 'no',
      '@reason' => $reason,
    ]);

    return [
      'needs_generation' => $needs_generation,
      'reason' => $reason,
      'node_id' => $node_id,
      'node_title' => $node->getTitle(),
      'meta_title' => $meta_title,
      'meta_description' => $meta_description,
      'has_meta_title' => !empty($meta_title),
      'has_meta_description' => !empty($meta_description),
      'is_placeholder_title' => $this->isTokenPlaceholder($meta_title),
      'is_placeholder_description' => $this->isTokenPlaceholder($meta_description),
    ];
  }

  /**
   * Checks if metatags need generation.
   *
   * @param string $meta_title
   *   The meta title.
   * @param string $meta_description
   *   The meta description.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return bool
   *   TRUE if metatags need generation.
   */
  protected function needsGeneration(string $meta_title, string $meta_description, $node): bool {
    // Empty meta title or description.
    if (empty($meta_title) || empty($meta_description)) {
      return TRUE;
    }

    // Check if either field contains only token placeholders.
    if ($this->isTokenPlaceholder($meta_title) || $this->isTokenPlaceholder($meta_description)) {
      return TRUE;
    }

    // Check if meta title is just the node title (not customized).
    if (trim($meta_title) === trim($node->getTitle())) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a value is a token placeholder.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is a token placeholder.
   */
  protected function isTokenPlaceholder(string $value): bool {
    // Check for common token patterns like [node:title], [site:name], etc.
    return (bool) preg_match('/^\[[\w:]+\]$/', trim($value));
  }

  /**
   * Determines the reason why metatags need generation.
   *
   * @param string $meta_title
   *   The meta title.
   * @param string $meta_description
   *   The meta description.
   *
   * @return string
   *   The reason code.
   */
  protected function determineReason(string $meta_title, string $meta_description): string {
    if (empty($meta_title) && empty($meta_description)) {
      return 'both_empty';
    }

    if (empty($meta_title)) {
      return 'title_empty';
    }

    if (empty($meta_description)) {
      return 'description_empty';
    }

    if ($this->isTokenPlaceholder($meta_title) && $this->isTokenPlaceholder($meta_description)) {
      return 'both_placeholders';
    }

    if ($this->isTokenPlaceholder($meta_title)) {
      return 'title_placeholder';
    }

    if ($this->isTokenPlaceholder($meta_description)) {
      return 'description_placeholder';
    }

    return 'ok';
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Node ID is required (either from input or config).
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'needs_generation' => [
          'type' => 'boolean',
          'description' => 'Whether metatags need generation',
        ],
        'reason' => [
          'type' => 'string',
          'description' => 'Reason why generation is needed',
        ],
        'node_id' => [
          'type' => 'integer',
          'description' => 'The node ID',
        ],
        'node_title' => [
          'type' => 'string',
          'description' => 'The node title',
        ],
        'meta_title' => [
          'type' => 'string',
          'description' => 'Current meta title',
        ],
        'meta_description' => [
          'type' => 'string',
          'description' => 'Current meta description',
        ],
        'has_meta_title' => [
          'type' => 'boolean',
          'description' => 'Whether node has a meta title',
        ],
        'has_meta_description' => [
          'type' => 'boolean',
          'description' => 'Whether node has a meta description',
        ],
        'is_placeholder_title' => [
          'type' => 'boolean',
          'description' => 'Whether meta title is a token placeholder',
        ],
        'is_placeholder_description' => [
          'type' => 'boolean',
          'description' => 'Whether meta description is a token placeholder',
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
        'nodeId' => [
          'type' => 'integer',
          'title' => 'Node ID (Manual)',
          'description' => 'Manual node ID if not from trigger',
          'required' => FALSE,
        ],
      ],
    ];
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
          'description' => 'Node ID from entity triggers',
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

}
