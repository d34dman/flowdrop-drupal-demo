<?php

declare(strict_types=1);

namespace Drupal\flowdrop_entity_events\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\DTO\Input;

/**
 * Service for triggering FlowDrop workflows on entity events.
 */
final class WorkflowTriggerService {

  /**
   * Static array to track nodes currently being processed (prevents loops).
   */
  private static array $processingNodes = [];

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a WorkflowTriggerService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\flowdrop_entity_events\Service\WorkflowMatcher $workflowMatcher
   *   The workflow matcher service.
   * @param \Drupal\flowdrop_entity_events\Service\EntityDataExtractor $entityDataExtractor
   *   The entity data extractor service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly WorkflowMatcher $workflowMatcher,
    private readonly EntityDataExtractor $entityDataExtractor,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('flowdrop_entity_events');
  }

  /**
   * Finds and triggers FlowDrop workflows for an entity event.
   *
   * @param string $eventType
   *   The event type (insert, update, delete, etc.).
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the event.
   */
  public function triggerWorkflows(string $eventType, EntityInterface $entity): void {
    try {
      $entity_key = $entity->getEntityTypeId() . ':' . $entity->id();

      // Check if this node is currently being processed (prevents infinite loops).
      if (isset(self::$processingNodes[$entity_key])) {
        $this->logger->info('Skipping workflow for @entity (already processing)', [
          '@entity' => $entity_key,
        ]);
        return;
      }

      // Additional check using NodeMetaTagUpdater's tempstore flag.
      if ($entity->getEntityTypeId() === 'node' && $this->shouldSkipWorkflow($entity)) {
        $this->logger->info('Skipping workflow for node @nid (flagged by NodeMetaTagUpdater)', [
          '@nid' => $entity->id(),
        ]);
        return;
      }

      // Mark this entity as being processed.
      self::$processingNodes[$entity_key] = TRUE;

      // Find matching workflows.
      $workflows = $this->workflowMatcher->findMatchingWorkflows($eventType, $entity);

      if (empty($workflows)) {
        // Clear processing flag before returning.
        unset(self::$processingNodes[$entity_key]);
        return;
      }

      $this->logger->info('Found @count matching workflow(s) for @event on @type:@bundle', [
        '@count' => count($workflows),
        '@event' => $eventType,
        '@type' => $entity->getEntityTypeId(),
        '@bundle' => $entity->bundle(),
      ]);

      // Trigger each matching workflow.
      foreach ($workflows as $workflow) {
        $this->executeTrigger($workflow, $entity, $eventType);
      }

      // Clear processing flag after workflows complete.
      unset(self::$processingNodes[$entity_key]);
    }
    catch (\Exception $e) {
      // Clear processing flag on error.
      $entity_key = $entity->getEntityTypeId() . ':' . $entity->id();
      unset(self::$processingNodes[$entity_key]);

      $this->logger->error('Error triggering workflows for @event: @message', [
        '@event' => $eventType,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Executes the trigger and starts a workflow.
   *
   * @param \Drupal\Core\Entity\EntityInterface $workflow
   *   The workflow entity to execute.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the workflow.
   * @param string $eventType
   *   The event type.
   */
  private function executeTrigger(
    EntityInterface $workflow,
    EntityInterface $entity,
    string $eventType
  ): void {
    try {
      // Create pipeline from workflow.
      $pipeline_storage = $this->entityTypeManager->getStorage('flowdrop_pipeline');

      $pipeline = $pipeline_storage->create([
        'bundle' => 'default',
        'label' => sprintf(
          'Auto-triggered: %s on %s (%s)',
          $eventType,
          $entity->label(),
          $entity->id()
        ),
        'workflow_id' => $workflow->id(),
        'status' => 'pending',
      ]);
      $pipeline->save();

      // Extract entity data.
      $entity_data = $this->entityDataExtractor->extractEntityData($entity, TRUE);

      // Prepare trigger input data.
      $input_data = [
        'entity' => $entity_data,
        'entity_id' => $entity->id(),
        'node_id' => $entity->id(), // Alias for backwards compatibility
        'entity_type' => $entity->getEntityTypeId(),
        'bundle' => $entity->bundle(),
        'event_type' => $eventType,
        'event_data' => [
          'timestamp' => time(),
          'source' => 'flowdrop_entity_events',
          'workflow_id' => $workflow->id(),
        ],
      ];

      // Set input data on pipeline.
      $pipeline->set('input_data', json_encode($input_data));
      $pipeline->save();

      // Generate jobs for the pipeline.
      $job_generation = \Drupal::service('flowdrop_pipeline.job_generation');
      $jobs = $job_generation->generateJobs($pipeline);

      // Set input data on trigger jobs.
      foreach ($jobs as $job) {
        $job_label = $job->label();
        if (str_contains($job_label, 'Trigger') ||
            str_contains($job_label, 'content_entity_trigger')) {
          $job->set('input_data', json_encode(['inputs' => $input_data]));
          $job->save();
        }
      }

      // Get execution mode from trigger configuration.
      $trigger_config = $this->workflowMatcher->extractTriggerConfig($workflow);
      $execution_mode = $trigger_config['execution_mode'] ?? 'async';

      // Execute based on configuration.
      if ($execution_mode === 'sync') {
        $sync_orchestrator = \Drupal::service('flowdrop_runtime.synchronous_orchestrator');
        $sync_orchestrator->executePipeline($pipeline);
        $this->logger->info('Synchronously executed workflow @workflow for @event', [
          '@workflow' => $workflow->label(),
          '@event' => $eventType,
        ]);
      }
      else {
        $async_orchestrator = \Drupal::service('flowdrop_runtime.asynchronous_orchestrator');
        $async_orchestrator->startPipeline($pipeline);
        $this->logger->info('Queued workflow @workflow for @event', [
          '@workflow' => $workflow->label(),
          '@event' => $eventType,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error executing workflow @workflow: @message', [
        '@workflow' => $workflow->label(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Checks if workflow should be skipped for this entity.
   *
   * Used to prevent infinite loops when workflows modify entities.
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
