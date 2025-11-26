<?php

declare(strict_types=1);

namespace Drupal\flowdrop_workflow;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of FlowDropWorkflow entities.
 */
class FlowDropWorkflowListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = [
      'data' => $this->t('Workflow'),
      'width' => '20%',
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
      'width' => '120px',
    ];
    $header['description'] = [
      'data' => $this->t('Description'),
      'width' => '45%',
    ];
    $header['nodes'] = [
      'data' => $this->t('Nodes'),
      'width' => '80px',
    ];
    $header['created'] = [
      'data' => $this->t('Created'),
      'width' => '140px',
    ];
    $header['changed'] = [
      'data' => $this->t('Modified'),
      'width' => '140px',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\flowdrop_workflow\Entity\FlowDropWorkflow $entity */
    $row['label'] = $entity->label();

    // Status column with proper render array to preserve styling.
    if ($entity->status()) {
      $row['status'] = [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'style' => 'color: #117928; white-space: nowrap; font-weight: 500;',
          ],
          '#value' => '✓ ' . $this->t('Enabled'),
        ],
      ];
    }
    else {
      $row['status'] = [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'style' => 'color: #6c757d; white-space: nowrap;',
          ],
          '#value' => '✗ ' . $this->t('Disabled'),
        ],
      ];
    }

    $row['description'] = $entity->getDescription() ?: $this->t('No description');
    $row['nodes'] = count($entity->getNodes());
    $row['created'] = $entity->getCreated() ? date('Y-m-d H:i:s', $entity->getCreated()) : $this->t('Unknown');
    $row['changed'] = $entity->getChanged() ? date('Y-m-d H:i:s', $entity->getChanged()) : $this->t('Unknown');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    return $operations;
  }

}
