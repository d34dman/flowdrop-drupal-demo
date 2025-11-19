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
 * Performs find and replace operations on text content.
 *
 * This node takes text content and performs simple or advanced find/replace
 * operations, supporting regex patterns and case sensitivity options.
 */
#[FlowDropNodeProcessor(
  id: "text_find_replace",
  label: new TranslatableMarkup("Text Find & Replace"),
  type: "default",
  supportedTypes: ["default"],
  category: "processing",
  description: "Find and replace text in content with advanced options",
  version: "1.0.0",
  tags: ["text", "replace", "content", "processing"]
)]
class TextFindReplace extends AbstractFlowDropNodeProcessor {

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
    $findText = $config->getConfig('findText', '');
    $replaceText = $config->getConfig('replaceText', '');
    $caseSensitive = $config->getConfig('caseSensitive', FALSE);
    $useRegex = $config->getConfig('useRegex', FALSE);
    $wholeWordsOnly = $config->getConfig('wholeWordsOnly', FALSE);

    $inputContent = $inputs->get('content');
    $processedItems = [];
    $totalReplacements = 0;

    if (is_array($inputContent)) {
      // Process array of content items.
      foreach ($inputContent as $item) {
        $processed = $this->processTextItem($item, $findText, $replaceText, $caseSensitive, $useRegex, $wholeWordsOnly);
        $processedItems[] = $processed['item'];
        $totalReplacements += $processed['replacements'];
      }
    }
    else {
      // Process single text item.
      $processed = $this->processText((string) $inputContent, $findText, $replaceText, $caseSensitive, $useRegex, $wholeWordsOnly);
      $processedItems = $processed['text'];
      $totalReplacements = $processed['replacements'];
    }

    $this->getLogger()->info('Text find/replace completed', [
      'find_text' => $findText,
      'replace_text' => $replaceText,
      'total_replacements' => $totalReplacements,
      'items_processed' => is_array($inputContent) ? count($inputContent) : 1,
    ]);

    return [
      'processed_content' => $processedItems,
      'replacements_made' => $totalReplacements,
      'find_text' => $findText,
      'replace_text' => $replaceText,
      'processed_at' => date('Y-m-d H:i:s'),
    ];
  }

  /**
   * Process a single content item.
   */
  private function processTextItem(array $item, string $findText, string $replaceText, bool $caseSensitive, bool $useRegex, bool $wholeWordsOnly): array {
    $replacements = 0;
    $processedItem = $item;

    // Process title and body fields.
    foreach (['title', 'body'] as $field) {
      if (isset($item[$field])) {
        $result = $this->processText($item[$field], $findText, $replaceText, $caseSensitive, $useRegex, $wholeWordsOnly);
        $processedItem[$field] = $result['text'];
        $replacements += $result['replacements'];
      }
    }

    return [
      'item' => $processedItem,
      'replacements' => $replacements,
    ];
  }

  /**
   * Process text with find/replace logic.
   */
  private function processText(string $text, string $findText, string $replaceText, bool $caseSensitive, bool $useRegex, bool $wholeWordsOnly): array {
    $originalText = $text;
    $replacements = 0;

    if ($useRegex) {
      // Use regex replacement.
      $flags = $caseSensitive ? '' : 'i';
      $pattern = "/{$findText}/{$flags}";
      $processedText = preg_replace($pattern, $replaceText, $text, -1, $replacements);
    }
    else {
      // Use simple string replacement.
      if ($wholeWordsOnly) {
        $pattern = '/\b' . preg_quote($findText, '/') . '\b/';
        $pattern .= $caseSensitive ? '' : 'i';
        $processedText = preg_replace($pattern, $replaceText, $text, -1, $replacements);
      }
      else {
        if ($caseSensitive) {
          $processedText = str_replace($findText, $replaceText, $text, $replacements);
        }
        else {
          $processedText = str_ireplace($findText, $replaceText, $text, $replacements);
        }
      }
    }

    return [
      'text' => $processedText ?? $originalText,
      'replacements' => $replacements,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Validate that we have content to process.
    if (!isset($inputs['content'])) {
      return FALSE;
    }

    // Content can be string or array.
    if (!is_string($inputs['content']) && !is_array($inputs['content'])) {
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
        'content' => [
          'type' => 'mixed',
          'title' => 'Content to Process',
          'description' => 'Text content or array of content items to process',
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
        'processed_content' => [
          'type' => 'mixed',
          'description' => 'The processed content with replacements made',
        ],
        'replacements_made' => [
          'type' => 'integer',
          'description' => 'Total number of replacements made',
        ],
        'find_text' => [
          'type' => 'string',
          'description' => 'The text that was searched for',
        ],
        'replace_text' => [
          'type' => 'string',
          'description' => 'The replacement text used',
        ],
        'processed_at' => [
          'type' => 'string',
          'description' => 'Timestamp when processing completed',
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
        'findText' => [
          'type' => 'string',
          'title' => 'Find Text',
          'description' => 'Text to search for',
          'default' => 'XB',
        ],
        'replaceText' => [
          'type' => 'string',
          'title' => 'Replace Text',
          'description' => 'Text to replace with',
          'default' => 'Canvas',
        ],
        'caseSensitive' => [
          'type' => 'boolean',
          'title' => 'Case Sensitive',
          'description' => 'Whether the search should be case sensitive',
          'default' => FALSE,
        ],
        'useRegex' => [
          'type' => 'boolean',
          'title' => 'Use Regular Expressions',
          'description' => 'Treat find text as a regular expression',
          'default' => FALSE,
        ],
        'wholeWordsOnly' => [
          'type' => 'boolean',
          'title' => 'Whole Words Only',
          'description' => 'Only match whole words, not partial matches',
          'default' => TRUE,
        ],
      ],
    ];
  }

}
