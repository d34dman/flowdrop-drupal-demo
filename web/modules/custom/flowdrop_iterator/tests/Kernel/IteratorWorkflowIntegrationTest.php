<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Kernel;

use Drupal\flowdrop_pipeline\Entity\FlowDropPipeline;
use Drupal\flowdrop_workflow\Entity\FlowDropWorkflow;
use Drupal\flowdrop_runtime\Service\Orchestrator\SynchronousOrchestrator;
use Drupal\flowdrop_runtime\DTO\Orchestrator\OrchestrationRequest;
use Drupal\KernelTests\KernelTestBase;

/**
 * End-to-end integration tests for Iterator workflow execution.
 *
 * Tests the complete flow from workflow definition through orchestration
 * to final output, including:
 * - Workflow compilation with iterator nodes
 * - Special edge handling (loopback edges)
 * - Sub-workflow detection and execution
 * - Child pipeline creation and management
 * - Result aggregation.
 *
 * @group flowdrop_iterator
 */
class IteratorWorkflowIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'flowdrop',
    'flowdrop_node_type',
    'flowdrop_node_processor',
    'flowdrop_workflow',
    'flowdrop_pipeline',
    'flowdrop_job',
    'flowdrop_runtime',
    'flowdrop_iterator',
  ];

  /**
   * The synchronous orchestrator.
   *
   * @var \Drupal\flowdrop_runtime\Service\Orchestrator\SynchronousOrchestrator
   */
  protected SynchronousOrchestrator $orchestrator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install schemas.
    $this->installEntitySchema('user');
    $this->installEntitySchema('flowdrop_workflow');
    $this->installEntitySchema('flowdrop_pipeline');
    $this->installEntitySchema('flowdrop_job');

    // Install configs.
    $this->installConfig([
      'flowdrop',
      'flowdrop_node_type',
      'flowdrop_iterator',
    ]);

    // Get orchestrator.
    $this->orchestrator = $this->container->get('flowdrop_runtime.orchestrator.synchronous');
  }

  /**
   * Test complete iterator workflow execution.
   *
   * Creates a workflow with:
   * - Input node providing list of items
   * - Iterator node processing the list
   * - Process node (in sub-workflow) handling each item
   * - Output node receiving aggregated results.
   */
  public function testCompleteIteratorWorkflow(): void {
    // Create workflow entity.
    $workflow = FlowDropWorkflow::create([
      'id' => 'iterator_test_workflow',
      'label' => 'Iterator Test Workflow',
      'status' => 1,
    ]);

    $workflowData = $this->createIteratorWorkflowData();
    $workflow->setWorkflowData($workflowData);
    $workflow->save();

    // Create pipeline.
    $pipeline = FlowDropPipeline::create([
      'workflow' => $workflow->id(),
      'status' => 'pending',
    ]);
    $pipeline->save();

    // Create orchestration request.
    $request = new OrchestrationRequest(
      workflowId: $workflow->id(),
      pipelineId: $pipeline->id(),
      workflow: $workflowData,
      initialData: [
        'items' => ['apple', 'banana', 'cherry'],
      ],
      options: [],
    );

    // Execute.
    $response = $this->orchestrator->orchestrate($request);

    // Verify completion.
    $this->assertSame('completed', $response->getStatus());

    // Verify results contain iterator output.
    $results = $response->getResults();
    $this->assertArrayHasKey('iterator_1', $results);

    $iteratorResult = $results['iterator_1'];
    $this->assertTrue($iteratorResult['isComplete'] ?? FALSE);
  }

  /**
   * Test iterator with empty input skips to done.
   */
  public function testIteratorEmptyInputSkipsToDone(): void {
    $workflow = FlowDropWorkflow::create([
      'id' => 'empty_iterator_workflow',
      'label' => 'Empty Iterator Test',
      'status' => 1,
    ]);

    $workflow->setWorkflowData($this->createIteratorWorkflowData());
    $workflow->save();

    $pipeline = FlowDropPipeline::create([
      'workflow' => $workflow->id(),
      'status' => 'pending',
    ]);
    $pipeline->save();

    $request = new OrchestrationRequest(
      workflowId: $workflow->id(),
      pipelineId: $pipeline->id(),
      workflow: $workflow->getWorkflowData(),
      initialData: [
    // Empty input.
        'items' => [],
      ],
      options: [],
    );

    $response = $this->orchestrator->orchestrate($request);

    $this->assertSame('completed', $response->getStatus());

    $iteratorResult = $response->getResults()['iterator_1'] ?? [];
    $this->assertTrue($iteratorResult['isComplete'] ?? FALSE);
    $this->assertSame(0, $iteratorResult['total'] ?? -1);
  }

  /**
   * Test iterator respects maxIterations config.
   */
  public function testIteratorMaxIterationsLimit(): void {
    $workflow = FlowDropWorkflow::create([
      'id' => 'max_iter_workflow',
      'label' => 'Max Iterations Test',
      'status' => 1,
    ]);

    $workflowData = $this->createIteratorWorkflowData([
      'iterator_config' => [
        'maxIterations' => 5,
      ],
    ]);
    $workflow->setWorkflowData($workflowData);
    $workflow->save();

    $pipeline = FlowDropPipeline::create([
      'workflow' => $workflow->id(),
      'status' => 'pending',
    ]);
    $pipeline->save();

    // Provide more items than maxIterations.
    $request = new OrchestrationRequest(
      workflowId: $workflow->id(),
      pipelineId: $pipeline->id(),
      workflow: $workflowData,
      initialData: [
    // 20 items, but max is 5.
        'items' => range(1, 20),
      ],
      options: [],
    );

    $response = $this->orchestrator->orchestrate($request);

    // Verify only 5 iterations happened.
    $iteratorResult = $response->getResults()['iterator_1'] ?? [];
    $processedCount = count($iteratorResult['done'] ?? []);
    $this->assertLessThanOrEqual(5, $processedCount);
  }

  /**
   * Test workflow compilation excludes loopback edges from cycle detection.
   */
  public function testWorkflowCompilationWithLoopbackEdge(): void {
    $compiler = $this->container->get('flowdrop_runtime.compiler');

    $workflowData = $this->createIteratorWorkflowData();
    $workflowDTO = $this->createWorkflowDto($workflowData);

    // Should not throw CompilationException.
    $compiled = $compiler->compile($workflowDTO);

    $this->assertNotNull($compiled);
    $this->assertSame('iterator_test_workflow', $compiled->getWorkflowId());

    // Verify execution graph exists.
    $executionGraph = $compiled->getExecutionGraph();
    $this->assertNotNull($executionGraph);

    // Verify iterator is in execution order.
    $executionOrder = $executionGraph->getExecutionOrder();
    $this->assertContains('iterator_1', $executionOrder);
  }

  /**
   * Test sub-workflow detection.
   */
  public function testSubWorkflowDetection(): void {
    $detector = $this->container->get('flowdrop_iterator.sub_workflow_detector');

    $workflowData = $this->createIteratorWorkflowData();
    $workflowDTO = $this->createWorkflowDto($workflowData);

    $result = $detector->detect($workflowDTO, 'iterator_1');

    $this->assertEmpty($result['errors']);
    $this->assertNotEmpty($result['subWorkflowNodes']);
    $this->assertContains('process_node', $result['subWorkflowNodes']);
    $this->assertSame('process_node', $result['loopbackSourceNodeId']);
  }

  /**
   * Test child pipeline is created for iterator.
   */
  public function testChildPipelineCreation(): void {
    $workflow = FlowDropWorkflow::create([
      'id' => 'child_pipeline_test',
      'label' => 'Child Pipeline Test',
      'status' => 1,
    ]);
    $workflow->setWorkflowData($this->createIteratorWorkflowData());
    $workflow->save();

    $parentPipeline = FlowDropPipeline::create([
      'workflow' => $workflow->id(),
      'status' => 'pending',
    ]);
    $parentPipeline->save();

    $request = new OrchestrationRequest(
      workflowId: $workflow->id(),
      pipelineId: $parentPipeline->id(),
      workflow: $workflow->getWorkflowData(),
      initialData: [
        'items' => ['x', 'y'],
      ],
      options: [],
    );

    $response = $this->orchestrator->orchestrate($request);

    // Check for child pipeline.
    $pipelineStorage = $this->container->get('entity_type.manager')->getStorage('flowdrop_pipeline');
    $childPipelines = $pipelineStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $parentPipeline->id(), '<>')
      ->execute();

    // Should have at least one child pipeline.
    $this->assertNotEmpty($childPipelines);

    // Verify child pipeline metadata.
    $childPipelineId = reset($childPipelines);
    $childPipeline = FlowDropPipeline::load($childPipelineId);
    $metadata = $childPipeline->getMetadata();

    $this->assertTrue($metadata['is_child_pipeline'] ?? FALSE);
    $this->assertSame($parentPipeline->id(), $metadata['parent_pipeline_id'] ?? '');
  }

  /**
   * Test iteration events are dispatched.
   */
  public function testIterationEventsDispatched(): void {
    // Set up event listener.
    $eventsCaptured = [];
    $eventDispatcher = $this->container->get('event_dispatcher');
    $eventDispatcher->addListener(
      'flowdrop.iterator.iteration_completed',
      function ($event) use (&$eventsCaptured) {
        $eventsCaptured[] = $event;
      }
    );

    $workflow = FlowDropWorkflow::create([
      'id' => 'event_test_workflow',
      'label' => 'Event Test',
      'status' => 1,
    ]);
    $workflow->setWorkflowData($this->createIteratorWorkflowData());
    $workflow->save();

    $pipeline = FlowDropPipeline::create([
      'workflow' => $workflow->id(),
      'status' => 'pending',
    ]);
    $pipeline->save();

    $request = new OrchestrationRequest(
      workflowId: $workflow->id(),
      pipelineId: $pipeline->id(),
      workflow: $workflow->getWorkflowData(),
      initialData: [
        'items' => ['a', 'b', 'c'],
      ],
      options: [],
    );

    $this->orchestrator->orchestrate($request);

    // Should have captured 3 iteration events.
    $this->assertCount(3, $eventsCaptured);
  }

  /**
   * Create workflow data for iterator test.
   *
   * @param array<string, mixed> $overrides
   *   Optional overrides for workflow configuration.
   *
   * @return array<string, mixed>
   *   The workflow data array.
   */
  protected function createIteratorWorkflowData(array $overrides = []): array {
    $iteratorConfig = $overrides['iterator_config'] ?? [
      'maxIterations' => 1000,
      'onError' => 'fail',
    ];

    return [
      'id' => 'iterator_test_workflow',
      'label' => 'Iterator Test Workflow',
      'nodes' => [
        [
          'id' => 'input_node',
          'type' => 'text_input',
          'position' => ['x' => 0, 'y' => 0],
          'data' => [
            'label' => 'Input',
            'config' => [],
          ],
        ],
        [
          'id' => 'iterator_1',
          'type' => 'iterator',
          'position' => ['x' => 200, 'y' => 0],
          'data' => [
            'label' => 'Iterator',
            'config' => $iteratorConfig,
          ],
        ],
        [
          'id' => 'process_node',
          'type' => 'text_output',
          'position' => ['x' => 400, 'y' => 0],
          'data' => [
            'label' => 'Process',
            'config' => [],
          ],
        ],
        [
          'id' => 'output_node',
          'type' => 'text_output',
          'position' => ['x' => 200, 'y' => 200],
          'data' => [
            'label' => 'Output',
            'config' => [],
          ],
        ],
      ],
      'edges' => [
        [
          'id' => 'e1',
          'source' => 'input_node',
          'target' => 'iterator_1',
          'sourceHandle' => 'input_node-output-data',
          'targetHandle' => 'iterator_1-input-data',
          'data' => [
            'metadata' => ['edgeType' => 'data'],
          ],
        ],
        [
          'id' => 'e2',
          'source' => 'iterator_1',
          'target' => 'process_node',
          'sourceHandle' => 'iterator_1-output-item',
          'targetHandle' => 'process_node-input-data',
          'data' => [
            'metadata' => ['edgeType' => 'data'],
          ],
        ],
        // Loopback edge - creates cycle but should be excluded from DAG.
        [
          'id' => 'e3',
          'source' => 'process_node',
          'target' => 'iterator_1',
          'sourceHandle' => 'process_node-output-data',
          'targetHandle' => 'iterator_1-input-loopback',
          'data' => [
            'metadata' => ['edgeType' => 'loopback'],
          ],
        ],
        [
          'id' => 'e4',
          'source' => 'iterator_1',
          'target' => 'output_node',
          'sourceHandle' => 'iterator_1-output-done',
          'targetHandle' => 'output_node-input-data',
          'data' => [
            'metadata' => ['edgeType' => 'data'],
          ],
        ],
      ],
    ];
  }

  /**
   * Create WorkflowDTO from array data.
   *
   * @param array<string, mixed> $data
   *   The workflow data array.
   *
   * @return \Drupal\flowdrop_workflow\DTO\WorkflowDTO
   *   The workflow DTO.
   */
  protected function createWorkflowDto(array $data) {
    $dtoFactory = $this->container->get('flowdrop_workflow.dto_factory');
    return $dtoFactory->createFromArray($data);
  }

}
