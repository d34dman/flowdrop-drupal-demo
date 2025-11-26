<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_runtime\Unit\Service\Compiler;

use Psr\Log\LoggerInterface;
use Drupal\flowdrop_runtime\Service\Compiler\WorkflowCompiler;
use Drupal\flowdrop_runtime\Exception\CompilationException;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Tests the WorkflowCompiler service.
 *
 * @group flowdrop_runtime
 */
class WorkflowCompilerTest extends UnitTestCase {

  /**
   * Test successful workflow compilation.
   */
  public function testSuccessfulCompilation(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
      'nodes' => [
        [
          'id' => 'node1',
          'type' => 'text_input',
          'config' => ['text' => 'Hello'],
        ],
        [
          'id' => 'node2',
          'type' => 'text_processor',
          'config' => ['operation' => 'uppercase'],
        ],
      ],
      'connections' => [
        [
          'source' => 'node1',
          'target' => 'node2',
        ],
      ],
    ];

    $compiledWorkflow = $compiler->compile($workflow);

    $this->assertEquals('test_workflow', $compiledWorkflow->getWorkflowId());
    $this->assertCount(2, $compiledWorkflow->getNodeMappings());
    $this->assertCount(2, $compiledWorkflow->getExecutionPlan()->getExecutionOrder());
  }

  /**
   * Test compilation with missing workflow ID.
   */
  public function testCompilationWithMissingWorkflowId(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'nodes' => [
        [
          'id' => 'node1',
          'type' => 'text_input',
        ],
      ],
    ];

    $this->expectException(CompilationException::class);
    $this->expectExceptionMessage('Workflow must have an ID');
    $compiler->compile($workflow);
  }

  /**
   * Test compilation with missing nodes.
   */
  public function testCompilationWithMissingNodes(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
    ];

    $this->expectException(CompilationException::class);
    $this->expectExceptionMessage('Workflow must have nodes array');
    $compiler->compile($workflow);
  }

  /**
   * Test compilation with node missing ID.
   */
  public function testCompilationWithNodeMissingId(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
      'nodes' => [
        [
          'type' => 'text_input',
        ],
      ],
    ];

    $this->expectException(CompilationException::class);
    $this->expectExceptionMessage('All nodes must have an ID');
    $compiler->compile($workflow);
  }

  /**
   * Test compilation with node missing type.
   */
  public function testCompilationWithNodeMissingType(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
      'nodes' => [
        [
          'id' => 'node1',
        ],
      ],
    ];

    $this->expectException(CompilationException::class);
    $this->expectExceptionMessage('Node node1 must have a type');
    $compiler->compile($workflow);
  }

  /**
   * Test compilation with circular dependencies.
   */
  public function testCompilationWithCircularDependencies(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
      'nodes' => [
        [
          'id' => 'node1',
          'type' => 'text_input',
        ],
        [
          'id' => 'node2',
          'type' => 'text_processor',
        ],
      ],
      'connections' => [
        [
          'source' => 'node1',
          'target' => 'node2',
        ],
        [
          'source' => 'node2',
          'target' => 'node1',
        ],
      ],
    ];

    $this->expectException(CompilationException::class);
    $this->expectExceptionMessage('Circular dependency detected in workflow');
    $compiler->compile($workflow);
  }

  /**
   * Test execution plan generation.
   */
  public function testExecutionPlanGeneration(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
      'nodes' => [
        [
          'id' => 'node1',
          'type' => 'text_input',
        ],
        [
          'id' => 'node2',
          'type' => 'text_processor',
        ],
        [
          'id' => 'node3',
          'type' => 'text_output',
        ],
      ],
      'connections' => [
        [
          'source' => 'node1',
          'target' => 'node2',
        ],
        [
          'source' => 'node2',
          'target' => 'node3',
        ],
      ],
    ];

    $compiledWorkflow = $compiler->compile($workflow);
    $executionPlan = $compiledWorkflow->getExecutionPlan();

    // Check execution order.
    $this->assertEquals(['node1', 'node2', 'node3'], $executionPlan->getExecutionOrder());

    // Check that node1 is a root node.
    $this->assertTrue($executionPlan->isRootNode('node1'));

    // Check that node3 is a leaf node.
    $this->assertTrue($executionPlan->isLeafNode('node3'));

    // Check dependencies.
    $this->assertEquals(['node1'], $executionPlan->getNodeDependencies('node2'));
    $this->assertEquals(['node2'], $executionPlan->getNodeDependencies('node3'));

    // Check dependents.
    $this->assertEquals(['node2'], $executionPlan->getNodeDependents('node1'));
    $this->assertEquals(['node3'], $executionPlan->getNodeDependents('node2'));
  }

  /**
   * Test node mapping generation.
   */
  public function testNodeMappingGeneration(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
      'nodes' => [
        [
          'id' => 'node1',
          'type' => 'text_input',
          'config' => ['text' => 'Hello'],
          'label' => 'Input Node',
          'description' => 'Input text node',
        ],
      ],
    ];

    $compiledWorkflow = $compiler->compile($workflow);
    $nodeMapping = $compiledWorkflow->getNodeMapping('node1');

    $this->assertNotNull($nodeMapping);
    $this->assertEquals('node1', $nodeMapping->getNodeId());
    $this->assertEquals('text_input', $nodeMapping->getProcessorId());
    $this->assertEquals(['text' => 'Hello'], $nodeMapping->getConfig());
    $this->assertEquals('Input Node', $nodeMapping->getLabel());
    $this->assertEquals('Input text node', $nodeMapping->getDescription());
  }

  /**
   * Test compiled workflow validation.
   */
  public function testCompiledWorkflowValidation(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $compiler = new WorkflowCompiler($loggerFactory);

    $workflow = [
      'id' => 'test_workflow',
      'nodes' => [
        [
          'id' => 'node1',
          'type' => 'text_input',
        ],
        [
          'id' => 'node2',
          'type' => 'text_processor',
        ],
      ],
      'connections' => [
        [
          'source' => 'node1',
          'target' => 'node2',
        ],
      ],
    ];

    $compiledWorkflow = $compiler->compile($workflow);
    $this->assertTrue($compiler->validateCompiledWorkflow($compiledWorkflow));
  }

}
