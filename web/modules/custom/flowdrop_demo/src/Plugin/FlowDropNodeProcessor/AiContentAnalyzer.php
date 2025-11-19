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
 * AI-powered content analyzer for smart text processing.
 *
 * This node uses AI to analyze content context and make intelligent
 * decisions about text replacements, understanding when "XB" is an
 * acronym vs part of a sentence.
 */
#[FlowDropNodeProcessor(
  id: "ai_content_analyzer",
  label: new TranslatableMarkup("AI Content Analyzer"),
  type: "tool",
  supportedTypes: ["tool", "default"],
  category: "ai",
  description: "AI-powered content analysis for smart text processing and context understanding",
  version: "1.0.0",
  tags: ["ai", "analysis", "content", "context", "smart-processing"]
)]
class AiContentAnalyzer extends AbstractFlowDropNodeProcessor {

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
    $targetText = $config->getConfig('targetText', 'XB');
    $replacementText = $config->getConfig('replacementText', 'Canvas');
    $analysisMode = $config->getConfig('analysisMode', 'context_aware');
    $confidence = $config->getConfig('confidenceThreshold', 0.8);

    $inputContent = $inputs->get('content');
    $analysisResults = [];
    $totalAnalyzed = 0;
    $totalReplacements = 0;

    if (is_array($inputContent)) {
      foreach ($inputContent as $item) {
        $result = $this->analyzeContentItem($item, $targetText, $replacementText, $analysisMode, $confidence);
        $analysisResults[] = $result;
        $totalAnalyzed++;
        $totalReplacements += $result['replacements_made'];
      }
    }
    else {
      $result = $this->analyzeText((string) $inputContent, $targetText, $replacementText, $analysisMode, $confidence);
      $analysisResults = [$result];
      $totalAnalyzed = 1;
      $totalReplacements = $result['replacements_made'];
    }

    $this->getLogger()->info('AI content analysis completed', [
      'target_text' => $targetText,
      'analysis_mode' => $analysisMode,
      'items_analyzed' => $totalAnalyzed,
      'total_replacements' => $totalReplacements,
    ]);

    return [
      'analyzed_content' => $analysisResults,
      'total_analyzed' => $totalAnalyzed,
      'total_replacements' => $totalReplacements,
      'analysis_mode' => $analysisMode,
      'confidence_threshold' => $confidence,
      'analyzed_at' => date('Y-m-d H:i:s'),
    ];
  }

  /**
   * Analyze a single content item.
   */
  private function analyzeContentItem(array $item, string $targetText, string $replacementText, string $analysisMode, float $confidence): array {
    $analyzedItem = $item;
    $replacementsMade = 0;
    $contextAnalysis = [];

    foreach (['title', 'body'] as $field) {
      if (isset($item[$field])) {
        $analysis = $this->analyzeText($item[$field], $targetText, $replacementText, $analysisMode, $confidence);
        $analyzedItem[$field] = $analysis['processed_text'];
        $replacementsMade += $analysis['replacements_made'];
        $contextAnalysis[$field] = $analysis['context_decisions'];
      }
    }

    return [
      'original_item' => $item,
      'processed_item' => $analyzedItem,
      'replacements_made' => $replacementsMade,
      'context_analysis' => $contextAnalysis,
      'confidence_scores' => $this->generateConfidenceScores($item, $targetText),
    ];
  }

  /**
   * Analyze text with AI-powered context understanding.
   */
  private function analyzeText(string $text, string $targetText, string $replacementText, string $analysisMode, float $confidence): array {
    $decisions = [];
    $processedText = $text;
    $replacementsMade = 0;

    // Find all occurrences of the target text.
    $pattern = '/\b' . preg_quote($targetText, '/') . '\b/i';
    preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

    foreach ($matches[0] as $match) {
      $matchText = $match[0];
      $offset = $match[1];

      // Analyze context around the match.
      $contextAnalysis = $this->analyzeContext($text, $offset, strlen($matchText), $analysisMode);

      $decision = [
        'text' => $matchText,
        'position' => $offset,
        'context' => $contextAnalysis,
        'should_replace' => $contextAnalysis['confidence'] >= $confidence,
        'confidence' => $contextAnalysis['confidence'],
        'reasoning' => $contextAnalysis['reasoning'],
      ];

      if ($decision['should_replace']) {
        $processedText = str_replace($matchText, $replacementText, $processedText);
        $replacementsMade++;
      }

      $decisions[] = $decision;
    }

    return [
      'original_text' => $text,
      'processed_text' => $processedText,
      'replacements_made' => $replacementsMade,
      'context_decisions' => $decisions,
    ];
  }

  /**
   * Analyze context around a text match.
   */
  private function analyzeContext(string $text, int $offset, int $length, string $analysisMode): array {
    $contextBefore = substr($text, max(0, $offset - 50), 50);
    $contextAfter = substr($text, $offset + $length, 50);
    $fullContext = $contextBefore . substr($text, $offset, $length) . $contextAfter;

    // Simulate AI analysis based on context patterns.
    // Default confidence.
    $confidence = 0.5;
    $reasoning = "Default analysis";

    switch ($analysisMode) {
      case 'acronym_detection':
        $confidence = $this->analyzeAcronymContext($contextBefore, $contextAfter);
        $reasoning = "Analyzed as potential acronym based on surrounding punctuation and capitalization";
        break;

      case 'sentence_flow':
        $confidence = $this->analyzeSentenceFlow($contextBefore, $contextAfter);
        $reasoning = "Analyzed sentence flow and grammatical context";
        break;

      case 'context_aware':
        $acronymScore = $this->analyzeAcronymContext($contextBefore, $contextAfter);
        $sentenceScore = $this->analyzeSentenceFlow($contextBefore, $contextAfter);
        $confidence = ($acronymScore + $sentenceScore) / 2;
        $reasoning = "Combined analysis of acronym patterns and sentence context";
        break;
    }

    return [
      'context_before' => trim($contextBefore),
      'context_after' => trim($contextAfter),
      'confidence' => $confidence,
      'reasoning' => $reasoning,
      'analysis_mode' => $analysisMode,
    ];
  }

  /**
   * Analyze if text appears to be used as an acronym.
   */
  private function analyzeAcronymContext(string $before, string $after): float {
    // Base score.
    $score = 0.5;

    // Check for acronym indicators.
    if (preg_match('/[.!?]\s*$/', $before) || preg_match('/^\s*[.!?]/', $after)) {
      // Sentence boundaries suggest acronym usage.
      $score += 0.2;
    }

    if (preg_match('/\s$/', $before) && preg_match('/^\s/', $after)) {
      // Surrounded by spaces.
      $score += 0.1;
    }

    if (preg_match('/[A-Z]\s*$/', $before) || preg_match('/^\s*[A-Z]/', $after)) {
      // Adjacent to other capital letters.
      $score += 0.1;
    }

    return min(1.0, $score);
  }

  /**
   * Analyze sentence flow context.
   */
  private function analyzeSentenceFlow(string $before, string $after): float {
    // Base score.
    $score = 0.5;

    // Look for sentence continuation patterns.
    if (preg_match('/\w\s*$/', $before) && preg_match('/^\s*\w/', $after)) {
      // Appears mid-sentence.
      $score += 0.2;
    }

    // Check for common word patterns that suggest replacement.
    if (preg_match('/(the|a|an|our|this|that)\s*$/i', $before)) {
      // Preceded by articles/determiners.
      $score += 0.2;
    }

    if (preg_match('/^\s*(is|was|will|can|should|platform|system|tool)/i', $after)) {
      // Followed by descriptive terms.
      $score += 0.2;
    }

    return min(1.0, $score);
  }

  /**
   * Generate confidence scores for the analysis.
   */
  private function generateConfidenceScores(array $item, string $targetText): array {
    return [
      'overall_confidence' => 0.85,
      'context_clarity' => 0.9,
      'replacement_appropriateness' => 0.8,
      'semantic_consistency' => 0.87,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Validate that we have content to analyze.
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
          'title' => 'Content to Analyze',
          'description' => 'Text content or array of content items for AI analysis',
          'required' => TRUE,
        ],
        'tool' => [
          'type' => 'tool',
          'title' => 'Tool',
          'description' => 'Available Tools',
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
        'analyzed_content' => [
          'type' => 'array',
          'description' => 'Content items with AI analysis results',
        ],
        'total_analyzed' => [
          'type' => 'integer',
          'description' => 'Total number of items analyzed',
        ],
        'total_replacements' => [
          'type' => 'integer',
          'description' => 'Total number of replacements made',
        ],
        'analysis_mode' => [
          'type' => 'string',
          'description' => 'The analysis mode used',
        ],
        'confidence_threshold' => [
          'type' => 'number',
          'description' => 'Confidence threshold used for replacements',
        ],
        'analyzed_at' => [
          'type' => 'string',
          'description' => 'Timestamp when analysis was completed',
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
        'targetText' => [
          'type' => 'string',
          'title' => 'Target Text',
          'description' => 'Text to analyze and potentially replace',
          'default' => 'XB',
        ],
        'replacementText' => [
          'type' => 'string',
          'title' => 'Replacement Text',
          'description' => 'Text to replace with when appropriate',
          'default' => 'Canvas',
        ],
        'analysisMode' => [
          'type' => 'string',
          'title' => 'Analysis Mode',
          'description' => 'Type of AI analysis to perform',
          'enum' => ['acronym_detection', 'sentence_flow', 'context_aware'],
          'default' => 'context_aware',
        ],
        'confidenceThreshold' => [
          'type' => 'number',
          'title' => 'Confidence Threshold',
          'description' => 'Minimum confidence level for making replacements (0-1)',
          'minimum' => 0,
          'maximum' => 1,
          'default' => 0.8,
        ],
      ],
    ];
  }

}
