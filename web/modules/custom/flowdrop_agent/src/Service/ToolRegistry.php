<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\Service;

use Drupal\flowdrop_runtime\DTO\Compiler\DependencyNode;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\Service\FlowDropNodeProcessorPluginManager;
use Drupal\flowdrop_ai\DTO\ToolDefinition;
use Drupal\flowdrop_runtime\DTO\Compiler\DependencyGraph;
use Drupal\flowdrop_runtime\DTO\Compiler\DependencyEdge;
use Drupal\flowdrop_workflow\DTO\WorkflowDTO;
use Psr\Log\LoggerInterface;

/**
 * Service for discovering and managing available tools.
 *
 * Discovers tools from nodes connected to an Agent via tool_availability edges.
 * Uses DependencyGraph for efficient tool discovery.
 */
final class ToolRegistry {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * Constructs a new ToolRegistry.
   *
   * @param \Drupal\flowdrop\Service\FlowDropNodeProcessorPluginManager $processorManager
   *   The node processor plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly FlowDropNodeProcessorPluginManager $processorManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get("flowdrop_agent");
  }

  /**
   * Discovers tools available to an Agent using DependencyGraph.
   *
   * This is the preferred method for tool discovery as it directly queries
   * the DependencyGraph for tool_availability edges.
   *
   * @param \Drupal\flowdrop_runtime\DTO\Compiler\DependencyGraph $dependencyGraph
   *   The dependency graph.
   * @param string $agentNodeId
   *   The Agent node ID.
   *
   * @return array<ToolDefinition>
   *   Array of discovered tool definitions.
   */
  public function discoverToolsFromGraph(
    DependencyGraph $dependencyGraph,
    string $agentNodeId,
  ): array {
    $tools = [];

    $this->logger->debug("Discovering tools for agent @id using DependencyGraph", [
      "@id" => $agentNodeId,
    ]);

    // Get all children connected via tool_availability edges.
    $toolChildren = $dependencyGraph->getChildren(
      $agentNodeId,
      DependencyEdge::TYPE_TOOL_AVAILABILITY
    );

    foreach ($toolChildren as $toolConnection) {
      $targetNodeId = $toolConnection["nodeId"];
      $targetNode = $dependencyGraph->getNode($targetNodeId);

      if ($targetNode === NULL) {
        $this->logger->warning("Tool node @id not found in dependency graph", [
          "@id" => $targetNodeId,
        ]);
        continue;
      }

      // Build edge metadata from connection info.
      $edgeMetadata = array_merge(
        $toolConnection["metadata"],
        [
          "edgeType" => $toolConnection["edgeType"],
          "sourceHandle" => $toolConnection["sourceHandle"],
          "targetHandle" => $toolConnection["targetHandle"],
          "edgeId" => $toolConnection["edgeId"],
        ]
      );

      $toolDefinition = $this->buildToolDefinitionFromNode($targetNode, $edgeMetadata);
      if ($toolDefinition !== NULL) {
        $tools[] = $toolDefinition;
        $this->logger->debug("Discovered tool @name from node @node", [
          "@name" => $toolDefinition->getName(),
          "@node" => $targetNodeId,
        ]);
      }
    }

    $this->logger->info("Discovered @count tools for agent @id", [
      "@count" => count($tools),
      "@id" => $agentNodeId,
    ]);

    return $tools;
  }

  /**
   * Discovers tools available to an Agent from workflow edges.
   *
   * This method is maintained for backward compatibility. Prefer using
   * discoverToolsFromGraph() when a DependencyGraph is available.
   *
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param string $agentNodeId
   *   The Agent node ID.
   *
   * @return array<ToolDefinition>
   *   Array of discovered tool definitions.
   */
  public function discoverTools(WorkflowDTO $workflow, string $agentNodeId): array {
    $tools = [];

    $this->logger->debug("Discovering tools for agent @id", [
      "@id" => $agentNodeId,
    ]);

    // Find all tool_availability edges from the agent.
    foreach ($workflow->getOutgoingEdges($agentNodeId) as $edge) {
      $metadata = $edge->getMetadata();
      $edgeType = $metadata["edgeType"] ?? "data";

      if ($edgeType !== "tool_availability") {
        continue;
      }

      $targetNodeId = $edge->getTarget();
      $targetNode = $workflow->getNode($targetNodeId);

      if ($targetNode === NULL) {
        $this->logger->warning("Tool node @id not found in workflow", [
          "@id" => $targetNodeId,
        ]);
        continue;
      }

      $toolDefinition = $this->buildToolDefinition($targetNode, $metadata);
      if ($toolDefinition !== NULL) {
        $tools[] = $toolDefinition;
        $this->logger->debug("Discovered tool @name from node @node", [
          "@name" => $toolDefinition->getName(),
          "@node" => $targetNodeId,
        ]);
      }
    }

    $this->logger->info("Discovered @count tools for agent @id", [
      "@count" => count($tools),
      "@id" => $agentNodeId,
    ]);

    return $tools;
  }

  /**
   * Builds a ToolDefinition from a DependencyNode.
   *
   * @param \Drupal\flowdrop_runtime\DTO\Compiler\DependencyNode $node
   *   The dependency node.
   * @param array<string, mixed> $edgeMetadata
   *   The edge metadata.
   *
   * @return \Drupal\flowdrop_ai\DTO\ToolDefinition|null
   *   The tool definition or NULL if cannot be built.
   */
  private function buildToolDefinitionFromNode(
    DependencyNode $node,
    array $edgeMetadata,
  ): ?ToolDefinition {
    try {
      $nodeId = $node->getId();
      $nodeTypeId = $node->getTypeId();
      $label = $node->getLabel() ?: $nodeTypeId;
      $config = $node->getConfig();

      // Get processor to access schemas.
      $processorDefinition = $this->processorManager->getDefinition($nodeTypeId, FALSE);

      $description = "";
      $inputSchema = [];
      $outputSchema = [];

      if ($processorDefinition !== NULL) {
        $description = $processorDefinition["description"] ?? "";

        // Try to get schemas from processor instance.
        try {
          $processor = $this->processorManager->createInstance($nodeTypeId, $config);
          if (method_exists($processor, "getInputSchema")) {
            $inputSchema = $processor->getInputSchema();
          }
          if (method_exists($processor, "getOutputSchema")) {
            $outputSchema = $processor->getOutputSchema();
          }
        }
        catch (\Exception $e) {
          $this->logger->warning("Could not instantiate processor @type: @error", [
            "@type" => $nodeTypeId,
            "@error" => $e->getMessage(),
          ]);
        }
      }

      return ToolDefinition::fromNode(
        nodeId: $nodeId,
        nodeTypeId: $nodeTypeId,
        label: $label,
        description: $description,
        inputSchema: $inputSchema,
        outputSchema: $outputSchema,
        edgeOverrides: $edgeMetadata,
      );
    }
    catch (\Exception $e) {
      $this->logger->error("Error building tool definition: @error", [
        "@error" => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Builds a ToolDefinition from a node.
   *
   * @param mixed $node
   *   The workflow node DTO.
   * @param array<string, mixed> $edgeMetadata
   *   The edge metadata.
   *
   * @return \Drupal\flowdrop_ai\DTO\ToolDefinition|null
   *   The tool definition or NULL if cannot be built.
   */
  private function buildToolDefinition(mixed $node, array $edgeMetadata): ?ToolDefinition {
    try {
      $nodeId = $node->getId();
      $nodeTypeId = $node->getTypeId();
      $label = $node->getLabel() ?: $nodeTypeId;
      $config = $node->getConfig();

      // Get processor to access schemas.
      $processorDefinition = $this->processorManager->getDefinition($nodeTypeId, FALSE);

      $description = "";
      $inputSchema = [];
      $outputSchema = [];

      if ($processorDefinition !== NULL) {
        $description = $processorDefinition["description"] ?? "";

        // Try to get schemas from processor instance.
        try {
          $processor = $this->processorManager->createInstance($nodeTypeId, $config);
          if (method_exists($processor, "getInputSchema")) {
            $inputSchema = $processor->getInputSchema();
          }
          if (method_exists($processor, "getOutputSchema")) {
            $outputSchema = $processor->getOutputSchema();
          }
        }
        catch (\Exception $e) {
          $this->logger->warning("Could not instantiate processor @type: @error", [
            "@type" => $nodeTypeId,
            "@error" => $e->getMessage(),
          ]);
        }
      }

      return ToolDefinition::fromNode(
        nodeId: $nodeId,
        nodeTypeId: $nodeTypeId,
        label: $label,
        description: $description,
        inputSchema: $inputSchema,
        outputSchema: $outputSchema,
        edgeOverrides: $edgeMetadata,
      );
    }
    catch (\Exception $e) {
      $this->logger->error("Error building tool definition: @error", [
        "@error" => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets a tool definition by name.
   *
   * @param array<ToolDefinition> $tools
   *   The available tools.
   * @param string $toolName
   *   The tool name to find.
   *
   * @return \Drupal\flowdrop_ai\DTO\ToolDefinition|null
   *   The tool or NULL if not found.
   */
  public function getTool(array $tools, string $toolName): ?ToolDefinition {
    foreach ($tools as $tool) {
      if ($tool->getName() === $toolName) {
        return $tool;
      }
    }
    return NULL;
  }

  /**
   * Gets a tool definition by node ID.
   *
   * @param array<ToolDefinition> $tools
   *   The available tools.
   * @param string $nodeId
   *   The node ID to find.
   *
   * @return \Drupal\flowdrop_ai\DTO\ToolDefinition|null
   *   The tool or NULL if not found.
   */
  public function getToolByNodeId(array $tools, string $nodeId): ?ToolDefinition {
    foreach ($tools as $tool) {
      if ($tool->getNodeId() === $nodeId) {
        return $tool;
      }
    }
    return NULL;
  }

  /**
   * Validates that required tools are available.
   *
   * @param array<ToolDefinition> $tools
   *   The available tools.
   * @param array<string> $requiredTools
   *   Names of required tools.
   *
   * @return array<string>
   *   Array of missing tool names.
   */
  public function validateRequiredTools(array $tools, array $requiredTools): array {
    $missing = [];
    $availableNames = array_map(fn(ToolDefinition $t) => $t->getName(), $tools);

    foreach ($requiredTools as $required) {
      if (!in_array($required, $availableNames, TRUE)) {
        $missing[] = $required;
      }
    }

    return $missing;
  }

}
