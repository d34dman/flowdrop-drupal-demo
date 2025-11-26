<?php

declare(strict_types=1);

namespace Drupal\flowdrop_entity_events\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Service for matching workflows to entity events.
 *
 * Finds FlowDrop workflows that should be triggered based on entity
 * events and configured filters.
 */
final class WorkflowMatcher {

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a WorkflowMatcher service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('flowdrop_entity_events');
  }

  /**
   * Finds workflows matching the given event and entity.
   *
   * @param string $eventType
   *   The event type (insert, update, delete, etc.).
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the event.
   *
   * @return array<\Drupal\Core\Entity\EntityInterface>
   *   Array of matching workflow entities.
   */
  public function findMatchingWorkflows(string $eventType, EntityInterface $entity): array {
    $matching = [];

    try {
      $storage = $this->entityTypeManager->getStorage('flowdrop_workflow');
      /** @var array<\Drupal\flowdrop_workflow\Entity\FlowDropWorkflowInterface> $workflows */
      $workflows = $storage->loadMultiple();

      foreach ($workflows as $workflow) {
        if ($this->workflowMatches($workflow, $eventType, $entity)) {
          $matching[] = $workflow;
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
   * Checks if a workflow matches the event and entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $workflow
   *   The workflow entity to check.
   * @param string $eventType
   *   The event type.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the workflow matches.
   */
  private function workflowMatches(EntityInterface $workflow, string $eventType, EntityInterface $entity): bool {
    // Get workflow nodes.
    if (!method_exists($workflow, 'getNodes')) {
      return FALSE;
    }

    $nodes = $workflow->getNodes();
    if (!is_array($nodes)) {
      return FALSE;
    }

    // Look for ContentEntityTrigger nodes in the workflow.
    foreach ($nodes as $node) {
      if (!is_array($node)) {
        continue;
      }

      // Check if this is a content_entity_trigger node by checking the executor_plugin
      $executor_plugin = $node['data']['metadata']['executor_plugin'] ?? NULL;
      $node_id = $node['id'] ?? '';

      // Match by executor_plugin or by node ID prefix
      $is_content_trigger = ($executor_plugin === 'content_entity_trigger') ||
                           (str_starts_with($node_id, 'content_entity_trigger'));

      if (!$is_content_trigger) {
        continue;
      }

      $config = $node['data']['config'] ?? [];
      if (!is_array($config)) {
        continue;
      }

      // Check if this trigger node matches our event.
      if ($this->triggerMatches($config, $eventType, $entity)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if a trigger configuration matches the event and entity.
   *
   * @param array<string, mixed> $config
   *   The trigger configuration.
   * @param string $eventType
   *   The event type.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the trigger matches.
   */
  private function triggerMatches(array $config, string $eventType, EntityInterface $entity): bool {
    // Must have auto-trigger enabled.
    if (empty($config['auto_trigger'])) {
      return FALSE;
    }

    // Must match event type.
    $configured_event_type = $config['event_type'] ?? '';
    if ($configured_event_type !== $eventType) {
      return FALSE;
    }

    // Must match entity filter.
    $entity_filter = $config['entity_filter'] ?? '*';
    return $this->entityMatchesFilter($entity, $entity_filter);
  }

  /**
   * Checks if an entity matches a wildcard filter.
   *
   * Supports filters like:
   * - "*" (all entities)
   * - "node" (all nodes)
   * - "node::article" (specific bundle)
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $filter
   *   The filter string.
   *
   * @return bool
   *   TRUE if the entity matches the filter.
   */
  public function entityMatchesFilter(EntityInterface $entity, string $filter): bool {
    // Match all entities.
    if ($filter === '*') {
      return TRUE;
    }

    // Match entity type only: "node".
    if (!str_contains($filter, '::')) {
      return $entity->getEntityTypeId() === $filter;
    }

    // Match entity type and bundle: "node::article".
    [$entity_type, $bundle] = explode('::', $filter, 2);
    return $entity->getEntityTypeId() === $entity_type &&
           $entity->bundle() === $bundle;
  }

  /**
   * Extracts trigger configuration from a workflow.
   *
   * @param \Drupal\Core\Entity\EntityInterface $workflow
   *   The workflow entity.
   *
   * @return array<string, mixed>
   *   The trigger configuration, or empty array if not found.
   */
  public function extractTriggerConfig(EntityInterface $workflow): array {
    if (!method_exists($workflow, 'getNodes')) {
      return [];
    }

    $nodes = $workflow->getNodes();
    if (!is_array($nodes)) {
      return [];
    }

    foreach ($nodes as $node) {
      if (!is_array($node)) {
        continue;
      }

      // Check if this is a content_entity_trigger node
      $executor_plugin = $node['data']['metadata']['executor_plugin'] ?? NULL;
      $node_id = $node['id'] ?? '';

      // Match by executor_plugin or by node ID prefix
      $is_content_trigger = ($executor_plugin === 'content_entity_trigger') ||
                           (str_starts_with($node_id, 'content_entity_trigger'));

      if ($is_content_trigger) {
        $config = $node['data']['config'] ?? [];
        return is_array($config) ? $config : [];
      }
    }

    return [];
  }

}
