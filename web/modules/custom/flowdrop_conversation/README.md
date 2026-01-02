# FlowDrop Conversation

Provides conversation history management for AI agents and chat interfaces in FlowDrop workflows.

## Overview

This module enables persistent conversation state management, which is essential for:

- AI agent memory across tool-calling iterations
- Chat interfaces with conversation history
- Multi-turn dialogue systems

## Components

### DTOs

- **Message**: Represents a single message (user, assistant, system, or tool)
- **ToolCall**: Represents a tool call request from an LLM
- **ConversationState**: Manages complete conversation state with all messages

### Services

- **ConversationManager**: Create, load, save, and modify conversations

### Node Processors

- **ConversationHistory**: Node processor for managing conversation state in workflows

## Usage

### Creating a Conversation

```php
$conversationManager = \Drupal::service('flowdrop_conversation.manager');

// Create with optional system prompt
$conversation = $conversationManager->createConversation(
  'You are a helpful assistant.'
);

echo $conversation->getConversationId(); // conv_abc123...
```

### Adding Messages

```php
// Add a user message
$conversation = $conversationManager->addMessage(
  $conversationId,
  'user',
  'What is the weather today?'
);

// Add an assistant message
$conversation = $conversationManager->addMessage(
  $conversationId,
  'assistant',
  'I can help you with that. Let me check the weather.'
);

// Add a tool result
$conversation = $conversationManager->addMessage(
  $conversationId,
  'tool',
  json_encode(['temperature' => 72, 'condition' => 'sunny']),
  ['toolCallId' => 'call_xyz789']
);
```

### Getting History for LLM

```php
// Get full history
$messages = $conversationManager->getHistoryForLlm($conversationId);

// Get recent history with sliding window
$recentMessages = $conversationManager->getRecentHistoryForLlm(
  $conversationId,
  windowSize: 20,
  keepSystem: true
);
```

### Using in Workflows

The `ConversationHistory` node supports these actions:

| Action | Description |
|--------|-------------|
| `create` | Create a new conversation |
| `get` | Retrieve conversation messages |
| `add` | Add a message to conversation |
| `clear` | Clear history (keep system prompt) |
| `delete` | Delete entire conversation |
| `get_or_create` | Get existing or create new |

## Configuration

### Node Configuration

```yaml
strategy: 'full'      # 'full' or 'window'
windowSize: 20        # Messages to keep in window mode
systemPrompt: ''      # Default system prompt
```

## Events

| Event | Description |
|-------|-------------|
| `flowdrop.conversation.created` | Conversation created |
| `flowdrop.conversation.deleted` | Conversation deleted |
| `flowdrop.conversation.message_added` | Message added |

## Dependencies

- flowdrop
- flowdrop_node_type
- flowdrop_node_processor

