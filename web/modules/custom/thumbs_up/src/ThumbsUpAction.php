<?php

declare(strict_types=1);

namespace Drupal\thumbs_up;

use Drupal\Core\Database\Connection;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\notification_server\DTO\ChannelDTO;
use Drupal\notification_server\DTO\ChannelRulesDTO;
use Drupal\notification_server\Service\NotificationServerClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @todo Add class description.
 */
final class ThumbsUpAction implements ThumbsUpActionInterface, DestructableInterface {

  /**
   * The thumbs_up_event UUIDs that need updating.
   *
   * @var string[]
   */
  protected $updateList = [];

  /**
   * Constructs a ThumbsUpAction object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly Connection $connection,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly NotificationServerClientInterface $notificationServerClient,
  ) {}

  public function subscribe($uuid) {
    $client_id = $this->notificationServerClient->generateClientId();
    $websocket_url = $this->notificationServerClient->getWebsocketEndpoint();
    $channel_name = $this->getChannelName($uuid);
    $channel_rules = new ChannelRulesDTO(
      allowedClientIds: [],
      isPublic: True,
      maxSubscribers: 10000,
    );
    $channel = new ChannelDTO(
      channel: $channel_name,
      rules: $channel_rules
    );
    $this->notificationServerClient->createChannel($channel);
    $this->notificationServerClient->subscribeToChannel($channel_name, $client_id);
    return [
      'endpoint' => $websocket_url . '?clientId=' . $client_id,
      'channel' => $this->getChannelName($uuid),
    ];
  }

  protected function getChannelName(string $uuid): string {
    return 'thumbs_up_event:' . $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function up($uuid): bool {
    $update = $this->connection->update('thumbs_up_event')
      ->condition('uuid', $uuid)
      ->expression('thumbs_up', 'thumbs_up + 1')
      ->execute();

    if (!$update) {
      return false;
    }
    $this->updateList[$uuid] = $uuid;
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function updateEvents() {
    if (!empty($this->updateList)) {
      foreach ($this->updateList as $uuid) {
        if ($entity = $this->entityRepository->loadEntityByUuid('thumbs_up_event', $uuid)) {
          // Get the latest count.
          $query = $this->connection->query("SELECT thumbs_up FROM {thumbs_up_event} WHERE uuid = :uuid", [
            ':uuid' => $uuid,
          ]);
          $result = $query->fetchAll();
          if (!empty($result[0])) {
            $count = (int) $result[0]->thumbs_up;
            $this->notificationServerClient->publishNotification($this->getChannelName($uuid), $count);
          }
          // Reset cache for the entity.
          $this->entityTypeManager->getStorage('thumbs_up_event')->resetCache([$entity->id()]);
        }
      }
    }
  }

  public function destruct() {
    $this->updateEvents();
  }
}
