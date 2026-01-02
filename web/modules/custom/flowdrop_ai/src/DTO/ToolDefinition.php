<?php

declare(strict_types=1);

namespace Drupal\flowdrop_ai\DTO;

/**
 * Definition of a tool available to an AI Agent.
 *
 * This DTO encapsulates all information needed to describe a tool to an LLM,
 * including its name, description, parameters schema, and associated metadata.
 */
final class ToolDefinition {

  /**
   * Constructs a new ToolDefinition object.
   *
   * @param string $name
   *   The tool name (used in LLM calls).
   * @param string $description
   *   Human-readable description of what the tool does.
   * @param string $nodeId
   *   The node ID that implements this tool.
   * @param string $nodeTypeId
   *   The node type ID.
   * @param array<string, mixed> $parametersSchema
   *   JSON Schema for tool parameters (from node input schema).
   * @param array<string, mixed> $returnSchema
   *   JSON Schema for tool return value (from node output schema).
   * @param string $onError
   *   Error handling strategy: 'return_to_agent', 'fail', or 'skip'.
   * @param array<string, mixed> $metadata
   *   Additional metadata.
   */
  public function __construct(
    private readonly string $name,
    private readonly string $description,
    private readonly string $nodeId,
    private readonly string $nodeTypeId,
    private readonly array $parametersSchema = [],
    private readonly array $returnSchema = [],
    private readonly string $onError = 'return_to_agent',
    private readonly array $metadata = [],
  ) {}

  /**
   * Creates a ToolDefinition from node information.
   *
   * @param string $nodeId
   *   The node ID.
   * @param string $nodeTypeId
   *   The node type ID.
   * @param string $label
   *   The node label.
   * @param string $description
   *   The node description.
   * @param array<string, mixed> $inputSchema
   *   The node input schema.
   * @param array<string, mixed> $outputSchema
   *   The node output schema.
   * @param array<string, mixed> $edgeOverrides
   *   Optional overrides from edge metadata.
   *
   * @return self
   *   A new ToolDefinition instance.
   */
  public static function fromNode(
    string $nodeId,
    string $nodeTypeId,
    string $label,
    string $description,
    array $inputSchema,
    array $outputSchema,
    array $edgeOverrides = [],
  ): self {
    // Apply edge overrides if provided.
    $name = $edgeOverrides['toolName'] ?? self::sanitizeName($label);
    $desc = $edgeOverrides['toolDescription'] ?? $description;
    $onError = $edgeOverrides['onError'] ?? 'return_to_agent';

    return new self(
      name: $name,
      description: $desc,
      nodeId: $nodeId,
      nodeTypeId: $nodeTypeId,
      parametersSchema: self::normalizeParametersSchema($inputSchema),
      returnSchema: $outputSchema,
      onError: $onError,
      metadata: $edgeOverrides,
    );
  }

  /**
   * Gets the tool name.
   *
   * @return string
   *   The tool name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Gets the tool description.
   *
   * @return string
   *   The description.
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * Gets the node ID.
   *
   * @return string
   *   The node ID.
   */
  public function getNodeId(): string {
    return $this->nodeId;
  }

  /**
   * Gets the node type ID.
   *
   * @return string
   *   The node type ID.
   */
  public function getNodeTypeId(): string {
    return $this->nodeTypeId;
  }

  /**
   * Gets the parameters schema.
   *
   * @return array<string, mixed>
   *   The parameters JSON Schema.
   */
  public function getParametersSchema(): array {
    return $this->parametersSchema;
  }

  /**
   * Gets the return schema.
   *
   * @return array<string, mixed>
   *   The return value JSON Schema.
   */
  public function getReturnSchema(): array {
    return $this->returnSchema;
  }

  /**
   * Gets the error handling strategy.
   *
   * @return string
   *   The error handling strategy.
   */
  public function getOnError(): string {
    return $this->onError;
  }

  /**
   * Gets the metadata.
   *
   * @return array<string, mixed>
   *   The metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Converts to OpenAI function format.
   *
   * @return array<string, mixed>
   *   The tool in OpenAI function calling format.
   */
  public function toOpenAiFunction(): array {
    return [
      'type' => 'function',
      'function' => [
        'name' => $this->name,
        'description' => $this->description,
        'parameters' => $this->parametersSchema ?: [
          'type' => 'object',
          'properties' => new \stdClass(),
        ],
      ],
    ];
  }

  /**
   * Converts to Anthropic tool format.
   *
   * @return array<string, mixed>
   *   The tool in Anthropic format.
   */
  public function toAnthropicTool(): array {
    return [
      'name' => $this->name,
      'description' => $this->description,
      'input_schema' => $this->parametersSchema ?: [
        'type' => 'object',
        'properties' => new \stdClass(),
      ],
    ];
  }

  /**
   * Converts to array format.
   *
   * @return array<string, mixed>
   *   The tool definition as array.
   */
  public function toArray(): array {
    return [
      'name' => $this->name,
      'description' => $this->description,
      'node_id' => $this->nodeId,
      'node_type_id' => $this->nodeTypeId,
      'parameters_schema' => $this->parametersSchema,
      'return_schema' => $this->returnSchema,
      'on_error' => $this->onError,
      'metadata' => $this->metadata,
    ];
  }

  /**
   * Creates from array format.
   *
   * @param array<string, mixed> $data
   *   The array data.
   *
   * @return self
   *   A new ToolDefinition instance.
   */
  public static function fromArray(array $data): self {
    return new self(
      name: $data['name'] ?? '',
      description: $data['description'] ?? '',
      nodeId: $data['node_id'] ?? '',
      nodeTypeId: $data['node_type_id'] ?? '',
      parametersSchema: $data['parameters_schema'] ?? [],
      returnSchema: $data['return_schema'] ?? [],
      onError: $data['on_error'] ?? 'return_to_agent',
      metadata: $data['metadata'] ?? [],
    );
  }

  /**
   * Sanitizes a label to a valid tool name.
   *
   * @param string $label
   *   The label to sanitize.
   *
   * @return string
   *   A valid tool name (snake_case, alphanumeric).
   */
  private static function sanitizeName(string $label): string {
    // Convert to lowercase.
    $name = strtolower($label);
    // Replace non-alphanumeric with underscore.
    $name = preg_replace('/[^a-z0-9]+/', '_', $name);
    // Remove leading/trailing underscores.
    $name = trim($name, '_');
    // Ensure not empty.
    return $name ?: 'unnamed_tool';
  }

  /**
   * Normalizes input schema to parameters format.
   *
   * @param array<string, mixed> $inputSchema
   *   The node input schema.
   *
   * @return array<string, mixed>
   *   Normalized parameters schema.
   */
  private static function normalizeParametersSchema(array $inputSchema): array {
    // If already in correct format, return as-is.
    if (isset($inputSchema['type']) && $inputSchema['type'] === 'object') {
      return $inputSchema;
    }

    // Wrap properties if needed.
    if (isset($inputSchema['properties'])) {
      return [
        'type' => 'object',
        'properties' => $inputSchema['properties'],
        'required' => $inputSchema['required'] ?? [],
      ];
    }

    // Return as object type with the schema as properties.
    return [
      'type' => 'object',
      'properties' => $inputSchema,
    ];
  }

}
