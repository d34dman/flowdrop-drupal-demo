# FlowDrop Iterator Module

Provides the Iterator node type for looping over collections in FlowDrop workflows.

## Overview

The Iterator node implements a Langflow-style loop pattern that enables workflows to:
- Accept an array of items as input
- Process each item through a connected sub-workflow
- Aggregate results into a single output array

## Features

- **Sequential Iteration**: Items are processed one at a time in order
- **Sub-workflow Execution**: Connected nodes form a sub-workflow that runs for each item
- **Child Pipeline Creation**: Each Iterator creates a child Pipeline with Jobs for detailed tracking
- **Configurable Error Handling**: Choose to fail, skip, or retry on errors
- **Special Edge Support**: Uses `loopback` edge type for cycle-exempt connections

## Installation

Enable the module:

```bash
drush en flowdrop_iterator
```

## Usage

### Basic Iterator Flow

```
[Data Source] → [Iterator] → [Process Node] → [Iterator (loopback)] → [Output]
                    ↓                              ↑
                  (item)                      (loopback)
```

1. Connect a data source that outputs an array to the Iterator's `data` input
2. Connect the Iterator's `item` output to your processing node(s)
3. Connect the final processing node back to the Iterator's `loopback` input
4. Connect the Iterator's `done` output to downstream nodes

### Input Ports

| Port | Type | Description |
|------|------|-------------|
| `data` | array | Array of items to iterate over |
| `loopback` | mixed | Receives processed result from sub-workflow |

### Output Ports

| Port | Type | Description |
|------|------|-------------|
| `item` | mixed | Current item being processed |
| `done` | array | Aggregated results after all iterations |
| `index` | integer | Current iteration index (0-based) |
| `total` | integer | Total number of items |

### Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `maxIterations` | integer | 1000 | Maximum iterations (safety limit) |
| `onError` | string | "fail" | Error handling: "fail", "skip", "retry" |
| `maxRetries` | integer | 3 | Max retry attempts (when onError is "retry") |

## Special Edges

The Iterator uses a special edge type called `loopback` that:
- Is exempt from DAG (Directed Acyclic Graph) cycle detection
- Must be marked with `edgeType: "loopback"` in edge metadata
- Connects from the last sub-workflow node back to the Iterator

## Architecture

### Services

- **IteratorExecutor**: Main service that handles iteration execution
- **SubWorkflowDetector**: Detects nodes forming the iteration sub-workflow

### DTOs

- **IteratorState**: Tracks iteration state (items, index, results, status)
- **IterationResult**: Represents a single iteration's result

### Exceptions

- **IteratorException**: Base exception for iterator errors
- **IterationFailedException**: Thrown when a single iteration fails
- **SubWorkflowDetectionException**: Thrown when sub-workflow detection fails
- **MaxIterationsExceededException**: Warning when input exceeds max iterations

## Events

| Event | Description |
|-------|-------------|
| `flowdrop.iterator.started` | Iterator begins execution |
| `flowdrop.iterator.iteration_started` | Single iteration begins |
| `flowdrop.iterator.iteration_completed` | Single iteration completes |
| `flowdrop.iterator.completed` | All iterations complete |
| `flowdrop.iterator.failed` | Iterator fails |
| `flowdrop.iterator.max_exceeded` | Input exceeds max iterations |

## Dependencies

- flowdrop
- flowdrop_node_type
- flowdrop_runtime
- flowdrop_pipeline
- flowdrop_job
- flowdrop_workflow

## Future Extensions

This module provides the foundation for:
- Agent node patterns (tool calling loops)
- Other special edge types

