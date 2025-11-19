# FlowDrop Demo Module

This demo module showcases the practical capabilities of FlowDrop through three comprehensive workflow scenarios that demonstrate real-world use cases.

## Demo Scenarios

### 1. Site-wide Content Replace (XB → Canvas)
**Workflow**: `demo_content_replace`

This workflow demonstrates bulk content processing capabilities:

- **Content Loader**: Fetches all blog posts from the site
- **Text Find & Replace**: Performs intelligent find/replace operations (XB → Canvas)

**Use Case**: Rebranding scenarios where you need to update terminology across your entire content library.

### 2. AI-Powered Content Enhancement
**Workflow**: `demo_ai_content`

This workflow shows intelligent content processing with AI:

- **Content Loader**: Loads content for analysis
- **AI Content Analyzer**: Uses AI to understand context (distinguishes "XB" as acronym vs. in sentences)
- **Text Find & Replace**: Applies AI-recommended changes based on analysis

**Use Case**: Smart content updates that require contextual understanding and human oversight.

### 3. Contact Form Triage & Scheduling
**Workflow**: `demo_form_triage`

This workflow demonstrates intelligent form processing and scheduling:

- **Form Data Receiver**: Processes contact form submissions
- **Content Classifier**: Automatically categorizes requests (support, features, sales)
- **Calendar Availability Checker**: Checks team member availability via Google Calendar
- **User Choice Presenter**: Presents meeting time options to the user

**Use Case**: Automated customer support triage with intelligent routing and scheduling.

## Custom Node Processors

### Content Management Nodes
- **ContentLoader**: Load content from Drupal with flexible filtering
- **TextFindReplace**: Advanced find/replace with regex and context options
- **AiContentAnalyzer**: AI-powered content analysis with context understanding

### Form Processing Nodes
- **FormDataReceiver**: Process and validate form submissions
- **ContentClassifier**: Classify content into categories with confidence scoring

### Calendar Integration Nodes
- **CalendarAvailabilityChecker**: Check Google Calendar availability
- **UserChoicePresenter**: Present options to users with multiple interaction modes

### Supporting Services
- **CalendarService**: Google Calendar API integration (simulated)
- **ContentService**: Drupal content operations
- **TriageService**: Content classification and team assignment logic

## Installation

1. **Enable the module** (this will also enable required dependencies):
   ```bash
   drush en flowdrop_demo
   ```

2. **Clear cache**:
   ```bash
   drush cr
   ```

The module will automatically install:
- **3 new node categories**: Content, Integrations, UI
- **7 custom node processors**: Content Loader, Text Find & Replace, AI Content Analyzer, Form Data Receiver, Content Classifier, Calendar Availability Checker, User Choice Presenter  
- **3 demo workflows**: Site-wide content replace, AI-powered content enhancement, Contact form triage & scheduling

## Viewing the Demos

After installation, you can view the demo workflows in the FlowDrop workflow editor:

1. Navigate to `/admin/structure/flowdrop-workflow`
2. Look for workflows starting with "Demo:"
3. Click "Open in Editor" to see the visual workflow

## Node Categories

The demo nodes are organized into logical categories:

- **content**: Content management and processing
- **ai**: AI-powered analysis and processing  
- **inputs**: Data input and form handling
- **processing**: Text and data processing
- **integrations**: External service integrations
- **ui**: User interface and interaction nodes

## Configuration Examples

### Content Loader Configuration
```yaml
contentType: 'blog_post'
status: 'published'
limit: 100
fields: ['title', 'body', 'author', 'created']
```

### AI Content Analyzer Configuration
```yaml
targetText: 'XB'
replacementText: 'Canvas'
analysisMode: 'context_aware'
confidenceThreshold: 0.8
```

### Calendar Availability Checker Configuration
```yaml
timeZone: 'America/New_York'
lookAheadDays: 7
meetingDuration: 30
workingHours:
  start: '09:00'
  end: '17:00'
```

## Key Features Demonstrated

### 1. **Rich Port Definitions**
Each node has well-defined input and output schemas that enable proper connections in the visual editor.

### 2. **Comprehensive Configuration**
Nodes include detailed configuration schemas with validation, defaults, and user-friendly interfaces.

### 3. **Realistic Data Flow**
The workflows demonstrate realistic data transformations and processing steps.

### 4. **Error Handling**
Nodes include proper error handling and logging for debugging workflows.

### 5. **Service Integration**
Shows how to integrate with external services (Google Calendar) and Drupal's entity system.

## Extending the Demo

You can extend these demos by:

1. **Adding new node processors** following the same patterns
2. **Creating additional workflow scenarios** for your specific use cases
3. **Integrating with real APIs** instead of simulated services
4. **Adding more sophisticated AI analysis** using actual ML services

## Technical Notes

- All nodes use proper PHP 8+ typing and modern Drupal patterns
- Service injection follows Drupal best practices
- Configuration schemas support the FlowDrop UI form generation
- Logging is implemented for debugging and monitoring
- The module is designed to work without the execution engine (for UI demonstration)

This demo module serves as both a showcase of FlowDrop's capabilities and a reference implementation for building custom workflow nodes.
