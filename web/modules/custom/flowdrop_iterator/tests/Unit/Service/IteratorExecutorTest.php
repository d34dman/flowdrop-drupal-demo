<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\DTO\Output;
use Drupal\flowdrop_iterator\Exception\IteratorException;
use Drupal\flowdrop_iterator\Service\IteratorExecutor;
use Drupal\flowdrop_iterator\Service\SubWorkflowDetector;
use Drupal\flowdrop_job\FlowDropJobInterface;
use Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface;
use Drupal\flowdrop_runtime\DTO\Runtime\NodeExecutionResult;
use Drupal\flowdrop_runtime\Service\Runtime\ExecutionContext;
use Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService;
use Drupal\flowdrop_workflow\DTO\WorkflowDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for the IteratorExecutor service.
 *
 * @coversDefaultClass \Drupal\flowdrop_iterator\Service\IteratorExecutor
 * @group flowdrop_iterator
 */
class IteratorExecutorTest extends TestCase {

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock node runtime service.
   *
   * @var \Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $nodeRuntime;

  /**
   * Mock execution context service.
   *
   * @var \Drupal\flowdrop_runtime\Service\Runtime\ExecutionContext|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executionContext;

  /**
   * Mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * Mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * Mock sub-workflow detector.
   *
   * @var \Drupal\flowdrop_iterator\Service\SubWorkflowDetector|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $subWorkflowDetector;

  /**
   * The executor under test.
   *
   * @var \Drupal\flowdrop_iterator\Service\IteratorExecutor
   */
  protected IteratorExecutor $executor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->nodeRuntime = $this->createMock(NodeRuntimeService::class);
    $this->executionContext = $this->createMock(ExecutionContext::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method("get")->willReturn($this->logger);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->subWorkflowDetector = $this->createMock(SubWorkflowDetector::class);

    $this->executor = new IteratorExecutor(
      $this->entityTypeManager,
      $this->nodeRuntime,
      $this->executionContext,
      $this->loggerFactory,
      $this->eventDispatcher,
      $this->subWorkflowDetector,
    );
  }

  /**
   * Test execute with empty array returns immediately.
   *
   * @covers ::execute
   */
  public function testExecuteEmptyArray(): void {
    $workflow = $this->createMock(WorkflowDTO::class);

    $output = $this->executor->execute(
      executionId: "exec_1",
      iteratorNodeId: "iterator_1",
      inputData: ["data" => []],
      config: [],
      workflow: $workflow,
      parentPipelineId: "parent_1",
    );

    $this->assertSame([], $output->get("done"));
    $this->assertTrue($output->get("isComplete"));
    $this->assertSame(0, $output->get("total"));
  }

  /**
   * Test execute with non-array data converts to array.
   *
   * @covers ::execute
   */
  public function testExecuteNonArrayData(): void {
    $workflow = $this->createMock(WorkflowDTO::class);

    // Set up sub-workflow detector.
    $this->subWorkflowDetector->method("detect")->willReturn([
      "subWorkflowNodes" => ["node_a"],
      "executionOrder" => ["node_a"],
      "loopbackSourceNodeId" => "node_a",
      "errors" => [],
    ]);

    // Set up entity storage mocks.
    $this->setupEntityStorageMocks();

    // Set up node mock for workflow.
    $nodeMock = $this->createMockNode("node_a", "processor");
    $workflow->method("getNode")->willReturn($nodeMock);

    // Set up node runtime to return result.
    $outputMock = new Output();
    $outputMock->fromArray(["processed" => TRUE]);
    $resultMock = $this->createMock(NodeExecutionResult::class);
    $resultMock->method("getOutput")->willReturn($outputMock);
    $this->nodeRuntime->method("executeNode")->willReturn($resultMock);

    // Set up execution context.
    $this->executionContext->method("createContext")->willReturn(new \stdClass());

    $output = $this->executor->execute(
      executionId: "exec_1",
      iteratorNodeId: "iterator_1",
    // Non-array.
      inputData: ["data" => "single_value"],
      config: [],
      workflow: $workflow,
      parentPipelineId: "parent_1",
    );

    $done = $output->get("done");
    $this->assertIsArray($done);
    $this->assertCount(1, $done);
    $this->assertTrue($output->get("isComplete"));
  }

  /**
   * Test execute truncates items exceeding max iterations.
   *
   * @covers ::execute
   */
  public function testExecuteTruncatesExcessItems(): void {
    $workflow = $this->createMock(WorkflowDTO::class);

    // Create array with more items than max.
    $items = range(1, 10);

    // Set up sub-workflow detector.
    $this->subWorkflowDetector->method("detect")->willReturn([
      "subWorkflowNodes" => ["node_a"],
      "executionOrder" => ["node_a"],
      "loopbackSourceNodeId" => "node_a",
      "errors" => [],
    ]);

    // Set up entity storage mocks.
    $this->setupEntityStorageMocks();

    // Set up node mock.
    $nodeMock = $this->createMockNode("node_a", "processor");
    $workflow->method("getNode")->willReturn($nodeMock);

    // Set up node runtime.
    $outputMock = new Output();
    $outputMock->fromArray(["result" => "ok"]);
    $resultMock = $this->createMock(NodeExecutionResult::class);
    $resultMock->method("getOutput")->willReturn($outputMock);
    $this->nodeRuntime->method("executeNode")->willReturn($resultMock);

    $this->executionContext->method("createContext")->willReturn(new \stdClass());

    // Execute with maxIterations = 5.
    $output = $this->executor->execute(
      executionId: "exec_1",
      iteratorNodeId: "iterator_1",
      inputData: ["data" => $items],
      config: ["maxIterations" => 5],
      workflow: $workflow,
      parentPipelineId: "parent_1",
    );

    // Should only have 5 results (truncated).
    $done = $output->get("done");
    $this->assertCount(5, $done);
  }

  /**
   * Test execute with onError skip continues on failure.
   *
   * @covers ::execute
   */
  public function testExecuteSkipOnError(): void {
    $workflow = $this->createMock(WorkflowDTO::class);

    // Set up sub-workflow detector.
    $this->subWorkflowDetector->method("detect")->willReturn([
      "subWorkflowNodes" => ["node_a"],
      "executionOrder" => ["node_a"],
      "loopbackSourceNodeId" => "node_a",
      "errors" => [],
    ]);

    $this->setupEntityStorageMocks();

    $nodeMock = $this->createMockNode("node_a", "processor");
    $workflow->method("getNode")->willReturn($nodeMock);

    // First call succeeds, second fails, third succeeds.
    $successOutput = new Output();
    $successOutput->fromArray(["success" => TRUE]);
    $successResult = $this->createMock(NodeExecutionResult::class);
    $successResult->method("getOutput")->willReturn($successOutput);

    $this->nodeRuntime->method("executeNode")
      ->willReturnCallback(function () use ($successResult) {
        static $callCount = 0;
        $callCount++;
        if ($callCount === 2) {
          throw new \RuntimeException("Processing failed");
        }
        return $successResult;
      });

    $this->executionContext->method("createContext")->willReturn(new \stdClass());

    $output = $this->executor->execute(
      executionId: "exec_1",
      iteratorNodeId: "iterator_1",
      inputData: ["data" => ["a", "b", "c"]],
      config: ["onError" => "skip"],
      workflow: $workflow,
      parentPipelineId: "parent_1",
    );

    // Should have 3 results (including skipped).
    $done = $output->get("done");
    $this->assertCount(3, $done);

    // Check that errors were recorded.
    $errors = $output->get("errors");
    $this->assertNotEmpty($errors);
  }

  /**
   * Test execute with onError fail throws exception.
   *
   * @covers ::execute
   */
  public function testExecuteFailOnError(): void {
    $workflow = $this->createMock(WorkflowDTO::class);

    // Set up sub-workflow detector.
    $this->subWorkflowDetector->method("detect")->willReturn([
      "subWorkflowNodes" => ["node_a"],
      "executionOrder" => ["node_a"],
      "loopbackSourceNodeId" => "node_a",
      "errors" => [],
    ]);

    $this->setupEntityStorageMocks();

    $nodeMock = $this->createMockNode("node_a", "processor");
    $workflow->method("getNode")->willReturn($nodeMock);

    // Always fail.
    $this->nodeRuntime->method("executeNode")
      ->willThrowException(new \RuntimeException("Always fails"));

    $this->executionContext->method("createContext")->willReturn(new \stdClass());

    $this->expectException(IteratorException::class);

    $this->executor->execute(
      executionId: "exec_1",
      iteratorNodeId: "iterator_1",
      inputData: ["data" => ["a"]],
      config: ["onError" => "fail"],
      workflow: $workflow,
      parentPipelineId: "parent_1",
    );
  }

  /**
   * Test execute dispatches events.
   *
   * @covers ::execute
   */
  public function testExecuteDispatchesEvents(): void {
    $workflow = $this->createMock(WorkflowDTO::class);

    $this->subWorkflowDetector->method("detect")->willReturn([
      "subWorkflowNodes" => ["node_a"],
      "executionOrder" => ["node_a"],
      "loopbackSourceNodeId" => "node_a",
      "errors" => [],
    ]);

    $this->setupEntityStorageMocks();

    $nodeMock = $this->createMockNode("node_a", "processor");
    $workflow->method("getNode")->willReturn($nodeMock);

    $outputMock = new Output();
    $outputMock->fromArray(["done" => TRUE]);
    $resultMock = $this->createMock(NodeExecutionResult::class);
    $resultMock->method("getOutput")->willReturn($outputMock);
    $this->nodeRuntime->method("executeNode")->willReturn($resultMock);

    $this->executionContext->method("createContext")->willReturn(new \stdClass());

    // Track dispatched events.
    $dispatchedEvents = [];
    $this->eventDispatcher->method("dispatch")
      ->willReturnCallback(function ($event, $eventName) use (&$dispatchedEvents) {
        $dispatchedEvents[] = $eventName;
        return $event;
      });

    $this->executor->execute(
      executionId: "exec_1",
      iteratorNodeId: "iterator_1",
      inputData: ["data" => ["a"]],
      config: [],
      workflow: $workflow,
      parentPipelineId: "parent_1",
    );

    // Should have dispatched iterator lifecycle events.
    $this->assertContains("flowdrop.iterator.started", $dispatchedEvents);
    $this->assertContains("flowdrop.iterator.iteration_started", $dispatchedEvents);
    $this->assertContains("flowdrop.iterator.iteration_completed", $dispatchedEvents);
    $this->assertContains("flowdrop.iterator.completed", $dispatchedEvents);
  }

  /**
   * Set up entity storage mocks for pipeline and job creation.
   */
  private function setupEntityStorageMocks(): void {
    // Pipeline storage.
    $pipelineMock = $this->createMock(FlowDropPipelineInterface::class);
    $pipelineMock->method("id")->willReturn("pipeline_123");
    $pipelineMock->method("save")->willReturn(1);

    $pipelineStorage = $this->createMock(EntityStorageInterface::class);
    $pipelineStorage->method("create")->willReturn($pipelineMock);

    // Job storage.
    $jobMock = $this->createMock(FlowDropJobInterface::class);
    $jobMock->method("id")->willReturn("job_456");
    $jobMock->method("getNodeId")->willReturn("node_a");
    $jobMock->method("save")->willReturn(1);
    $jobMock->method("getPipeline")->willReturn($pipelineMock);

    $jobStorage = $this->createMock(EntityStorageInterface::class);
    $jobStorage->method("create")->willReturn($jobMock);

    $this->entityTypeManager->method("getStorage")
      ->willReturnCallback(function ($entityType) use ($pipelineStorage, $jobStorage) {
        if ($entityType === "flowdrop_pipeline") {
          return $pipelineStorage;
        }
        if ($entityType === "flowdrop_job") {
          return $jobStorage;
        }
        return NULL;
      });
  }

  /**
   * Create a mock node object.
   *
   * @param string $id
   *   Node ID.
   * @param string $typeId
   *   Node type ID.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   Mock node.
   */
  private function createMockNode(string $id, string $typeId): object {
    $node = $this->getMockBuilder(\stdClass::class)
      ->addMethods(["getId", "getTypeId", "getConfig", "getLabel"])
      ->getMock();

    $node->method("getId")->willReturn($id);
    $node->method("getTypeId")->willReturn($typeId);
    $node->method("getConfig")->willReturn([]);
    $node->method("getLabel")->willReturn("Node {$id}");

    return $node;
  }

}
