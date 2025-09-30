<?php

declare(strict_types=1);

namespace Drupal\thumbs_up;

/**
 * @todo Add interface description.
 */
interface ThumbsUpActionInterface {

  /**
   * Do a thumbs up.
   */
  public function up(string $uuid): bool;

}
