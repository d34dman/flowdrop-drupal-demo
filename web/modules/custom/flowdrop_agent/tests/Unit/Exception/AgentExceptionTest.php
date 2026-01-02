<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_agent\Unit\Exception;

use Drupal\flowdrop_agent\Exception\AgentException;
use Drupal\flowdrop_agent\Exception\ToolExecutionException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Agent exceptions.
 *
 * @group flowdrop_agent
 */
class AgentExceptionTest extends TestCase {

  /**
   * Test maxIterationsReached factory.
   *
   * @covers \Drupal\flowdrop_agent\Exception\AgentException::maxIterationsReached
   */
  public function testMaxIterationsReached(): void {
    $exception = AgentException::maxIterationsReached('agent_123', 10);

    $this->assertInstanceOf(AgentException::class, $exception);
    $this->assertStringContainsString('agent_123', $exception->getMessage());
    $this->assertStringContainsString('10', $exception->getMessage());
    $this->assertStringContainsString('maximum iterations', $exception->getMessage());
  }

  /**
   * Test toolNotFound factory.
   *
   * @covers \Drupal\flowdrop_agent\Exception\AgentException::toolNotFound
   */
  public function testToolNotFound(): void {
    $exception = AgentException::toolNotFound('search', 'agent_456');

    $this->assertInstanceOf(AgentException::class, $exception);
    $this->assertStringContainsString('search', $exception->getMessage());
    $this->assertStringContainsString('agent_456', $exception->getMessage());
  }

  /**
   * Test noToolsAvailable factory.
   *
   * @covers \Drupal\flowdrop_agent\Exception\AgentException::noToolsAvailable
   */
  public function testNoToolsAvailable(): void {
    $exception = AgentException::noToolsAvailable('agent_789');

    $this->assertInstanceOf(AgentException::class, $exception);
    $this->assertStringContainsString('agent_789', $exception->getMessage());
    $this->assertStringContainsString('No tools', $exception->getMessage());
  }

  /**
   * Test ToolExecutionException executionFailed factory.
   *
   * @covers \Drupal\flowdrop_agent\Exception\ToolExecutionException::executionFailed
   */
  public function testToolExecutionFailed(): void {
    $previous = new \RuntimeException('Original error');
    $exception = ToolExecutionException::executionFailed(
      'get_weather',
      'node_abc',
      'API timeout',
      $previous
    );

    $this->assertInstanceOf(ToolExecutionException::class, $exception);
    $this->assertInstanceOf(AgentException::class, $exception);
    $this->assertStringContainsString('get_weather', $exception->getMessage());
    $this->assertStringContainsString('node_abc', $exception->getMessage());
    $this->assertStringContainsString('API timeout', $exception->getMessage());
    $this->assertSame($previous, $exception->getPrevious());
  }

  /**
   * Test ToolExecutionException getters.
   *
   * @covers \Drupal\flowdrop_agent\Exception\ToolExecutionException::getToolName
   * @covers \Drupal\flowdrop_agent\Exception\ToolExecutionException::getNodeId
   */
  public function testToolExecutionExceptionGetters(): void {
    $exception = ToolExecutionException::executionFailed(
      'calculate',
      'node_xyz',
      'Division by zero'
    );

    $this->assertSame('calculate', $exception->getToolName());
    $this->assertSame('node_xyz', $exception->getNodeId());
  }

}
