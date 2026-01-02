<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_agent\Unit\DTO;

use Drupal\flowdrop_agent\DTO\AgentState;
use Drupal\flowdrop_agent\DTO\ToolResult;
use Drupal\flowdrop_conversation\DTO\ToolCall;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AgentState DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_agent\DTO\AgentState
 * @group flowdrop_agent
 */
class AgentStateTest extends TestCase {

  /**
   * Test initialization.
   *
   * @covers ::initialize
   * @covers ::getExecutionId
   * @covers ::getConversationId
   * @covers ::getMaxIterations
   */
  public function testInitialize(): void {
    $state = AgentState::initialize(
      executionId: 'exec_123',
      conversationId: 'conv_456',
      maxIterations: 15,
    );

    $this->assertSame('exec_123', $state->getExecutionId());
    $this->assertSame('conv_456', $state->getConversationId());
    $this->assertSame(0, $state->getCurrentIteration());
    $this->assertSame(15, $state->getMaxIterations());
    $this->assertFalse($state->isComplete());
  }

  /**
   * Test advancing iteration.
   *
   * @covers ::advanceIteration
   * @covers ::getCurrentIteration
   */
  public function testAdvanceIteration(): void {
    $state = AgentState::initialize('exec', 'conv', 10);

    $state = $state->advanceIteration();
    $this->assertSame(1, $state->getCurrentIteration());

    $state = $state->advanceIteration();
    $this->assertSame(2, $state->getCurrentIteration());
  }

  /**
   * Test max iterations check.
   *
   * @covers ::hasReachedMaxIterations
   */
  public function testHasReachedMaxIterations(): void {
    $state = AgentState::initialize('exec', 'conv', 2);

    $this->assertFalse($state->hasReachedMaxIterations());

    $state = $state->advanceIteration();
    $this->assertFalse($state->hasReachedMaxIterations());

    $state = $state->advanceIteration();
    $this->assertTrue($state->hasReachedMaxIterations());
  }

  /**
   * Test marking complete.
   *
   * @covers ::markComplete
   * @covers ::isComplete
   * @covers ::getFinalAnswer
   */
  public function testMarkComplete(): void {
    $state = AgentState::initialize('exec', 'conv', 10);

    $state = $state->markComplete('The answer is 42.');

    $this->assertTrue($state->isComplete());
    $this->assertSame('The answer is 42.', $state->getFinalAnswer());
  }

  /**
   * Test recording tool call.
   *
   * @covers ::recordToolCall
   * @covers ::getToolCalls
   */
  public function testRecordToolCall(): void {
    $state = AgentState::initialize('exec', 'conv', 10);
    $toolCall = ToolCall::create('get_weather', ['location' => 'NYC']);

    $state = $state->recordToolCall($toolCall);
    $calls = $state->getToolCalls();

    $this->assertCount(1, $calls);
    $this->assertSame('get_weather', $calls[0]->getToolName());
  }

  /**
   * Test recording tool result.
   *
   * @covers ::recordToolResult
   * @covers ::getToolResults
   */
  public function testRecordToolResult(): void {
    $state = AgentState::initialize('exec', 'conv', 10);
    $result = ToolResult::success('tc_1', 'search', 'node_1', ['data' => 'test'], 50.5);

    $state = $state->recordToolResult($result);
    $results = $state->getToolResults();

    $this->assertCount(1, $results);
    $this->assertSame('search', $results[0]->getToolName());
  }

  /**
   * Test adding tokens.
   *
   * @covers ::addTokensUsed
   * @covers ::getTotalTokensUsed
   */
  public function testAddTokensUsed(): void {
    $state = AgentState::initialize('exec', 'conv', 10);

    $state = $state->addTokensUsed(100);
    $this->assertSame(100, $state->getTotalTokensUsed());

    $state = $state->addTokensUsed(50);
    $this->assertSame(150, $state->getTotalTokensUsed());
  }

  /**
   * Test setting child pipeline ID.
   *
   * @covers ::withChildPipelineId
   * @covers ::getChildPipelineId
   */
  public function testWithChildPipelineId(): void {
    $state = AgentState::initialize('exec', 'conv', 10);

    $this->assertNull($state->getChildPipelineId());

    $state = $state->withChildPipelineId('child_pipeline_123');
    $this->assertSame('child_pipeline_123', $state->getChildPipelineId());
  }

  /**
   * Test immutability.
   *
   * @covers ::advanceIteration
   */
  public function testImmutability(): void {
    $original = AgentState::initialize('exec', 'conv', 10);
    $advanced = $original->advanceIteration();

    // Original should be unchanged.
    $this->assertSame(0, $original->getCurrentIteration());
    $this->assertSame(1, $advanced->getCurrentIteration());
  }

  /**
   * Test toArray.
   *
   * @covers ::toArray
   */
  public function testToArray(): void {
    $state = AgentState::initialize('exec_abc', 'conv_xyz', 10);
    $state = $state->markComplete('Final answer');

    $array = $state->toArray();

    $this->assertSame('exec_abc', $array['execution_id']);
    $this->assertSame('conv_xyz', $array['conversation_id']);
    $this->assertTrue($array['is_complete']);
    $this->assertSame('Final answer', $array['final_answer']);
  }

  /**
   * Test started_at timestamp.
   *
   * @covers ::getStartedAt
   */
  public function testStartedAt(): void {
    $state = AgentState::initialize('exec', 'conv', 10);

    $this->assertInstanceOf(\DateTimeImmutable::class, $state->getStartedAt());
  }

}
