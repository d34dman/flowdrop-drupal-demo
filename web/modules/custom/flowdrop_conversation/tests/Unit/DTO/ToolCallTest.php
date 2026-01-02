<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_conversation\Unit\DTO;

use Drupal\flowdrop_conversation\DTO\ToolCall;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ToolCall DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_conversation\DTO\ToolCall
 * @group flowdrop_conversation
 */
class ToolCallTest extends TestCase {

  /**
   * Test creating a tool call.
   *
   * @covers ::create
   * @covers ::getId
   * @covers ::getToolName
   * @covers ::getArguments
   */
  public function testCreate(): void {
    $toolCall = ToolCall::create('get_weather', ['location' => 'NYC']);

    $this->assertStringStartsWith('call_', $toolCall->getId());
    $this->assertSame('get_weather', $toolCall->getToolName());
    $this->assertSame(['location' => 'NYC'], $toolCall->getArguments());
  }

  /**
   * Test creating with empty arguments.
   *
   * @covers ::create
   */
  public function testCreateEmptyArguments(): void {
    $toolCall = ToolCall::create('list_files');

    $this->assertSame('list_files', $toolCall->getToolName());
    $this->assertSame([], $toolCall->getArguments());
  }

  /**
   * Test getting specific argument.
   *
   * @covers ::getArgument
   */
  public function testGetArgument(): void {
    $toolCall = ToolCall::create('search', [
      'query' => 'test',
      'limit' => 10,
    ]);

    $this->assertSame('test', $toolCall->getArgument('query'));
    $this->assertSame(10, $toolCall->getArgument('limit'));
    $this->assertNull($toolCall->getArgument('nonexistent'));
    $this->assertSame('default', $toolCall->getArgument('nonexistent', 'default'));
  }

  /**
   * Test timestamp.
   *
   * @covers ::getRequestedAt
   */
  public function testTimestamp(): void {
    $before = new \DateTimeImmutable();
    $toolCall = ToolCall::create('test');
    $after = new \DateTimeImmutable();

    $this->assertGreaterThanOrEqual($before, $toolCall->getRequestedAt());
    $this->assertLessThanOrEqual($after, $toolCall->getRequestedAt());
  }

  /**
   * Test toArrayForLlm (OpenAI format).
   *
   * @covers ::toArrayForLlm
   */
  public function testToArrayForLlm(): void {
    $toolCall = ToolCall::create('calculate', ['expression' => '2+2']);
    $array = $toolCall->toArrayForLlm();

    $this->assertArrayHasKey('id', $array);
    $this->assertSame('function', $array['type']);
    $this->assertArrayHasKey('function', $array);
    $this->assertSame('calculate', $array['function']['name']);
    $this->assertSame('{"expression":"2+2"}', $array['function']['arguments']);
  }

  /**
   * Test serialization.
   *
   * @covers ::toArray
   * @covers ::fromArray
   */
  public function testSerialization(): void {
    $original = ToolCall::create('api_call', ['endpoint' => '/users', 'method' => 'GET']);

    $array = $original->toArray();
    $restored = ToolCall::fromArray($array);

    $this->assertSame($original->getId(), $restored->getId());
    $this->assertSame($original->getToolName(), $restored->getToolName());
    $this->assertSame($original->getArguments(), $restored->getArguments());
  }

  /**
   * Test fromOpenAiFormat.
   *
   * @covers ::fromOpenAiFormat
   */
  public function testFromOpenAiFormat(): void {
    $openAiData = [
      'id' => 'call_abc123',
      'type' => 'function',
      'function' => [
        'name' => 'get_weather',
        'arguments' => '{"location": "San Francisco", "unit": "celsius"}',
      ],
    ];

    $toolCall = ToolCall::fromOpenAiFormat($openAiData);

    $this->assertSame('call_abc123', $toolCall->getId());
    $this->assertSame('get_weather', $toolCall->getToolName());
    $this->assertSame('San Francisco', $toolCall->getArgument('location'));
    $this->assertSame('celsius', $toolCall->getArgument('unit'));
  }

  /**
   * Test fromOpenAiFormat with invalid JSON arguments.
   *
   * @covers ::fromOpenAiFormat
   */
  public function testFromOpenAiFormatInvalidJson(): void {
    $openAiData = [
      'id' => 'call_xyz',
      'function' => [
        'name' => 'test',
        'arguments' => 'not valid json',
      ],
    ];

    $toolCall = ToolCall::fromOpenAiFormat($openAiData);

    $this->assertSame('test', $toolCall->getToolName());
    $this->assertSame([], $toolCall->getArguments());
  }

  /**
   * Test fromAnthropicFormat.
   *
   * @covers ::fromAnthropicFormat
   */
  public function testFromAnthropicFormat(): void {
    $anthropicData = [
      'id' => 'toolu_01ABC',
      'name' => 'search_database',
      'input' => [
        'query' => 'SELECT * FROM users',
        'database' => 'production',
      ],
    ];

    $toolCall = ToolCall::fromAnthropicFormat($anthropicData);

    $this->assertSame('toolu_01ABC', $toolCall->getId());
    $this->assertSame('search_database', $toolCall->getToolName());
    $this->assertSame('SELECT * FROM users', $toolCall->getArgument('query'));
    $this->assertSame('production', $toolCall->getArgument('database'));
  }

  /**
   * Test unique ID generation.
   *
   * @covers ::create
   */
  public function testUniqueIds(): void {
    $ids = [];
    for ($i = 0; $i < 100; $i++) {
      $toolCall = ToolCall::create('test');
      $ids[] = $toolCall->getId();
    }

    $uniqueIds = array_unique($ids);
    $this->assertCount(100, $uniqueIds);
  }

}
