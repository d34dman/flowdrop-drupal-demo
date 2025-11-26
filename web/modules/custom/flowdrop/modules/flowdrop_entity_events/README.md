# FlowDrop Entity Events

Trigger FlowDrop workflows automatically on Drupal content entity lifecycle events.

## Overview

This module provides native FlowDrop integration with Drupal's entity system, allowing workflows to be triggered automatically when entities are created, updated, deleted, or undergo other lifecycle events.

## Features

- **Content Entity Trigger Node**: Drag-and-drop trigger node for the FlowDrop visual editor
- **Event Types**: Support for all entity lifecycle events:
  - `insert` - After new entity is saved
  - `update` - After existing entity is updated
  - `presave` - Before entity is saved (can modify entity)
  - `predelete` - Before entity is deleted
  - `delete` - After entity is deleted
  - `load` - When entity is loaded
  - `view` - When entity is rendered
  - `translation_*` - Translation lifecycle events
- **Wildcard Filtering**: Flexible entity/bundle filtering
  - `*` - All entities
  - `node` - All nodes
  - `node::article` - Specific bundle
- **Auto-triggering**: Workflows start automatically when events occur
- **Execution Modes**: Synchronous (immediate) or asynchronous (queue-based)
- **Comprehensive Data**: Full entity data passed to workflows

## Installation

```bash
# Enable the module
ddev drush en flowdrop_entity_events -y

# Import configuration
ddev drush cim -y

# Clear cache
ddev drush cr
```

## Usage

### 1. Create a Workflow with Content Entity Trigger

1. Navigate to **Structure > FlowDrop Workflows**
2. Create or edit a workflow
3. Drag **Content Entity Trigger** node from the **Triggers** category
4. Configure the trigger:
   - **Event Type**: Choose when to trigger (insert, update, etc.)
   - **Entity Filter**: Specify which entities to watch (`*`, `node`, `node::article`)
   - **Auto-trigger**: Enable to automatically start workflow
   - **Execution Mode**: Choose async (recommended) or sync

### 2. Connect Workflow Nodes

The Content Entity Trigger outputs comprehensive entity data:

```
Content Entity Trigger
  ↓ entity         (Full entity data)
  ↓ entity_id      (Entity ID)
  ↓ entity_type    (node, user, etc.)
  ↓ bundle         (article, page, etc.)
  ↓ label          (Entity title/name)
  ↓ event_type     (insert, update, etc.)
  ↓ changed_fields (For update events)
```

Connect these outputs to downstream nodes for processing.

### 3. Example Workflows

**Workflow 1: Auto-generate SEO meta tags when article is created**

```
Content Entity Trigger (insert, node::article)
  ↓ entity
AI Content Analyzer
  ↓ seo_data
Node Meta Tag Updater
```

**Workflow 2: Send notification when user is updated**

```
Content Entity Trigger (update, user)
  ↓ entity, changed_fields
Condition: Check if email changed
  ↓ true
Send Email Notification
```

**Workflow 3: Audit trail for all content changes**

```
Content Entity Trigger (update, node)
  ↓ entity, original_entity, changed_fields
Log Changes to Database
```

## Architecture

### Components

- **ContentEntityTrigger**: FlowDrop processor plugin (trigger node)
- **EntityEventSubscriber**: Subscribes to Drupal entity events
- **EntityDataExtractor**: Extracts entity data for workflows
- **WorkflowMatcher**: Finds workflows matching entity events

### Event Flow

```
Drupal Entity Event (e.g., node saved)
         ↓
  EntityEventSubscriber
         ↓
  WorkflowMatcher (find matching workflows)
         ↓
  Create FlowDrop Pipeline
         ↓
  Generate Jobs & Execute
         ↓
  Workflow Runs
```

## Configuration

### Auto-trigger Configuration

Auto-triggering must be explicitly enabled in the trigger node configuration:

```yaml
auto_trigger: true
event_type: 'insert'
entity_filter: 'node::article'
execution_mode: 'async'
```

### Execution Modes

- **Async (Recommended)**: Workflows run in background queue
  - Non-blocking, scalable
  - Process with: `ddev drush queue:run flowdrop_runtime_pipeline_execution`

- **Sync**: Workflows run immediately
  - Blocks the request
  - Good for debugging, not recommended for production

## API Usage

### Programmatically Trigger Workflow

```php
use Drupal\flowdrop\DTO\Input;

$entity = \Drupal\node\Entity\Node::load(123);
$workflow = \Drupal::entityTypeManager()
  ->getStorage('flowdrop_workflow')
  ->load('my_workflow');

// Create pipeline
$pipeline = \Drupal::entityTypeManager()
  ->getStorage('flowdrop_pipeline')
  ->create([
    'workflow_id' => $workflow->id(),
    'status' => 'pending',
  ]);
$pipeline->save();

// Generate jobs
\Drupal::service('flowdrop_pipeline.job_generation')
  ->generateJobs($pipeline);

// Execute
\Drupal::service('flowdrop_runtime.asynchronous_orchestrator')
  ->startPipeline($pipeline);
```

### Extract Entity Data

```php
$extractor = \Drupal::service('flowdrop_entity_events.entity_data_extractor');
$entity = \Drupal\node\Entity\Node::load(123);
$data = $extractor->extractEntityData($entity);
// Returns: ['id' => 123, 'entity_type' => 'node', 'bundle' => 'article', ...]
```

### Check Entity Filter Match

```php
$matcher = \Drupal::service('flowdrop_entity_events.workflow_matcher');
$entity = \Drupal\node\Entity\Node::load(123);

$matches = $matcher->entityMatchesFilter($entity, 'node::article'); // true
$matches = $matcher->entityMatchesFilter($entity, 'node'); // true
$matches = $matcher->entityMatchesFilter($entity, '*'); // true
$matches = $matcher->entityMatchesFilter($entity, 'user'); // false
```

## Best Practices

1. **Use Async Mode**: Always use asynchronous execution in production
2. **Specific Filters**: Use specific filters (`node::article`) instead of wildcards (`*`) for better performance
3. **Event Selection**: Choose the right event:
   - `insert`: One-time setup for new entities
   - `update`: React to changes
   - `presave`: Modify entity before saving
4. **Error Handling**: Add error handling nodes in your workflows
5. **Logging**: Monitor workflow execution via FlowDrop logs

## Troubleshooting

### Workflow Not Triggering

1. **Check auto_trigger is enabled**
   ```bash
   ddev drush config:get flowdrop_workflow.flowdrop_workflow.YOUR_WORKFLOW
   ```

2. **Verify entity filter matches**
   - Use `*` to match all entities for testing
   - Check entity type and bundle names

3. **Check logs**
   ```bash
   ddev drush watchdog:show --type=flowdrop_entity_events
   ```

### Workflows Running Too Slow

1. **Use async mode** with queue workers
2. **Process queues** regularly:
   ```bash
   ddev drush queue:run flowdrop_runtime_pipeline_execution
   ```

3. **Optimize filters** to reduce false matches

## Development

### Running Tests

```bash
# PHPStan analysis
ddev exec phpstan analyse web/modules/contrib/flowdrop/modules/flowdrop_entity_events

# Drupal coding standards
ddev exec phpcs --standard=Drupal web/modules/contrib/flowdrop/modules/flowdrop_entity_events
```

### Debugging

Enable debug logging:

```php
// In settings.php or settings.local.php
$config['system.logging']['error_level'] = 'verbose';
```

Watch logs:
```bash
ddev drush watchdog:tail --extended
```

## Requirements

- Drupal: ^10 || ^11
- FlowDrop core modules:
  - flowdrop
  - flowdrop_workflow
  - flowdrop_pipeline
  - flowdrop_runtime
  - flowdrop_node_processor

## License

GPL-2.0-or-later

## Maintainers

FlowDrop development team
