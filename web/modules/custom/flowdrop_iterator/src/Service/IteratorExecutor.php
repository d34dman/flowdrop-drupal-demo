<?php

declare(strict_types=1);

namespace Drupal\flowdrop_iterator\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\Output;
use Drupal\flowdrop\DTO\OutputInterface;
use Drupal\flowdrop_iterator\DTO\IteratorState;
use Drupal\flowdrop_iterator\DTO\IterationResult;
use Drupal\flowdrop_iterator\Exception\IteratorException;
use Drupal\flowdrop_iterator\Exception\IterationFailedException;
use Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface;
use Drupal\flowdrop_job\FlowDropJobInterface;
use Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService;
use Drupal\flowdrop_runtime\Service\Runtime\ExecutionContext;
use Drupal\flowdrop_workflow\DTO\WorkflowDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Executes Iterator nodes with child Pipeline/Job creation.
 *
 * This service handles the execution of Iterator nodes by:
 * - Creating a child Pipeline for iteration tracking
 * - Creating Jobs for each node in the sub-workflow for each iteration
 * - Executing the sub-workflow sequentially for each item
 * - Aggregating results into a final output array.
 */
class IteratorExecutor {

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * Constructs an IteratorExecutor instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService $nodeRuntime
   *   The node runtime service.
   * @param \Drupal\flowdrop_runtime\Service\Runtime\ExecutionContext $executionContext
   *   The execution context service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\flowdrop_iterator\Service\SubWorkflowDetector $subWorkflowDetector
   *   The sub-workflow detector service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly NodeRuntimeService $nodeRuntime,
    private readonly ExecutionContext $executionContext,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly SubWorkflowDetector $subWorkflowDetector,
  ) {
    $this->logger = $loggerFactory->get("flowdrop_iterator");
  }

  /**
   * Execute an Iterator node.
   *
   * @param string $executionId
   *   The execution ID.
   * @param string $iteratorNodeId
   *   The Iterator node ID.
   * @param array<string, mixed> $inputData
   *   Input data containing the array to iterate.
   * @param array<string, mixed> $config
   *   Iterator configuration.
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param string $parentPipelineId
   *   Parent pipeline ID.
   *
   * @return \Drupal\flowdrop\DTO\OutputInterface
   *   The output with aggregated results.
   *
   * @throws \Drupal\flowdrop_iterator\Exception\IteratorException
   *   When iteration fails.
   */
  public function execute(
    string $executionId,
    string $iteratorNodeId,
    array $inputData,
    array $config,
    WorkflowDTO $workflow,
    string $parentPipelineId,
  ): OutputInterface {
    // Extract configuration.
    $maxIterations = (int) ($config["maxIterations"] ?? 1000);
    $onError = (string) ($config["onError"] ?? "fail");
    $maxRetries = (int) ($config["maxRetries"] ?? 3);

    // Get items to iterate.
    $items = $inputData["data"] ?? [];
    if (!is_array($items)) {
      $items = [$items];
    }

    $this->logger->info("Starting iterator execution for @node with @count items", [
      "@node" => $iteratorNodeId,
      "@count" => count($items),
    ]);

    // Handle empty array - skip to done.
    if (empty($items)) {
      $this->logger->info("Iterator @node received empty array, skipping to done", [
        "@node" => $iteratorNodeId,
      ]);

      return $this->createOutput([
        "done" => [],
        "isComplete" => TRUE,
        "index" => 0,
        "total" => 0,
      ]);
    }

    // Check max iterations and truncate if needed.
    if (count($items) > $maxIterations) {
      $this->logger->warning("Iterator @node items (@count) exceeds max (@max), truncating", [
        "@node" => $iteratorNodeId,
        "@count" => count($items),
        "@max" => $maxIterations,
      ]);

      // Dispatch warning event.
      $this->eventDispatcher->dispatch(
        new GenericEvent([
          "iteratorNodeId" => $iteratorNodeId,
          "requestedCount" => count($items),
          "maxIterations" => $maxIterations,
        ], [
          "execution_id" => $executionId,
        ]),
        "flowdrop.iterator.max_exceeded"
      );

      $items = array_slice($items, 0, $maxIterations);
    }

    // Detect sub-workflow.
    $subWorkflowInfo = $this->subWorkflowDetector->detect($workflow, $iteratorNodeId);
    $this->subWorkflowDetector->validate($subWorkflowInfo, $iteratorNodeId);

    // Create initial state.
    $state = new IteratorState(
      iteratorNodeId: $iteratorNodeId,
      items: $items,
    );

    // Create child pipeline.
    $childPipeline = $this->createChildPipeline(
      $workflow,
      $parentPipelineId,
      $iteratorNodeId,
      count($items)
    );

    $state = $state->withChildPipeline($childPipeline->id());
    $state = $state->withIterating();

    // Dispatch iterator started event.
    $this->eventDispatcher->dispatch(
      new GenericEvent($state->toArray(), [
        "execution_id" => $executionId,
        "iterator_node_id" => $iteratorNodeId,
      ]),
      "flowdrop.iterator.started"
    );

    // Execute iterations.
    try {
      $state = $this->executeIterations(
        $executionId,
        $state,
        $workflow,
        $subWorkflowInfo,
        $childPipeline,
        $onError,
        $maxRetries,
      );
    }
    catch (\Exception $e) {
      // Mark child pipeline as failed.
      $childPipeline->markAsFailed($e->getMessage());
      $childPipeline->save();

      // Dispatch failed event.
      $this->eventDispatcher->dispatch(
        new GenericEvent([
          "error" => $e->getMessage(),
          "state" => $state->toArray(),
        ], [
          "execution_id" => $executionId,
          "iterator_node_id" => $iteratorNodeId,
        ]),
        "flowdrop.iterator.failed"
      );

      throw IteratorException::forNode(
        "Iterator execution failed: " . $e->getMessage(),
        $iteratorNodeId,
        $e
      );
    }

    // Mark child pipeline as completed.
    $childPipeline->markAsCompleted($state->getAccumulatedResults());
    $childPipeline->save();

    // Dispatch iterator completed event.
    $this->eventDispatcher->dispatch(
      new GenericEvent($state->toArray(), [
        "execution_id" => $executionId,
        "iterator_node_id" => $iteratorNodeId,
      ]),
      "flowdrop.iterator.completed"
    );

    $this->logger->info("Iterator @node completed with @count results", [
      "@node" => $iteratorNodeId,
      "@count" => count($state->getAccumulatedResults()),
    ]);

    return $this->createOutput([
      "done" => $state->getAccumulatedResults(),
      "isComplete" => TRUE,
      "index" => $state->getCurrentIndex(),
      "total" => $state->getTotalCount(),
      "errors" => $state->getErrors(),
      "_childPipelineId" => $state->getChildPipelineId(),
    ]);
  }

  /**
   * Execute all iterations.
   *
   * @param string $executionId
   *   The execution ID.
   * @param \Drupal\flowdrop_iterator\DTO\IteratorState $state
   *   The iterator state.
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param array<string, mixed> $subWorkflowInfo
   *   Sub-workflow detection result.
   * @param \Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface $childPipeline
   *   The child pipeline.
   * @param string $onError
   *   Error handling strategy.
   * @param int $maxRetries
   *   Maximum retry attempts.
   *
   * @return \Drupal\flowdrop_iterator\DTO\IteratorState
   *   Updated iterator state.
   */
  private function executeIterations(
    string $executionId,
    IteratorState $state,
    WorkflowDTO $workflow,
    array $subWorkflowInfo,
    FlowDropPipelineInterface $childPipeline,
    string $onError,
    int $maxRetries,
  ): IteratorState {
    while ($state->hasMoreItems()) {
      $currentItem = $state->getCurrentItem();
      $currentIndex = $state->getCurrentIndex();

      $this->logger->info("Iterator executing iteration @index of @total", [
        "@index" => $currentIndex + 1,
        "@total" => $state->getTotalCount(),
      ]);

      // Dispatch iteration started event.
      $this->eventDispatcher->dispatch(
        new GenericEvent([
          "item" => $currentItem,
          "index" => $currentIndex,
          "total" => $state->getTotalCount(),
        ], [
          "execution_id" => $executionId,
          "iterator_node_id" => $state->getIteratorNodeId(),
        ]),
        "flowdrop.iterator.iteration_started"
      );

      try {
        $result = $this->executeSingleIteration(
          $executionId,
          $currentItem,
          $currentIndex,
          $workflow,
          $subWorkflowInfo,
          $childPipeline,
          $maxRetries,
          $onError,
        );

        $state = $state->withNextIteration($result->getOutputResult());

        // Dispatch iteration completed event.
        $this->eventDispatcher->dispatch(
          new GenericEvent([
            "index" => $currentIndex,
            "status" => "success",
            "result" => $result->toArray(),
          ], [
            "execution_id" => $executionId,
            "iterator_node_id" => $state->getIteratorNodeId(),
          ]),
          "flowdrop.iterator.iteration_completed"
        );

      }
      catch (\Exception $e) {
        $this->logger->error("Iteration @index failed: @error", [
          "@index" => $currentIndex,
          "@error" => $e->getMessage(),
        ]);

        switch ($onError) {
          case "skip":
            $state = $state->withSkippedItem($e->getMessage());

            // Dispatch iteration skipped event.
            $this->eventDispatcher->dispatch(
              new GenericEvent([
                "index" => $currentIndex,
                "status" => "skipped",
                "error" => $e->getMessage(),
              ], [
                "execution_id" => $executionId,
                "iterator_node_id" => $state->getIteratorNodeId(),
              ]),
              "flowdrop.iterator.iteration_completed"
            );
            break;

          case "fail":
          default:
            throw $e;
        }
      }
    }

    return $state->withCompleted();
  }

  /**
   * Execute a single iteration.
   *
   * @param string $executionId
   *   The execution ID.
   * @param mixed $item
   *   The current item to process.
   * @param int $index
   *   The current iteration index.
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param array<string, mixed> $subWorkflowInfo
   *   Sub-workflow detection result.
   * @param \Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface $childPipeline
   *   The child pipeline.
   * @param int $maxRetries
   *   Maximum retry attempts.
   * @param string $onError
   *   Error handling strategy.
   *
   * @return \Drupal\flowdrop_iterator\DTO\IterationResult
   *   The iteration result.
   */
  private function executeSingleIteration(
    string $executionId,
    mixed $item,
    int $index,
    WorkflowDTO $workflow,
    array $subWorkflowInfo,
    FlowDropPipelineInterface $childPipeline,
    int $maxRetries,
    string $onError,
  ): IterationResult {
    $executionOrder = $subWorkflowInfo["executionOrder"] ?? [];
    $retryCount = 0;
    $startTime = microtime(TRUE);

    while (TRUE) {
      try {
        // Create jobs for this iteration.
        $iterationJobs = $this->createIterationJobs(
          $childPipeline,
          $executionOrder,
          $workflow,
          $index,
        );

        // Execute sub-workflow jobs sequentially.
        $lastResult = $item;
        foreach ($executionOrder as $nodeId) {
          $job = $iterationJobs[$nodeId] ?? NULL;
          if ($job === NULL) {
            continue;
          }

          $lastResult = $this->executeJob(
            $executionId,
            $job,
            $workflow,
            $lastResult,
            $index,
          );
        }

        $executionTime = microtime(TRUE) - $startTime;

        return IterationResult::success(
          index: $index,
          inputItem: $item,
          outputResult: $lastResult,
          metadata: [
            "executionTime" => $executionTime,
            "retryCount" => $retryCount,
            "jobCount" => count($iterationJobs),
          ]
        );

      }
      catch (\Exception $e) {
        $retryCount++;

        if ($onError !== "retry" || $retryCount >= $maxRetries) {
          throw IterationFailedException::forIteration(
            $e->getMessage(),
            $subWorkflowInfo["iteratorNodeId"] ?? "unknown",
            $index,
            $item,
            $e
          );
        }

        $this->logger->warning("Iteration @index failed, retry @retry of @max", [
          "@index" => $index,
          "@retry" => $retryCount,
          "@max" => $maxRetries,
        ]);
      }
    }
  }

  /**
   * Create child pipeline for iterator.
   *
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param string $parentPipelineId
   *   The parent pipeline ID.
   * @param string $iteratorNodeId
   *   The iterator node ID.
   * @param int $itemCount
   *   Total number of items to iterate.
   *
   * @return \Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface
   *   The created child pipeline.
   */
  private function createChildPipeline(
    WorkflowDTO $workflow,
    string $parentPipelineId,
    string $iteratorNodeId,
    int $itemCount,
  ): FlowDropPipelineInterface {
    $pipelineStorage = $this->entityTypeManager->getStorage("flowdrop_pipeline");

    /** @var \Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface $pipeline */
    $pipeline = $pipelineStorage->create([
      "workflow" => $workflow->getId(),
      "status" => "pending",
      "metadata" => [
        "is_child_pipeline" => TRUE,
        "parent_pipeline_id" => $parentPipelineId,
        "iterator_node_id" => $iteratorNodeId,
        "total_items" => $itemCount,
        "created_at" => time(),
      ],
    ]);

    $pipeline->save();

    $this->logger->info("Created child pipeline @id for iterator @node", [
      "@id" => $pipeline->id(),
      "@node" => $iteratorNodeId,
    ]);

    return $pipeline;
  }

  /**
   * Create jobs for an iteration.
   *
   * @param \Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface $pipeline
   *   The pipeline.
   * @param array<string> $executionOrder
   *   Node IDs in execution order.
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param int $iterationIndex
   *   The current iteration index.
   *
   * @return array<string, \Drupal\flowdrop_job\FlowDropJobInterface>
   *   Jobs keyed by node ID.
   */
  private function createIterationJobs(
    FlowDropPipelineInterface $pipeline,
    array $executionOrder,
    WorkflowDTO $workflow,
    int $iterationIndex,
  ): array {
    $jobStorage = $this->entityTypeManager->getStorage("flowdrop_job");
    $jobs = [];

    foreach ($executionOrder as $nodeId) {
      $node = $workflow->getNode($nodeId);
      if ($node === NULL) {
        continue;
      }

      /** @var \Drupal\flowdrop_job\FlowDropJobInterface $job */
      $job = $jobStorage->create([
        "pipeline" => $pipeline->id(),
        "node_id" => $nodeId,
        "status" => "pending",
        "input_data" => [],
        "metadata" => [
          "node_type_id" => $node->getTypeId(),
          "iteration_index" => $iterationIndex,
          "node_label" => $node->getLabel(),
        ],
      ]);

      $job->save();
      $jobs[$nodeId] = $job;
    }

    return $jobs;
  }

  /**
   * Execute a single job in the iteration.
   *
   * @param string $executionId
   *   The execution ID.
   * @param \Drupal\flowdrop_job\FlowDropJobInterface $job
   *   The job to execute.
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The workflow DTO.
   * @param mixed $previousResult
   *   Result from previous node (or original item for first node).
   * @param int $iterationIndex
   *   The current iteration index.
   *
   * @return mixed
   *   The job output.
   */
  private function executeJob(
    string $executionId,
    FlowDropJobInterface $job,
    WorkflowDTO $workflow,
    mixed $previousResult,
    int $iterationIndex,
  ): mixed {
    $nodeId = $job->getNodeId();
    $node = $workflow->getNode($nodeId);

    if ($node === NULL) {
      throw new IteratorException("Node not found: {$nodeId}");
    }

    // Mark job as started.
    $job->markAsStarted();
    $job->save();

    try {
      // Prepare inputs - inject previous result.
      $inputData = is_array($previousResult)
        ? $previousResult
        : ["data" => $previousResult, "item" => $previousResult];

      $inputData["_iteration_index"] = $iterationIndex;

      // Update job with input data.
      $job->setInputData($inputData);
      $job->save();

      $configData = $node->getConfig();

      // Create execution context.
      $context = $this->executionContext->createContext(
        $workflow->getId(),
        (string) $job->getPipeline()->id(),
        $inputData
      );

      // Execute the node with raw arrays.
      $result = $this->nodeRuntime->executeNode(
        $executionId,
        $nodeId,
        $node->getTypeId(),
        $inputData,
        $configData,
        $context
      );

      $outputData = $result->getOutput()->toArray();

      // Mark job as completed.
      $job->markAsCompleted($outputData);
      $job->save();

      return $outputData;

    }
    catch (\Exception $e) {
      $job->markAsFailed($e->getMessage());
      $job->save();
      throw $e;
    }
  }

  /**
   * Create output DTO.
   *
   * @param array<string, mixed> $data
   *   The output data.
   *
   * @return \Drupal\flowdrop\DTO\OutputInterface
   *   The output DTO.
   */
  private function createOutput(array $data): OutputInterface {
    $output = new Output();
    $output->fromArray($data);
    $output->setStatus("success");
    return $output;
  }

}
