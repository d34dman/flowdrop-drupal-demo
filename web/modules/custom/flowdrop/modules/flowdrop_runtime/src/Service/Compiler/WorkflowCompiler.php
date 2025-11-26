<?php

declare(strict_types=1);

namespace Drupal\flowdrop_runtime\Service\Compiler;

use Psr\Log\LoggerInterface;
use Drupal\flowdrop_runtime\DTO\Compiler\CompiledWorkflow;
use Drupal\flowdrop_runtime\DTO\Compiler\ExecutionPlan;
use Drupal\flowdrop_runtime\DTO\Compiler\NodeMapping;
use Drupal\flowdrop_runtime\Exception\CompilationException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Compiles workflow entities into executable DAGs.
 */
class WorkflowCompiler {

  /**
   * Logger channel for this service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  public function __construct(LoggerChannelFactoryInterface $loggerFactory) {
    $this->logger = $loggerFactory->get('flowdrop_runtime');
  }

  /**
   * Compile a workflow entity into an executable DAG.
   *
   * @param array $workflow
   *   The workflow definition array.
   *
   * @return \Drupal\flowdrop_runtime\DTO\Compiler\CompiledWorkflow
   *   The compiled workflow with execution plan.
   *
   * @throws \Drupal\flowdrop_runtime\Exception\CompilationException
   *   When compilation fails due to validation errors.
   */
  public function compile(array $workflow): CompiledWorkflow {
    $this->logger->info('Compiling workflow @id', [
      '@id' => $workflow['id'] ?? 'unknown',
    ]);

    try {
      // Validate workflow structure.
      $this->validateWorkflowStructure($workflow);

      // Extract nodes and connections.
      $nodes = $workflow['nodes'] ?? [];
      $connections = $workflow['connections'] ?? [];

      // Build dependency graph.
      $dependencyGraph = $this->buildDependencyGraph($nodes, $connections);

      // Detect circular dependencies.
      $this->detectCircularDependencies($dependencyGraph);

      // Generate execution plan.
      $executionPlan = $this->generateExecutionPlan($nodes, $dependencyGraph);

      // Create node mappings.
      $nodeMappings = $this->createNodeMappings($nodes);

      $compiledWorkflow = new CompiledWorkflow(
        workflowId: $workflow['id'] ?? '',
        executionPlan: $executionPlan,
        nodeMappings: $nodeMappings,
        dependencyGraph: $dependencyGraph,
        metadata: [
          'node_count' => count($nodes),
          'connection_count' => count($connections),
          'compilation_timestamp' => time(),
        ]
      );

      $this->logger->info('Successfully compiled workflow @id with @nodes nodes', [
        '@id' => $workflow['id'] ?? 'unknown',
        '@nodes' => count($nodes),
      ]);

      return $compiledWorkflow;

    }
    catch (\Exception $e) {
      $this->logger->error('Workflow compilation failed: @error', [
        '@error' => $e->getMessage(),
        '@workflow_id' => $workflow['id'] ?? 'unknown',
      ]);

      throw new CompilationException(
        "Workflow compilation failed: " . $e->getMessage(),
        0,
        $e
      );
    }
  }

  /**
   * Validate workflow structure.
   *
   * @param array $workflow
   *   The workflow definition.
   *
   * @throws \Drupal\flowdrop_runtime\Exception\CompilationException
   *   When workflow structure is invalid.
   */
  private function validateWorkflowStructure(array $workflow): void {
    if (empty($workflow['id'])) {
      throw new CompilationException('Workflow must have an ID');
    }

    if (empty($workflow['nodes']) || !is_array($workflow['nodes'])) {
      throw new CompilationException('Workflow must have nodes array');
    }

    // Validate each node.
    foreach ($workflow['nodes'] as $node) {
      if (empty($node['id'])) {
        throw new CompilationException('All nodes must have an ID');
      }

      if (empty($node['type'])) {
        throw new CompilationException("Node {$node['id']} must have a type");
      }
    }

    // Validate connections if present.
    if (isset($workflow['connections']) && !is_array($workflow['connections'])) {
      throw new CompilationException('Connections must be an array');
    }
  }

  /**
   * Build dependency graph from nodes and connections.
   *
   * @param array $nodes
   *   The workflow nodes.
   * @param array $connections
   *   The node connections.
   *
   * @return array
   *   The dependency graph.
   */
  private function buildDependencyGraph(array $nodes, array $connections): array {
    $graph = [];

    // Initialize graph with all nodes.
    foreach ($nodes as $node) {
      $nodeId = $node['id'];
      $graph[$nodeId] = [
        'dependencies' => [],
        'dependents' => [],
        'in_degree' => 0,
      ];
    }

    // Build connections.
    foreach ($connections as $connection) {
      $sourceId = $connection['source'] ?? '';
      $targetId = $connection['target'] ?? '';

      if (empty($sourceId) || empty($targetId)) {
        continue;
      }

      // Validate that both nodes exist.
      if (!isset($graph[$sourceId]) || !isset($graph[$targetId])) {
        $this->logger->warning('Connection references non-existent node: @source -> @target', [
          '@source' => $sourceId,
          '@target' => $targetId,
        ]);
        continue;
      }

      // Add dependency relationship.
      $graph[$sourceId]['dependents'][] = $targetId;
      $graph[$targetId]['dependencies'][] = $sourceId;
      $graph[$targetId]['in_degree']++;
    }

    return $graph;
  }

  /**
   * Detect circular dependencies using DFS.
   *
   * @param array $dependencyGraph
   *   The dependency graph.
   *
   * @throws \Drupal\flowdrop_runtime\Exception\CompilationException
   *   When circular dependencies are detected.
   */
  private function detectCircularDependencies(array $dependencyGraph): void {
    $visited = [];
    $recursionStack = [];

    foreach (array_keys($dependencyGraph) as $nodeId) {
      if (!isset($visited[$nodeId])) {
        if ($this->hasCycle($nodeId, $dependencyGraph, $visited, $recursionStack)) {
          throw new CompilationException(
            'Circular dependency detected in workflow'
          );
        }
      }
    }
  }

  /**
   * Check for cycles using DFS.
   *
   * @param string $nodeId
   *   The current node ID.
   * @param array $graph
   *   The dependency graph.
   * @param array $visited
   *   Visited nodes.
   * @param array $recursionStack
   *   Current recursion stack.
   *
   * @return bool
   *   True if cycle is detected.
   */
  private function hasCycle(string $nodeId, array $graph, array &$visited, array &$recursionStack): bool {
    $visited[$nodeId] = TRUE;
    $recursionStack[$nodeId] = TRUE;

    foreach ($graph[$nodeId]['dependents'] ?? [] as $dependentId) {
      if (!isset($visited[$dependentId])) {
        if ($this->hasCycle($dependentId, $graph, $visited, $recursionStack)) {
          return TRUE;
        }
      }
      elseif (isset($recursionStack[$dependentId]) && $recursionStack[$dependentId]) {
        return TRUE;
      }
    }

    $recursionStack[$nodeId] = FALSE;
    return FALSE;
  }

  /**
   * Generate execution plan using topological sort.
   *
   * @param array $nodes
   *   The workflow nodes.
   * @param array $dependencyGraph
   *   The dependency graph.
   *
   * @return \Drupal\flowdrop_runtime\DTO\Compiler\ExecutionPlan
   *   The execution plan.
   */
  private function generateExecutionPlan(array $nodes, array $dependencyGraph): ExecutionPlan {
    $executionOrder = [];
    $inputMappings = [];
    $outputMappings = [];

    // Create node lookup.
    $nodeLookup = [];
    foreach ($nodes as $node) {
      $nodeLookup[$node['id']] = $node;
    }

    // Topological sort for execution order.
    $queue = [];
    $inDegree = [];

    // Initialize in-degree counts.
    foreach ($dependencyGraph as $nodeId => $nodeData) {
      $inDegree[$nodeId] = $nodeData['in_degree'];
      if ($nodeData['in_degree'] === 0) {
        $queue[] = $nodeId;
      }
    }

    // Process nodes in topological order.
    while (!empty($queue)) {
      $nodeId = array_shift($queue);
      $executionOrder[] = $nodeId;

      // Process dependents.
      foreach ($dependencyGraph[$nodeId]['dependents'] ?? [] as $dependentId) {
        $inDegree[$dependentId]--;
        if ($inDegree[$dependentId] === 0) {
          $queue[] = $dependentId;
        }
      }
    }

    // Generate input/output mappings.
    foreach ($executionOrder as $nodeId) {
      $node = $nodeLookup[$nodeId];
      $dependencies = $dependencyGraph[$nodeId]['dependencies'] ?? [];

      // Input mappings: map dependency outputs to this node's inputs.
      $inputMappings[$nodeId] = [];
      foreach ($dependencies as $dependencyId) {
        $inputMappings[$nodeId][$dependencyId] = [
          'type' => 'output',
          'source_node' => $dependencyId,
          'target_input' => 'default',
        ];
      }

      // Output mappings: map this node's outputs to dependents.
      $outputMappings[$nodeId] = [];
      foreach ($dependencyGraph[$nodeId]['dependents'] ?? [] as $dependentId) {
        $outputMappings[$nodeId][$dependentId] = [
          'type' => 'input',
          'target_node' => $dependentId,
          'source_output' => 'default',
        ];
      }
    }

    return new ExecutionPlan(
      executionOrder: $executionOrder,
      inputMappings: $inputMappings,
      outputMappings: $outputMappings,
      metadata: [
        'total_nodes' => count($executionOrder),
        'leaf_nodes' => count(array_filter($dependencyGraph, fn($node) => empty($node['dependents']))),
        'root_nodes' => count(array_filter($dependencyGraph, fn($node) => empty($node['dependencies']))),
      ]
    );
  }

  /**
   * Create node mappings for processor plugins.
   *
   * @param array $nodes
   *   The workflow nodes.
   *
   * @return array
   *   Array of NodeMapping objects.
   */
  private function createNodeMappings(array $nodes): array {
    $nodeMappings = [];

    foreach ($nodes as $node) {
      $nodeMappings[$node['id']] = new NodeMapping(
        nodeId: $node['id'],
        processorId: $node['type'],
        config: $node['config'] ?? [],
        metadata: [
          'label' => $node['label'] ?? $node['id'],
          'description' => $node['description'] ?? '',
        ]
      );
    }

    return $nodeMappings;
  }

  /**
   * Validate a compiled workflow.
   *
   * @param \Drupal\flowdrop_runtime\DTO\Compiler\CompiledWorkflow $compiledWorkflow
   *   The compiled workflow to validate.
   *
   * @return bool
   *   True if valid.
   */
  public function validateCompiledWorkflow(CompiledWorkflow $compiledWorkflow): bool {
    $executionPlan = $compiledWorkflow->getExecutionPlan();
    $nodeMappings = $compiledWorkflow->getNodeMappings();

    // Check that all nodes in execution order have mappings.
    foreach ($executionPlan->getExecutionOrder() as $nodeId) {
      if (!isset($nodeMappings[$nodeId])) {
        $this->logger->error('Execution plan references unmapped node: @node_id', [
          '@node_id' => $nodeId,
        ]);
        return FALSE;
      }
    }

    // Check that all mapped nodes are in execution order.
    foreach (array_keys($nodeMappings) as $nodeId) {
      if (!in_array($nodeId, $executionPlan->getExecutionOrder())) {
        $this->logger->error('Mapped node not in execution order: @node_id', [
          '@node_id' => $nodeId,
        ]);
        return FALSE;
      }
    }

    return TRUE;
  }

}
