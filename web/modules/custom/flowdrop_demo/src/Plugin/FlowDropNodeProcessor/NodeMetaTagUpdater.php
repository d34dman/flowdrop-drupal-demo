<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates meta tags on Drupal nodes without triggering workflows.
 */
#[FlowDropNodeProcessor(
  id: "node_metatag_updater",
  label: new TranslatableMarkup("Node MetaTag Updater"),
  type: "default",
  supportedTypes: ["default"],
  category: "content",
  description: "Update node meta tags (title and description) without triggering infinite loops",
  version: "1.0.0",
  tags: ["node", "metatag", "seo", "update"]
)]
class NodeMetaTagUpdater extends AbstractFlowDropNodeProcessor {

  /**
   * Flag to prevent infinite loops.
   */
  const SKIP_WORKFLOW_FLAG = 'flowdrop_skip_metatag_workflow';

  /**
   * Constructs a NodeMetaTagUpdater.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PrivateTempStoreFactory $tempStoreFactory,
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
      $container->get('tempstore.private'),
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
    // Get node ID.
    $node_id = $inputs->get('node_id') ?? $config->getConfig('nodeId');

    if (!$node_id) {
      throw new \Exception('No node ID provided');
    }

    // Get meta tag values from input (from LLM).
    $meta_title = $inputs->get('meta_title') ?? $inputs->get('title');
    $meta_description = $inputs->get('meta_description') ?? $inputs->get('description');

    // Handle case where arrays are passed instead of strings.
    // This can happen if the entire parsed_data is incorrectly passed.
    if (is_array($meta_title)) {
      // If it's an array with meta_title key, extract it.
      if (isset($meta_title['meta_title']) && is_string($meta_title['meta_title'])) {
        $meta_title = $meta_title['meta_title'];
      }
      else {
        $this->getLogger()->error('meta_title input is an array instead of string: @data', [
          '@data' => json_encode($meta_title),
        ]);
        throw new \Exception('meta_title must be a string, received array. Check workflow connections.');
      }
    }

    if (is_array($meta_description)) {
      // If it's an array with meta_description key, extract it.
      if (isset($meta_description['meta_description']) && is_string($meta_description['meta_description'])) {
        $meta_description = $meta_description['meta_description'];
      }
      else {
        $this->getLogger()->error('meta_description input is an array instead of string: @data', [
          '@data' => json_encode($meta_description),
        ]);
        throw new \Exception('meta_description must be a string, received array. Check workflow connections.');
      }
    }

    if (empty($meta_title) && empty($meta_description)) {
      throw new \Exception('No meta tag values provided (meta_title or meta_description)');
    }

    // Load the node.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($node_id);

    if (!$node) {
      throw new \Exception("Node {$node_id} not found");
    }

    // Check if node has metatag field.
    if (!$node->hasField('field_metatag')) {
      $this->getLogger()->warning('Node @nid does not have field_metatag field. Skipping metatag update.', [
        '@nid' => $node->id(),
      ]);

      return [
        'success' => FALSE,
        'node_id' => $node->id(),
        'message' => 'Node does not have metatag field',
      ];
    }

    // Set flag to prevent infinite loop.
    $tempstore = $this->tempStoreFactory->get('flowdrop_metatag_updater');
    $tempstore->set(self::SKIP_WORKFLOW_FLAG . '_' . $node_id, TRUE);

    // Get existing metatag data.
    $metatag_value = $node->get('field_metatag')->value;
    $metatag_data = [];

    if (!empty($metatag_value)) {
      if (is_array($metatag_value)) {
        // Already an array.
        $metatag_data = $metatag_value;
      }
      elseif (is_string($metatag_value)) {
        // Try to unserialize (PHP serialized format).
        $unserialized = @unserialize($metatag_value);
        if ($unserialized !== FALSE) {
          $metatag_data = $unserialized;
        }
        else {
          // Try JSON decode as fallback.
          $json_decoded = @json_decode($metatag_value, TRUE);
          if ($json_decoded !== NULL) {
            $metatag_data = $json_decoded;
          }
          else {
            // Log warning and start with empty array.
            $this->getLogger()->warning('Could not parse metatag data for node @nid. Starting fresh.', [
              '@nid' => $node->id(),
            ]);
            $metatag_data = [];
          }
        }
      }
    }

    // Update meta tags.
    $updates = [];
    if ($meta_title) {
      $metatag_data['title'] = $meta_title;
      $updates['title'] = $meta_title;
    }
    if ($meta_description) {
      $metatag_data['description'] = $meta_description;
      $updates['description'] = $meta_description;
    }

    // Save metatag field.
    // The metatag field stores data as JSON in Drupal 11.
    $node->set('field_metatag', json_encode($metatag_data));

    // Save node without triggering hooks that would start the workflow again.
    try {
      $node->save();

      $this->getLogger()->info('Updated meta tags for node @nid (@title): @updates', [
        '@nid' => $node->id(),
        '@title' => $node->getTitle(),
        '@updates' => json_encode($updates),
      ]);

      // Clean up flag after a delay (using tempstore expiration).
      // The tempstore will auto-expire after 1 week by default.

      return [
        'success' => TRUE,
        'node_id' => $node->id(),
        'node_title' => $node->getTitle(),
        'updated_fields' => $updates,
        'message' => 'Meta tags updated successfully',
      ];
    }
    catch (\Exception $e) {
      // Clean up flag on error.
      $tempstore->delete(self::SKIP_WORKFLOW_FLAG . '_' . $node_id);

      throw new \Exception("Failed to update node: " . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Must have node_id AND at least one meta tag value.
    $has_node_id = !empty($inputs['node_id']);
    $has_meta_tags = !empty($inputs['meta_title']) || !empty($inputs['meta_description'])
      || !empty($inputs['title']) || !empty($inputs['description']);

    return $has_node_id && $has_meta_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'node_id' => [
          'type' => 'integer',
          'title' => 'Node ID',
          'description' => 'ID of the node to update',
          'required' => TRUE,
        ],
        'meta_title' => [
          'type' => 'string',
          'title' => 'Meta Title',
          'description' => 'SEO meta title (55-60 characters recommended)',
          'required' => FALSE,
        ],
        'meta_description' => [
          'type' => 'string',
          'title' => 'Meta Description',
          'description' => 'SEO meta description (150-160 characters recommended)',
          'required' => FALSE,
        ],
        'title' => [
          'type' => 'string',
          'title' => 'Title (alternative)',
          'description' => 'Alternative field name for meta title',
          'required' => FALSE,
        ],
        'description' => [
          'type' => 'string',
          'title' => 'Description (alternative)',
          'description' => 'Alternative field name for meta description',
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
        'success' => [
          'type' => 'boolean',
          'description' => 'Whether the update was successful',
        ],
        'node_id' => [
          'type' => 'integer',
          'description' => 'The updated node ID',
        ],
        'node_title' => [
          'type' => 'string',
          'description' => 'The node title',
        ],
        'updated_fields' => [
          'type' => 'object',
          'description' => 'Fields that were updated',
        ],
        'message' => [
          'type' => 'string',
          'description' => 'Status message',
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
        'nodeId' => [
          'type' => 'integer',
          'title' => 'Node ID (Fallback)',
          'description' => 'Fallback node ID if not provided in input',
          'required' => FALSE,
        ],
      ],
    ];
  }

  /**
   * Checks if workflow should be skipped for this node.
   *
   * Call this from the ECA trigger service to prevent infinite loops.
   *
   * @param int $node_id
   *   The node ID.
   *
   * @return bool
   *   TRUE if workflow should be skipped.
   */
  public static function shouldSkipWorkflow(int $node_id): bool {
    $tempstore = \Drupal::service('tempstore.private')->get('flowdrop_metatag_updater');
    $skip = $tempstore->get(self::SKIP_WORKFLOW_FLAG . '_' . $node_id);

    if ($skip) {
      // Clear flag after checking.
      $tempstore->delete(self::SKIP_WORKFLOW_FLAG . '_' . $node_id);
      return TRUE;
    }

    return FALSE;
  }

}
