<?php

declare(strict_types=1);

namespace Drupal\flowdrop_entity_events\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop_entity_events\Service\WorkflowTriggerService;

/**
 * Implements entity hooks for the FlowDrop Entity Events module.
 */
class EntityEventsHooks {

  /**
   * Constructs a new EntityEventsHooks object.
   *
   * @param \Drupal\flowdrop_entity_events\Service\WorkflowTriggerService $workflowTrigger
   *   The workflow trigger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    protected WorkflowTriggerService $workflowTrigger,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $this->triggerWorkflows('insert', $entity);
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $this->triggerWorkflows('update', $entity);
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    $this->triggerWorkflows('presave', $entity);
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    $this->triggerWorkflows('predelete', $entity);
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    $this->triggerWorkflows('delete', $entity);
  }

  /**
   * Helper method to trigger workflows for entity events.
   *
   * @param string $event_type
   *   The event type (insert, update, delete, etc.).
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the event.
   */
  protected function triggerWorkflows(string $event_type, EntityInterface $entity): void {
    try {
      $this->workflowTrigger->triggerWorkflows($event_type, $entity);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('flowdrop_entity_events')->error(
        'Error triggering workflows for @event: @message',
        [
          '@event' => $event_type,
          '@message' => $e->getMessage(),
        ]
      );
    }
  }

}
