<?php

declare(strict_types=1);

namespace Drupal\flowdrop_ai\Exception;

/**
 * Exception thrown when an LLM API call fails.
 */
class LlmApiException extends \RuntimeException {

  /**
   * The provider that threw the error.
   *
   * @var string
   */
  protected string $provider;

  /**
   * The HTTP status code if applicable.
   *
   * @var int|null
   */
  protected ?int $statusCode;

  /**
   * The raw error response.
   *
   * @var array<string, mixed>
   */
  protected array $errorResponse;

  /**
   * Constructs a new LlmApiException.
   *
   * @param string $message
   *   The error message.
   * @param string $provider
   *   The provider name.
   * @param int|null $statusCode
   *   The HTTP status code.
   * @param array<string, mixed> $errorResponse
   *   The raw error response.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(
    string $message,
    string $provider = '',
    ?int $statusCode = NULL,
    array $errorResponse = [],
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, $statusCode ?? 0, $previous);
    $this->provider = $provider;
    $this->statusCode = $statusCode;
    $this->errorResponse = $errorResponse;
  }

  /**
   * Creates an exception for a rate limit error.
   *
   * @param string $provider
   *   The provider name.
   * @param int $retryAfter
   *   Seconds to wait before retry.
   *
   * @return self
   *   A new exception instance.
   */
  public static function rateLimited(string $provider, int $retryAfter = 60): self {
    return new self(
      "Rate limited by {$provider}. Retry after {$retryAfter} seconds.",
      $provider,
      429,
      ['retry_after' => $retryAfter]
    );
  }

  /**
   * Creates an exception for authentication failure.
   *
   * @param string $provider
   *   The provider name.
   *
   * @return self
   *   A new exception instance.
   */
  public static function authenticationFailed(string $provider): self {
    return new self(
      "Authentication failed for {$provider}. Check your API key.",
      $provider,
      401
    );
  }

  /**
   * Creates an exception for an invalid model.
   *
   * @param string $provider
   *   The provider name.
   * @param string $model
   *   The model ID.
   *
   * @return self
   *   A new exception instance.
   */
  public static function invalidModel(string $provider, string $model): self {
    return new self(
      "Model '{$model}' is not available for {$provider}.",
      $provider,
      400,
      ['model' => $model]
    );
  }

  /**
   * Creates an exception for a timeout.
   *
   * @param string $provider
   *   The provider name.
   * @param int $timeout
   *   The timeout in seconds.
   *
   * @return self
   *   A new exception instance.
   */
  public static function timeout(string $provider, int $timeout): self {
    return new self(
      "Request to {$provider} timed out after {$timeout} seconds.",
      $provider,
      408,
      ['timeout' => $timeout]
    );
  }

  /**
   * Gets the provider name.
   *
   * @return string
   *   The provider name.
   */
  public function getProvider(): string {
    return $this->provider;
  }

  /**
   * Gets the HTTP status code.
   *
   * @return int|null
   *   The status code or NULL.
   */
  public function getStatusCode(): ?int {
    return $this->statusCode;
  }

  /**
   * Gets the error response.
   *
   * @return array<string, mixed>
   *   The error response.
   */
  public function getErrorResponse(): array {
    return $this->errorResponse;
  }

  /**
   * Checks if this is a retryable error.
   *
   * @return bool
   *   TRUE if retryable.
   */
  public function isRetryable(): bool {
    return in_array($this->statusCode, [429, 500, 502, 503, 504], TRUE);
  }

}
