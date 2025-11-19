<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ConfigInterface;
use Drupal\flowdrop\DTO\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Receives and processes form submission data.
 *
 * This node acts as the entry point for form submissions, extracting
 * and structuring form data for further processing in the workflow.
 */
#[FlowDropNodeProcessor(
  id: "form_data_receiver",
  label: new TranslatableMarkup("Form Data Receiver"),
  type: "tool",
  supportedTypes: ["tool", "default"],
  category: "inputs",
  description: "Receive and process form submission data from contact forms",
  version: "1.0.0",
  tags: ["form", "input", "contact", "submission"]
)]
class FormDataReceiver extends AbstractFlowDropNodeProcessor {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
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
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('flowdrop_demo');
  }

  /**
   * {@inheritdoc}
   */
  protected function process(InputInterface $inputs, ConfigInterface $config): array {
    $formId = $config->getConfig('formId', 'contact_form');
    $requiredFields = $config->getConfig('requiredFields', ['name', 'email', 'message']);
    $validateEmail = $config->getConfig('validateEmail', TRUE);

    // Extract form data from inputs.
    $formData = $inputs->get('form_data', []);
    $submissionId = $inputs->get('submission_id', uniqid('form_'));

    // Validate and structure the form data.
    $processedData = $this->validateAndStructureFormData($formData, $requiredFields, $validateEmail);

    $this->getLogger()->info('Form data received and processed', [
      'form_id' => $formId,
      'submission_id' => $submissionId,
      'fields_count' => count($processedData['structured_data']),
      'validation_status' => $processedData['is_valid'] ? 'valid' : 'invalid',
    ]);

    return [
      'submission_id' => $submissionId,
      'form_id' => $formId,
      'raw_data' => $formData,
      'structured_data' => $processedData['structured_data'],
      'validation_results' => $processedData['validation_results'],
      'is_valid' => $processedData['is_valid'],
      'received_at' => date('Y-m-d H:i:s'),
      'processing_metadata' => [
    // Simulated.
        'ip_address' => '192.168.1.100',
    // Simulated.
        'user_agent' => 'Mozilla/5.0...',
        'referrer' => 'https://example.com/contact',
      ],
    ];
  }

  /**
   * Validate and structure form data.
   */
  private function validateAndStructureFormData(array $formData, array $requiredFields, bool $validateEmail): array {
    $structuredData = [];
    $validationResults = [];
    $isValid = TRUE;

    // Process each form field.
    foreach ($formData as $fieldName => $fieldValue) {
      $fieldValidation = [
        'field' => $fieldName,
        'value' => $fieldValue,
        'is_valid' => TRUE,
        'errors' => [],
      ];

      // Check if required field is present and not empty.
      if (in_array($fieldName, $requiredFields)) {
        if (empty($fieldValue)) {
          $fieldValidation['is_valid'] = FALSE;
          $fieldValidation['errors'][] = 'Required field is empty';
          $isValid = FALSE;
        }
      }

      // Validate email field.
      if ($fieldName === 'email' && $validateEmail && !empty($fieldValue)) {
        if (!filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
          $fieldValidation['is_valid'] = FALSE;
          $fieldValidation['errors'][] = 'Invalid email format';
          $isValid = FALSE;
        }
      }

      // Structure the data based on field type.
      $structuredData[$fieldName] = $this->structureFieldData($fieldName, $fieldValue);
      $validationResults[$fieldName] = $fieldValidation;
    }

    // Check for missing required fields.
    foreach ($requiredFields as $requiredField) {
      if (!isset($formData[$requiredField])) {
        $validationResults[$requiredField] = [
          'field' => $requiredField,
          'value' => NULL,
          'is_valid' => FALSE,
          'errors' => ['Required field is missing'],
        ];
        $isValid = FALSE;
      }
    }

    return [
      'structured_data' => $structuredData,
      'validation_results' => $validationResults,
      'is_valid' => $isValid,
    ];
  }

  /**
   * Structure field data based on field type.
   */
  private function structureFieldData(string $fieldName, $fieldValue): array {
    $structured = [
      'raw_value' => $fieldValue,
      'processed_value' => $fieldValue,
      'field_type' => $this->detectFieldType($fieldName, $fieldValue),
      'metadata' => [],
    ];

    // Add field-specific processing.
    switch ($fieldName) {
      case 'message':
        $structured['metadata'] = [
          'word_count' => str_word_count((string) $fieldValue),
          'character_count' => strlen((string) $fieldValue),
          'has_urls' => preg_match('/https?:\/\//', (string) $fieldValue) ? TRUE : FALSE,
        ];
        break;

      case 'phone':
        $structured['processed_value'] = preg_replace('/[^0-9+\-\(\)\s]/', '', (string) $fieldValue);
        break;

      case 'name':
        $structured['metadata'] = [
          'parts' => explode(' ', trim((string) $fieldValue)),
          'is_complete' => strpos((string) $fieldValue, ' ') !== FALSE,
        ];
        break;
    }

    return $structured;
  }

  /**
   * Detect field type based on name and content.
   */
  private function detectFieldType(string $fieldName, $fieldValue): string {
    $fieldName = strtolower($fieldName);

    if (in_array($fieldName, ['email', 'email_address'])) {
      return 'email';
    }
    if (in_array($fieldName, ['phone', 'telephone', 'mobile'])) {
      return 'phone';
    }
    if (in_array($fieldName, ['message', 'comment', 'description'])) {
      return 'textarea';
    }
    if (in_array($fieldName, ['name', 'first_name', 'last_name'])) {
      return 'name';
    }
    if (in_array($fieldName, ['subject', 'title'])) {
      return 'subject';
    }
    if (in_array($fieldName, ['type', 'category', 'department'])) {
      return 'select';
    }

    return 'text';
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Validate that we have form data.
    if (!isset($inputs['form_data'])) {
      return FALSE;
    }

    // Form data should be an array.
    if (!is_array($inputs['form_data'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'tool' => [
          'type' => 'tool',
          'title' => 'Tool',
          'description' => 'Available Tools',
        ],
        'form_data' => [
          'type' => 'object',
          'title' => 'Form Data',
          'description' => 'Raw form submission data',
          'required' => TRUE,
        ],
        'submission_id' => [
          'type' => 'string',
          'title' => 'Submission ID',
          'description' => 'Unique identifier for this submission',
          'required' => FALSE,
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
        'tool' => [
          'type' => 'tool',
          'title' => 'Tool',
          'description' => 'Available tools',
        ],
        'submission_id' => [
          'type' => 'string',
          'description' => 'Unique submission identifier',
        ],
        'form_id' => [
          'type' => 'string',
          'description' => 'Form identifier',
        ],
        'raw_data' => [
          'type' => 'object',
          'description' => 'Original form data as submitted',
        ],
        'structured_data' => [
          'type' => 'object',
          'description' => 'Processed and structured form data',
        ],
        'validation_results' => [
          'type' => 'object',
          'description' => 'Validation results for each field',
        ],
        'is_valid' => [
          'type' => 'boolean',
          'description' => 'Whether the form data is valid',
        ],
        'received_at' => [
          'type' => 'string',
          'description' => 'Timestamp when form was received',
        ],
        'processing_metadata' => [
          'type' => 'object',
          'description' => 'Additional metadata about the submission',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'nodeType' => [
          'type' => 'select',
          'title' => 'Node Type',
          'description' => 'Choose the visual representation for this node',
          'default' => 'tool',
          'enum' => ["tool", "default"],
          'enumNames' => ["Tool Node (with metadata port)", "Default Node (standard ports)"],
        ],
        'formId' => [
          'type' => 'string',
          'title' => 'Form ID',
          'description' => 'Identifier for the form type',
          'default' => 'contact_form',
        ],
        'requiredFields' => [
          'type' => 'array',
          'title' => 'Required Fields',
          'description' => 'List of required form fields',
          'items' => [
            'type' => 'string',
          ],
          'default' => ['name', 'email', 'message'],
        ],
        'validateEmail' => [
          'type' => 'boolean',
          'title' => 'Validate Email',
          'description' => 'Whether to validate email field format',
          'default' => TRUE,
        ],
      ],
    ];
  }

}
