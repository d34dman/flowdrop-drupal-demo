<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_agent\Unit\DTO;

use Drupal\flowdrop_agent\DTO\ToolResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ToolResult DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_agent\DTO\ToolResult
 * @group flowdrop_agent
 */
class ToolResultTest extends TestCase {

  /**
   * Test success factory method.
   *
   * @covers ::success
   * @covers ::isSuccess
   * @covers ::getStatus
   */
  public function testSuccess(): void {
    $result = ToolResult::success(
      toolCallId: 'tc_123',
      toolName: 'get_weather',
      nodeId: 'node_abc',
      output: ['temperature' => 72, 'condition' => 'sunny'],
      executionTimeMs: 150.5,
    );

    $this->assertTrue($result->isSuccess());
    $this->assertFalse($result->isError());
    $this->assertFalse($result->isSkipped());
    $this->assertSame(ToolResult::STATUS_SUCCESS, $result->getStatus());
  }

  /**
   * Test error factory method.
   *
   * @covers ::error
   * @covers ::isError
   * @covers ::getErrorMessage
   */
  public function testError(): void {
    $result = ToolResult::error(
      toolCallId: 'tc_456',
      toolName: 'api_call',
      nodeId: 'node_def',
      errorMessage: 'Connection timeout',
      executionTimeMs: 5000.0,
    );

    $this->assertTrue($result->isError());
    $this->assertFalse($result->isSuccess());
    $this->assertSame(ToolResult::STATUS_ERROR, $result->getStatus());
    $this->assertSame('Connection timeout', $result->getErrorMessage());
  }

  /**
   * Test skipped factory method.
   *
   * @covers ::skipped
   * @covers ::isSkipped
   */
  public function testSkipped(): void {
    $result = ToolResult::skipped(
      toolCallId: 'tc_789',
      toolName: 'optional_tool',
      nodeId: 'node_ghi',
      reason: 'Tool not available in current context',
    );

    $this->assertTrue($result->isSkipped());
    $this->assertFalse($result->isSuccess());
    $this->assertFalse($result->isError());
    $this->assertSame(ToolResult::STATUS_SKIPPED, $result->getStatus());
  }

  /**
   * Test getters.
   *
   * @covers ::getToolCallId
   * @covers ::getToolName
   * @covers ::getNodeId
   * @covers ::getOutput
   * @covers ::getExecutionTimeMs
   */
  public function testGetters(): void {
    $output = ['result' => 'data'];
    $result = ToolResult::success('tc_1', 'tool', 'node', $output, 100.0);

    $this->assertSame('tc_1', $result->getToolCallId());
    $this->assertSame('tool', $result->getToolName());
    $this->assertSame('node', $result->getNodeId());
    $this->assertSame($output, $result->getOutput());
    $this->assertSame(100.0, $result->getExecutionTimeMs());
  }

  /**
   * Test getOutputForLlm with success.
   *
   * @covers ::getOutputForLlm
   */
  public function testGetOutputForLlmSuccess(): void {
    // String output.
    $result = ToolResult::success('tc', 'tool', 'node', 'plain string', 10.0);
    $this->assertSame('plain string', $result->getOutputForLlm());

    // Array output.
    $result = ToolResult::success('tc', 'tool', 'node', ['key' => 'value'], 10.0);
    $this->assertSame('{"key":"value"}', $result->getOutputForLlm());
  }

  /**
   * Test getOutputForLlm with error.
   *
   * @covers ::getOutputForLlm
   */
  public function testGetOutputForLlmError(): void {
    $result = ToolResult::error('tc', 'tool', 'node', 'Something went wrong', 10.0);
    $this->assertSame('Error: Something went wrong', $result->getOutputForLlm());
  }

  /**
   * Test getOutputForLlm with skipped.
   *
   * @covers ::getOutputForLlm
   */
  public function testGetOutputForLlmSkipped(): void {
    $result = ToolResult::skipped('tc', 'tool', 'node', 'Not available');
    $this->assertSame('Skipped: Not available', $result->getOutputForLlm());
  }

  /**
   * Test toArray.
   *
   * @covers ::toArray
   */
  public function testToArray(): void {
    $result = ToolResult::success('tc_123', 'get_data', 'node_456', ['x' => 1], 50.0);
    $array = $result->toArray();

    $this->assertSame('tc_123', $array['tool_call_id']);
    $this->assertSame('get_data', $array['tool_name']);
    $this->assertSame('node_456', $array['node_id']);
    $this->assertSame(ToolResult::STATUS_SUCCESS, $array['status']);
    $this->assertSame(['x' => 1], $array['output']);
    $this->assertSame(50.0, $array['execution_time_ms']);
  }

  /**
   * Test fromArray.
   *
   * @covers ::fromArray
   */
  public function testFromArray(): void {
    $data = [
      'tool_call_id' => 'tc_abc',
      'tool_name' => 'search',
      'node_id' => 'node_def',
      'status' => ToolResult::STATUS_SUCCESS,
      'output' => ['results' => []],
      'error_message' => NULL,
      'execution_time_ms' => 200.0,
    ];

    $result = ToolResult::fromArray($data);

    $this->assertSame('tc_abc', $result->getToolCallId());
    $this->assertSame('search', $result->getToolName());
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Test getExecutedAt.
   *
   * @covers ::getExecutedAt
   */
  public function testGetExecutedAt(): void {
    $result = ToolResult::success('tc', 'tool', 'node', NULL, 10.0);
    $this->assertInstanceOf(\DateTimeImmutable::class, $result->getExecutedAt());
  }

}
