<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_ai\Unit\DTO;

use Drupal\flowdrop_ai\DTO\ToolDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ToolDefinition DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_ai\DTO\ToolDefinition
 * @group flowdrop_ai
 */
class ToolDefinitionTest extends TestCase {

  /**
   * Test basic construction.
   *
   * @covers ::__construct
   * @covers ::getName
   * @covers ::getDescription
   * @covers ::getNodeId
   * @covers ::getNodeTypeId
   */
  public function testConstruction(): void {
    $tool = new ToolDefinition(
      name: 'get_weather',
      description: 'Get current weather for a location',
      nodeId: 'node_123',
      nodeTypeId: 'http_request',
      parametersSchema: ['type' => 'object', 'properties' => []],
      returnSchema: ['type' => 'object'],
      onError: 'return_to_agent',
      metadata: ['source' => 'test'],
    );

    $this->assertSame('get_weather', $tool->getName());
    $this->assertSame('Get current weather for a location', $tool->getDescription());
    $this->assertSame('node_123', $tool->getNodeId());
    $this->assertSame('http_request', $tool->getNodeTypeId());
  }

  /**
   * Test fromNode factory method.
   *
   * @covers ::fromNode
   */
  public function testFromNode(): void {
    $tool = ToolDefinition::fromNode(
      nodeId: 'node_abc',
      nodeTypeId: 'calculator',
      label: 'Math Calculator',
      description: 'Performs mathematical calculations',
      inputSchema: [
        'properties' => [
          'expression' => ['type' => 'string'],
        ],
      ],
      outputSchema: ['type' => 'number'],
    );

    $this->assertSame('math_calculator', $tool->getName());
    $this->assertSame('Performs mathematical calculations', $tool->getDescription());
    $this->assertSame('node_abc', $tool->getNodeId());
  }

  /**
   * Test fromNode with edge overrides.
   *
   * @covers ::fromNode
   */
  public function testFromNodeWithOverrides(): void {
    $tool = ToolDefinition::fromNode(
      nodeId: 'node_xyz',
      nodeTypeId: 'http_request',
      label: 'HTTP Request',
      description: 'Makes HTTP requests',
      inputSchema: [],
      outputSchema: [],
      edgeOverrides: [
        'toolName' => 'fetch_data',
        'toolDescription' => 'Fetches data from API',
        'onError' => 'fail',
      ],
    );

    $this->assertSame('fetch_data', $tool->getName());
    $this->assertSame('Fetches data from API', $tool->getDescription());
    $this->assertSame('fail', $tool->getOnError());
  }

  /**
   * Test name sanitization.
   *
   * @covers ::fromNode
   * @dataProvider nameSanitizationProvider
   */
  public function testNameSanitization(string $label, string $expected): void {
    $tool = ToolDefinition::fromNode(
      nodeId: 'node_1',
      nodeTypeId: 'test',
      label: $label,
      description: 'Test',
      inputSchema: [],
      outputSchema: [],
    );

    $this->assertSame($expected, $tool->getName());
  }

  /**
   * Data provider for name sanitization tests.
   *
   * @return array<string, array{string, string}>
   *   Test cases.
   */
  public static function nameSanitizationProvider(): array {
    return [
      'simple' => ['Weather API', 'weather_api'],
      'special chars' => ['Get User (Admin)', 'get_user_admin'],
      'numbers' => ['API v2 Request', 'api_v2_request'],
      'already snake' => ['get_weather', 'get_weather'],
      'empty' => ['', 'unnamed_tool'],
      'only special' => ['!@#$%', 'unnamed_tool'],
      'mixed case' => ['GetWeatherData', 'getweatherdata'],
    ];
  }

  /**
   * Test toOpenAiFunction format.
   *
   * @covers ::toOpenAiFunction
   */
  public function testToOpenAiFunction(): void {
    $tool = new ToolDefinition(
      name: 'search',
      description: 'Search for information',
      nodeId: 'node_1',
      nodeTypeId: 'search',
      parametersSchema: [
        'type' => 'object',
        'properties' => [
          'query' => ['type' => 'string'],
          'limit' => ['type' => 'integer'],
        ],
        'required' => ['query'],
      ],
    );

    $openAi = $tool->toOpenAiFunction();

    $this->assertSame('function', $openAi['type']);
    $this->assertSame('search', $openAi['function']['name']);
    $this->assertSame('Search for information', $openAi['function']['description']);
    $this->assertSame('object', $openAi['function']['parameters']['type']);
    $this->assertArrayHasKey('query', $openAi['function']['parameters']['properties']);
  }

  /**
   * Test toAnthropicTool format.
   *
   * @covers ::toAnthropicTool
   */
  public function testToAnthropicTool(): void {
    $tool = new ToolDefinition(
      name: 'calculate',
      description: 'Perform calculation',
      nodeId: 'node_1',
      nodeTypeId: 'calc',
      parametersSchema: [
        'type' => 'object',
        'properties' => [
          'expression' => ['type' => 'string'],
        ],
      ],
    );

    $anthropic = $tool->toAnthropicTool();

    $this->assertSame('calculate', $anthropic['name']);
    $this->assertSame('Perform calculation', $anthropic['description']);
    $this->assertArrayHasKey('input_schema', $anthropic);
  }

  /**
   * Test empty parameters schema handling.
   *
   * @covers ::toOpenAiFunction
   * @covers ::toAnthropicTool
   */
  public function testEmptyParametersSchema(): void {
    $tool = new ToolDefinition(
      name: 'no_params',
      description: 'Tool with no parameters',
      nodeId: 'node_1',
      nodeTypeId: 'test',
    );

    $openAi = $tool->toOpenAiFunction();
    $this->assertSame('object', $openAi['function']['parameters']['type']);

    $anthropic = $tool->toAnthropicTool();
    $this->assertSame('object', $anthropic['input_schema']['type']);
  }

  /**
   * Test serialization.
   *
   * @covers ::toArray
   * @covers ::fromArray
   */
  public function testSerialization(): void {
    $original = new ToolDefinition(
      name: 'test_tool',
      description: 'A test tool',
      nodeId: 'node_abc',
      nodeTypeId: 'custom',
      parametersSchema: ['type' => 'object'],
      returnSchema: ['type' => 'string'],
      onError: 'skip',
      metadata: ['key' => 'value'],
    );

    $array = $original->toArray();
    $restored = ToolDefinition::fromArray($array);

    $this->assertSame($original->getName(), $restored->getName());
    $this->assertSame($original->getDescription(), $restored->getDescription());
    $this->assertSame($original->getNodeId(), $restored->getNodeId());
    $this->assertSame($original->getOnError(), $restored->getOnError());
  }

  /**
   * Test getters.
   *
   * @covers ::getParametersSchema
   * @covers ::getReturnSchema
   * @covers ::getOnError
   * @covers ::getMetadata
   */
  public function testGetters(): void {
    $parametersSchema = ['type' => 'object', 'properties' => ['x' => []]];
    $returnSchema = ['type' => 'array'];
    $metadata = ['custom' => 'data'];

    $tool = new ToolDefinition(
      name: 'test',
      description: 'Test',
      nodeId: 'n1',
      nodeTypeId: 't1',
      parametersSchema: $parametersSchema,
      returnSchema: $returnSchema,
      onError: 'fail',
      metadata: $metadata,
    );

    $this->assertSame($parametersSchema, $tool->getParametersSchema());
    $this->assertSame($returnSchema, $tool->getReturnSchema());
    $this->assertSame('fail', $tool->getOnError());
    $this->assertSame($metadata, $tool->getMetadata());
  }

}
