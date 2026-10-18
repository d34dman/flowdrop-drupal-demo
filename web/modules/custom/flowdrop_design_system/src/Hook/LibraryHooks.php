<?php

declare(strict_types=1);

namespace Drupal\flowdrop_design_system\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Puts the bridge wherever FlowDrop's tokens are.
 */
final class LibraryHooks {

  /**
   * Adds the bridge to flowdrop_ui_components/design-tokens.
   *
   * Every FlowDrop page loads that library, directly or through theme-layer,
   * so the bridge comes with it and needs no list of routes.
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'flowdrop_ui_components' && isset($libraries['design-tokens'])) {
      $libraries['design-tokens']['dependencies'][] = 'flowdrop_design_system/bridge';
    }
  }

}
