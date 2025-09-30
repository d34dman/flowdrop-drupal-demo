<?php

declare(strict_types=1);

namespace Drupal\thumbs_up;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a thumbs_up_event entity type.
 */
interface ThumbsUpEventInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
