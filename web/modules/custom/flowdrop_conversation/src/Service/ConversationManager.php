<?php

declare(strict_types=1);

namespace Drupal\flowdrop_conversation\Service;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop_conversation\DTO\ConversationState;
use Drupal\flowdrop_conversation\DTO\Message;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Service for managing conversation state.
 *
 * Provides methods to create, load, save, and modify conversations.
 * Uses key-value storage for persistence.
 */
final class ConversationManager {

  /**
   * The key-value store collection name.
   */
  private const COLLECTION = 'flowdrop_conversation';

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * Constructs a new ConversationManager.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValueFactory
   *   The key-value factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly KeyValueFactoryInterface $keyValueFactory,
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {
    $this->logger = $loggerFactory->get('flowdrop_conversation');
  }

  /**
   * Creates a new conversation.
   *
   * @param string|null $systemPrompt
   *   Optional system prompt to initialize the conversation.
   * @param array<string, mixed> $metadata
   *   Optional metadata for the conversation.
   *
   * @return \Drupal\flowdrop_conversation\DTO\ConversationState
   *   The newly created conversation state.
   */
  public function createConversation(
    ?string $systemPrompt = NULL,
    array $metadata = [],
  ): ConversationState {
    $conversation = ConversationState::create($systemPrompt, $metadata);

    $this->saveConversation($conversation);

    $this->logger->info('Created conversation @id', [
      '@id' => $conversation->getConversationId(),
    ]);

    $this->eventDispatcher->dispatch(
      new GenericEvent($conversation, ['action' => 'created']),
      'flowdrop.conversation.created'
    );

    return $conversation;
  }

  /**
   * Loads an existing conversation.
   *
   * @param string $conversationId
   *   The conversation ID to load.
   *
   * @return \Drupal\flowdrop_conversation\DTO\ConversationState|null
   *   The conversation state or NULL if not found.
   */
  public function loadConversation(string $conversationId): ?ConversationState {
    $store = $this->keyValueFactory->get(self::COLLECTION);
    $data = $store->get($conversationId);

    if ($data === NULL) {
      $this->logger->debug('Conversation @id not found', [
        '@id' => $conversationId,
      ]);
      return NULL;
    }

    return ConversationState::fromArray($data);
  }

  /**
   * Saves a conversation state.
   *
   * @param \Drupal\flowdrop_conversation\DTO\ConversationState $conversation
   *   The conversation state to save.
   */
  public function saveConversation(ConversationState $conversation): void {
    $store = $this->keyValueFactory->get(self::COLLECTION);
    $store->set($conversation->getConversationId(), $conversation->toArray());

    $this->logger->debug('Saved conversation @id with @count messages', [
      '@id' => $conversation->getConversationId(),
      '@count' => $conversation->getMessageCount(),
    ]);
  }

  /**
   * Deletes a conversation.
   *
   * @param string $conversationId
   *   The conversation ID to delete.
   *
   * @return bool
   *   TRUE if deleted, FALSE if not found.
   */
  public function deleteConversation(string $conversationId): bool {
    $store = $this->keyValueFactory->get(self::COLLECTION);

    if (!$store->has($conversationId)) {
      return FALSE;
    }

    $store->delete($conversationId);

    $this->logger->info('Deleted conversation @id', [
      '@id' => $conversationId,
    ]);

    $this->eventDispatcher->dispatch(
      new GenericEvent(NULL, [
        'conversation_id' => $conversationId,
        'action' => 'deleted',
      ]),
      'flowdrop.conversation.deleted'
    );

    return TRUE;
  }

  /**
   * Adds a message to a conversation.
   *
   * @param string $conversationId
   *   The conversation ID.
   * @param string $role
   *   The message role (user, assistant, system, tool).
   * @param string $content
   *   The message content.
   * @param array<string, mixed> $options
   *   Additional options (toolCall, toolCallId).
   *
   * @return \Drupal\flowdrop_conversation\DTO\ConversationState|null
   *   The updated conversation state or NULL if not found.
   */
  public function addMessage(
    string $conversationId,
    string $role,
    string $content,
    array $options = [],
  ): ?ConversationState {
    $conversation = $this->loadConversation($conversationId);

    if ($conversation === NULL) {
      $this->logger->warning('Cannot add message: conversation @id not found', [
        '@id' => $conversationId,
      ]);
      return NULL;
    }

    $message = match ($role) {
      Message::ROLE_USER => Message::user($content),
      Message::ROLE_ASSISTANT => Message::assistant(
        $content,
        $options['toolCall'] ?? NULL
      ),
      Message::ROLE_TOOL => Message::tool(
        $options['toolCallId'] ?? '',
        $content
      ),
      Message::ROLE_SYSTEM => Message::system($content),
      default => throw new \InvalidArgumentException("Unknown role: {$role}"),
    };

    $conversation = $conversation->addMessage($message);
    $this->saveConversation($conversation);

    $this->eventDispatcher->dispatch(
      new GenericEvent($message, [
        'conversation_id' => $conversationId,
        'action' => 'message_added',
      ]),
      'flowdrop.conversation.message_added'
    );

    return $conversation;
  }

  /**
   * Gets conversation history formatted for LLM.
   *
   * @param string $conversationId
   *   The conversation ID.
   *
   * @return array<array{role: string, content: string}>
   *   Messages formatted for LLM or empty array if not found.
   */
  public function getHistoryForLlm(string $conversationId): array {
    $conversation = $this->loadConversation($conversationId);

    if ($conversation === NULL) {
      return [];
    }

    return $conversation->getMessagesForLlm();
  }

  /**
   * Gets recent messages with a sliding window.
   *
   * @param string $conversationId
   *   The conversation ID.
   * @param int $windowSize
   *   Number of recent messages to return.
   * @param bool $keepSystem
   *   Whether to always include system message.
   *
   * @return array<array{role: string, content: string}>
   *   Messages formatted for LLM.
   */
  public function getRecentHistoryForLlm(
    string $conversationId,
    int $windowSize = 20,
    bool $keepSystem = TRUE,
  ): array {
    $conversation = $this->loadConversation($conversationId);

    if ($conversation === NULL) {
      return [];
    }

    $recentMessages = $conversation->getRecentMessages($windowSize, $keepSystem);

    return array_map(
      fn(Message $msg) => $msg->toArrayForLlm(),
      $recentMessages
    );
  }

  /**
   * Gets or creates a conversation.
   *
   * @param string|null $conversationId
   *   Optional conversation ID to load.
   * @param string|null $systemPrompt
   *   System prompt for new conversation.
   * @param array<string, mixed> $metadata
   *   Metadata for new conversation.
   *
   * @return \Drupal\flowdrop_conversation\DTO\ConversationState
   *   The conversation state.
   */
  public function getOrCreate(
    ?string $conversationId = NULL,
    ?string $systemPrompt = NULL,
    array $metadata = [],
  ): ConversationState {
    if ($conversationId !== NULL) {
      $existing = $this->loadConversation($conversationId);
      if ($existing !== NULL) {
        return $existing;
      }
    }

    return $this->createConversation($systemPrompt, $metadata);
  }

  /**
   * Clears all messages except system prompt.
   *
   * @param string $conversationId
   *   The conversation ID.
   *
   * @return \Drupal\flowdrop_conversation\DTO\ConversationState|null
   *   The cleared conversation or NULL if not found.
   */
  public function clearHistory(string $conversationId): ?ConversationState {
    $conversation = $this->loadConversation($conversationId);

    if ($conversation === NULL) {
      return NULL;
    }

    $systemPrompt = $conversation->getSystemPrompt();
    $metadata = $conversation->getMetadata();

    // Create new state with same ID but only system message.
    $cleared = ConversationState::create($systemPrompt, $metadata);

    // We need to preserve the conversation ID.
    $clearedWithId = ConversationState::fromArray([
      'conversation_id' => $conversationId,
      'messages' => $cleared->getMessages()
        ? array_map(fn($m) => $m->toArray(), $cleared->getMessages())
        : [],
      'metadata' => $metadata,
      'created_at' => $conversation->getCreatedAt()
        ->format(\DateTimeInterface::RFC3339_EXTENDED),
      'updated_at' => (new \DateTimeImmutable())
        ->format(\DateTimeInterface::RFC3339_EXTENDED),
    ]);

    $this->saveConversation($clearedWithId);

    $this->logger->info('Cleared history for conversation @id', [
      '@id' => $conversationId,
    ]);

    return $clearedWithId;
  }

  /**
   * Lists all conversation IDs.
   *
   * @return array<string>
   *   Array of conversation IDs.
   */
  public function listConversations(): array {
    $store = $this->keyValueFactory->get(self::COLLECTION);
    return array_keys($store->getAll());
  }

  /**
   * Checks if a conversation exists.
   *
   * @param string $conversationId
   *   The conversation ID.
   *
   * @return bool
   *   TRUE if exists.
   */
  public function conversationExists(string $conversationId): bool {
    $store = $this->keyValueFactory->get(self::COLLECTION);
    return $store->has($conversationId);
  }

}
