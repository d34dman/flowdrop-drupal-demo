<?php

declare(strict_types=1);

namespace Drupal\fd_demo\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hooks for the start page.
 */
final class StartPageHooks {

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Drops the admin header band on /start: the hero is the page's heading.
   *
   * Claro renders the band (breadcrumb, page title, shortcut star) only when
   * one of those regions has content. The route keeps its _title, so the
   * browser tab still reads right.
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    if ($this->routeMatch->getRouteName() === 'fd_demo.start') {
      $variables['page']['header'] = [];
      $variables['page']['breadcrumb'] = [];
    }
  }

}
