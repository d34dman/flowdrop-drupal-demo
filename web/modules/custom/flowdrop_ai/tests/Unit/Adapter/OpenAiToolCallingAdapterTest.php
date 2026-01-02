<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_ai\Unit\Adapter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop_ai\Adapter\OpenAiToolCallingAdapter;
use Drupal\flowdrop_ai\DTO\LlmResponse;
use Drupal\flowdrop_ai\DTO\ToolDefinition;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OpenAiToolCallingAdapter.
 *
 * @coversDefaultClass \Drupal\flowdrop_ai\Adapter\OpenAiToolCallingAdapter
 * @group flowdrop_ai
 */
class OpenAiToolCallingAdapterTest extends TestCase {

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
   * @var \Drupal\flowdrop_ai\Adapter\OpenAiToolCallingAdapter
   */
  protected OpenAiToolCallingAdapter $adapter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(fn($key) => match ($key) {
      'openai_api_key' => 'test-api-key',
      'openai_base_url' => NULL,
      default => NULL,
    });

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')->willReturn($config);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $this->adapter = new OpenAiToolCallingAdapter(
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
    $this->assertSame('openai', $this->adapter->getProvider());
  }

  /**
   * Test supportsModel for GPT models.
   *
   * @covers ::supportsModel
   * @dataProvider gptModelProvider
   */
  public function testSupportsModelGpt(string $model, bool $expected): void {
    $this->assertSame($expected, $this->adapter->supportsModel($model));
  }

  /**
   * Data provider for GPT model tests.
   *
   * @return array<string, array{string, bool}>
   *   Test cases.
   */
  public static function gptModelProvider(): array {
    return [
      'gpt-4' => ['gpt-4', TRUE],
      'gpt-4-turbo' => ['gpt-4-turbo', TRUE],
      'gpt-3.5-turbo' => ['gpt-3.5-turbo', TRUE],
      'gpt-4o' => ['gpt-4o', TRUE],
      'claude' => ['claude-3-sonnet', FALSE],
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

    $this->assertArrayHasKey('gpt-4', $models);
    $this->assertArrayHasKey('gpt-4-turbo', $models);
    $this->assertArrayHasKey('gpt-3.5-turbo', $models);

    $this->assertSame('GPT-4', $models['gpt-4']['name']);
    $this->assertSame(8192, $models['gpt-4']['max_tokens']);
  }

  /**
   * Test formatTools.
   *
   * @covers ::formatTools
   */
  public function testFormatTools(): void {
    $tools = [
      new ToolDefinition(
        name: 'get_weather',
        description: 'Get weather info',
        nodeId: 'n1',
        nodeTypeId: 't1',
        parametersSchema: [
          'type' => 'object',
          'properties' => [
            'location' => ['type' => 'string'],
          ],
        ],
      ),
    ];

    $formatted = $this->adapter->formatTools($tools);

    $this->assertCount(1, $formatted);
    $this->assertSame('function', $formatted[0]['type']);
    $this->assertSame('get_weather', $formatted[0]['function']['name']);
  }

  /**
   * Test successful call.
   *
   * @covers ::call
   */
  public function testCall(): void {
    $responseBody = json_encode([
      'id' => 'chatcmpl-abc',
      'object' => 'chat.completion',
      'model' => 'gpt-4',
      'choices' => [
        [
          'index' => 0,
          'message' => [
            'role' => 'assistant',
            'content' => 'Hello! How can I help you today?',
          ],
          'finish_reason' => 'stop',
        ],
      ],
      'usage' => [
        'prompt_tokens' => 10,
        'completion_tokens' => 8,
        'total_tokens' => 18,
      ],
    ]);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], $responseBody));

    $response = $this->adapter->call(
      messages: [['role' => 'user', 'content' => 'Hello']],
      model: 'gpt-4',
    );

    $this->assertInstanceOf(LlmResponse::class, $response);
    $this->assertSame('Hello! How can I help you today?', $response->getContent());
    $this->assertSame(LlmResponse::FINISH_STOP, $response->getFinishReason());
    $this->assertSame(18, $response->getTotalTokens());
  }

  /**
   * Test call with tool response.
   *
   * @covers ::callWithTools
   */
  public function testCallWithToolResponse(): void {
    $responseBody = json_encode([
      'id' => 'chatcmpl-xyz',
      'model' => 'gpt-4',
      'choices' => [
        [
          'message' => [
            'role' => 'assistant',
            'content' => NULL,
            'tool_calls' => [
              [
                'id' => 'call_abc123',
                'type' => 'function',
                'function' => [
                  'name' => 'get_weather',
                  'arguments' => '{"location": "NYC"}',
                ],
              ],
            ],
          ],
          'finish_reason' => 'tool_calls',
        ],
      ],
      'usage' => [
        'prompt_tokens' => 50,
        'completion_tokens' => 20,
        'total_tokens' => 70,
      ],
    ]);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], $responseBody));

    $tools = [
      new ToolDefinition(
        name: 'get_weather',
        description: 'Get weather',
        nodeId: 'n1',
        nodeTypeId: 't1',
      ),
    ];

    $response = $this->adapter->callWithTools(
      messages: [['role' => 'user', 'content' => 'What is the weather in NYC?']],
      tools: $tools,
      model: 'gpt-4',
    );

    $this->assertTrue($response->hasToolCalls());
    $this->assertTrue($response->wantsToolCalls());
    $this->assertSame(LlmResponse::FINISH_TOOL_CALLS, $response->getFinishReason());

    $toolCall = $response->getFirstToolCall();
    $this->assertSame('get_weather', $toolCall->getToolName());
    $this->assertSame('NYC', $toolCall->getArgument('location'));
  }

}
