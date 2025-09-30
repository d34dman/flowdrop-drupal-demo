<?php

declare(strict_types=1);

namespace Drupal\thumbs_up\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\thumbs_up\ThumbsUpAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for thumbs_up routes.
 */
final class ThumbsUpController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly ThumbsUpAction $thumbsUpAction
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('thumbs_up'),
    );
  }

  public function subscribe(string $uuid): JsonResponse {
    return new JsonResponse($this->thumbsUpAction->subscribe($uuid));
  }

  /**
   * Builds the response.
   */
  public function up(string $uuid): JsonResponse {
    if ($this->thumbsUpAction->up($uuid)) {
      return new JsonResponse(
        [
          'message' => 'ok',
        ]);
    }
    else {
      throw new AccessDeniedHttpException('Un-authorized');
    }
  }

}
