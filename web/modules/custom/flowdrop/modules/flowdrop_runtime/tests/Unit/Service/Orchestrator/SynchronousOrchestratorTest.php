<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_runtime\Unit\Service\Orchestrator;

use Drupal\Tests\UnitTestCase;
use Drupal\flowdrop_runtime\Service\Orchestrator\SynchronousOrchestrator;
use Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService;
use Drupal\flowdrop_runtime\Service\Runtime\ExecutionContext;
use Drupal\flowdrop_runtime\Service\RealTime\RealTimeManager;
use Drupal\flowdrop_runtime\Service\Compiler\WorkflowCompiler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the SynchronousOrchestrator service.
 *
 * @coversDefaultClass \Drupal\flowdrop_runtime\Service\Orchestrator\SynchronousOrchestrator
 * @group flowdrop_runtime
 */
class SynchronousOrchestratorTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The orchestrator under test.
   *
   * @var \Drupal\flowdrop_runtime\Service\Orchestrator\SynchronousOrchestrator
   */
  private SynchronousOrchestrator $orchestrator;

  /**
   * Mock services.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService>
   */
  private $nodeRuntime;

  /**
   * Mock execution context service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\flowdrop_runtime\Service\Runtime\ExecutionContext>
   */
  private $executionContext;

  /**
   * Mock real-time manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\flowdrop_runtime\Service\RealTime\RealTimeManager>
   */
  private $realTimeManager;

  /**
   * Mock workflow compiler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\flowdrop_runtime\Service\Compiler\WorkflowCompiler>
   */
  private $workflowCompiler;

  /**
   * Mock logger factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Logger\LoggerChannelFactoryInterface>
   */
  private $loggerFactory;

  /**
   * Mock logger service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Logger\LoggerChannelInterface>
   */
  private $logger;

  /**
   * Mock event dispatcher service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\EventDispatcher\EventDispatcherInterface>
   */
  private $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mock services.
    $this->nodeRuntime = $this->prophesize(NodeRuntimeService::class);
    $this->executionContext = $this->prophesize(ExecutionContext::class);
    $this->realTimeManager = $this->prophesize(RealTimeManager::class);
    $this->workflowCompiler = $this->prophesize(WorkflowCompiler::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

    // Setup logger factory.
    // PHPStan doesn't recognize LoggerChannelFactoryInterface::get() method.
    // @phpstan-ignore-next-line
    $this->loggerFactory->get('flowdrop_runtime')->willReturn($this->logger->reveal());

    // Create the orchestrator.
    $this->orchestrator = new SynchronousOrchestrator(
      $this->nodeRuntime->reveal(),
      $this->executionContext->reveal(),
      $this->realTimeManager->reveal(),
      $this->workflowCompiler->reveal(),
      $this->loggerFactory->reveal(),
      $this->eventDispatcher->reveal()
    );
  }

  /**
   * @covers ::getType
   */
  public function testGetType(): void {
    $this->assertEquals('synchronous', $this->orchestrator->getType());
  }

  /**
   * @covers ::supportsWorkflow
   */
  public function testSupportsWorkflow(): void {
    $workflow = ['id' => 'test_workflow'];
    $this->assertTrue($this->orchestrator->supportsWorkflow($workflow));
  }

  /**
   * @covers ::getCapabilities
   */
  public function testGetCapabilities(): void {
    $capabilities = $this->orchestrator->getCapabilities();

    $this->assertArrayHasKey('synchronous_execution', $capabilities);
    $this->assertArrayHasKey('parallel_execution', $capabilities);
    $this->assertArrayHasKey('async_execution', $capabilities);
    $this->assertArrayHasKey('retry_support', $capabilities);
    $this->assertArrayHasKey('error_recovery', $capabilities);
    $this->assertArrayHasKey('workflow_compilation', $capabilities);

    $this->assertTrue($capabilities['synchronous_execution']);
    $this->assertFalse($capabilities['parallel_execution']);
    $this->assertFalse($capabilities['async_execution']);
    $this->assertTrue($capabilities['retry_support']);
    $this->assertTrue($capabilities['error_recovery']);
    $this->assertTrue($capabilities['workflow_compilation']);
  }

}
