<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_agent\Unit\DTO;

use Drupal\flowdrop_agent\DTO\AgentTrace;
use Drupal\flowdrop_agent\DTO\TraceStep;
use Drupal\flowdrop_ai\DTO\ToolDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AgentTrace DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_agent\DTO\AgentTrace
 * @group flowdrop_agent
 */
class AgentTraceTest extends TestCase {

  /**
   * Test construction.
   *
   * @covers ::__construct
   * @covers ::getExecutionId
   * @covers ::getAgentNodeId
   * @covers ::getStatus
   */
  public function testConstruction(): void {
    $trace = new AgentTrace(
      executionId: 'exec_123',
      agentNodeId: 'agent_456',
    );

    $this->assertSame('exec_123', $trace->getExecutionId());
    $this->assertSame('agent_456', $trace->getAgentNodeId());
    $this->assertSame(AgentTrace::STATUS_RUNNING, $trace->getStatus());
    $this->assertNull($trace->getFinalAnswer());
  }

  /**
   * Test construction with tools.
   *
   * @covers ::getAvailableTools
   */
  public function testConstructionWithTools(): void {
    $tools = [
      new ToolDefinition('search', 'Search tool', 'n1', 't1'),
      new ToolDefinition('calculate', 'Calculator', 'n2', 't2'),
    ];

    $trace = new AgentTrace(
      executionId: 'exec',
      agentNodeId: 'agent',
      availableTools: $tools,
    );

    $this->assertCount(2, $trace->getAvailableTools());
  }

  /**
   * Test adding steps.
   *
   * @covers ::addStep
   * @covers ::getSteps
   * @covers ::getTotalTokensUsed
   */
  public function testAddStep(): void {
    $trace = new AgentTrace('exec', 'agent');

    $step1 = TraceStep::llmCall(1, ['messages' => []], ['response' => 'x'], 100, 500);
    $step2 = TraceStep::llmCall(2, ['messages' => []], ['response' => 'y'], 150, 300);

    $trace->addStep($step1)->addStep($step2);

    $this->assertCount(2, $trace->getSteps());
    $this->assertSame(250, $trace->getTotalTokensUsed());
  }

  /**
   * Test complete.
   *
   * @covers ::complete
   * @covers ::getFinalAnswer
   * @covers ::getTotalIterations
   * @covers ::getTotalExecutionTimeMs
   * @covers ::getCompletedAt
   */
  public function testComplete(): void {
    $trace = new AgentTrace('exec', 'agent');

    $trace->complete(
      status: AgentTrace::STATUS_COMPLETED,
      finalAnswer: 'The answer is 42',
      totalIterations: 3,
      totalExecutionTimeMs: 5000.0,
    );

    $this->assertSame(AgentTrace::STATUS_COMPLETED, $trace->getStatus());
    $this->assertSame('The answer is 42', $trace->getFinalAnswer());
    $this->assertSame(3, $trace->getTotalIterations());
    $this->assertSame(5000.0, $trace->getTotalExecutionTimeMs());
    $this->assertNotNull($trace->getCompletedAt());
  }

  /**
   * Test fail.
   *
   * @covers ::fail
   * @covers ::getErrorMessage
   */
  public function testFail(): void {
    $trace = new AgentTrace('exec', 'agent');

    $trace->fail(
      errorMessage: 'API rate limit exceeded',
      totalIterations: 2,
      totalExecutionTimeMs: 3000.0,
    );

    $this->assertSame(AgentTrace::STATUS_FAILED, $trace->getStatus());
    $this->assertSame('API rate limit exceeded', $trace->getErrorMessage());
    $this->assertSame(2, $trace->getTotalIterations());
  }

  /**
   * Test max iterations status.
   *
   * @covers ::complete
   */
  public function testMaxIterationsStatus(): void {
    $trace = new AgentTrace('exec', 'agent');

    $trace->complete(
      status: AgentTrace::STATUS_MAX_ITERATIONS,
      finalAnswer: NULL,
      totalIterations: 10,
      totalExecutionTimeMs: 10000.0,
    );

    $this->assertSame(AgentTrace::STATUS_MAX_ITERATIONS, $trace->getStatus());
    $this->assertNull($trace->getFinalAnswer());
  }

  /**
   * Test toOutput.
   *
   * @covers ::toOutput
   */
  public function testToOutput(): void {
    $trace = new AgentTrace(
      executionId: 'exec',
      agentNodeId: 'agent',
      availableTools: [new ToolDefinition('test', 'Test tool', 'n1', 't1')],
    );

    $trace->addStep(TraceStep::llmCall(1, [], ['content' => 'Hello'], 50, 100));
    $trace->complete(AgentTrace::STATUS_COMPLETED, 'Done', 1, 200.0);

    $output = $trace->toOutput();

    $this->assertSame('Done', $output['answer']);
    $this->assertSame(AgentTrace::STATUS_COMPLETED, $output['status']);
    $this->assertSame(1, $output['iterations']);
    $this->assertSame(50, $output['tokensUsed']);
    $this->assertCount(1, $output['steps']);
    $this->assertCount(1, $output['availableTools']);
    $this->assertNull($output['error']);
  }

  /**
   * Test toArray.
   *
   * @covers ::toArray
   */
  public function testToArray(): void {
    $trace = new AgentTrace('exec_abc', 'agent_xyz');
    $trace->complete(AgentTrace::STATUS_COMPLETED, 'Answer', 2, 1000.0);

    $array = $trace->toArray();

    $this->assertSame('exec_abc', $array['execution_id']);
    $this->assertSame('agent_xyz', $array['agent_node_id']);
    $this->assertSame(AgentTrace::STATUS_COMPLETED, $array['status']);
    $this->assertSame('Answer', $array['final_answer']);
    $this->assertArrayHasKey('started_at', $array);
    $this->assertArrayHasKey('completed_at', $array);
    $this->assertArrayHasKey('steps', $array);
    $this->assertArrayHasKey('available_tools', $array);
  }

  /**
   * Test getStartedAt.
   *
   * @covers ::getStartedAt
   */
  public function testGetStartedAt(): void {
    $trace = new AgentTrace('exec', 'agent');
    $this->assertInstanceOf(\DateTimeImmutable::class, $trace->getStartedAt());
  }

  /**
   * Test status constants.
   */
  public function testStatusConstants(): void {
    $this->assertSame('running', AgentTrace::STATUS_RUNNING);
    $this->assertSame('completed', AgentTrace::STATUS_COMPLETED);
    $this->assertSame('failed', AgentTrace::STATUS_FAILED);
    $this->assertSame('max_iterations', AgentTrace::STATUS_MAX_ITERATIONS);
  }

}
