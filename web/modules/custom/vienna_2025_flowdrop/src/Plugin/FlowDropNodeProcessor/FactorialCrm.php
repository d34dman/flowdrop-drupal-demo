<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\factorial_crm\DTO\Lead;
use Drupal\factorial_crm\Service\FactorialCrm as FactorialCrmService;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Executor for Factrial CRM.
 */
#[FlowDropNodeProcessor(
  id: "factorial_crm",
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Factorial CRM"),
  type: "simple",
  supportedTypes: ["simple", "square", "default"],
  category: "output",
  description: "Submit to Factorial CRM",
  version: "1.0.0",
  tags: ["crm", "save", "output"]
)]
class FactorialCrm extends AbstractFlowDropNodeProcessor {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FactorialCrmService $crmService,
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
      $container->get('factorial_crm'),
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
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    try {
      $dataInput = $inputs->get("data");
      if ($dataInput === NULL || $dataInput === "") {
        $this->getLogger()->warning("No data provided to FactorialCrm processor");
        return [
          "success" => FALSE,
          "message" => "No data provided",
        ];
      }

      // Normalize input: handle both JSON strings and structured data
      $data = $this->normalizeDataInput($dataInput);
      if ($data === NULL || !is_array($data)) {
        $this->getLogger()->error("Failed to process data input");
        return [
          "success" => FALSE,
          "message" => "Invalid data input",
        ];
      }

      $lead = new Lead(
        firstName: $data["firstName"] ?? "",
        lastName: $data["lastName"] ?? "",
        company: $data["company"] ?? "",
        email: $data["email"] ?? "",
        drupalNickname: $data["drupalNickname"] ?? "",
        leadSource: $data["leadSource"] ?? "",
        message: $data["message"] ?? "",
        internalNote: $data["internalNote"] ?? "",
      );
      $this->crmService->submitLead($lead);
      return [
        "success" => TRUE,
        "message" => "ok",
      ];
    }
    catch (\Exception $exception) {
      $this->getLogger()->error(
        "Failed to submit data: @message",
        ["@message" => $exception->getMessage()]
      );
      return [
        "success" => FALSE,
        "message" => "Error",
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
  public function getConfigSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'nodeType' => [
          'type' => 'select',
          'title' => 'Node Type',
          'description' => 'Choose the visual representation for this node',
          'default' => 'simple',
          'enum' => ["simple", "square", "default"],
          'enumNames' => ["Simple", "Square", "Default"],
        ],
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'data' => [
          'type' => 'json',
          'title' => 'Data',
          'description' => 'Data to be submitted to CRM',
          'required' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputSchema(): array {
    return [];
  }

}
