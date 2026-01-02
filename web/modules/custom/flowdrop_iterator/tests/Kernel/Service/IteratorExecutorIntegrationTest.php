<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Kernel\Service;

use Drupal\flowdrop_runtime\DTO\Runtime\NodeExecutionResult;
use Drupal\flowdrop\DTO\Output;
use Drupal\flowdrop_iterator\Service\IteratorExecutor;
use Drupal\flowdrop_iterator\Service\SubWorkflowDetector;
use Drupal\flowdrop_pipeline\Entity\FlowDropPipeline;
use Drupal\flowdrop_job\Entity\FlowDropJob;
use Drupal\flowdrop_workflow\DTO\WorkflowDTO;
use Drupal\flowdrop_workflow\DTO\WorkflowNodeDTO;
use Drupal\flowdrop_workflow\DTO\WorkflowEdgeDTO;
use Drupal\KernelTests\KernelTestBase;

/**
 * Integration tests for the IteratorExecutor service.
 *
 * Tests the full iteration flow with actual Pipeline and Job entities.
 *
 * @coversDefaultClass \Drupal\flowdrop_iterator\Service\IteratorExecutor
 * @group flowdrop_iterator
 */
class IteratorExecutorIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'flowdrop',
    'flowdrop_node_type',
    'flowdrop_workflow',
    'flowdrop_pipeline',
    'flowdrop_job',
    'flowdrop_runtime',
    'flowdrop_iterator',
  ];

  /**
   * The iterator executor service.
   *
   * @var \Drupal\flowdrop_iterator\Service\IteratorExecutor
   */
  protected IteratorExecutor $iteratorExecutor;

  /**
   * The sub-workflow detector service.
   *
   * @var \Drupal\flowdrop_iterator\Service\SubWorkflowDetector
   */
  protected SubWorkflowDetector $subWorkflowDetector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install entity schemas.
    $this->installEntitySchema('user');
    $this->installEntitySchema('flowdrop_pipeline');
    $this->installEntitySchema('flowdrop_job');

    // Install config.
    $this->installConfig(['flowdrop', 'flowdrop_node_type']);

    // Get services.
    $this->iteratorExecutor = $this->container->get('flowdrop_iterator.iterator_executor');
    $this->subWorkflowDetector = $this->container->get('flowdrop_iterator.sub_workflow_detector');
  }

  /**
   * Test iterator execution creates child pipeline.
   *
   * @covers ::execute
   */
  public function testIteratorCreatesChildPipeline(): void {
    // Create a parent pipeline first.
    $parentPipeline = FlowDropPipeline::create([
      'workflow' => 'test_workflow',
      'status' => 'running',
    ]);
    $parentPipeline->save();

    // Create workflow with iterator and sub-workflow.
    $workflow = $this->createTestWorkflow();

    // Mock the node runtime to return simple results.
    $this->mockNodeRuntime();

    // Execute iterator.
    $output = $this->iteratorExecutor->execute(
      executionId: 'exec_test_1',
      iteratorNodeId: 'iterator_1',
      inputData: ['data' => ['item1', 'item2', 'item3']],
      config: ['maxIterations' => 100, 'onError' => 'fail'],
      workflow: $workflow,
      parentPipelineId: $parentPipeline->id(),
    );

    // Verify output.
    $this->assertTrue($output->get('isComplete'));
    $this->assertSame(3, $output->get('total'));

    // Verify child pipeline was created.
    $childPipelineId = $output->get('_childPipelineId');
    $this->assertNotNull($childPipelineId);

    $childPipeline = FlowDropPipeline::load($childPipelineId);
    $this->assertNotNull($childPipeline);
    $this->assertSame('test_workflow', $childPipeline->getWorkflow());

    // Check metadata.
    $metadata = $childPipeline->getMetadata();
    $this->assertTrue($metadata['is_child_pipeline'] ?? FALSE);
    $this->assertSame($parentPipeline->id(), $metadata['parent_pipeline_id'] ?? '');
    $this->assertSame('iterator_1', $metadata['iterator_node_id'] ?? '');
  }

  /**
   * Test iterator execution creates jobs for each iteration.
   *
   * @covers ::execute
   */
  public function testIteratorCreatesJobsPerIteration(): void {
    $parentPipeline = FlowDropPipeline::create([
      'workflow' => 'test_workflow',
      'status' => 'running',
    ]);
    $parentPipeline->save();

    $workflow = $this->createTestWorkflow();
    $this->mockNodeRuntime();

    $output = $this->iteratorExecutor->execute(
      executionId: 'exec_test_2',
      iteratorNodeId: 'iterator_1',
      inputData: ['data' => ['a', 'b']],
      config: [],
      workflow: $workflow,
      parentPipelineId: $parentPipeline->id(),
    );

    $childPipelineId = $output->get('_childPipelineId');
    $childPipeline = FlowDropPipeline::load($childPipelineId);

    // Get jobs for child pipeline.
    $jobStorage = $this->container->get('entity_type.manager')->getStorage('flowdrop_job');
    $jobIds = $jobStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('pipeline', $childPipelineId)
      ->execute();

    // Should have 2 items * 1 sub-workflow node = 2 jobs.
    $this->assertCount(2, $jobIds);

    // Verify job metadata.
    $jobs = FlowDropJob::loadMultiple($jobIds);
    foreach ($jobs as $job) {
      $metadata = $job->getMetadata();
      $this->assertArrayHasKey('iteration_index', $metadata);
      $this->assertArrayHasKey('node_type_id', $metadata);
    }
  }

  /**
   * Test iterator with empty input returns immediately.
   *
   * @covers ::execute
   */
  public function testIteratorEmptyInput(): void {
    $parentPipeline = FlowDropPipeline::create([
      'workflow' => 'test_workflow',
      'status' => 'running',
    ]);
    $parentPipeline->save();

    $workflow = $this->createTestWorkflow();

    $output = $this->iteratorExecutor->execute(
      executionId: 'exec_test_3',
      iteratorNodeId: 'iterator_1',
      inputData: ['data' => []],
      config: [],
      workflow: $workflow,
      parentPipelineId: $parentPipeline->id(),
    );

    $this->assertTrue($output->get('isComplete'));
    $this->assertSame(0, $output->get('total'));
    $this->assertSame([], $output->get('done'));

    // No child pipeline should be created for empty input.
    // (Check by counting pipelines - should only have the parent).
    $pipelineStorage = $this->container->get('entity_type.manager')->getStorage('flowdrop_pipeline');
    $pipelineCount = $pipelineStorage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $this->assertSame(1, (int) $pipelineCount);
  }

  /**
   * Test iterator respects max iterations limit.
   *
   * @covers ::execute
   */
  public function testIteratorMaxIterations(): void {
    $parentPipeline = FlowDropPipeline::create([
      'workflow' => 'test_workflow',
      'status' => 'running',
    ]);
    $parentPipeline->save();

    $workflow = $this->createTestWorkflow();
    $this->mockNodeRuntime();

    // Create array with 10 items but set max to 3.
    $items = range(1, 10);

    $output = $this->iteratorExecutor->execute(
      executionId: 'exec_test_4',
      iteratorNodeId: 'iterator_1',
      inputData: ['data' => $items],
      config: ['maxIterations' => 3],
      workflow: $workflow,
      parentPipelineId: $parentPipeline->id(),
    );

    // Should only process 3 items.
    $done = $output->get('done');
    $this->assertCount(3, $done);
  }

  /**
   * Test sub-workflow detector with real workflow.
   *
   * @covers \Drupal\flowdrop_iterator\Service\SubWorkflowDetector::detect
   */
  public function testSubWorkflowDetection(): void {
    $workflow = $this->createTestWorkflow();

    $result = $this->subWorkflowDetector->detect($workflow, 'iterator_1');

    $this->assertEmpty($result['errors']);
    $this->assertSame('node_a', $result['loopbackSourceNodeId']);
    $this->assertContains('node_a', $result['subWorkflowNodes']);
    $this->assertSame(['node_a'], $result['executionOrder']);
  }

  /**
   * Test sub-workflow detector validates successfully.
   *
   * @covers \Drupal\flowdrop_iterator\Service\SubWorkflowDetector::validate
   */
  public function testSubWorkflowValidation(): void {
    $workflow = $this->createTestWorkflow();

    $result = $this->subWorkflowDetector->detect($workflow, 'iterator_1');

    // Should not throw exception.
    $this->subWorkflowDetector->validate($result, 'iterator_1');

    $this->assertTrue(TRUE);
  }

  /**
   * Test child pipeline status reflects iteration outcome.
   *
   * @covers ::execute
   */
  public function testChildPipelineStatusOnSuccess(): void {
    $parentPipeline = FlowDropPipeline::create([
      'workflow' => 'test_workflow',
      'status' => 'running',
    ]);
    $parentPipeline->save();

    $workflow = $this->createTestWorkflow();
    $this->mockNodeRuntime();

    $output = $this->iteratorExecutor->execute(
      executionId: 'exec_test_5',
      iteratorNodeId: 'iterator_1',
      inputData: ['data' => ['x', 'y']],
      config: [],
      workflow: $workflow,
      parentPipelineId: $parentPipeline->id(),
    );

    $childPipelineId = $output->get('_childPipelineId');
    $childPipeline = FlowDropPipeline::load($childPipelineId);

    // Pipeline should be marked as completed.
    $this->assertSame('completed', $childPipeline->getStatus());
  }

  /**
   * Create a test workflow with iterator and sub-workflow.
   *
   * @return \Drupal\flowdrop_workflow\DTO\WorkflowDTO
   *   The workflow DTO.
   */
  protected function createTestWorkflow(): WorkflowDTO {
    $nodes = [
      'iterator_1' => new WorkflowNodeDTO(
        id: 'iterator_1',
        typeId: 'iterator',
        label: 'Test Iterator',
        config: [],
        position: ['x' => 0, 'y' => 0],
      ),
      'node_a' => new WorkflowNodeDTO(
        id: 'node_a',
        typeId: 'text_output',
        label: 'Process Node',
        config: [],
        position: ['x' => 200, 'y' => 0],
      ),
    ];

    $edges = [
      new WorkflowEdgeDTO(
        id: 'e1',
        source: 'iterator_1',
        target: 'node_a',
        sourceHandle: 'iterator_1-output-item',
        targetHandle: 'node_a-input-data',
      ),
      new WorkflowEdgeDTO(
        id: 'e2',
        source: 'node_a',
        target: 'iterator_1',
        sourceHandle: 'node_a-output-data',
        targetHandle: 'iterator_1-input-loopback',
        metadata: ['edgeType' => 'loopback'],
      ),
    ];

    return new WorkflowDTO(
      id: 'test_workflow',
      label: 'Test Workflow',
      nodes: $nodes,
      edges: $edges,
    );
  }

  /**
   * Mock the node runtime service to return simple results.
   */
  protected function mockNodeRuntime(): void {
    // Create a mock that returns successful output.
    $mockNodeRuntime = $this->getMockBuilder('Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService')
      ->disableOriginalConstructor()
      ->getMock();

    $mockNodeRuntime->method('executeNode')
      ->willReturnCallback(function ($executionId, $nodeId, $nodeType, $inputs, $config, $context) {
        $output = new Output();
        $output->fromArray([
          'processed' => TRUE,
          'nodeId' => $nodeId,
          'input' => $inputs->toArray(),
        ]);
        $output->setStatus('success');

        $result = new NodeExecutionResult(
          nodeId: $nodeId,
          output: $output,
          status: 'success',
          executionTime: 0.01,
        );

        return $result;
      });

    // Replace the service in the container.
    $this->container->set('flowdrop_runtime.node_runtime', $mockNodeRuntime);

    // Recreate the iterator executor with the mocked service.
    $this->iteratorExecutor = new IteratorExecutor(
      $this->container->get('entity_type.manager'),
      $mockNodeRuntime,
      $this->container->get('flowdrop_runtime.execution_context'),
      $this->container->get('logger.factory'),
      $this->container->get('event_dispatcher'),
      $this->container->get('flowdrop_iterator.sub_workflow_detector'),
    );
  }

}
