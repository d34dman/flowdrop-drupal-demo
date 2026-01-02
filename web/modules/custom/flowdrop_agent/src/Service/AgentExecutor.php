<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\Service;

use Drupal\flowdrop_conversation\DTO\ToolCall;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\Output;
use Drupal\flowdrop_agent\DTO\AgentState;
use Drupal\flowdrop_agent\DTO\AgentTrace;
use Drupal\flowdrop_agent\DTO\ToolResult;
use Drupal\flowdrop_agent\DTO\TraceStep;
use Drupal\flowdrop_agent\Exception\ToolExecutionException;
use Drupal\flowdrop_ai\Adapter\ToolCallingAdapterFactory;
use Drupal\flowdrop_ai\DTO\ToolDefinition;
use Drupal\flowdrop_conversation\DTO\ConversationState;
use Drupal\flowdrop_conversation\Service\ConversationManager;
use Drupal\flowdrop_runtime\DTO\Compiler\DependencyGraph;
use Drupal\flowdrop_runtime\DTO\Compiler\DependencyEdge;
use Drupal\flowdrop_runtime\DTO\Runtime\NodeExecutionContext;
use Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService;
use Drupal\flowdrop_workflow\DTO\WorkflowDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Service responsible for orchestrating Agent execution.
 *
 * Implements the ReAct (Reasoning + Acting) loop:
 * 1. Send prompt + history + tools to LLM
 * 2. If LLM returns tool call → execute tool → add result to history → goto 1
 * 3. If LLM returns final answer → complete
 * 4. If max iterations reached → return with warning.
 *
 * Tools are discovered from the DependencyGraph using tool_availability edges.
 * Tool inputs are a merge of LLM-provided arguments and data from upstream
 * data edges (resolved via DependencyGraph).
 */
final class AgentExecutor {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private readonly LoggerInterface $logger;

  /**
   * Constructs a new AgentExecutor.
   *
   * @param \Drupal\flowdrop_agent\Service\ToolRegistry $toolRegistry
   *   The tool registry.
   * @param \Drupal\flowdrop_ai\Adapter\ToolCallingAdapterFactory $adapterFactory
   *   The LLM adapter factory.
   * @param \Drupal\flowdrop_conversation\Service\ConversationManager $conversationManager
   *   The conversation manager.
   * @param \Drupal\flowdrop_runtime\Service\Runtime\NodeRuntimeService $nodeRuntime
   *   The node runtime service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    private readonly ToolRegistry $toolRegistry,
    private readonly ToolCallingAdapterFactory $adapterFactory,
    private readonly ConversationManager $conversationManager,
    private readonly NodeRuntimeService $nodeRuntime,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {
    $this->logger = $loggerFactory->get("flowdrop_agent");
  }

  /**
   * Executes an Agent node.
   *
   * @param string $executionId
   *   The overall workflow execution ID.
   * @param string $agentNodeId
   *   The Agent node ID.
   * @param array<string, mixed> $inputData
   *   Input data including "prompt" and optional "systemPrompt".
   * @param array<string, mixed> $config
   *   Agent configuration (model, maxIterations, etc.).
   * @param \Drupal\flowdrop_workflow\DTO\WorkflowDTO $workflow
   *   The full workflow DTO.
   * @param string $parentPipelineId
   *   Parent pipeline ID.
   * @param \Drupal\flowdrop_runtime\DTO\Compiler\DependencyGraph|null $dependencyGraph
   *   The dependency graph for tool discovery and data resolution.
   * @param \Drupal\flowdrop_runtime\DTO\Runtime\NodeExecutionContext|null $executionContext
   *   The execution context with node outputs.
   *
   * @return \Drupal\flowdrop_agent\DTO\AgentTrace
   *   The complete execution trace.
   *
   * @throws \Drupal\flowdrop_agent\Exception\AgentException
   *   If the agent execution fails critically.
   */
  public function execute(
    string $executionId,
    string $agentNodeId,
    array $inputData,
    array $config,
    WorkflowDTO $workflow,
    string $parentPipelineId,
    ?DependencyGraph $dependencyGraph = NULL,
    ?NodeExecutionContext $executionContext = NULL,
  ): AgentTrace {
    $startTime = microtime(TRUE);

    // Extract configuration.
    $maxIterations = $config["maxIterations"] ?? 10;
    $modelId = $config["model"] ?? "gpt-4";
    $systemPrompt = $config["systemPrompt"] ?? "You are a helpful assistant.";
    $temperature = $config["temperature"] ?? 0.7;
    $maxTokens = $config["maxTokens"] ?? 1000;

    $this->logger->info("Starting agent execution @id with model @model", [
      "@id" => $agentNodeId,
      "@model" => $modelId,
      "execution_id" => $executionId,
    ]);

    // Discover available tools.
    // Prefer using DependencyGraph when available.
    if ($dependencyGraph !== NULL) {
      $tools = $this->toolRegistry->discoverToolsFromGraph($dependencyGraph, $agentNodeId);
    }
    else {
      $tools = $this->toolRegistry->discoverTools($workflow, $agentNodeId);
    }

    // Initialize or load conversation.
    $conversation = $this->initializeConversation($inputData, $systemPrompt);

    // Initialize agent state.
    $state = AgentState::initialize(
      $executionId,
      $conversation->getConversationId(),
      $maxIterations
    );

    // Initialize trace.
    $trace = new AgentTrace(
      executionId: $executionId,
      agentNodeId: $agentNodeId,
      availableTools: $tools,
    );

    // Dispatch start event.
    $this->eventDispatcher->dispatch(
      new GenericEvent($trace, [
        "execution_id" => $executionId,
        "agent_node_id" => $agentNodeId,
        "tools" => array_map(fn(ToolDefinition $t) => $t->getName(), $tools),
      ]),
      "flowdrop.agent.started"
    );

    // Get LLM adapter.
    $adapter = $this->adapterFactory->getAdapter($modelId);

    // ReAct loop.
    $stepNumber = 0;
    while (!$state->isComplete() && !$state->hasReachedMaxIterations()) {
      $iterationStart = microtime(TRUE);

      $this->logger->debug("Agent iteration @iter starting", [
        "@iter" => $state->getCurrentIteration() + 1,
        "execution_id" => $executionId,
      ]);

      // Step 1: Call LLM with conversation history and tools.
      $llmResponse = $adapter->callWithTools(
        messages: $conversation->getMessagesForLlm(),
        tools: $tools,
        model: $modelId,
        temperature: $temperature,
        maxTokens: $maxTokens,
      );

      $llmDurationMs = (microtime(TRUE) - $iterationStart) * 1000;

      // Add LLM call to trace.
      $trace->addStep(TraceStep::llmCall(
        stepNumber: $stepNumber++,
        input: ["messages_count" => $conversation->getMessageCount()],
        output: $llmResponse->toArray(),
        tokensUsed: $llmResponse->getTotalTokens(),
        durationMs: $llmDurationMs,
      ));

      $state = $state->addTokensUsed($llmResponse->getTotalTokens());

      // Step 2: Check if LLM wants to call a tool.
      if ($llmResponse->wantsToolCalls()) {
        $toolCall = $llmResponse->getFirstToolCall();

        if ($toolCall === NULL) {
          // Unexpected state - has tool_calls
          // finish reason but no actual calls.
          $this->logger->warning("LLM signaled tool_calls but none found");
          break;
        }

        // Add assistant message with tool call to conversation.
        $conversation = $conversation->addAssistantMessage(
          $llmResponse->getContent() ?? "",
          $toolCall
        );
        $state = $state->recordToolCall($toolCall);

        $this->logger->debug("Agent calling tool @tool", [
          "@tool" => $toolCall->getToolName(),
          "execution_id" => $executionId,
        ]);

        // Dispatch tool call event.
        $this->eventDispatcher->dispatch(
          new GenericEvent($toolCall, [
            "execution_id" => $executionId,
            "agent_node_id" => $agentNodeId,
            "iteration" => $state->getCurrentIteration(),
          ]),
          "flowdrop.agent.tool_called"
        );

        // Step 3: Execute the tool.
        $toolResult = $this->executeTool(
          $toolCall,
          $tools,
          $executionId,
          $parentPipelineId,
          $dependencyGraph,
          $executionContext,
        );
        $state = $state->recordToolResult($toolResult);

        // Add tool result to trace.
        $trace->addStep(TraceStep::toolCall(
          stepNumber: $stepNumber++,
          input: $toolCall->toArray(),
          output: $toolResult->toArray(),
          durationMs: $toolResult->getExecutionTimeMs(),
          error: $toolResult->isError() ? $toolResult->getErrorMessage() : NULL,
        ));

        // Add tool result to conversation.
        $conversation = $conversation->addToolResult(
          $toolCall->getId(),
          $toolResult->getOutputForLlm()
        );

        // Dispatch tool completed event.
        $this->eventDispatcher->dispatch(
          new GenericEvent($toolResult, [
            "execution_id" => $executionId,
            "agent_node_id" => $agentNodeId,
          ]),
          "flowdrop.agent.tool_completed"
        );

        // Handle errors based on configuration.
        if ($toolResult->isError()) {
          $tool = $this->toolRegistry->getTool($tools, $toolCall->getToolName());
          if ($tool !== NULL && $tool->getOnError() === "fail") {
            throw ToolExecutionException::executionFailed(
              $toolCall->getToolName(),
              $tool->getNodeId(),
              $toolResult->getErrorMessage() ?? "Unknown error"
            );
          }
          // "return_to_agent" or "skip" - continue, error is in conversation.
        }
      }
      // Step 4: Check if LLM provided final answer.
      elseif ($llmResponse->isComplete()) {
        $finalAnswer = $llmResponse->getContent() ?? "";
        $state = $state->markComplete($finalAnswer);
        $conversation = $conversation->addAssistantMessage($finalAnswer);

        $trace->addStep(TraceStep::finalAnswer(
          stepNumber: $stepNumber++,
          output: ["answer" => $finalAnswer],
        ));

        $this->logger->info("Agent completed with answer", [
          "execution_id" => $executionId,
          "iterations" => $state->getCurrentIteration() + 1,
        ]);
      }
      else {
        // No tool call and no final answer - add response and continue.
        $content = $llmResponse->getContent() ?? "";
        $conversation = $conversation->addAssistantMessage($content);
      }

      // Save conversation state.
      $this->conversationManager->saveConversation($conversation);

      // Advance iteration.
      $state = $state->advanceIteration();

      // Dispatch iteration event.
      $this->eventDispatcher->dispatch(
        new GenericEvent($state->toArray(), [
          "execution_id" => $executionId,
          "agent_node_id" => $agentNodeId,
          "iteration" => $state->getCurrentIteration(),
        ]),
        "flowdrop.agent.iteration_completed"
      );
    }

    // Finalize trace.
    $totalTime = (microtime(TRUE) - $startTime) * 1000;

    if ($state->isComplete()) {
      $trace->complete(
        status: AgentTrace::STATUS_COMPLETED,
        finalAnswer: $state->getFinalAnswer(),
        totalIterations: $state->getCurrentIteration(),
        totalExecutionTimeMs: $totalTime,
      );
    }
    elseif ($state->hasReachedMaxIterations()) {
      $this->logger->warning("Agent reached max iterations @max", [
        "@max" => $maxIterations,
        "execution_id" => $executionId,
      ]);
      $trace->complete(
        status: AgentTrace::STATUS_MAX_ITERATIONS,
        finalAnswer: $state->getFinalAnswer(),
        totalIterations: $state->getCurrentIteration(),
        totalExecutionTimeMs: $totalTime,
      );
    }

    // Dispatch completed event.
    $this->eventDispatcher->dispatch(
      new GenericEvent($trace, [
        "execution_id" => $executionId,
        "agent_node_id" => $agentNodeId,
        "status" => $trace->getStatus(),
      ]),
      "flowdrop.agent.completed"
    );

    return $trace;
  }

  /**
   * Initializes or loads a conversation.
   *
   * @param array<string, mixed> $inputData
   *   The input data.
   * @param string $systemPrompt
   *   The system prompt.
   *
   * @return \Drupal\flowdrop_conversation\DTO\ConversationState
   *   The conversation state.
   */
  private function initializeConversation(
    array $inputData,
    string $systemPrompt,
  ): ConversationState {
    $conversationId = $inputData["conversationId"] ?? NULL;
    $prompt = $inputData["prompt"] ?? $inputData["data"] ?? "";

    // Try to load existing conversation.
    if ($conversationId !== NULL && is_string($conversationId)) {
      $existing = $this->conversationManager->loadConversation($conversationId);
      if ($existing !== NULL) {
        // Add the new user prompt.
        return $existing->addUserMessage($prompt);
      }
    }

    // Create new conversation.
    $conversation = $this->conversationManager->createConversation($systemPrompt);

    // Add initial user prompt.
    if (!empty($prompt)) {
      $conversation = $conversation->addUserMessage($prompt);
      $this->conversationManager->saveConversation($conversation);
    }

    return $conversation;
  }

  /**
   * Executes a tool (node) call.
   *
   * Merges LLM-provided arguments with data from upstream nodes
   * (via DependencyGraph data edges).
   *
   * @param \Drupal\flowdrop_conversation\DTO\ToolCall $toolCall
   *   The tool call to execute.
   * @param array<ToolDefinition> $tools
   *   Available tools.
   * @param string $executionId
   *   The execution ID.
   * @param string $parentPipelineId
   *   The parent pipeline ID.
   * @param \Drupal\flowdrop_runtime\DTO\Compiler\DependencyGraph|null $dependencyGraph
   *   The dependency graph for data resolution.
   * @param \Drupal\flowdrop_runtime\DTO\Runtime\NodeExecutionContext|null $executionContext
   *   The execution context with node outputs.
   *
   * @return \Drupal\flowdrop_agent\DTO\ToolResult
   *   The tool result.
   */
  private function executeTool(
    ToolCall $toolCall,
    array $tools,
    string $executionId,
    string $parentPipelineId,
    ?DependencyGraph $dependencyGraph = NULL,
    ?NodeExecutionContext $executionContext = NULL,
  ): ToolResult {
    $startTime = microtime(TRUE);

    $tool = $this->toolRegistry->getTool($tools, $toolCall->getToolName());
    if ($tool === NULL) {
      return ToolResult::error(
        $toolCall->getId(),
        $toolCall->getToolName(),
        "",
        "Tool '{$toolCall->getToolName()}' not found",
        0.0,
      );
    }

    try {
      // Resolve data inputs from DependencyGraph.
      $dataInputs = $this->resolveToolDataInputs(
        $tool->getNodeId(),
        $dependencyGraph,
        $executionContext
      );

      // Merge: LLM arguments take precedence over data edges.
      $mergedInputs = array_merge($dataInputs, $toolCall->getArguments());

      $this->logger->debug("Tool @tool inputs: @data from graph, @llm from LLM", [
        "@tool" => $toolCall->getToolName(),
        "@data" => count($dataInputs),
        "@llm" => count($toolCall->getArguments()),
        "execution_id" => $executionId,
      ]);

      // Create job for tracking.
      /** @var \Drupal\flowdrop_job\FlowDropJobInterface $job */
      $job = $this->entityTypeManager->getStorage("flowdrop_job")->create([
        "pipeline_id" => $parentPipelineId,
        "node_id" => $tool->getNodeId(),
        "label" => "Tool: {$tool->getName()}",
        "input_data" => $mergedInputs,
        "status" => "pending",
        "metadata" => [
          "tool_call_id" => $toolCall->getId(),
          "tool_name" => $tool->getName(),
          "execution_id" => $executionId,
          "is_agent_tool_call" => TRUE,
          "llm_arguments" => array_keys($toolCall->getArguments()),
          "data_inputs" => array_keys($dataInputs),
        ],
      ]);
      $job->save();

      // Create execution context for the tool call.
      $context = new NodeExecutionContext(
        workflowId: "agent_" . $executionId,
        pipelineId: "agent_" . $executionId,
        initialData: $mergedInputs
      );

      // Execute the node with raw arrays.
      $result = $this->nodeRuntime->executeNode(
        $executionId,
        $tool->getNodeId(),
        $tool->getNodeTypeId(),
        $mergedInputs,
        [],
        $context
      );

      $executionTime = (microtime(TRUE) - $startTime) * 1000;

      // Update job.
      $output = $result->getOutput();
      $job->markAsCompleted($output instanceof Output ? $output->toArray() : $output);
      $job->save();

      return ToolResult::success(
        $toolCall->getId(),
        $toolCall->getToolName(),
        $tool->getNodeId(),
        $output instanceof Output ? $output->toArray() : $output,
        $executionTime,
      );
    }
    catch (\Exception $e) {
      $executionTime = (microtime(TRUE) - $startTime) * 1000;

      $this->logger->error("Tool @tool execution failed: @error", [
        "@tool" => $toolCall->getToolName(),
        "@error" => $e->getMessage(),
        "execution_id" => $executionId,
      ]);

      // Handle based on tool's onError config.
      if ($tool->getOnError() === "skip") {
        return ToolResult::skipped(
          $toolCall->getId(),
          $toolCall->getToolName(),
          $tool->getNodeId(),
          $e->getMessage(),
        );
      }

      return ToolResult::error(
        $toolCall->getId(),
        $toolCall->getToolName(),
        $tool->getNodeId(),
        $e->getMessage(),
        $executionTime,
      );
    }
  }

  /**
   * Resolves data inputs for a tool node from DependencyGraph.
   *
   * Gets outputs from upstream nodes connected via data edges.
   *
   * @param string $toolNodeId
   *   The tool node ID.
   * @param \Drupal\flowdrop_runtime\DTO\Compiler\DependencyGraph|null $dependencyGraph
   *   The dependency graph.
   * @param \Drupal\flowdrop_runtime\DTO\Runtime\NodeExecutionContext|null $executionContext
   *   The execution context with node outputs.
   *
   * @return array<string, mixed>
   *   Data inputs resolved from upstream nodes.
   */
  private function resolveToolDataInputs(
    string $toolNodeId,
    ?DependencyGraph $dependencyGraph,
    ?NodeExecutionContext $executionContext,
  ): array {
    if ($dependencyGraph === NULL || $executionContext === NULL) {
      return [];
    }

    $dataInputs = [];

    // Get data parents from DependencyGraph.
    $dataParents = $dependencyGraph->getParents($toolNodeId, DependencyEdge::TYPE_DATA);

    foreach ($dataParents as $parent) {
      $parentNodeId = $parent["nodeId"];
      $parentOutput = $executionContext->getNodeOutput($parentNodeId);

      if ($parentOutput === NULL) {
        $this->logger->debug("No output found for data parent @parent of tool @tool", [
          "@parent" => $parentNodeId,
          "@tool" => $toolNodeId,
        ]);
        continue;
      }

      $outputArray = $parentOutput->toArray();
      $sourcePort = $parent["sourcePortName"] ?? "";
      $targetPort = $parent["targetPortName"] ?? "";

      if (!empty($sourcePort) && !empty($targetPort) && isset($outputArray[$sourcePort])) {
        // Map specific port to specific input.
        $dataInputs[$targetPort] = $outputArray[$sourcePort];
        $this->logger->debug("Resolved tool input @target from @parent.@source", [
          "@target" => $targetPort,
          "@parent" => $parentNodeId,
          "@source" => $sourcePort,
        ]);
      }
      else {
        // Merge all outputs.
        $dataInputs = array_merge($dataInputs, $outputArray);
        $this->logger->debug("Merged all outputs from @parent into tool inputs", [
          "@parent" => $parentNodeId,
        ]);
      }
    }

    return $dataInputs;
  }

}
