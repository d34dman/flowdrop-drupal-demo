<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Unit\DTO;

use Drupal\flowdrop_iterator\DTO\IteratorState;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the IteratorState DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_iterator\DTO\IteratorState
 * @group flowdrop_iterator
 */
class IteratorStateTest extends TestCase {

  /**
   * Test initial state creation.
   *
   * @covers ::__construct
   * @covers ::getIteratorNodeId
   * @covers ::getItems
   * @covers ::getCurrentIndex
   * @covers ::getStatus
   * @covers ::getAccumulatedResults
   * @covers ::getChildPipelineId
   * @covers ::getErrors
   */
  public function testInitialState(): void {
    $items = ["item1", "item2", "item3"];
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
    );

    $this->assertSame("iterator_1", $state->getIteratorNodeId());
    $this->assertSame($items, $state->getItems());
    $this->assertSame(0, $state->getCurrentIndex());
    $this->assertSame(IteratorState::STATUS_PENDING, $state->getStatus());
    $this->assertSame([], $state->getAccumulatedResults());
    $this->assertNull($state->getChildPipelineId());
    $this->assertSame([], $state->getErrors());
  }

  /**
   * Test getCurrentItem returns correct item.
   *
   * @covers ::getCurrentItem
   */
  public function testGetCurrentItem(): void {
    $items = ["first", "second", "third"];
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
    );

    $this->assertSame("first", $state->getCurrentItem());

    // After incrementing index.
    $stateAtIndex1 = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
      currentIndex: 1,
    );

    $this->assertSame("second", $stateAtIndex1->getCurrentItem());
  }

  /**
   * Test getCurrentItem returns null for out of bounds.
   *
   * @covers ::getCurrentItem
   */
  public function testGetCurrentItemOutOfBounds(): void {
    $items = ["only"];
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
      currentIndex: 5,
    );

    $this->assertNull($state->getCurrentItem());
  }

  /**
   * Test getTotalCount returns correct count.
   *
   * @covers ::getTotalCount
   */
  public function testGetTotalCount(): void {
    $items = ["a", "b", "c", "d"];
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
    );

    $this->assertSame(4, $state->getTotalCount());
  }

  /**
   * Test getTotalCount with empty array.
   *
   * @covers ::getTotalCount
   */
  public function testGetTotalCountEmpty(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
    );

    $this->assertSame(0, $state->getTotalCount());
  }

  /**
   * Test hasMoreItems returns correct value.
   *
   * @covers ::hasMoreItems
   */
  public function testHasMoreItems(): void {
    $items = ["a", "b"];

    // At start - has more.
    $state0 = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
      currentIndex: 0,
    );
    $this->assertTrue($state0->hasMoreItems());

    // At index 1 - has more.
    $state1 = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
      currentIndex: 1,
    );
    $this->assertTrue($state1->hasMoreItems());

    // At index 2 - no more items.
    $state2 = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
      currentIndex: 2,
    );
    $this->assertFalse($state2->hasMoreItems());
  }

  /**
   * Test isComplete returns correct value.
   *
   * @covers ::isComplete
   */
  public function testIsComplete(): void {
    // Pending - not complete.
    $statePending = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
      status: IteratorState::STATUS_PENDING,
    );
    $this->assertFalse($statePending->isComplete());

    // Iterating - not complete.
    $stateIterating = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
      status: IteratorState::STATUS_ITERATING,
    );
    $this->assertFalse($stateIterating->isComplete());

    // Completed - is complete.
    $stateCompleted = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
      status: IteratorState::STATUS_COMPLETED,
    );
    $this->assertTrue($stateCompleted->isComplete());

    // Failed - is complete.
    $stateFailed = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
      status: IteratorState::STATUS_FAILED,
    );
    $this->assertTrue($stateFailed->isComplete());
  }

  /**
   * Test hasErrors returns correct value.
   *
   * @covers ::hasErrors
   */
  public function testHasErrors(): void {
    // No errors.
    $stateNoErrors = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
    );
    $this->assertFalse($stateNoErrors->hasErrors());

    // With errors.
    $stateWithErrors = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
      errors: ["Error 1", "Error 2"],
    );
    $this->assertTrue($stateWithErrors->hasErrors());
  }

  /**
   * Test withNextIteration creates correct new state.
   *
   * @covers ::withNextIteration
   */
  public function testWithNextIteration(): void {
    $items = ["a", "b", "c"];
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
      currentIndex: 0,
      accumulatedResults: [],
      status: IteratorState::STATUS_ITERATING,
    );

    $result = ["processed" => "a"];
    $newState = $state->withNextIteration($result);

    // Original state should be unchanged (immutability).
    $this->assertSame(0, $state->getCurrentIndex());
    $this->assertSame([], $state->getAccumulatedResults());

    // New state should have updated values.
    $this->assertSame(1, $newState->getCurrentIndex());
    $this->assertSame([$result], $newState->getAccumulatedResults());
    $this->assertSame(IteratorState::STATUS_ITERATING, $newState->getStatus());
    $this->assertSame("iterator_1", $newState->getIteratorNodeId());
    $this->assertSame($items, $newState->getItems());
  }

  /**
   * Test withNextIteration accumulates multiple results.
   *
   * @covers ::withNextIteration
   */
  public function testWithNextIterationAccumulation(): void {
    $items = ["a", "b", "c"];
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: $items,
      currentIndex: 1,
      accumulatedResults: ["result_0"],
      status: IteratorState::STATUS_ITERATING,
    );

    $newState = $state->withNextIteration("result_1");

    $this->assertSame(2, $newState->getCurrentIndex());
    $this->assertSame(["result_0", "result_1"], $newState->getAccumulatedResults());
  }

  /**
   * Test withCompleted creates correct new state.
   *
   * @covers ::withCompleted
   */
  public function testWithCompleted(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: ["a", "b"],
      currentIndex: 2,
      accumulatedResults: ["r1", "r2"],
      status: IteratorState::STATUS_ITERATING,
    );

    $newState = $state->withCompleted();

    $this->assertSame(IteratorState::STATUS_COMPLETED, $newState->getStatus());
    $this->assertSame(2, $newState->getCurrentIndex());
    $this->assertSame(["r1", "r2"], $newState->getAccumulatedResults());
  }

  /**
   * Test withError creates correct new state.
   *
   * @covers ::withError
   */
  public function testWithError(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: ["a"],
      currentIndex: 0,
      status: IteratorState::STATUS_ITERATING,
    );

    $newState = $state->withError("Something went wrong");

    $this->assertSame(IteratorState::STATUS_FAILED, $newState->getStatus());
    $this->assertSame(["Something went wrong"], $newState->getErrors());
    // Index should NOT increment on error.
    $this->assertSame(0, $newState->getCurrentIndex());
  }

  /**
   * Test withError accumulates errors.
   *
   * @covers ::withError
   */
  public function testWithErrorAccumulation(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: [],
      errors: ["Error 1"],
    );

    $newState = $state->withError("Error 2");

    $this->assertSame(["Error 1", "Error 2"], $newState->getErrors());
  }

  /**
   * Test withSkippedItem creates correct new state.
   *
   * @covers ::withSkippedItem
   */
  public function testWithSkippedItem(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: ["a", "b", "c"],
      currentIndex: 1,
      accumulatedResults: ["r0"],
      status: IteratorState::STATUS_ITERATING,
    );

    $newState = $state->withSkippedItem("Item failed");

    // Index should increment.
    $this->assertSame(2, $newState->getCurrentIndex());
    // Status should remain iterating.
    $this->assertSame(IteratorState::STATUS_ITERATING, $newState->getStatus());
    // Should have skipped marker in results.
    $expectedResults = [
      "r0",
      [
        "_skipped" => TRUE,
        "_error" => "Item failed",
        "_index" => 1,
      ],
    ];
    $this->assertSame($expectedResults, $newState->getAccumulatedResults());
    // Error should be recorded.
    $this->assertSame(["Item failed"], $newState->getErrors());
  }

  /**
   * Test withChildPipeline creates correct new state.
   *
   * @covers ::withChildPipeline
   */
  public function testWithChildPipeline(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: ["a"],
    );

    $newState = $state->withChildPipeline("pipeline_123");

    $this->assertSame("pipeline_123", $newState->getChildPipelineId());
    // Original unchanged.
    $this->assertNull($state->getChildPipelineId());
  }

  /**
   * Test withIterating creates correct new state.
   *
   * @covers ::withIterating
   */
  public function testWithIterating(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: ["a"],
      status: IteratorState::STATUS_PENDING,
    );

    $newState = $state->withIterating();

    $this->assertSame(IteratorState::STATUS_ITERATING, $newState->getStatus());
  }

  /**
   * Test toArray returns correct structure.
   *
   * @covers ::toArray
   */
  public function testToArray(): void {
    $state = new IteratorState(
      iteratorNodeId: "iterator_1",
      items: ["a", "b"],
      currentIndex: 1,
      accumulatedResults: ["r0"],
      status: IteratorState::STATUS_ITERATING,
      childPipelineId: "pipeline_1",
      errors: ["error1"],
    );

    $array = $state->toArray();

    $this->assertSame("iterator_1", $array["iteratorNodeId"]);
    $this->assertSame(["a", "b"], $array["items"]);
    $this->assertSame(1, $array["currentIndex"]);
    $this->assertSame(["r0"], $array["accumulatedResults"]);
    $this->assertSame(IteratorState::STATUS_ITERATING, $array["status"]);
    $this->assertSame("pipeline_1", $array["childPipelineId"]);
    $this->assertSame(["error1"], $array["errors"]);
    $this->assertSame(2, $array["totalCount"]);
    $this->assertTrue($array["hasMoreItems"]);
    $this->assertFalse($array["isComplete"]);
    $this->assertTrue($array["hasErrors"]);
  }

  /**
   * Test fromArray creates correct state.
   *
   * @covers ::fromArray
   */
  public function testFromArray(): void {
    $data = [
      "iteratorNodeId" => "iterator_1",
      "items" => ["x", "y"],
      "currentIndex" => 1,
      "accumulatedResults" => ["rx"],
      "status" => IteratorState::STATUS_COMPLETED,
      "childPipelineId" => "pipeline_abc",
      "errors" => ["err"],
    ];

    $state = IteratorState::fromArray($data);

    $this->assertSame("iterator_1", $state->getIteratorNodeId());
    $this->assertSame(["x", "y"], $state->getItems());
    $this->assertSame(1, $state->getCurrentIndex());
    $this->assertSame(["rx"], $state->getAccumulatedResults());
    $this->assertSame(IteratorState::STATUS_COMPLETED, $state->getStatus());
    $this->assertSame("pipeline_abc", $state->getChildPipelineId());
    $this->assertSame(["err"], $state->getErrors());
  }

  /**
   * Test fromArray with missing fields uses defaults.
   *
   * @covers ::fromArray
   */
  public function testFromArrayWithDefaults(): void {
    $data = [
      "iteratorNodeId" => "iter_1",
      "items" => ["a"],
    ];

    $state = IteratorState::fromArray($data);

    $this->assertSame("iter_1", $state->getIteratorNodeId());
    $this->assertSame(["a"], $state->getItems());
    $this->assertSame(0, $state->getCurrentIndex());
    $this->assertSame([], $state->getAccumulatedResults());
    $this->assertSame(IteratorState::STATUS_PENDING, $state->getStatus());
    $this->assertNull($state->getChildPipelineId());
    $this->assertSame([], $state->getErrors());
  }

  /**
   * Test round-trip conversion (toArray -> fromArray).
   *
   * @covers ::toArray
   * @covers ::fromArray
   */
  public function testRoundTrip(): void {
    $original = new IteratorState(
      iteratorNodeId: "iter_test",
      items: [1, 2, 3],
      currentIndex: 2,
      accumulatedResults: ["a", "b"],
      status: IteratorState::STATUS_ITERATING,
      childPipelineId: "pipe_1",
      errors: ["e1"],
    );

    $restored = IteratorState::fromArray($original->toArray());

    $this->assertSame($original->getIteratorNodeId(), $restored->getIteratorNodeId());
    $this->assertSame($original->getItems(), $restored->getItems());
    $this->assertSame($original->getCurrentIndex(), $restored->getCurrentIndex());
    $this->assertSame($original->getAccumulatedResults(), $restored->getAccumulatedResults());
    $this->assertSame($original->getStatus(), $restored->getStatus());
    $this->assertSame($original->getChildPipelineId(), $restored->getChildPipelineId());
    $this->assertSame($original->getErrors(), $restored->getErrors());
  }

}
