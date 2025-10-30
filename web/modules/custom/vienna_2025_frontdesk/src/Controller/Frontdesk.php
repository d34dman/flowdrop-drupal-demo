<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_frontdesk\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for vienna_2025_frontdesk routes.
 */
final class Frontdesk extends ControllerBase {

  /**
   * Builds the frontdesk registration form response.
   *
   * This controller renders the multi-step registration form component
   * for DrupalCamp Vienna 2025 attendees.
   *
   * @return array
   *   A render array containing the frontdesk component.
   */
  public function __invoke(): array {
    // Render the frontdesk component using Drupal's SDC system
    $build = [
      '#type' => 'component',
      '#component' => 'vienna_2025_frontdesk:frontdesk',
      '#props' => [],
    ];

    return $build;
  }

}
