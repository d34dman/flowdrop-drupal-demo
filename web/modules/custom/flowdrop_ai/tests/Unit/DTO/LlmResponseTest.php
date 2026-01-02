<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_ai\Unit\DTO;

use Drupal\flowdrop_ai\DTO\LlmResponse;
use Drupal\flowdrop_conversation\DTO\ToolCall;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LlmResponse DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_ai\DTO\LlmResponse
 * @group flowdrop_ai
 */
class LlmResponseTest extends TestCase {

  /**
   * Test basic construction.
   *
   * @covers ::__construct
   * @covers ::getContent
   * @covers ::getToolCalls
   * @covers ::getFinishReason
   */
  public function testConstruction(): void {
    $response = new LlmResponse(
      content: 'Hello, how can I help?',
      toolCalls: [],
      finishReason: LlmResponse::FINISH_STOP,
      promptTokens: 10,
      completionTokens: 5,
      totalTokens: 15,
      model: 'gpt-4',
    );

    $this->assertSame('Hello, how can I help?', $response->getContent());
    $this->assertSame([], $response->getToolCalls());
    $this->assertSame(LlmResponse::FINISH_STOP, $response->getFinishReason());
  }

  /**
   * Test response with tool calls.
   *
   * @covers ::hasToolCalls
   * @covers ::getFirstToolCall
   * @covers ::wantsToolCalls
   */
  public function testWithToolCalls(): void {
    $toolCall = ToolCall::create('get_weather', ['location' => 'NYC']);

    $response = new LlmResponse(
      content: 'Let me check the weather.',
      toolCalls: [$toolCall],
      finishReason: LlmResponse::FINISH_TOOL_CALLS,
    );

    $this->assertTrue($response->hasToolCalls());
    $this->assertSame($toolCall, $response->getFirstToolCall());
    $this->assertTrue($response->wantsToolCalls());
    $this->assertFalse($response->isComplete());
  }

  /**
   * Test complete response.
   *
   * @covers ::isComplete
   */
  public function testIsComplete(): void {
    $complete = new LlmResponse(
      content: 'Done!',
      finishReason: LlmResponse::FINISH_STOP,
    );

    $this->assertTrue($complete->isComplete());
    $this->assertFalse($complete->wantsToolCalls());
  }

  /**
   * Test response with tool calls is not complete.
   *
   * @covers ::isComplete
   */
  public function testIsNotCompleteWithToolCalls(): void {
    $toolCall = ToolCall::create('test', []);

    $response = new LlmResponse(
      content: NULL,
      toolCalls: [$toolCall],
      finishReason: LlmResponse::FINISH_STOP,
    );

    // Even with stop finish reason, presence of tool calls means not complete.
    $this->assertFalse($response->isComplete());
  }

  /**
   * Test token usage.
   *
   * @covers ::getPromptTokens
   * @covers ::getCompletionTokens
   * @covers ::getTotalTokens
   */
  public function testTokenUsage(): void {
    $response = new LlmResponse(
      content: 'Response',
      promptTokens: 100,
      completionTokens: 50,
      totalTokens: 150,
    );

    $this->assertSame(100, $response->getPromptTokens());
    $this->assertSame(50, $response->getCompletionTokens());
    $this->assertSame(150, $response->getTotalTokens());
  }

  /**
   * Test model getter.
   *
   * @covers ::getModel
   */
  public function testGetModel(): void {
    $response = new LlmResponse(
      content: 'Test',
      model: 'claude-3-sonnet',
    );

    $this->assertSame('claude-3-sonnet', $response->getModel());
  }

  /**
   * Test raw response getter.
   *
   * @covers ::getRaw
   */
  public function testGetRaw(): void {
    $raw = ['id' => 'chatcmpl-abc', 'object' => 'chat.completion'];

    $response = new LlmResponse(
      content: 'Test',
      raw: $raw,
    );

    $this->assertSame($raw, $response->getRaw());
  }

  /**
   * Test toArray.
   *
   * @covers ::toArray
   */
  public function testToArray(): void {
    $toolCall = ToolCall::create('search', ['q' => 'test']);

    $response = new LlmResponse(
      content: 'Result',
      toolCalls: [$toolCall],
      finishReason: LlmResponse::FINISH_TOOL_CALLS,
      promptTokens: 20,
      completionTokens: 10,
      totalTokens: 30,
      model: 'gpt-4',
    );

    $array = $response->toArray();

    $this->assertSame('Result', $array['content']);
    $this->assertCount(1, $array['tool_calls']);
    $this->assertSame(LlmResponse::FINISH_TOOL_CALLS, $array['finish_reason']);
    $this->assertSame(20, $array['usage']['prompt_tokens']);
    $this->assertSame('gpt-4', $array['model']);
  }

  /**
   * Test finish reason constants.
   *
   * @covers ::FINISH_STOP
   * @covers ::FINISH_TOOL_CALLS
   * @covers ::FINISH_LENGTH
   * @covers ::FINISH_CONTENT_FILTER
   */
  public function testFinishReasonConstants(): void {
    $this->assertSame('stop', LlmResponse::FINISH_STOP);
    $this->assertSame('tool_calls', LlmResponse::FINISH_TOOL_CALLS);
    $this->assertSame('length', LlmResponse::FINISH_LENGTH);
    $this->assertSame('content_filter', LlmResponse::FINISH_CONTENT_FILTER);
  }

  /**
   * Test null content.
   *
   * @covers ::getContent
   */
  public function testNullContent(): void {
    $response = new LlmResponse(
      content: NULL,
      toolCalls: [ToolCall::create('tool', [])],
      finishReason: LlmResponse::FINISH_TOOL_CALLS,
    );

    $this->assertNull($response->getContent());
  }

  /**
   * Test getFirstToolCall with no tool calls.
   *
   * @covers ::getFirstToolCall
   */
  public function testGetFirstToolCallEmpty(): void {
    $response = new LlmResponse(content: 'No tools');

    $this->assertNull($response->getFirstToolCall());
  }

  /**
   * Test wantsToolCalls with finish reason.
   *
   * @covers ::wantsToolCalls
   */
  public function testWantsToolCallsWithFinishReason(): void {
    // Finish reason alone should trigger.
    $response = new LlmResponse(
      content: '',
      toolCalls: [],
      finishReason: LlmResponse::FINISH_TOOL_CALLS,
    );

    $this->assertTrue($response->wantsToolCalls());
  }

}
