<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_thumbs_up\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for vienna_2025_thumbs_up routes.
 */
final class ThumbsUp extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
