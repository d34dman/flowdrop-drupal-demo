<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Executor for Factrial CRM.
 */
#[FlowDropNodeProcessor(
  id: "drupal_username_lookup",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Drupal Username Lookup"),
  description: "Lookup Drupal Username and fetch details about the user.",
  version: "1.0.0",
)]
#[AsTool(
  name: 'drupal_username_lookup',
  description: 'Lookup Drupal Username and fetch details about the user.',
  method: 'fetchDrupalOrgUserInfo',
)]
class DrupalUserNameLookup extends AbstractFlowDropNodeProcessor {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ClientFactory $httpClientFactory,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client_factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('flowdrop_node_processor');
  }

  /**
   * {@inheritdoc}
   */
  public function process(ParameterBagInterface $params): array {
    try {
      $username = $params->get("text");
      if (empty($username)) {
        $dataInput = $params->get("data");
        if ($dataInput !== NULL && $dataInput !== "") {
          // Normalize input: handle both JSON strings and structured data
          $data = $this->normalizeDataInput($dataInput);
          if ($data !== NULL && is_array($data)) {
            $username = $data["username"] ?? $data["nickname"] ?? $data["drupalNickname"] ?? $data["drupalUsername"] ?? NULL;
          }
        }
      }
      return $this->fetchDrupalOrgUserInfo($username);
    }
    catch (\Exception $exception) {
      return [
        'success' => FALSE,
        'message' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Normalize data input to array format.
   *
   * Handles both JSON strings and already-decoded arrays from data flow.
   *
   * @param mixed $dataInput
   *   The data input (string or array).
   *
   * @return array|null
   *   The decoded data array or NULL on error.
   */
  private function normalizeDataInput(mixed $dataInput): ?array {
    // If already an array, return as-is.
    if (is_array($dataInput)) {
      return $dataInput;
    }

    // If string, attempt to decode it.
    if (is_string($dataInput)) {
      $decoded = json_decode($dataInput, TRUE);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
      }
      $this->getLogger()->error("Failed to decode JSON: @error", [
        "@error" => json_last_error_msg(),
      ]);
      return NULL;
    }

    // For other types, return NULL.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Save to file nodes can accept any inputs or none.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'text' => [
          'type' => 'string',
          'title' => 'Username',
          'description' => 'Drupal.org user name',
          'required' => TRUE,
        ],
        'data' => [
          'type' => 'object',
          'title' => 'User Info',
          'description' => 'User Info in json format',
          'required' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'firstName' => [
          'type' => 'string',
          'title' => 'Firstname',
        ],
        'lastName' => [
          'type' => 'string',
          'title' => 'Lastname',
        ],
        'data' => [
          'type' => 'object',
          'title' => 'User Info',
        ],
      ],
    ];
  }


  /**
   * Fetches user information from Drupal.org API.
   *
   * @param string|null $username
   *   The Drupal.org username.
   *
   * @return array<string, string>|null
   *   User information array or NULL if not found.
   */
  private function fetchDrupalOrgUserInfo(?string $username): ?array {
    // Handle NULL or empty username
    if ($username === NULL || trim($username) === "") {
      return [
        "success" => FALSE,
        "message" => "No username provided",
        "firstName" => "",
        "lastName" => "",
        "data" => [],
      ];
    }
    try {
      // Fetch user data from Drupal.org API
      $url = "https://www.drupal.org/api-d7/user.json?name=" . urlencode($username);

      $client = $this->httpClientFactory->fromOptions();
      $response = $client->get($url);

      if ($response->getStatusCode() !== 200) {
        return NULL;
      }

      $body = json_decode($response->getBody()->getContents(), TRUE);
      if (empty($body["list"]) || !is_array($body["list"])) {
        return NULL;
      }
      $userData = reset($body["list"]);
      return [
        "firstName" => $userData["field_first_name"],
        "lastName" =>$userData["field_last_name"],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error(
        "Failed to fetch Drupal.org user info: @message",
        ["@message" => $e->getMessage()]
      );
      return NULL;
    }
  }

}
