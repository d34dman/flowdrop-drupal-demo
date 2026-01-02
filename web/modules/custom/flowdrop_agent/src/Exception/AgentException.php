<?php

declare(strict_types=1);

namespace Drupal\flowdrop_agent\Exception;

/**
 * Base exception for Agent-related errors.
 */
class AgentException extends \RuntimeException {

  /**
   * Creates exception for max iterations reached.
   *
   * @param string $agentNodeId
   *   The agent node ID.
   * @param int $maxIterations
   *   The max iterations limit.
   *
   * @return self
   *   A new exception instance.
   */
  public static function maxIterationsReached(string $agentNodeId, int $maxIterations): self {
    return new self(
      "Agent '{$agentNodeId}' reached maximum iterations ({$maxIterations})."
    );
  }

  /**
   * Creates exception for tool not found.
   *
   * @param string $toolName
   *   The tool name.
   * @param string $agentNodeId
   *   The agent node ID.
   *
   * @return self
   *   A new exception instance.
   */
  public static function toolNotFound(string $toolName, string $agentNodeId): self {
    return new self(
      "Tool '{$toolName}' not found for agent '{$agentNodeId}'."
    );
  }

  /**
   * Creates exception for no tools available.
   *
   * @param string $agentNodeId
   *   The agent node ID.
   *
   * @return self
   *   A new exception instance.
   */
  public static function noToolsAvailable(string $agentNodeId): self {
    return new self(
      "No tools available for agent '{$agentNodeId}'. Connect tools via tool_availability edges."
    );
  }

}
