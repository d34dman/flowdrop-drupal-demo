<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_conversation\Unit\Service;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop_conversation\DTO\ConversationState;
use Drupal\flowdrop_conversation\DTO\Message;
use Drupal\flowdrop_conversation\Service\ConversationManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for the ConversationManager service.
 *
 * @coversDefaultClass \Drupal\flowdrop_conversation\Service\ConversationManager
 * @group flowdrop_conversation
 */
class ConversationManagerTest extends TestCase {

  /**
   * The mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The mock key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyValueStore;

  /**
   * The mock event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The conversation manager under test.
   *
   * @var \Drupal\flowdrop_conversation\Service\ConversationManager
   */
  protected ConversationManager $manager;

  /**
   * Storage for mock key-value data.
   *
   * @var array<string, array>
   */
  protected array $storage = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $this->storage = [];
    $this->keyValueStore = $this->createMock(KeyValueStoreInterface::class);
    $this->keyValueStore->method('get')->willReturnCallback(
      fn($key) => $this->storage[$key] ?? NULL
    );
    $this->keyValueStore->method('set')->willReturnCallback(
      function ($key, $value) {
        $this->storage[$key] = $value;
      }
    );
    $this->keyValueStore->method('has')->willReturnCallback(
      fn($key) => isset($this->storage[$key])
    );
    $this->keyValueStore->method('delete')->willReturnCallback(
      function ($key) {
        unset($this->storage[$key]);
      }
    );
    $this->keyValueStore->method('getAll')->willReturnCallback(
      fn() => $this->storage
    );

    $keyValueFactory = $this->createMock(KeyValueFactoryInterface::class);
    $keyValueFactory->method('get')->willReturn($this->keyValueStore);

    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->manager = new ConversationManager(
      $loggerFactory,
      $keyValueFactory,
      $this->eventDispatcher,
    );
  }

  /**
   * Test creating a conversation.
   *
   * @covers ::createConversation
   */
  public function testCreateConversation(): void {
    $conversation = $this->manager->createConversation();

    $this->assertStringStartsWith('conv_', $conversation->getConversationId());
    $this->assertSame(0, $conversation->getMessageCount());

    // Should be saved to storage.
    $this->assertArrayHasKey($conversation->getConversationId(), $this->storage);
  }

  /**
   * Test creating a conversation with system prompt.
   *
   * @covers ::createConversation
   */
  public function testCreateConversationWithSystemPrompt(): void {
    $conversation = $this->manager->createConversation('You are helpful.');

    $this->assertSame('You are helpful.', $conversation->getSystemPrompt());
    $this->assertSame(1, $conversation->getMessageCount());
  }

  /**
   * Test creating a conversation with metadata.
   *
   * @covers ::createConversation
   */
  public function testCreateConversationWithMetadata(): void {
    $metadata = ['user_id' => '123'];
    $conversation = $this->manager->createConversation(NULL, $metadata);

    $this->assertSame($metadata, $conversation->getMetadata());
  }

  /**
   * Test creating a conversation dispatches event.
   *
   * @covers ::createConversation
   */
  public function testCreateConversationDispatchesEvent(): void {
    $this->eventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->anything(),
        'flowdrop.conversation.created'
      );

    $this->manager->createConversation();
  }

  /**
   * Test loading a conversation.
   *
   * @covers ::loadConversation
   */
  public function testLoadConversation(): void {
    $created = $this->manager->createConversation('Test system prompt');
    $loaded = $this->manager->loadConversation($created->getConversationId());

    $this->assertNotNull($loaded);
    $this->assertSame($created->getConversationId(), $loaded->getConversationId());
    $this->assertSame('Test system prompt', $loaded->getSystemPrompt());
  }

  /**
   * Test loading nonexistent conversation.
   *
   * @covers ::loadConversation
   */
  public function testLoadConversationNotFound(): void {
    $loaded = $this->manager->loadConversation('nonexistent_id');

    $this->assertNull($loaded);
  }

  /**
   * Test saving a conversation.
   *
   * @covers ::saveConversation
   */
  public function testSaveConversation(): void {
    $conversation = ConversationState::create('Test')
      ->addUserMessage('Hello');

    $this->manager->saveConversation($conversation);

    $loaded = $this->manager->loadConversation($conversation->getConversationId());
    $this->assertNotNull($loaded);
    $this->assertSame(2, $loaded->getMessageCount());
  }

  /**
   * Test deleting a conversation.
   *
   * @covers ::deleteConversation
   */
  public function testDeleteConversation(): void {
    $conversation = $this->manager->createConversation();
    $id = $conversation->getConversationId();

    $this->assertTrue($this->manager->conversationExists($id));

    $result = $this->manager->deleteConversation($id);

    $this->assertTrue($result);
    $this->assertFalse($this->manager->conversationExists($id));
  }

  /**
   * Test deleting nonexistent conversation.
   *
   * @covers ::deleteConversation
   */
  public function testDeleteConversationNotFound(): void {
    $result = $this->manager->deleteConversation('nonexistent');

    $this->assertFalse($result);
  }

  /**
   * Test adding a message.
   *
   * @covers ::addMessage
   */
  public function testAddMessage(): void {
    $conversation = $this->manager->createConversation();
    $id = $conversation->getConversationId();

    $updated = $this->manager->addMessage($id, 'user', 'Hello!');

    $this->assertNotNull($updated);
    $this->assertSame(1, $updated->getMessageCount());
    $this->assertTrue($updated->getLastMessage()->isUser());
  }

  /**
   * Test adding messages with different roles.
   *
   * @covers ::addMessage
   * @dataProvider roleProvider
   */
  public function testAddMessageRoles(string $role, string $expectedRole): void {
    $conversation = $this->manager->createConversation();
    $options = $role === 'tool' ? ['toolCallId' => 'call_123'] : [];

    $updated = $this->manager->addMessage(
      $conversation->getConversationId(),
      $role,
      'Test content',
      $options
    );

    $this->assertSame($expectedRole, $updated->getLastMessage()->getRole());
  }

  /**
   * Data provider for role tests.
   *
   * @return array<string, array{string, string}>
   *   Test cases.
   */
  public static function roleProvider(): array {
    return [
      'user' => ['user', Message::ROLE_USER],
      'assistant' => ['assistant', Message::ROLE_ASSISTANT],
      'system' => ['system', Message::ROLE_SYSTEM],
      'tool' => ['tool', Message::ROLE_TOOL],
    ];
  }

  /**
   * Test adding message to nonexistent conversation.
   *
   * @covers ::addMessage
   */
  public function testAddMessageNotFound(): void {
    $result = $this->manager->addMessage('nonexistent', 'user', 'Hello');

    $this->assertNull($result);
  }

  /**
   * Test adding message dispatches event.
   *
   * @covers ::addMessage
   */
  public function testAddMessageDispatchesEvent(): void {
    $conversation = $this->manager->createConversation();

    $this->eventDispatcher->expects($this->atLeast(2))
      ->method('dispatch');

    $this->manager->addMessage(
      $conversation->getConversationId(),
      'user',
      'Hello'
    );
  }

  /**
   * Test getting history for LLM.
   *
   * @covers ::getHistoryForLlm
   */
  public function testGetHistoryForLlm(): void {
    $conversation = $this->manager->createConversation('System prompt');
    $id = $conversation->getConversationId();

    $this->manager->addMessage($id, 'user', 'Hello');
    $this->manager->addMessage($id, 'assistant', 'Hi there!');

    $history = $this->manager->getHistoryForLlm($id);

    $this->assertCount(3, $history);
    $this->assertSame('system', $history[0]['role']);
    $this->assertSame('user', $history[1]['role']);
    $this->assertSame('assistant', $history[2]['role']);
  }

  /**
   * Test getting history for nonexistent conversation.
   *
   * @covers ::getHistoryForLlm
   */
  public function testGetHistoryForLlmNotFound(): void {
    $history = $this->manager->getHistoryForLlm('nonexistent');

    $this->assertSame([], $history);
  }

  /**
   * Test getting recent history.
   *
   * @covers ::getRecentHistoryForLlm
   */
  public function testGetRecentHistoryForLlm(): void {
    $conversation = $this->manager->createConversation('System');
    $id = $conversation->getConversationId();

    for ($i = 1; $i <= 10; $i++) {
      $this->manager->addMessage($id, 'user', "Message {$i}");
    }

    $recent = $this->manager->getRecentHistoryForLlm($id, 3, TRUE);

    // Should have system + 3 recent.
    $this->assertCount(4, $recent);
    $this->assertSame('system', $recent[0]['role']);
  }

  /**
   * Test getOrCreate with existing conversation.
   *
   * @covers ::getOrCreate
   */
  public function testGetOrCreateExisting(): void {
    $original = $this->manager->createConversation('Original');
    $id = $original->getConversationId();

    $result = $this->manager->getOrCreate($id, 'New prompt');

    $this->assertSame($id, $result->getConversationId());
    $this->assertSame('Original', $result->getSystemPrompt());
  }

  /**
   * Test getOrCreate with new conversation.
   *
   * @covers ::getOrCreate
   */
  public function testGetOrCreateNew(): void {
    $result = $this->manager->getOrCreate(NULL, 'New system prompt');

    $this->assertSame('New system prompt', $result->getSystemPrompt());
  }

  /**
   * Test getOrCreate with nonexistent ID.
   *
   * @covers ::getOrCreate
   */
  public function testGetOrCreateNonexistentId(): void {
    $result = $this->manager->getOrCreate('nonexistent', 'Fallback prompt');

    $this->assertSame('Fallback prompt', $result->getSystemPrompt());
    $this->assertNotSame('nonexistent', $result->getConversationId());
  }

  /**
   * Test clearing history.
   *
   * @covers ::clearHistory
   */
  public function testClearHistory(): void {
    $conversation = $this->manager->createConversation('System prompt');
    $id = $conversation->getConversationId();

    $this->manager->addMessage($id, 'user', 'Hello');
    $this->manager->addMessage($id, 'assistant', 'Hi');

    $cleared = $this->manager->clearHistory($id);

    $this->assertNotNull($cleared);
    $this->assertSame($id, $cleared->getConversationId());
    // Should only have system message.
    $this->assertSame(1, $cleared->getMessageCount());
    $this->assertSame('System prompt', $cleared->getSystemPrompt());
  }

  /**
   * Test clearing nonexistent conversation.
   *
   * @covers ::clearHistory
   */
  public function testClearHistoryNotFound(): void {
    $result = $this->manager->clearHistory('nonexistent');

    $this->assertNull($result);
  }

  /**
   * Test listing conversations.
   *
   * @covers ::listConversations
   */
  public function testListConversations(): void {
    $conv1 = $this->manager->createConversation();
    $conv2 = $this->manager->createConversation();

    $list = $this->manager->listConversations();

    $this->assertContains($conv1->getConversationId(), $list);
    $this->assertContains($conv2->getConversationId(), $list);
  }

  /**
   * Test conversationExists.
   *
   * @covers ::conversationExists
   */
  public function testConversationExists(): void {
    $conversation = $this->manager->createConversation();

    $this->assertTrue(
      $this->manager->conversationExists($conversation->getConversationId())
    );
    $this->assertFalse($this->manager->conversationExists('nonexistent'));
  }

}
