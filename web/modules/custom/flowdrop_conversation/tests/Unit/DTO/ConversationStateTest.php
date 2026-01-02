<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_conversation\Unit\DTO;

use Drupal\flowdrop_conversation\DTO\ConversationState;
use Drupal\flowdrop_conversation\DTO\ToolCall;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ConversationState DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_conversation\DTO\ConversationState
 * @group flowdrop_conversation
 */
class ConversationStateTest extends TestCase {

  /**
   * Test creating a conversation.
   *
   * @covers ::create
   * @covers ::getConversationId
   * @covers ::getMessages
   * @covers ::getMessageCount
   */
  public function testCreate(): void {
    $conversation = ConversationState::create();

    $this->assertStringStartsWith('conv_', $conversation->getConversationId());
    $this->assertSame([], $conversation->getMessages());
    $this->assertSame(0, $conversation->getMessageCount());
  }

  /**
   * Test creating with system prompt.
   *
   * @covers ::create
   * @covers ::getSystemPrompt
   */
  public function testCreateWithSystemPrompt(): void {
    $systemPrompt = 'You are a helpful assistant.';
    $conversation = ConversationState::create($systemPrompt);

    $this->assertSame(1, $conversation->getMessageCount());
    $this->assertSame($systemPrompt, $conversation->getSystemPrompt());

    $messages = $conversation->getMessages();
    $this->assertTrue($messages[0]->isSystem());
  }

  /**
   * Test creating with metadata.
   *
   * @covers ::create
   * @covers ::getMetadata
   * @covers ::getMetadataValue
   */
  public function testCreateWithMetadata(): void {
    $metadata = ['user_id' => '123', 'session' => 'abc'];
    $conversation = ConversationState::create(NULL, $metadata);

    $this->assertSame($metadata, $conversation->getMetadata());
    $this->assertSame('123', $conversation->getMetadataValue('user_id'));
    $this->assertSame('default', $conversation->getMetadataValue('nonexistent', 'default'));
  }

  /**
   * Test adding user message.
   *
   * @covers ::addUserMessage
   */
  public function testAddUserMessage(): void {
    $conversation = ConversationState::create();
    $updated = $conversation->addUserMessage('Hello!');

    // Original should be unchanged (immutability).
    $this->assertSame(0, $conversation->getMessageCount());

    // Updated should have the message.
    $this->assertSame(1, $updated->getMessageCount());
    $this->assertTrue($updated->getLastMessage()->isUser());
    $this->assertSame('Hello!', $updated->getLastMessage()->getContent());
  }

  /**
   * Test adding assistant message.
   *
   * @covers ::addAssistantMessage
   */
  public function testAddAssistantMessage(): void {
    $conversation = ConversationState::create()
      ->addUserMessage('Hi')
      ->addAssistantMessage('Hello! How can I help?');

    $this->assertSame(2, $conversation->getMessageCount());
    $this->assertTrue($conversation->getLastMessage()->isAssistant());
  }

  /**
   * Test adding assistant message with tool call.
   *
   * @covers ::addAssistantMessage
   */
  public function testAddAssistantMessageWithToolCall(): void {
    $toolCall = ToolCall::create('search', ['query' => 'test']);
    $conversation = ConversationState::create()
      ->addAssistantMessage('Let me search for that.', $toolCall);

    $lastMessage = $conversation->getLastMessage();
    $this->assertTrue($lastMessage->hasToolCall());
    $this->assertSame('search', $lastMessage->getToolCall()->getToolName());
  }

  /**
   * Test adding tool call.
   *
   * @covers ::addToolCall
   */
  public function testAddToolCall(): void {
    $toolCall = ToolCall::create('api_call', ['endpoint' => '/data']);
    $conversation = ConversationState::create()->addToolCall($toolCall);

    $this->assertTrue($conversation->getLastMessage()->hasToolCall());
  }

  /**
   * Test adding tool result.
   *
   * @covers ::addToolResult
   */
  public function testAddToolResult(): void {
    $conversation = ConversationState::create()
      ->addToolResult('call_123', ['status' => 'success']);

    $lastMessage = $conversation->getLastMessage();
    $this->assertTrue($lastMessage->isTool());
    $this->assertSame('call_123', $lastMessage->getToolCallId());
  }

  /**
   * Test adding tool result with string.
   *
   * @covers ::addToolResult
   */
  public function testAddToolResultString(): void {
    $conversation = ConversationState::create()
      ->addToolResult('call_456', 'Simple result');

    $this->assertSame('Simple result', $conversation->getLastMessage()->getContent());
  }

  /**
   * Test getLastMessage.
   *
   * @covers ::getLastMessage
   */
  public function testGetLastMessage(): void {
    $conversation = ConversationState::create();
    $this->assertNull($conversation->getLastMessage());

    $updated = $conversation
      ->addUserMessage('First')
      ->addAssistantMessage('Second');

    $this->assertSame('Second', $updated->getLastMessage()->getContent());
  }

  /**
   * Test isEmpty.
   *
   * @covers ::isEmpty
   */
  public function testIsEmpty(): void {
    $empty = ConversationState::create();
    $this->assertTrue($empty->isEmpty());

    $withSystem = ConversationState::create('System prompt');
    $this->assertTrue($withSystem->isEmpty());

    $withUser = $withSystem->addUserMessage('Hello');
    $this->assertFalse($withUser->isEmpty());
  }

  /**
   * Test withMetadata.
   *
   * @covers ::withMetadata
   */
  public function testWithMetadata(): void {
    $conversation = ConversationState::create(NULL, ['key1' => 'value1']);
    $updated = $conversation->withMetadata(['key2' => 'value2']);

    $this->assertSame(['key1' => 'value1'], $conversation->getMetadata());
    $this->assertSame(
      ['key1' => 'value1', 'key2' => 'value2'],
      $updated->getMetadata()
    );
  }

  /**
   * Test getMessagesForLlm.
   *
   * @covers ::getMessagesForLlm
   */
  public function testGetMessagesForLlm(): void {
    $conversation = ConversationState::create('You are helpful.')
      ->addUserMessage('Hello')
      ->addAssistantMessage('Hi there!');

    $messages = $conversation->getMessagesForLlm();

    $this->assertCount(3, $messages);
    $this->assertSame('system', $messages[0]['role']);
    $this->assertSame('user', $messages[1]['role']);
    $this->assertSame('assistant', $messages[2]['role']);
  }

  /**
   * Test getRecentMessages.
   *
   * @covers ::getRecentMessages
   */
  public function testGetRecentMessages(): void {
    $conversation = ConversationState::create('System');
    for ($i = 1; $i <= 10; $i++) {
      $conversation = $conversation->addUserMessage("Message {$i}");
    }

    // Get last 3 messages, keep system.
    $recent = $conversation->getRecentMessages(3, TRUE);

    // Should have system + 3 recent.
    $this->assertCount(4, $recent);
    $this->assertTrue($recent[0]->isSystem());
  }

  /**
   * Test getRecentMessages without keeping system.
   *
   * @covers ::getRecentMessages
   */
  public function testGetRecentMessagesNoSystem(): void {
    $conversation = ConversationState::create('System');
    for ($i = 1; $i <= 5; $i++) {
      $conversation = $conversation->addUserMessage("Message {$i}");
    }

    $recent = $conversation->getRecentMessages(3, FALSE);

    $this->assertCount(3, $recent);
    foreach ($recent as $msg) {
      $this->assertFalse($msg->isSystem());
    }
  }

  /**
   * Test timestamps.
   *
   * @covers ::getCreatedAt
   * @covers ::getUpdatedAt
   */
  public function testTimestamps(): void {
    $before = new \DateTimeImmutable();
    $conversation = ConversationState::create();
    $after = new \DateTimeImmutable();

    $this->assertGreaterThanOrEqual($before, $conversation->getCreatedAt());
    $this->assertLessThanOrEqual($after, $conversation->getCreatedAt());

    // Updated should change when adding message.
    $updated = $conversation->addUserMessage('Test');
    $this->assertGreaterThanOrEqual(
      $conversation->getUpdatedAt(),
      $updated->getUpdatedAt()
    );
  }

  /**
   * Test serialization.
   *
   * @covers ::toArray
   * @covers ::fromArray
   */
  public function testSerialization(): void {
    $original = ConversationState::create('You are helpful.', ['key' => 'value'])
      ->addUserMessage('Hello')
      ->addAssistantMessage('Hi!');

    $array = $original->toArray();
    $restored = ConversationState::fromArray($array);

    $this->assertSame($original->getConversationId(), $restored->getConversationId());
    $this->assertSame($original->getMessageCount(), $restored->getMessageCount());
    $this->assertSame($original->getSystemPrompt(), $restored->getSystemPrompt());
    $this->assertSame($original->getMetadata(), $restored->getMetadata());
  }

  /**
   * Test immutability.
   *
   * @covers ::addMessage
   */
  public function testImmutability(): void {
    $original = ConversationState::create();
    $modified = $original->addUserMessage('Test');

    $this->assertNotSame($original, $modified);
    $this->assertSame(0, $original->getMessageCount());
    $this->assertSame(1, $modified->getMessageCount());
  }

  /**
   * Test unique ID generation.
   *
   * @covers ::create
   */
  public function testUniqueIds(): void {
    $ids = [];
    for ($i = 0; $i < 50; $i++) {
      $conversation = ConversationState::create();
      $ids[] = $conversation->getConversationId();
    }

    $uniqueIds = array_unique($ids);
    $this->assertCount(50, $uniqueIds);
  }

}
