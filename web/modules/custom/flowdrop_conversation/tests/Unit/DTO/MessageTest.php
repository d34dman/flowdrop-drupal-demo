<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_conversation\Unit\DTO;

use Drupal\flowdrop_conversation\DTO\Message;
use Drupal\flowdrop_conversation\DTO\ToolCall;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Message DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_conversation\DTO\Message
 * @group flowdrop_conversation
 */
class MessageTest extends TestCase {

  /**
   * Test creating a user message.
   *
   * @covers ::user
   * @covers ::getRole
   * @covers ::getContent
   * @covers ::isUser
   */
  public function testUserMessage(): void {
    $message = Message::user('Hello, how are you?');

    $this->assertSame(Message::ROLE_USER, $message->getRole());
    $this->assertSame('Hello, how are you?', $message->getContent());
    $this->assertTrue($message->isUser());
    $this->assertFalse($message->isAssistant());
    $this->assertFalse($message->isTool());
    $this->assertFalse($message->isSystem());
    $this->assertStringStartsWith('msg_', $message->getId());
  }

  /**
   * Test creating an assistant message.
   *
   * @covers ::assistant
   * @covers ::isAssistant
   */
  public function testAssistantMessage(): void {
    $message = Message::assistant('I am doing well, thank you!');

    $this->assertSame(Message::ROLE_ASSISTANT, $message->getRole());
    $this->assertSame('I am doing well, thank you!', $message->getContent());
    $this->assertTrue($message->isAssistant());
    $this->assertFalse($message->hasToolCall());
  }

  /**
   * Test creating an assistant message with tool call.
   *
   * @covers ::assistant
   * @covers ::hasToolCall
   * @covers ::getToolCall
   */
  public function testAssistantMessageWithToolCall(): void {
    $toolCall = ToolCall::create('get_weather', ['location' => 'NYC']);
    $message = Message::assistant('Let me check the weather.', $toolCall);

    $this->assertTrue($message->isAssistant());
    $this->assertTrue($message->hasToolCall());
    $this->assertSame($toolCall, $message->getToolCall());
  }

  /**
   * Test creating a tool message.
   *
   * @covers ::tool
   * @covers ::isTool
   * @covers ::getToolCallId
   */
  public function testToolMessage(): void {
    $toolCallId = 'call_abc123';
    $content = '{"temperature": 72}';
    $message = Message::tool($toolCallId, $content);

    $this->assertSame(Message::ROLE_TOOL, $message->getRole());
    $this->assertSame($content, $message->getContent());
    $this->assertTrue($message->isTool());
    $this->assertSame($toolCallId, $message->getToolCallId());
  }

  /**
   * Test creating a system message.
   *
   * @covers ::system
   * @covers ::isSystem
   */
  public function testSystemMessage(): void {
    $message = Message::system('You are a helpful assistant.');

    $this->assertSame(Message::ROLE_SYSTEM, $message->getRole());
    $this->assertSame('You are a helpful assistant.', $message->getContent());
    $this->assertTrue($message->isSystem());
  }

  /**
   * Test message timestamp.
   *
   * @covers ::getTimestamp
   */
  public function testTimestamp(): void {
    $before = new \DateTimeImmutable();
    $message = Message::user('Test');
    $after = new \DateTimeImmutable();

    $this->assertGreaterThanOrEqual($before, $message->getTimestamp());
    $this->assertLessThanOrEqual($after, $message->getTimestamp());
  }

  /**
   * Test message metadata.
   *
   * @covers ::getMetadata
   */
  public function testMetadata(): void {
    $metadata = ['source' => 'api', 'client_id' => 'xyz'];
    $message = Message::user('Test', $metadata);

    $this->assertSame($metadata, $message->getMetadata());
  }

  /**
   * Test toArrayForLlm for user message.
   *
   * @covers ::toArrayForLlm
   */
  public function testToArrayForLlmUser(): void {
    $message = Message::user('Hello');
    $array = $message->toArrayForLlm();

    $this->assertSame('user', $array['role']);
    $this->assertSame('Hello', $array['content']);
    $this->assertArrayNotHasKey('tool_calls', $array);
    $this->assertArrayNotHasKey('tool_call_id', $array);
  }

  /**
   * Test toArrayForLlm for assistant message with tool call.
   *
   * @covers ::toArrayForLlm
   */
  public function testToArrayForLlmWithToolCall(): void {
    $toolCall = ToolCall::create('search', ['query' => 'test']);
    $message = Message::assistant('', $toolCall);
    $array = $message->toArrayForLlm();

    $this->assertSame('assistant', $array['role']);
    $this->assertArrayHasKey('tool_calls', $array);
    $this->assertCount(1, $array['tool_calls']);
  }

  /**
   * Test toArrayForLlm for tool message.
   *
   * @covers ::toArrayForLlm
   */
  public function testToArrayForLlmTool(): void {
    $message = Message::tool('call_123', '{"result": "ok"}');
    $array = $message->toArrayForLlm();

    $this->assertSame('tool', $array['role']);
    $this->assertSame('call_123', $array['tool_call_id']);
  }

  /**
   * Test serialization and deserialization.
   *
   * @covers ::toArray
   * @covers ::fromArray
   */
  public function testSerialization(): void {
    $toolCall = ToolCall::create('test_tool', ['arg' => 'value']);
    $original = Message::assistant('Test content', $toolCall);

    $array = $original->toArray();
    $restored = Message::fromArray($array);

    $this->assertSame($original->getId(), $restored->getId());
    $this->assertSame($original->getRole(), $restored->getRole());
    $this->assertSame($original->getContent(), $restored->getContent());
    $this->assertNotNull($restored->getToolCall());
    $this->assertSame(
      $original->getToolCall()->getToolName(),
      $restored->getToolCall()->getToolName()
    );
  }

  /**
   * Test role constants.
   *
   * @covers ::ROLE_SYSTEM
   * @covers ::ROLE_USER
   * @covers ::ROLE_ASSISTANT
   * @covers ::ROLE_TOOL
   */
  public function testRoleConstants(): void {
    $this->assertSame('system', Message::ROLE_SYSTEM);
    $this->assertSame('user', Message::ROLE_USER);
    $this->assertSame('assistant', Message::ROLE_ASSISTANT);
    $this->assertSame('tool', Message::ROLE_TOOL);
  }

}
