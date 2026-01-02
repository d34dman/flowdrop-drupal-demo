<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException;
use Drupal\flowdrop_iterator\Service\SubWorkflowDetector;
use Drupal\flowdrop_workflow\DTO\WorkflowDTO;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the SubWorkflowDetector service.
 *
 * @coversDefaultClass \Drupal\flowdrop_iterator\Service\SubWorkflowDetector
 * @group flowdrop_iterator
 */
class SubWorkflowDetectorTest extends TestCase {

  /**
   * The mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The mock logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The detector under test.
   *
   * @var \Drupal\flowdrop_iterator\Service\SubWorkflowDetector
   */
  protected SubWorkflowDetector $detector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method("get")
      ->willReturn($this->logger);

    $this->detector = new SubWorkflowDetector($this->loggerFactory);
  }

  /**
   * Test detecting simple linear sub-workflow.
   *
   * Iterator -> NodeA -> NodeB -> Iterator (loopback)
   *
   * @covers ::detect
   */
  public function testDetectSimpleLinearSubWorkflow(): void {
    $workflow = $this->createWorkflowMock(
      nodes: [
        "iterator_1" => $this->createNodeMock("iterator_1", "iterator"),
        "node_a" => $this->createNodeMock("node_a", "prompt_template"),
        "node_b" => $this->createNodeMock("node_b", "text_output"),
      ],
      edges: [
        // Iterator item output to NodeA.
        $this->createEdgeMock("e1", "iterator_1", "node_a", "iterator_1-output-item", "node_a-input-data"),
        // NodeA to NodeB.
        $this->createEdgeMock("e2", "node_a", "node_b", "node_a-output-data", "node_b-input-data"),
        // NodeB back to Iterator loopback.
        $this->createEdgeMock("e3", "node_b", "iterator_1", "node_b-output-data", "iterator_1-input-loopback", "loopback"),
      ]
    );

    $result = $this->detector->detect($workflow, "iterator_1");

    $this->assertEmpty($result["errors"]);
    $this->assertSame("node_b", $result["loopbackSourceNodeId"]);
    $this->assertContains("node_a", $result["subWorkflowNodes"]);
    $this->assertContains("node_b", $result["subWorkflowNodes"]);
    $this->assertCount(2, $result["subWorkflowNodes"]);

    // Execution order should be node_a then node_b.
    $this->assertSame(["node_a", "node_b"], $result["executionOrder"]);
  }

  /**
   * Test detecting sub-workflow with single node.
   *
   * Iterator -> NodeA -> Iterator (loopback)
   *
   * @covers ::detect
   */
  public function testDetectSingleNodeSubWorkflow(): void {
    $workflow = $this->createWorkflowMock(
      nodes: [
        "iterator_1" => $this->createNodeMock("iterator_1", "iterator"),
        "node_a" => $this->createNodeMock("node_a", "processor"),
      ],
      edges: [
        $this->createEdgeMock("e1", "iterator_1", "node_a", "iterator_1-output-item", "node_a-input-data"),
        $this->createEdgeMock("e2", "node_a", "iterator_1", "node_a-output-data", "iterator_1-input-loopback", "loopback"),
      ]
    );

    $result = $this->detector->detect($workflow, "iterator_1");

    $this->assertEmpty($result["errors"]);
    $this->assertSame("node_a", $result["loopbackSourceNodeId"]);
    $this->assertSame(["node_a"], $result["subWorkflowNodes"]);
    $this->assertSame(["node_a"], $result["executionOrder"]);
  }

  /**
   * Test detecting sub-workflow with branching.
   *
   * Iterator -> NodeA -> NodeB
   *                  \-> NodeC -> Iterator (loopback via NodeC)
   *
   * @covers ::detect
   */
  public function testDetectBranchingSubWorkflow(): void {
    $workflow = $this->createWorkflowMock(
      nodes: [
        "iterator_1" => $this->createNodeMock("iterator_1", "iterator"),
        "node_a" => $this->createNodeMock("node_a", "processor"),
        "node_b" => $this->createNodeMock("node_b", "output"),
        "node_c" => $this->createNodeMock("node_c", "aggregator"),
      ],
      edges: [
        // Iterator to NodeA.
        $this->createEdgeMock("e1", "iterator_1", "node_a", "iterator_1-output-item", "node_a-input-data"),
        // NodeA to NodeB (branch).
        $this->createEdgeMock("e2", "node_a", "node_b", "node_a-output-data", "node_b-input-data"),
        // NodeA to NodeC.
        $this->createEdgeMock("e3", "node_a", "node_c", "node_a-output-alt", "node_c-input-data"),
        // NodeC back to Iterator.
        $this->createEdgeMock("e4", "node_c", "iterator_1", "node_c-output-data", "iterator_1-input-loopback", "loopback"),
      ]
    );

    $result = $this->detector->detect($workflow, "iterator_1");

    $this->assertEmpty($result["errors"]);
    $this->assertSame("node_c", $result["loopbackSourceNodeId"]);
    // Should include node_a, node_b, node_c (all reachable from item port).
    $this->assertContains("node_a", $result["subWorkflowNodes"]);
    $this->assertContains("node_b", $result["subWorkflowNodes"]);
    $this->assertContains("node_c", $result["subWorkflowNodes"]);
  }

  /**
   * Test detection fails when no item port connections.
   *
   * @covers ::detect
   */
  public function testDetectNoItemPortConnections(): void {
    $workflow = $this->createWorkflowMock(
      nodes: [
        "iterator_1" => $this->createNodeMock("iterator_1", "iterator"),
      ],
      edges: []
    );

    // Mock getOutgoingEdges to return empty array.
    $workflow->method("getOutgoingEdges")
      ->willReturn([]);

    $result = $this->detector->detect($workflow, "iterator_1");

    $this->assertNotEmpty($result["errors"]);
    $this->assertStringContainsString("item", $result["errors"][0]);
  }

  /**
   * Test detection fails when no loopback edge.
   *
   * @covers ::detect
   */
  public function testDetectNoLoopbackEdge(): void {
    $itemEdge = $this->createEdgeMock("e1", "iterator_1", "node_a", "iterator_1-output-item", "node_a-input-data");

    $workflow = $this->createWorkflowMock(
      nodes: [
        "iterator_1" => $this->createNodeMock("iterator_1", "iterator"),
        "node_a" => $this->createNodeMock("node_a", "processor"),
      ],
      edges: [$itemEdge]
    );

    // No incoming edges with loopback.
    $workflow->method("getIncomingEdges")
      ->willReturn([]);

    $result = $this->detector->detect($workflow, "iterator_1");

    $this->assertNotEmpty($result["errors"]);
    $this->assertStringContainsString("loopback", $result["errors"][0]);
    $this->assertNull($result["loopbackSourceNodeId"]);
  }

  /**
   * Test validate throws exception on errors.
   *
   * @covers ::validate
   */
  public function testValidateThrowsOnErrors(): void {
    $detectionResult = [
      "subWorkflowNodes" => [],
      "executionOrder" => [],
      "loopbackSourceNodeId" => NULL,
      "errors" => ["Error 1", "Error 2"],
    ];

    $this->expectException(SubWorkflowDetectionException::class);
    $this->expectExceptionMessage("Error 1; Error 2");

    $this->detector->validate($detectionResult, "iterator_1");
  }

  /**
   * Test validate throws exception on empty sub-workflow.
   *
   * @covers ::validate
   */
  public function testValidateThrowsOnEmptySubWorkflow(): void {
    $detectionResult = [
      "subWorkflowNodes" => [],
      "executionOrder" => [],
      "loopbackSourceNodeId" => "node_x",
      "errors" => [],
    ];

    $this->expectException(SubWorkflowDetectionException::class);
    $this->expectExceptionMessage("no nodes");

    $this->detector->validate($detectionResult, "iterator_1");
  }

  /**
   * Test validate throws exception on missing loopback.
   *
   * @covers ::validate
   */
  public function testValidateThrowsOnMissingLoopback(): void {
    $detectionResult = [
      "subWorkflowNodes" => ["node_a"],
      "executionOrder" => ["node_a"],
      "loopbackSourceNodeId" => NULL,
      "errors" => [],
    ];

    $this->expectException(SubWorkflowDetectionException::class);
    $this->expectExceptionMessage("loopback");

    $this->detector->validate($detectionResult, "iterator_1");
  }

  /**
   * Test validate passes with valid detection result.
   *
   * @covers ::validate
   */
  public function testValidatePasses(): void {
    $detectionResult = [
      "subWorkflowNodes" => ["node_a", "node_b"],
      "executionOrder" => ["node_a", "node_b"],
      "loopbackSourceNodeId" => "node_b",
      "errors" => [],
    ];

    // Should not throw.
    $this->detector->validate($detectionResult, "iterator_1");
    $this->assertTrue(TRUE);
  }

  /**
   * Create a mock WorkflowDTO.
   *
   * @param array<string, object> $nodes
   *   Nodes keyed by ID.
   * @param array<object> $edges
   *   Array of edge mocks.
   *
   * @return \Drupal\flowdrop_workflow\DTO\WorkflowDTO|\PHPUnit\Framework\MockObject\MockObject
   *   The mock workflow.
   */
  private function createWorkflowMock(array $nodes, array $edges) {
    $workflow = $this->createMock(WorkflowDTO::class);

    $workflow->method("getNodes")->willReturn($nodes);
    $workflow->method("getNode")->willReturnCallback(
      fn($id) => $nodes[$id] ?? NULL
    );

    // Set up edge retrieval.
    $workflow->method("getOutgoingEdges")->willReturnCallback(
      function ($nodeId) use ($edges) {
        return array_filter($edges, fn($e) => $e->getSource() === $nodeId);
      }
    );

    $workflow->method("getIncomingEdges")->willReturnCallback(
      function ($nodeId) use ($edges) {
        return array_filter($edges, fn($e) => $e->getTarget() === $nodeId);
      }
    );

    return $workflow;
  }

  /**
   * Create a mock node.
   *
   * @param string $id
   *   Node ID.
   * @param string $typeId
   *   Node type ID.
   *
   * @return object
   *   Mock node object.
   */
  private function createNodeMock(string $id, string $typeId): object {
    $node = new \stdClass();
    $node->id = $id;
    $node->typeId = $typeId;

    return $node;
  }

  /**
   * Create a mock edge.
   *
   * @param string $id
   *   Edge ID.
   * @param string $source
   *   Source node ID.
   * @param string $target
   *   Target node ID.
   * @param string $sourceHandle
   *   Source handle.
   * @param string $targetHandle
   *   Target handle.
   * @param string $edgeType
   *   Edge type (default 'data').
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   Mock edge object.
   */
  private function createEdgeMock(
    string $id,
    string $source,
    string $target,
    string $sourceHandle = "",
    string $targetHandle = "",
    string $edgeType = "data",
  ): object {
    $edge = $this->getMockBuilder(\stdClass::class)
      ->addMethods([
        "getId",
        "getSource",
        "getTarget",
        "getSourceHandle",
        "getTargetHandle",
        "getMetadata",
        "getSourceOutputName",
        "getTargetInputName",
      ])
      ->getMock();

    $edge->method("getId")->willReturn($id);
    $edge->method("getSource")->willReturn($source);
    $edge->method("getTarget")->willReturn($target);
    $edge->method("getSourceHandle")->willReturn($sourceHandle);
    $edge->method("getTargetHandle")->willReturn($targetHandle);
    $edge->method("getMetadata")->willReturn(["edgeType" => $edgeType]);

    // Extract port names from handles.
    $sourceOutputName = "";
    if (preg_match("/-output-(.+)$/", $sourceHandle, $matches)) {
      $sourceOutputName = $matches[1];
    }
    $targetInputName = "";
    if (preg_match("/-input-(.+)$/", $targetHandle, $matches)) {
      $targetInputName = $matches[1];
    }

    $edge->method("getSourceOutputName")->willReturn($sourceOutputName);
    $edge->method("getTargetInputName")->willReturn($targetInputName);

    return $edge;
  }

}
