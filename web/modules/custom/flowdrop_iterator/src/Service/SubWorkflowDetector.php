<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException;
use Drupal\flowdrop_workflow\DTO\WorkflowDTO;
use Psr\Log\LoggerInterface;

/**
 * Detects sub-workflow nodes for Iterator nodes.
 *
 * This service traverses the workflow graph from an Iterator's 'item' output
 * port to find all nodes that form the iteration sub-workflow, stopping at
 * the 'loopback' edge back to the Iterator.
 */
class SubWorkflowDetector {

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * Constructs a SubWorkflowDetector instance.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get("flowdrop_iterator");
  }

  /**
   * Detect sub-workflow nodes for an iterator.
   *
   * Traverses from the Iterator's 'item' output port to find all nodes
   * that are part of the iteration sub-workflow, stopping at the 'loopback'
   * edge back to the Iterator.
   *
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param string $iteratorNodeId
   *   The Iterator node ID.
   *
   * @return array{
   *   subWorkflowNodes: array<string>,
   *   executionOrder: array<string>,
   *   loopbackSourceNodeId: string|null,
   *   errors: array<string>
   *   }
   *   Detection result with sub-workflow node IDs in execution order.
   *
   * @throws \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException
   *   When detection fails with errors.
   */
  public function detect(WorkflowDTO $workflow, string $iteratorNodeId): array {
    $result = [
      "subWorkflowNodes" => [],
      "executionOrder" => [],
      "loopbackSourceNodeId" => NULL,
      "errors" => [],
    ];

    $this->logger->debug("Starting sub-workflow detection for iterator @id", [
      "@id" => $iteratorNodeId,
    ]);

    // Find outgoing edges from iterator's 'item' port.
    $outgoingEdges = $workflow->getOutgoingEdges($iteratorNodeId);
    $itemEdges = array_filter(
      $outgoingEdges,
      fn($edge) => $this->isItemPortEdge($edge)
    );

    if (empty($itemEdges)) {
      $result["errors"][] = "Iterator has no 'item' port connections";
      $this->logger->warning("Iterator @id has no item port connections", [
        "@id" => $iteratorNodeId,
      ]);
      return $result;
    }

    // Find the loopback edge to identify the end of sub-workflow.
    $loopbackEdge = $this->findLoopbackEdge($workflow, $iteratorNodeId);
    if ($loopbackEdge === NULL) {
      $result["errors"][] = "Iterator has no loopback edge";
      $this->logger->warning("Iterator @id has no loopback edge", [
        "@id" => $iteratorNodeId,
      ]);
      return $result;
    }

    $result["loopbackSourceNodeId"] = $loopbackEdge->getSource();

    // BFS traversal from item port to loopback source.
    $visited = [];
    $queue = [];
    $dependencies = [];

    // Initialize queue with nodes connected to item port.
    foreach ($itemEdges as $edge) {
      $targetId = $edge->getTarget();
      if (!empty($targetId)) {
        $queue[] = $targetId;
        $dependencies[$targetId] = [];
      }
    }

    // Traverse graph.
    while (!empty($queue)) {
      $currentNodeId = array_shift($queue);

      if (isset($visited[$currentNodeId])) {
        continue;
      }

      $visited[$currentNodeId] = TRUE;
      $result["subWorkflowNodes"][] = $currentNodeId;

      // If this is the loopback source, don't traverse further.
      if ($currentNodeId === $result["loopbackSourceNodeId"]) {
        continue;
      }

      // Find downstream nodes (excluding loopback edges).
      $nodeOutgoingEdges = $workflow->getOutgoingEdges($currentNodeId);
      foreach ($nodeOutgoingEdges as $edge) {
        // Skip loopback edges.
        if ($this->isLoopbackEdge($edge)) {
          continue;
        }

        $targetId = $edge->getTarget();
        if (empty($targetId) || $targetId === $iteratorNodeId) {
          continue;
        }

        if (!isset($visited[$targetId])) {
          $queue[] = $targetId;
        }

        // Track dependencies for execution order.
        if (!isset($dependencies[$targetId])) {
          $dependencies[$targetId] = [];
        }
        $dependencies[$targetId][] = $currentNodeId;
      }
    }

    // Generate execution order using topological sort.
    $result["executionOrder"] = $this->topologicalSort(
      $result["subWorkflowNodes"],
      $dependencies
    );

    $this->logger->info("Detected sub-workflow for iterator @id: @count nodes", [
      "@id" => $iteratorNodeId,
      "@count" => count($result["executionOrder"]),
    ]);

    $this->logger->debug("Sub-workflow execution order: @nodes", [
      "@nodes" => implode(" -> ", $result["executionOrder"]),
    ]);

    return $result;
  }

  /**
   * Validate a detected sub-workflow.
   *
   * @param array $detectionResult
   *   The detection result to validate. Contains keys:
   *   - subWorkflowNodes: array<string>
   *   - executionOrder: array<string>
   *   - loopbackSourceNodeId: string|null
   *   - errors: array<string>.
   * @param string $iteratorNodeId
   *   The iterator node ID.
   *
   * @throws \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException
   *   When validation fails.
   */
  public function validate(array $detectionResult, string $iteratorNodeId): void {
    if (!empty($detectionResult["errors"])) {
      throw SubWorkflowDetectionException::withErrors(
        $iteratorNodeId,
        $detectionResult["errors"]
      );
    }

    if (empty($detectionResult["subWorkflowNodes"])) {
      throw SubWorkflowDetectionException::emptySubWorkflow($iteratorNodeId);
    }

    if ($detectionResult["loopbackSourceNodeId"] === NULL) {
      throw SubWorkflowDetectionException::noLoopbackEdge($iteratorNodeId);
    }
  }

  /**
   * Check if edge is from 'item' port.
   *
   * @param object $edge
   *   The edge object.
   *
   * @return bool
   *   TRUE if edge is from item port.
   */
  private function isItemPortEdge(object $edge): bool {
    $sourceHandle = $edge->getSourceHandle();

    // Check handle naming convention.
    if (str_contains($sourceHandle, "-output-item")) {
      return TRUE;
    }

    // Check output name method if available.
    if (method_exists($edge, "getSourceOutputName")) {
      return $edge->getSourceOutputName() === "item";
    }

    return FALSE;
  }

  /**
   * Check if edge is a loopback edge.
   *
   * @param object $edge
   *   The edge object.
   *
   * @return bool
   *   TRUE if edge is a loopback edge.
   */
  private function isLoopbackEdge(object $edge): bool {
    // Check metadata for edge type.
    if (method_exists($edge, "getMetadata")) {
      $metadata = $edge->getMetadata();
      if (($metadata["edgeType"] ?? "") === "loopback") {
        return TRUE;
      }
    }

    // Check target handle for loopback port.
    $targetHandle = $edge->getTargetHandle();
    if (str_contains($targetHandle, "-input-loopback")) {
      return TRUE;
    }

    // Check target input name method if available.
    if (method_exists($edge, "getTargetInputName")) {
      return $edge->getTargetInputName() === "loopback";
    }

    return FALSE;
  }

  /**
   * Find the loopback edge for an iterator.
   *
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param string $iteratorNodeId
   *   The iterator node ID.
   *
   * @return object|null
   *   The loopback edge, or NULL if not found.
   */
  private function findLoopbackEdge(WorkflowDTO $workflow, string $iteratorNodeId): ?object {
    $incomingEdges = $workflow->getIncomingEdges($iteratorNodeId);

    foreach ($incomingEdges as $edge) {
      if ($this->isLoopbackEdge($edge)) {
        return $edge;
      }
    }

    return NULL;
  }

  /**
   * Topological sort for execution order.
   *
   * Uses Kahn's algorithm to generate a valid execution order
   * respecting node dependencies.
   *
   * @param array<string> $nodes
   *   Node IDs to sort.
   * @param array<string, array<string>> $dependencies
   *   Map of node ID to its dependency node IDs.
   *
   * @return array<string>
   *   Nodes in execution order.
   */
  private function topologicalSort(array $nodes, array $dependencies): array {
    $inDegree = [];
    $graph = [];

    // Initialize.
    foreach ($nodes as $nodeId) {
      $inDegree[$nodeId] = 0;
      $graph[$nodeId] = [];
    }

    // Build graph.
    foreach ($dependencies as $nodeId => $deps) {
      if (!isset($inDegree[$nodeId])) {
        continue;
      }

      foreach ($deps as $depId) {
        if (isset($graph[$depId])) {
          $graph[$depId][] = $nodeId;
          $inDegree[$nodeId]++;
        }
      }
    }

    // Kahn's algorithm.
    $queue = [];
    foreach ($nodes as $nodeId) {
      if ($inDegree[$nodeId] === 0) {
        $queue[] = $nodeId;
      }
    }

    $sorted = [];
    while (!empty($queue)) {
      $nodeId = array_shift($queue);
      $sorted[] = $nodeId;

      foreach ($graph[$nodeId] as $dependentId) {
        $inDegree[$dependentId]--;
        if ($inDegree[$dependentId] === 0) {
          $queue[] = $dependentId;
        }
      }
    }

    return $sorted;
  }

}
