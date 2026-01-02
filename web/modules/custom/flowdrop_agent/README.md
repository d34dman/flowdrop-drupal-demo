# FlowDrop Agent

Provides LLM-powered Agent nodes for agentic workflows with dynamic tool calling.

## Overview

This module enables AI agents that can autonomously decide which tools to use based on user prompts. It implements the ReAct (Reasoning + Acting) pattern:

1. User sends a prompt
2. Agent (LLM) analyzes the prompt and available tools
3. If tools are needed, agent calls them and processes results
4. Repeat until agent has enough information
5. Agent provides final answer

## Components

### DTOs

- **AgentState**: Tracks iteration count, tool calls, and completion status
- **AgentTrace**: Complete execution trace including all steps
- **ToolResult**: Result of a tool execution
- **TraceStep**: Individual step in the execution trace

### Services

- **ToolRegistry**: Discovers tools from workflow edges
- **AgentExecutor**: Orchestrates the ReAct loop

### Node Processors

- **Agent**: The main Agent node processor

## Usage

### Creating an Agent Workflow

```yaml
nodes:
  - id: agent_1
    type: agent
    config:
      model: gpt-4
      systemPrompt: "You are a helpful assistant."
      maxIterations: 10
      
  - id: weather_tool
    type: http_request
    config:
      url: "https://api.weather.com/v1/current"
      
edges:
  # Connect tool to agent via tool_availability edge
  - source: agent_1
    target: weather_tool
    data:
      metadata:
        edgeType: tool_availability
        toolName: get_weather
        toolDescription: "Get current weather"
        onError: return_to_agent
```

### Programmatic Execution

```php
$executor = \Drupal::service('flowdrop_agent.executor');

$trace = $executor->execute(
  executionId: 'exec_123',
  agentNodeId: 'agent_1',
  inputData: ['prompt' => 'What is the weather in NYC?'],
  config: [
    'model' => 'gpt-4',
    'maxIterations' => 10,
  ],
  workflow: $workflowDTO,
  parentPipelineId: 'pipeline_abc',
);

$answer = $trace->getFinalAnswer();
$iterations = $trace->getTotalIterations();
$tokensUsed = $trace->getTotalTokensUsed();
```

## Configuration

### Agent Node Config

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `model` | string | `gpt-4` | LLM model to use |
| `systemPrompt` | string | - | System prompt for the agent |
| `maxIterations` | int | 10 | Maximum tool-calling iterations |
| `temperature` | float | 0.7 | LLM temperature (0-2) |
| `maxTokens` | int | 1000 | Max tokens per LLM call |

### Tool Edge Metadata

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `toolName` | string | from node | Override tool name |
| `toolDescription` | string | from node | Override description |
| `onError` | string | `return_to_agent` | Error handling strategy |

### Error Handling Strategies

- `return_to_agent`: Pass error back to LLM to decide next action
- `fail`: Stop execution and fail the workflow
- `skip`: Skip the tool and continue

## Events

| Event | When |
|-------|------|
| `flowdrop.agent.started` | Agent execution begins |
| `flowdrop.agent.tool_called` | Before tool execution |
| `flowdrop.agent.tool_completed` | After tool execution |
| `flowdrop.agent.iteration_completed` | After each iteration |
| `flowdrop.agent.completed` | Agent execution ends |

## Supported Models

### OpenAI
- gpt-4, gpt-4-turbo, gpt-4o, gpt-4o-mini, gpt-3.5-turbo

### Anthropic
- claude-3-opus, claude-3-sonnet, claude-3-haiku, claude-3.5-sonnet

## Dependencies

- flowdrop
- flowdrop_node_type
- flowdrop_node_processor
- flowdrop_runtime
- flowdrop_workflow
- flowdrop_pipeline
- flowdrop_job
- flowdrop_ai
- flowdrop_conversation

