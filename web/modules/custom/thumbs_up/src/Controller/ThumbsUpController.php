<?php

declare(strict_types=1);

namespace Drupal\thumbs_up\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\notification_server\Service\NotificationServerClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for thumbs_up routes.
 */
final class ThumbsUpController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly NotificationServerClientInterface $notificationServerClient,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('config.factory'),
      $container->get('notification_server.client'),
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('state'),
    );
  }

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
