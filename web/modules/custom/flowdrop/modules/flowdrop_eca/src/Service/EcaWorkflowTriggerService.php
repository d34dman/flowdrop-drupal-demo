<?php

declare(strict_types=1);

namespace Drupal\flowdrop_eca\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Service for automatically triggering FlowDrop workflows based on ECA events.
 */
class EcaWorkflowTriggerService {

  /**
   * The logger channel.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs an EcaWorkflowTriggerService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('flowdrop_eca');
  }

  /**
   * Triggers workflows based on entity events.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the event.
   * @param string $event_type
   *   The event type (insert, update, delete).
   */
  public function triggerWorkflows(EntityInterface $entity, string $event_type): void {
    // Check if workflow should be skipped (e.g., to prevent infinite loops).
    if ($entity->getEntityTypeId() === 'node' && $this->shouldSkipWorkflow($entity)) {
      $this->logger->info('Skipping workflow for node @nid (flagged to prevent infinite loop)', [
        '@nid' => $entity->id(),
      ]);
      return;
    }

    // Find all workflows with matching ECA triggers.
    $matching_workflows = $this->findMatchingWorkflows($entity, $event_type);

    if (empty($matching_workflows)) {
      return;
    }

    $this->logger->info('Found @count matching workflows for @entity_type @event_type event', [
      '@count' => count($matching_workflows),
      '@entity_type' => $entity->getEntityTypeId(),
      '@event_type' => $event_type,
    ]);

    // Execute each matching workflow.
    foreach ($matching_workflows as $workflow_id => $trigger_config) {
      $this->executeWorkflow($workflow_id, $entity, $event_type, $trigger_config);
    }
  }

  /**
   * Finds workflows with ECA triggers matching the entity event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the event.
   * @param string $event_type
   *   The event type (insert, update, delete).
   *
   * @return array
   *   Array of matching workflow IDs and their trigger configurations.
   */
  protected function findMatchingWorkflows(EntityInterface $entity, string $event_type): array {
    $matching = [];

    try {
      $workflow_storage = $this->entityTypeManager->getStorage('flowdrop_workflow');
      $workflows = $workflow_storage->loadMultiple();

      foreach ($workflows as $workflow) {
        // Get workflow nodes.
        $nodes = $workflow->get('nodes') ?? [];

        // Find ECA trigger nodes.
        foreach ($nodes as $node) {
          if (!$this->isEcaTriggerNode($node)) {
            continue;
          }

          // Check if this trigger matches the event.
          if ($this->triggerMatchesEvent($node, $entity, $event_type)) {
            $matching[$workflow->id()] = $node['data']['config'] ?? [];
            // Only take the first matching trigger per workflow.
            break;
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error finding matching workflows: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $matching;
  }

  /**
   * Checks if a workflow node is an ECA trigger.
   *
   * @param array $node
   *   The workflow node configuration.
   *
   * @return bool
   *   TRUE if this is an ECA trigger node.
   */
  protected function isEcaTriggerNode(array $node): bool {
    // Check if this is an ECA trigger node.
    $executor_plugin = $node['data']['metadata']['executor_plugin'] ?? '';
    $node_type = $node['data']['metadata']['type'] ?? '';

    return $executor_plugin === 'eca_trigger' || $node_type === 'trigger';
  }

  /**
   * Checks if a trigger configuration matches the entity event.
   *
   * @param array $node
   *   The trigger node configuration.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $event_type
   *   The event type.
   *
   * @return bool
   *   TRUE if the trigger matches.
   */
  protected function triggerMatchesEvent(array $node, EntityInterface $entity, string $event_type): bool {
    $config = $node['data']['config'] ?? [];

    // Check if autoExecute/enabled is set (support both field names).
    $auto_execute = $config['autoExecute'] ?? $config['enabled'] ?? FALSE;
    if (empty($auto_execute)) {
      return FALSE;
    }

    // Check trigger type matches event (support multiple field name variations).
    // UI may use ecaEventTypes, while YAML may use triggerType or triggerData.
    // Use empty() to handle both null and empty string values.
    $trigger_types = [];
    if (!empty($config['triggerType'])) {
      $trigger_types = is_array($config['triggerType']) ? $config['triggerType'] : [$config['triggerType']];
    }
    elseif (!empty($config['triggerData'])) {
      $trigger_types = is_array($config['triggerData']) ? $config['triggerData'] : [$config['triggerData']];
    }
    elseif (!empty($config['ecaEventTypes'])) {
      // Handle JSON string from UI.
      $eca_event_types = $config['ecaEventTypes'];
      if (is_string($eca_event_types)) {
        $decoded = json_decode($eca_event_types, TRUE);
        $trigger_types = is_array($decoded) ? $decoded : [$eca_event_types];
      }
      else {
        $trigger_types = is_array($eca_event_types) ? $eca_event_types : [$eca_event_types];
      }
    }

    // Map event_type to all supported trigger values.
    $expected_triggers = match ($event_type) {
      'insert' => ['insert', 'node_create', 'entity_insert', 'node_insert'],
      'update' => ['update', 'node_update', 'entity_update'],
      'delete' => ['delete', 'node_delete', 'entity_delete'],
      default => [],
    };

    // Check if any trigger type matches expected triggers.
    $trigger_matches = FALSE;
    foreach ($trigger_types as $trigger_type) {
      if (in_array($trigger_type, $expected_triggers, TRUE)) {
        $trigger_matches = TRUE;
        break;
      }
    }

    if (!$trigger_matches) {
      return FALSE;
    }

    // Check entity type filter (support both entityTypes and ecaEntityTypes).
    $entity_types = $config['entityTypes'] ?? $config['ecaEntityTypes'] ?? [];
    // Handle JSON string from UI.
    if (is_string($entity_types)) {
      $decoded = json_decode($entity_types, TRUE);
      if (is_array($decoded)) {
        $entity_types = $decoded;
      }
      else {
        $entity_types = array_filter(array_map('trim', explode(',', $entity_types)));
      }
    }
    if (!empty($entity_types) && !in_array($entity->getEntityTypeId(), $entity_types, TRUE)) {
      return FALSE;
    }

    // Check bundle filter (for entities that have bundles).
    if ($entity->getEntityType()->hasKey('bundle')) {
      $bundles = $config['bundles'] ?? $config['ecaBundles'] ?? [];
      // Handle JSON string from UI.
      if (is_string($bundles)) {
        $decoded = json_decode($bundles, TRUE);
        if (is_array($decoded)) {
          $bundles = $decoded;
        }
        else {
          $bundles = array_filter(array_map('trim', explode(',', $bundles)));
        }
      }
      if (!empty($bundles) && !in_array($entity->bundle(), $bundles, TRUE)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Executes a workflow for an entity event.
   *
   * @param string $workflow_id
   *   The workflow ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $event_type
   *   The event type.
   * @param array $trigger_config
   *   The trigger configuration.
   */
  protected function executeWorkflow(string $workflow_id, EntityInterface $entity, string $event_type, array $trigger_config): void {
    try {
      // Load the workflow.
      $workflow_storage = $this->entityTypeManager->getStorage('flowdrop_workflow');
      $workflow = $workflow_storage->load($workflow_id);

      if (!$workflow) {
        $this->logger->warning('Workflow @id not found', ['@id' => $workflow_id]);
        return;
      }

      // Prepare entity data for the workflow.
      $entity_data = $this->prepareEntityData($entity, $event_type);

      // Create a pipeline for this workflow execution.
      $pipeline_storage = $this->entityTypeManager->getStorage('flowdrop_pipeline');
      $pipeline = $pipeline_storage->create([
        'bundle' => 'default',
        'label' => sprintf('Auto: %s "%s" %s',
          ucfirst($entity->getEntityTypeId()),
          $entity->label(),
          $event_type
        ),
        'workflow_id' => $workflow->id(),
        'status' => 'pending',
        'input_data' => json_encode($entity_data),
      ]);
      $pipeline->save();

      // Generate jobs from the workflow definition.
      $job_service = \Drupal::service('flowdrop_pipeline.job_generation');
      $jobs = $job_service->generateJobs($pipeline);

      // Find the trigger job and set its input data.
      foreach ($jobs as $job) {
        if (strpos($job->label(), 'Trigger') !== FALSE ||
            strpos($job->label(), 'eca_trigger') !== FALSE) {
          $job->set('input_data', json_encode(['inputs' => $entity_data]));
          $job->save();
          break;
        }
      }

      $this->logger->info('Generated @count jobs for pipeline @id', [
        '@count' => count($jobs),
        '@id' => $pipeline->id(),
      ]);

      // Execute the pipeline synchronously.
      $orchestrator = \Drupal::service('flowdrop_runtime.synchronous_orchestrator');
      $orchestrator->executePipeline($pipeline);

      $this->logger->info('Auto-triggered workflow @workflow_id for @entity_type: @label (@id, Pipeline: @pid)', [
        '@workflow_id' => $workflow_id,
        '@entity_type' => $entity->getEntityTypeId(),
        '@label' => $entity->label(),
        '@id' => $entity->id(),
        '@pid' => $pipeline->id(),
      ]);

      // Display a message to the user.
      \Drupal::messenger()->addStatus(t('FlowDrop workflow triggered for @entity_type: @label', [
        '@entity_type' => $entity->getEntityTypeId(),
        '@label' => $entity->label(),
      ]));
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to execute workflow @workflow_id: @message', [
        '@workflow_id' => $workflow_id,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Prepares entity data for workflow execution.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $event_type
   *   The event type.
   *
   * @return array
   *   The prepared entity data.
   */
  protected function prepareEntityData(EntityInterface $entity, string $event_type): array {
    $data = [
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'entity_label' => $entity->label(),
      'eca_event' => sprintf('%s_%s', $entity->getEntityTypeId(), $event_type),
      'eca_entity' => [
        'type' => $entity->getEntityTypeId(),
        'id' => $entity->id(),
        'label' => $entity->label(),
      ],
      'trigger_data' => [
        'event_type' => sprintf('%s_%s', $entity->getEntityTypeId(), $event_type),
        'entity_type' => $entity->getEntityTypeId(),
      ],
    ];

    // Add bundle information if available.
    if ($entity->getEntityType()->hasKey('bundle')) {
      $data['entity_bundle'] = $entity->bundle();
      $data['eca_entity']['bundle'] = $entity->bundle();
      $data['trigger_data']['bundle'] = $entity->bundle();
    }

    // Add node-specific data for node entities.
    if ($entity->getEntityTypeId() === 'node' && method_exists($entity, 'isPublished')) {
      $data['node_id'] = $entity->id();
      $data['node_title'] = $entity->label();
      $data['node_type'] = $entity->bundle();
      $data['node_status'] = $entity->isPublished() ? 'published' : 'unpublished';
      $data['eca_entity']['title'] = $entity->label();
      $data['eca_entity']['status'] = $entity->isPublished();

      if (method_exists($entity, 'getCreatedTime')) {
        $data['node_created'] = $entity->getCreatedTime();
      }
      if (method_exists($entity, 'getOwnerId')) {
        $data['node_author'] = $entity->getOwnerId();
      }
    }

    return $data;
  }

  /**
   * Checks if workflow should be skipped for this entity.
   *
   * This prevents infinite loops when workflows update entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if workflow should be skipped.
   */
  protected function shouldSkipWorkflow(EntityInterface $entity): bool {
    // Check using the NodeMetaTagUpdater's static method.
    if (class_exists('\Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor\NodeMetaTagUpdater')) {
      return \Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor\NodeMetaTagUpdater::shouldSkipWorkflow((int) $entity->id());
    }

    return FALSE;
  }

}
