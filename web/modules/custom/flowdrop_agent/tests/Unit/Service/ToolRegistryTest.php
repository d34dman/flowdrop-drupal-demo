<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_agent\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Service\FlowDropNodeProcessorPluginManager;
use Drupal\flowdrop_agent\Service\ToolRegistry;
use Drupal\flowdrop_ai\DTO\ToolDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ToolRegistry service.
 *
 * @coversDefaultClass \Drupal\flowdrop_agent\Service\ToolRegistry
 * @group flowdrop_agent
 */
class ToolRegistryTest extends TestCase {

  /**
   * The mock processor manager.
   *
   * @var \Drupal\flowdrop\Service\FlowDropNodeProcessorPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $processorManager;

  /**
   * The tool registry under test.
   *
   * @var \Drupal\flowdrop_agent\Service\ToolRegistry
   */
  protected ToolRegistry $toolRegistry;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->processorManager = $this->createMock(FlowDropNodeProcessorPluginManager::class);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $this->toolRegistry = new ToolRegistry(
      $this->processorManager,
      $loggerFactory,
    );
  }

  /**
   * Test getTool finds tool by name.
   *
   * @covers ::getTool
   */
  public function testGetTool(): void {
    $tools = [
      new ToolDefinition('search', 'Search tool', 'n1', 't1'),
      new ToolDefinition('calculate', 'Calculator', 'n2', 't2'),
      new ToolDefinition('fetch', 'Fetch data', 'n3', 't3'),
    ];

    $result = $this->toolRegistry->getTool($tools, 'calculate');
    $this->assertNotNull($result);
    $this->assertSame('calculate', $result->getName());
    $this->assertSame('n2', $result->getNodeId());
  }

  /**
   * Test getTool returns NULL for missing tool.
   *
   * @covers ::getTool
   */
  public function testGetToolNotFound(): void {
    $tools = [
      new ToolDefinition('search', 'Search', 'n1', 't1'),
    ];

    $result = $this->toolRegistry->getTool($tools, 'nonexistent');
    $this->assertNull($result);
  }

  /**
   * Test getToolByNodeId.
   *
   * @covers ::getToolByNodeId
   */
  public function testGetToolByNodeId(): void {
    $tools = [
      new ToolDefinition('search', 'Search', 'node_abc', 't1'),
      new ToolDefinition('calc', 'Calc', 'node_def', 't2'),
    ];

    $result = $this->toolRegistry->getToolByNodeId($tools, 'node_def');
    $this->assertNotNull($result);
    $this->assertSame('calc', $result->getName());
  }

  /**
   * Test getToolByNodeId returns NULL for missing node.
   *
   * @covers ::getToolByNodeId
   */
  public function testGetToolByNodeIdNotFound(): void {
    $tools = [
      new ToolDefinition('search', 'Search', 'n1', 't1'),
    ];

    $result = $this->toolRegistry->getToolByNodeId($tools, 'nonexistent_node');
    $this->assertNull($result);
  }

  /**
   * Test validateRequiredTools with all present.
   *
   * @covers ::validateRequiredTools
   */
  public function testValidateRequiredToolsAllPresent(): void {
    $tools = [
      new ToolDefinition('search', 'Search', 'n1', 't1'),
      new ToolDefinition('calculate', 'Calc', 'n2', 't2'),
      new ToolDefinition('fetch', 'Fetch', 'n3', 't3'),
    ];

    $missing = $this->toolRegistry->validateRequiredTools(
      $tools,
      ['search', 'calculate']
    );

    $this->assertEmpty($missing);
  }

  /**
   * Test validateRequiredTools with some missing.
   *
   * @covers ::validateRequiredTools
   */
  public function testValidateRequiredToolsSomeMissing(): void {
    $tools = [
      new ToolDefinition('search', 'Search', 'n1', 't1'),
    ];

    $missing = $this->toolRegistry->validateRequiredTools(
      $tools,
      ['search', 'calculate', 'fetch']
    );

    $this->assertCount(2, $missing);
    $this->assertContains('calculate', $missing);
    $this->assertContains('fetch', $missing);
  }

  /**
   * Test validateRequiredTools with empty requirements.
   *
   * @covers ::validateRequiredTools
   */
  public function testValidateRequiredToolsEmptyRequirements(): void {
    $tools = [
      new ToolDefinition('search', 'Search', 'n1', 't1'),
    ];

    $missing = $this->toolRegistry->validateRequiredTools($tools, []);
    $this->assertEmpty($missing);
  }

  /**
   * Test validateRequiredTools with empty tools.
   *
   * @covers ::validateRequiredTools
   */
  public function testValidateRequiredToolsEmptyTools(): void {
    $missing = $this->toolRegistry->validateRequiredTools(
      [],
      ['search', 'calculate']
    );

    $this->assertCount(2, $missing);
    $this->assertContains('search', $missing);
    $this->assertContains('calculate', $missing);
  }

}
