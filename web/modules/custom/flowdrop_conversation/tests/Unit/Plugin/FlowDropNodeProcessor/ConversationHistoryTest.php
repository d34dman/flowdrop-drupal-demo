<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_conversation\Unit\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\DTO\ParameterBag;
use Drupal\flowdrop_conversation\DTO\ConversationState;
use Drupal\flowdrop_conversation\Plugin\FlowDropNodeProcessor\ConversationHistory;
use Drupal\flowdrop_conversation\Service\ConversationManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ConversationHistory node processor.
 *
 * @coversDefaultClass \Drupal\flowdrop_conversation\Plugin\FlowDropNodeProcessor\ConversationHistory
 * @group flowdrop_conversation
 */
class ConversationHistoryTest extends TestCase {

  /**
   * The mock conversation manager.
   *
   * @var \Drupal\flowdrop_conversation\Service\ConversationManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $conversationManager;

  /**
   * The processor under test.
   *
   * @var \Drupal\flowdrop_conversation\Plugin\FlowDropNodeProcessor\ConversationHistory
   */
  protected ConversationHistory $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $this->conversationManager = $this->createMock(ConversationManager::class);

    $this->processor = new ConversationHistory(
      [],
      'conversation_history',
      [
        'id' => 'conversation_history',
        'label' => 'Conversation History',
      ],
      $loggerFactory,
      $this->conversationManager,
    );
  }

  /**
   * Test getParameterSchema.
   *
   * @covers ::getParameterSchema
   */
  public function testGetParameterSchema(): void {
    $schema = $this->processor->getParameterSchema();

    $this->assertSame('object', $schema['type']);
    $this->assertArrayHasKey('action', $schema['properties']);
    $this->assertArrayHasKey('conversationId', $schema['properties']);
    $this->assertArrayHasKey('role', $schema['properties']);
    $this->assertArrayHasKey('content', $schema['properties']);
  }

  /**
   * Test getOutputSchema.
   *
   * @covers ::getOutputSchema
   */
  public function testGetOutputSchema(): void {
    $schema = $this->processor->getOutputSchema();

    $this->assertSame('object', $schema['type']);
    $this->assertArrayHasKey('conversationId', $schema['properties']);
    $this->assertArrayHasKey('messages', $schema['properties']);
    $this->assertArrayHasKey('messageCount', $schema['properties']);
  }

  /**
   * Test getType.
   *
   * @covers ::getType
   */
  public function testGetType(): void {
    $this->assertSame('conversation_history', $this->processor->getType());
  }

  /**
   * Test create action.
   *
   * @covers ::process
   */
  public function testCreateAction(): void {
    $conversation = ConversationState::create('Test prompt');

    $this->conversationManager->expects($this->once())
      ->method('createConversation')
      ->with('Test prompt', [])
      ->willReturn($conversation);

    $params = new ParameterBag([
      'action' => 'create',
      'systemPrompt' => 'Test prompt',
    ]);

    // Use reflection to call protected method.
    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertSame($conversation->getConversationId(), $result['conversationId']);
    $this->assertTrue($result['created']);
  }

  /**
   * Test get action with existing conversation.
   *
   * @covers ::process
   */
  public function testGetAction(): void {
    $conversation = ConversationState::create('System')
      ->addUserMessage('Hello');

    $this->conversationManager->expects($this->once())
      ->method('loadConversation')
      ->with('conv_123')
      ->willReturn($conversation);

    $params = new ParameterBag([
      'action' => 'get',
      'conversationId' => 'conv_123',
      'strategy' => 'full',
    ]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertSame('conv_123', $result['conversationId']);
    $this->assertTrue($result['found']);
    $this->assertSame(2, $result['messageCount']);
  }

  /**
   * Test get action with nonexistent conversation.
   *
   * @covers ::process
   */
  public function testGetActionNotFound(): void {
    $this->conversationManager->expects($this->once())
      ->method('loadConversation')
      ->willReturn(NULL);

    $params = new ParameterBag([
      'action' => 'get',
      'conversationId' => 'nonexistent',
    ]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertFalse($result['found']);
    $this->assertSame([], $result['messages']);
  }

  /**
   * Test get action without conversation ID.
   *
   * @covers ::process
   */
  public function testGetActionNoId(): void {
    $params = new ParameterBag(['action' => 'get']);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertFalse($result['found']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * Test add action.
   *
   * @covers ::process
   */
  public function testAddAction(): void {
    $conversation = ConversationState::create()->addUserMessage('Test');

    $this->conversationManager->expects($this->once())
      ->method('addMessage')
      ->with('conv_123', 'user', 'Hello!', [])
      ->willReturn($conversation);

    $params = new ParameterBag([
      'action' => 'add',
      'conversationId' => 'conv_123',
      'role' => 'user',
      'content' => 'Hello!',
    ]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertTrue($result['added']);
  }

  /**
   * Test clear action.
   *
   * @covers ::process
   */
  public function testClearAction(): void {
    $conversation = ConversationState::create('System');

    $this->conversationManager->expects($this->once())
      ->method('clearHistory')
      ->with('conv_123')
      ->willReturn($conversation);

    $params = new ParameterBag([
      'action' => 'clear',
      'conversationId' => 'conv_123',
    ]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertTrue($result['cleared']);
  }

  /**
   * Test delete action.
   *
   * @covers ::process
   */
  public function testDeleteAction(): void {
    $this->conversationManager->expects($this->once())
      ->method('deleteConversation')
      ->with('conv_123')
      ->willReturn(TRUE);

    $params = new ParameterBag([
      'action' => 'delete',
      'conversationId' => 'conv_123',
    ]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertTrue($result['deleted']);
  }

  /**
   * Test invalid action.
   *
   * @covers ::process
   */
  public function testInvalidAction(): void {
    $params = new ParameterBag(['action' => 'invalid']);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown action: invalid');

    $method->invoke($this->processor, $params);
  }

  /**
   * Test window strategy.
   *
   * @covers ::process
   */
  public function testWindowStrategy(): void {
    $conversation = ConversationState::create('System');
    $recentMessages = [
      ['role' => 'system', 'content' => 'System'],
      ['role' => 'user', 'content' => 'Recent'],
    ];

    $this->conversationManager->expects($this->once())
      ->method('loadConversation')
      ->willReturn($conversation);

    $this->conversationManager->expects($this->once())
      ->method('getRecentHistoryForLlm')
      ->with('conv_123', 10)
      ->willReturn($recentMessages);

    $params = new ParameterBag([
      'action' => 'get',
      'conversationId' => 'conv_123',
      'strategy' => 'window',
      'windowSize' => 10,
    ]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod('process');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertSame($recentMessages, $result['messages']);
  }

}
