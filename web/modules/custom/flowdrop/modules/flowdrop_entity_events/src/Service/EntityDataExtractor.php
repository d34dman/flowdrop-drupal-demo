<?php

declare(strict_types=1);

namespace Drupal\flowdrop_entity_events\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Service for extracting entity data for FlowDrop workflows.
 *
 * Extracts comprehensive entity data including fields, metadata, and
 * relationships in a structured format suitable for workflow processing.
 */
final class EntityDataExtractor {

  /**
   * Constructs an EntityDataExtractor service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Extracts comprehensive data from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to extract data from.
   * @param bool $includeFields
   *   Whether to include field values.
   *
   * @return array<string, mixed>
   *   Array of entity data.
   */
  public function extractEntityData(EntityInterface $entity, bool $includeFields = TRUE): array {
    $data = [
      'id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'label' => $entity->label(),
      'uuid' => $entity->uuid(),
      'langcode' => $entity->language()->getId(),
    ];

    // Add content entity specific data.
    if ($entity instanceof ContentEntityInterface) {
      $data = array_merge($data, $this->extractContentEntityData($entity));
    }

    // Add field data if requested.
    if ($includeFields && $entity instanceof ContentEntityInterface) {
      $data['fields'] = $this->extractFieldData($entity);
    }

    // Add original entity data for comparison (update events).
    if (isset($entity->original) && $entity->original instanceof EntityInterface) {
      $data['original'] = $this->extractEntityData($entity->original, FALSE);
      $data['is_new'] = FALSE;
    }
    else {
      $data['is_new'] = method_exists($entity, 'isNew') ? $entity->isNew() : TRUE;
    }

    return $data;
  }

  /**
   * Extracts content entity specific data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return array<string, mixed>
   *   Array of content entity data.
   */
  private function extractContentEntityData(ContentEntityInterface $entity): array {
    $data = [];

    // Published status.
    if (method_exists($entity, 'isPublished')) {
      $data['published'] = $entity->isPublished();
    }

    // Timestamps.
    if (method_exists($entity, 'getCreatedTime')) {
      $data['created'] = $entity->getCreatedTime();
    }

    if (method_exists($entity, 'getChangedTime')) {
      $data['changed'] = $entity->getChangedTime();
    }

    // Owner.
    if (method_exists($entity, 'getOwnerId')) {
      $data['owner_id'] = $entity->getOwnerId();
    }

    if (method_exists($entity, 'getOwner')) {
      $owner = $entity->getOwner();
      if ($owner) {
        $data['owner'] = [
          'id' => $owner->id(),
          'name' => $owner->getAccountName(),
          'email' => $owner->getEmail(),
        ];
      }
    }

    // Revision information.
    if ($entity->getEntityType()->isRevisionable()) {
      $data['revision_id'] = $entity->getRevisionId();
      if (method_exists($entity, 'isDefaultRevision')) {
        $data['is_default_revision'] = $entity->isDefaultRevision();
      }
    }

    // Translation information.
    if ($entity->getEntityType()->isTranslatable()) {
      $data['translations'] = array_keys($entity->getTranslationLanguages());
      $data['is_default_translation'] = $entity->isDefaultTranslation();
    }

    return $data;
  }

  /**
   * Extracts field data from a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return array<string, mixed>
   *   Array of field values keyed by field name.
   */
  private function extractFieldData(ContentEntityInterface $entity): array {
    $fields = [];

    foreach ($entity->getFields() as $field_name => $field) {
      // Skip computed fields and internal fields.
      if ($field->getFieldDefinition()->isComputed() || str_starts_with($field_name, '_')) {
        continue;
      }

      $fields[$field_name] = $this->extractFieldValue($field);
    }

    return $fields;
  }

  /**
   * Extracts value from a field item list.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item list.
   *
   * @return mixed
   *   The field value(s).
   */
  private function extractFieldValue(FieldItemListInterface $field): mixed {
    if ($field->isEmpty()) {
      return NULL;
    }

    // For single-value fields, return the value directly.
    if (count($field) === 1) {
      $item = $field->first();
      if ($item === NULL) {
        return NULL;
      }

      // Get main property value.
      $main_property = $item->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getMainPropertyName();

      return $item->get($main_property)->getValue();
    }

    // For multi-value fields, return array of values.
    $values = [];
    foreach ($field as $item) {
      $main_property = $item->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getMainPropertyName();

      $values[] = $item->get($main_property)->getValue();
    }

    return $values;
  }

  /**
   * Gets list of fields that changed between original and current entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The current entity.
   * @param \Drupal\Core\Entity\EntityInterface $original
   *   The original entity.
   *
   * @return array<string>
   *   Array of changed field names.
   */
  public function getChangedFields(EntityInterface $entity, EntityInterface $original): array {
    if (!$entity instanceof ContentEntityInterface || !$original instanceof ContentEntityInterface) {
      return [];
    }

    $changed = [];

    foreach ($entity->getFields() as $field_name => $field) {
      // Skip computed and internal fields.
      if ($field->getFieldDefinition()->isComputed() || str_starts_with($field_name, '_')) {
        continue;
      }

      $original_field = $original->get($field_name);

      if (!$field->equals($original_field)) {
        $changed[] = $field_name;
      }
    }

    return $changed;
  }

}
