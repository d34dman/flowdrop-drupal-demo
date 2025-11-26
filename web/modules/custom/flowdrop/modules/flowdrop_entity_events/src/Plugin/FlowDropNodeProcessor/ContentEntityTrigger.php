<?php

declare(strict_types=1);

namespace Drupal\flowdrop_entity_events\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Drupal\flowdrop\DTO\Output;
use Drupal\flowdrop\DTO\OutputInterface;
use Drupal\flowdrop_entity_events\Service\EntityDataExtractor;
use Drupal\flowdrop_entity_events\Service\WorkflowMatcher;
use Drupal\flowdrop_node_processor\Plugin\FlowDropNodeProcessor\AbstractTrigger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trigger workflows on Drupal content entity lifecycle events.
 *
 * Listens to entity events (insert, update, delete, etc.) and automatically
 * triggers FlowDrop workflows based on configured filters.
 */
#[FlowDropNodeProcessor(
  id: 'content_entity_trigger',
  label: new TranslatableMarkup('Content Entity Trigger'),
  type: 'trigger',
  supportedTypes: ['trigger'],
  category: 'triggers',
  description: 'Trigger workflows on entity lifecycle events (insert, update, delete, etc.)',
  version: '1.0.0',
  tags: ['entity', 'trigger', 'events', 'lifecycle']
)]
final class ContentEntityTrigger extends AbstractTrigger {

  /**
   * Event type definitions matching Drupal's entity lifecycle events.
   */
  public const EVENT_TYPES = [
    'create' => 'Initialize (entity created in memory)',
    'presave' => 'Presave (before save)',
    'insert' => 'Insert (after new entity saved)',
    'update' => 'Update (after existing entity saved)',
    'predelete' => 'Predelete (before delete)',
    'delete' => 'Delete (after delete)',
    'load' => 'Load (entity loaded)',
    'view' => 'View (entity rendered)',
    'translation_create' => 'Translation Create',
    'translation_insert' => 'Translation Insert',
    'translation_delete' => 'Translation Delete',
  ];

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a ContentEntityTrigger processor.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\flowdrop_entity_events\Service\EntityDataExtractor $entityDataExtractor
   *   The entity data extractor service.
   * @param \Drupal\flowdrop_entity_events\Service\WorkflowMatcher $workflowMatcher
   *   The workflow matcher service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityDataExtractor $entityDataExtractor,
    private readonly WorkflowMatcher $workflowMatcher,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $loggerFactory->get('flowdrop_entity_events');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('flowdrop_entity_events.entity_data_extractor'),
      $container->get('flowdrop_entity_events.workflow_matcher'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->logger;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTriggerType(): string {
    return 'content_entity';
  }

  /**
   * {@inheritdoc}
   */
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    return parent::execute($inputs, $config)->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $inputs, ConfigInterface $config): OutputInterface {
    $output = parent::execute($inputs, $config);

    // Extract entity data from inputs.
    $input_array = $inputs->toArray();
    $entity_data = $input_array['entity'] ?? [];
    $event_type = $input_array['event_type'] ?? 'unknown';

    // Build comprehensive output for downstream nodes.
    $output_data = [
      'entity' => $entity_data,
      'entity_id' => $entity_data['id'] ?? NULL,
      'entity_type' => $entity_data['entity_type'] ?? NULL,
      'bundle' => $entity_data['bundle'] ?? NULL,
      'label' => $entity_data['label'] ?? NULL,
      'event_type' => $event_type,
      'event_data' => $input_array['event_data'] ?? [],
      'timestamp' => time(),
    ];

    // For update events, include changed fields.
    if ($event_type === 'update' && isset($entity_data['original'])) {
      $output_data['original_entity'] = $entity_data['original'];

      // Calculate changed fields if we have the actual entity objects.
      if (isset($input_array['_entity']) && isset($input_array['_original_entity'])) {
        $entity = $input_array['_entity'];
        $original = $input_array['_original_entity'];

        if ($entity instanceof EntityInterface && $original instanceof EntityInterface) {
          $output_data['changed_fields'] = $this->entityDataExtractor
            ->getChangedFields($entity, $original);
        }
      }
    }

    $new_output = new Output();
    $new_output->fromArray($output_data);
    $new_output->setStatus('success');

    $this->logger->info('Content Entity Trigger: @event on @type:@bundle (@id)', [
      '@event' => $event_type,
      '@type' => $output_data['entity_type'] ?? 'unknown',
      '@bundle' => $output_data['bundle'] ?? 'unknown',
      '@id' => $output_data['entity_id'] ?? 'unknown',
    ]);

    return $new_output;
  }

  /**
   * {@inheritdoc}
   */
  protected function processTriggerData(mixed $trigger_data, array $inputs): array {
    // Handle both array and non-array trigger_data (UI may pass string).
    if (!is_array($trigger_data)) {
      $trigger_data = [];
    }

    $entity_trigger_data = [];

    if (!empty($inputs)) {
      $entity_trigger_data = [
        'entity' => $inputs['entity'] ?? [],
        'event_type' => $inputs['event_type'] ?? 'unknown',
        'event_data' => $inputs['event_data'] ?? [],
        'entity_id' => $inputs['entity_id'] ?? NULL,
        'entity_type' => $inputs['entity_type'] ?? NULL,
        'bundle' => $inputs['bundle'] ?? NULL,
      ];
    }

    // Add entity event specific metadata.
    $entity_trigger_data['trigger_source'] = 'content_entity_events';
    $entity_trigger_data['timestamp'] = time();

    // Merge with configured trigger data.
    return array_merge($trigger_data, $entity_trigger_data);
  }

  /**
   * Checks if an entity matches configured filters.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param \Drupal\flowdrop\DTO\ConfigInterface $config
   *   The trigger configuration.
   *
   * @return bool
   *   TRUE if the entity matches the configured filters.
   */
  public function matchesFilter(EntityInterface $entity, ConfigInterface $config): bool {
    $filter = $config->getConfig('entity_filter', '*');
    assert(is_string($filter));

    return $this->workflowMatcher->entityMatchesFilter($entity, $filter);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSchema(): array {
    $base_schema = parent::getConfigSchema();

    $base_schema['properties']['event_type'] = [
      'type' => 'select',
      'title' => 'Event Type',
      'description' => 'Which entity lifecycle event should trigger this workflow',
      'enum' => array_keys(self::EVENT_TYPES),
      'enumNames' => array_values(self::EVENT_TYPES),
      'default' => 'insert',
      'required' => TRUE,
    ];

    $base_schema['properties']['entity_filter'] = [
      'type' => 'text',
      'title' => 'Entity Filter',
      'description' => 'Wildcard filter: * (all), node (entity type), node::article (specific bundle)',
      'default' => '*',
    ];

    $base_schema['properties']['auto_trigger'] = [
      'type' => 'boolean',
      'title' => 'Auto-trigger',
      'description' => 'Automatically start workflow when matching events occur',
      'default' => FALSE,
    ];

    $base_schema['properties']['execution_mode'] = [
      'type' => 'select',
      'title' => 'Execution Mode',
      'description' => 'How to execute the workflow when triggered',
      'enum' => ['async', 'sync'],
      'enumNames' => [
        'Asynchronous (queue-based, recommended)',
        'Synchronous (immediate, blocks request)',
      ],
      'default' => 'async',
    ];

    return $base_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    $base_schema = parent::getOutputSchema();

    $base_schema['properties']['entity'] = [
      'type' => 'object',
      'description' => 'Full entity data including all fields',
    ];

    $base_schema['properties']['entity_id'] = [
      'type' => 'integer',
      'description' => 'Entity ID',
    ];

    $base_schema['properties']['entity_type'] = [
      'type' => 'string',
      'description' => 'Entity type (node, user, taxonomy_term, etc.)',
    ];

    $base_schema['properties']['bundle'] = [
      'type' => 'string',
      'description' => 'Bundle (article, page, etc.)',
    ];

    $base_schema['properties']['label'] = [
      'type' => 'string',
      'description' => 'Entity label/title',
    ];

    $base_schema['properties']['event_type'] = [
      'type' => 'string',
      'description' => 'The event type that triggered (insert, update, etc.)',
    ];

    $base_schema['properties']['original_entity'] = [
      'type' => 'object',
      'description' => 'Original entity data (for update events)',
    ];

    $base_schema['properties']['changed_fields'] = [
      'type' => 'array',
      'description' => 'List of fields that changed (for update events)',
      'items' => [
        'type' => 'string',
      ],
    ];

    $base_schema['properties']['timestamp'] = [
      'type' => 'integer',
      'description' => 'Unix timestamp when the event occurred',
    ];

    return $base_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    $base_schema = parent::getInputSchema();

    $base_schema['properties']['entity'] = [
      'type' => 'object',
      'title' => 'Entity',
      'description' => 'The entity data',
      'required' => FALSE,
    ];

    $base_schema['properties']['event_type'] = [
      'type' => 'string',
      'title' => 'Event Type',
      'description' => 'The event type',
      'required' => FALSE,
    ];

    $base_schema['properties']['event_data'] = [
      'type' => 'object',
      'title' => 'Event Data',
      'description' => 'Additional event metadata',
      'required' => FALSE,
    ];

    return $base_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Trigger nodes don't require inputs - they're the starting point.
    return TRUE;
  }

}
