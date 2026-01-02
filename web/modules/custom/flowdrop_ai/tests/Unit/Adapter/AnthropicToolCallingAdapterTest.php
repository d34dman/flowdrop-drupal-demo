<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_ai\Unit\Adapter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop_ai\Adapter\AnthropicToolCallingAdapter;
use Drupal\flowdrop_ai\DTO\LlmResponse;
use Drupal\flowdrop_ai\DTO\ToolDefinition;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AnthropicToolCallingAdapter.
 *
 * @coversDefaultClass \Drupal\flowdrop_ai\Adapter\AnthropicToolCallingAdapter
 * @group flowdrop_ai
 */
class AnthropicToolCallingAdapterTest extends TestCase {

  /**
   * The mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The adapter under test.
   *
   * @var \Drupal\flowdrop_ai\Adapter\AnthropicToolCallingAdapter
   */
  protected AnthropicToolCallingAdapter $adapter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(fn($key) => match ($key) {
      'anthropic_api_key' => 'test-anthropic-key',
      'anthropic_base_url' => NULL,
      default => NULL,
    });

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')->willReturn($config);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $this->adapter = new AnthropicToolCallingAdapter(
      $this->httpClient,
      $this->configFactory,
      $loggerFactory,
    );
  }

  /**
   * Test getProvider.
   *
   * @covers ::getProvider
   */
  public function testGetProvider(): void {
    $this->assertSame('anthropic', $this->adapter->getProvider());
  }

  /**
   * Test supportsModel for Claude models.
   *
   * @covers ::supportsModel
   * @dataProvider claudeModelProvider
   */
  public function testSupportsModelClaude(string $model, bool $expected): void {
    $this->assertSame($expected, $this->adapter->supportsModel($model));
  }

  /**
   * Data provider for Claude model tests.
   *
   * @return array<string, array{string, bool}>
   *   Test cases.
   */
  public static function claudeModelProvider(): array {
    return [
      'claude-3-opus' => ['claude-3-opus-20240229', TRUE],
      'claude-3-sonnet' => ['claude-3-sonnet-20240229', TRUE],
      'claude-3-haiku' => ['claude-3-haiku-20240307', TRUE],
      'claude-3.5-sonnet' => ['claude-3-5-sonnet-20241022', TRUE],
      'gpt' => ['gpt-4', FALSE],
      'llama' => ['llama-2', FALSE],
    ];
  }

  /**
   * Test getAvailableModels.
   *
   * @covers ::getAvailableModels
   */
  public function testGetAvailableModels(): void {
    $models = $this->adapter->getAvailableModels();

    $this->assertArrayHasKey('claude-3-opus-20240229', $models);
    $this->assertArrayHasKey('claude-3-sonnet-20240229', $models);

    $this->assertSame('Claude 3 Opus', $models['claude-3-opus-20240229']['name']);
  }

  /**
   * Test formatTools.
   *
   * @covers ::formatTools
   */
  public function testFormatTools(): void {
    $tools = [
      new ToolDefinition(
        name: 'search',
        description: 'Search the web',
        nodeId: 'n1',
        nodeTypeId: 't1',
        parametersSchema: [
          'type' => 'object',
          'properties' => [
            'query' => ['type' => 'string'],
          ],
        ],
      ),
    ];

    $formatted = $this->adapter->formatTools($tools);

    $this->assertCount(1, $formatted);
    $this->assertSame('search', $formatted[0]['name']);
    $this->assertSame('Search the web', $formatted[0]['description']);
    $this->assertArrayHasKey('input_schema', $formatted[0]);
  }

  /**
   * Test successful call.
   *
   * @covers ::call
   */
  public function testCall(): void {
    $responseBody = json_encode([
      'id' => 'msg_abc123',
      'type' => 'message',
      'role' => 'assistant',
      'model' => 'claude-3-sonnet-20240229',
      'content' => [
        [
          'type' => 'text',
          'text' => 'Hello! How may I assist you today?',
        ],
      ],
      'stop_reason' => 'end_turn',
      'usage' => [
        'input_tokens' => 15,
        'output_tokens' => 10,
      ],
    ]);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], $responseBody));

    $response = $this->adapter->call(
      messages: [['role' => 'user', 'content' => 'Hello']],
      model: 'claude-3-sonnet-20240229',
    );

    $this->assertInstanceOf(LlmResponse::class, $response);
    $this->assertSame('Hello! How may I assist you today?', $response->getContent());
    $this->assertSame(LlmResponse::FINISH_STOP, $response->getFinishReason());
    $this->assertSame(25, $response->getTotalTokens());
  }

  /**
   * Test call with tool response.
   *
   * @covers ::callWithTools
   */
  public function testCallWithToolResponse(): void {
    $responseBody = json_encode([
      'id' => 'msg_xyz789',
      'type' => 'message',
      'role' => 'assistant',
      'model' => 'claude-3-sonnet-20240229',
      'content' => [
        [
          'type' => 'text',
          'text' => 'Let me search for that.',
        ],
        [
          'type' => 'tool_use',
          'id' => 'toolu_abc',
          'name' => 'search',
          'input' => [
            'query' => 'weather NYC',
          ],
        ],
      ],
      'stop_reason' => 'tool_use',
      'usage' => [
        'input_tokens' => 100,
        'output_tokens' => 50,
      ],
    ]);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], $responseBody));

    $tools = [
      new ToolDefinition(
        name: 'search',
        description: 'Search',
        nodeId: 'n1',
        nodeTypeId: 't1',
      ),
    ];

    $response = $this->adapter->callWithTools(
      messages: [['role' => 'user', 'content' => 'Search for weather in NYC']],
      tools: $tools,
      model: 'claude-3-sonnet-20240229',
    );

    $this->assertTrue($response->hasToolCalls());
    $this->assertTrue($response->wantsToolCalls());
    $this->assertSame(LlmResponse::FINISH_TOOL_CALLS, $response->getFinishReason());
    $this->assertSame('Let me search for that.', $response->getContent());

    $toolCall = $response->getFirstToolCall();
    $this->assertSame('search', $toolCall->getToolName());
    $this->assertSame('weather NYC', $toolCall->getArgument('query'));
  }

  /**
   * Test system message extraction.
   *
   * @covers ::call
   */
  public function testSystemMessageExtraction(): void {
    $responseBody = json_encode([
      'id' => 'msg_test',
      'content' => [['type' => 'text', 'text' => 'OK']],
      'stop_reason' => 'end_turn',
      'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
    ]);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'POST',
        $this->anything(),
        $this->callback(function ($options) {
          $json = $options['json'];
          // System should be extracted from messages.
          $this->assertArrayHasKey('system', $json);
          $this->assertSame('You are helpful.', $json['system']);
          // Messages should not contain system.
          foreach ($json['messages'] as $msg) {
            $this->assertNotSame('system', $msg['role']);
          }
          return TRUE;
        })
      )
      ->willReturn(new Response(200, [], $responseBody));

    $this->adapter->call(
      messages: [
        ['role' => 'system', 'content' => 'You are helpful.'],
        ['role' => 'user', 'content' => 'Hi'],
      ],
      model: 'claude-3-sonnet-20240229',
    );
  }

}
