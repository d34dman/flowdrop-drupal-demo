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
use Drupal\flowdrop_demo\Service\TriageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Classifies content to determine appropriate handling.
 *
 * This node analyzes form submissions or other content to classify them
 * into categories like support requests, feature requests, sales
 * inquiries, etc.
 */
#[FlowDropNodeProcessor(
  id: "content_classifier",
  label: new TranslatableMarkup("Content Classifier"),
  type: "tool",
  supportedTypes: ["tool", "default"],
  category: "ai",
  description: "Classify content into categories (support, features, sales) for proper triage",
  version: "1.0.0",
  tags: ["classification", "triage", "ai", "content-analysis"]
)]
class ContentClassifier extends AbstractFlowDropNodeProcessor {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected TriageService $triageService,
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
      $container->get('logger.factory'),
      $container->get('flowdrop_demo.triage_service')
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
    $classificationMode = $config->getConfig('classificationMode', 'full_analysis');
    $confidenceThreshold = $config->getConfig('confidenceThreshold', 0.7);
    $categories = $config->getConfig('categories', ['support', 'features', 'sales', 'general']);

    // Extract content to classify.
    $submissionData = $inputs->get('structured_data', []);
    $rawData = $inputs->get('raw_data', []);
    $submissionId = $inputs->get('submission_id', 'unknown');

    // Perform classification.
    $classificationResult = $this->classifyContent($submissionData, $rawData, $categories, $classificationMode, $confidenceThreshold);

    $this->getLogger()->info('Content classification completed', [
      'submission_id' => $submissionId,
      'predicted_category' => $classificationResult['primary_category'],
      'confidence' => $classificationResult['confidence'],
      'classification_mode' => $classificationMode,
    ]);

    return [
      'submission_id' => $submissionId,
      'primary_category' => $classificationResult['primary_category'],
      'confidence' => $classificationResult['confidence'],
      'category_scores' => $classificationResult['category_scores'],
      'classification_reasoning' => $classificationResult['reasoning'],
      'suggested_teams' => $classificationResult['suggested_teams'],
      'priority_level' => $classificationResult['priority_level'],
      'keywords_found' => $classificationResult['keywords_found'],
      'classified_at' => date('Y-m-d H:i:s'),
      'metadata' => [
        'classification_mode' => $classificationMode,
        'confidence_threshold' => $confidenceThreshold,
        'available_categories' => $categories,
      ],
    ];
  }

  /**
   * Classify content based on analysis.
   */
  private function classifyContent(array $structuredData, array $rawData, array $categories, string $mode, float $threshold): array {
    $text = $this->extractTextForAnalysis($structuredData, $rawData);
    $categoryScores = [];
    $keywordsFound = [];

    // Analyze content for each category.
    foreach ($categories as $category) {
      $analysis = $this->analyzeCategoryMatch($text, $category, $mode);
      $categoryScores[$category] = $analysis['score'];
      if (!empty($analysis['keywords'])) {
        $keywordsFound[$category] = $analysis['keywords'];
      }
    }

    // Determine primary category.
    arsort($categoryScores);
    $primaryCategory = array_key_first($categoryScores);
    $confidence = $categoryScores[$primaryCategory];

    // Generate reasoning.
    $reasoning = $this->generateClassificationReasoning($primaryCategory, $confidence, $keywordsFound);

    // Suggest appropriate teams.
    $suggestedTeams = $this->suggestTeams($primaryCategory, $confidence, $structuredData);

    // Determine priority level.
    $priorityLevel = $this->determinePriorityLevel($primaryCategory, $text, $structuredData);

    return [
      'primary_category' => $primaryCategory,
      'confidence' => $confidence,
      'category_scores' => $categoryScores,
      'reasoning' => $reasoning,
      'suggested_teams' => $suggestedTeams,
      'priority_level' => $priorityLevel,
      'keywords_found' => $keywordsFound,
    ];
  }

  /**
   * Extract text content for analysis.
   */
  private function extractTextForAnalysis(array $structuredData, array $rawData): string {
    $textParts = [];

    // Extract from structured data.
    foreach (['subject', 'message', 'description', 'comment'] as $field) {
      if (isset($structuredData[$field]['processed_value'])) {
        $textParts[] = $structuredData[$field]['processed_value'];
      }
    }

    // Extract from raw data as fallback.
    foreach (['subject', 'message', 'description', 'comment', 'type'] as $field) {
      if (isset($rawData[$field]) && !in_array($rawData[$field], $textParts)) {
        $textParts[] = $rawData[$field];
      }
    }

    return implode(' ', $textParts);
  }

  /**
   * Analyze how well content matches a category.
   */
  private function analyzeCategoryMatch(string $text, string $category, string $mode): array {
    $text = strtolower($text);
    $keywords = $this->getCategoryKeywords($category);
    $foundKeywords = [];
    $score = 0.0;

    // Basic keyword matching.
    foreach ($keywords as $keyword => $weight) {
      if (strpos($text, strtolower($keyword)) !== FALSE) {
        $foundKeywords[] = $keyword;
        $score += $weight;
      }
    }

    // Apply mode-specific analysis.
    switch ($mode) {
      case 'keyword_only':
        // Score is just based on keywords.
        break;

      case 'sentiment_analysis':
        $score += $this->analyzeSentiment($text, $category);
        break;

      case 'full_analysis':
        $score += $this->analyzeSentiment($text, $category);
        $score += $this->analyzeStructuralPatterns($text, $category);
        break;
    }

    // Normalize score to 0-1 range.
    $normalizedScore = min(1.0, $score / count($keywords));

    return [
      'score' => $normalizedScore,
      'keywords' => $foundKeywords,
    ];
  }

  /**
   * Get keywords for each category.
   */
  private function getCategoryKeywords(string $category): array {
    $keywords = [
      'support' => [
        'help' => 0.3,
        'problem' => 0.4,
        'issue' => 0.4,
        'bug' => 0.5,
        'error' => 0.4,
        'not working' => 0.5,
        'broken' => 0.4,
        'fix' => 0.3,
        'support' => 0.3,
        'assistance' => 0.3,
      ],
      'features' => [
        'feature' => 0.5,
        'enhancement' => 0.4,
        'suggestion' => 0.3,
        'improvement' => 0.4,
        'request' => 0.3,
        'add' => 0.2,
        'new' => 0.2,
        'would like' => 0.3,
        'could you' => 0.3,
        'functionality' => 0.4,
      ],
      'sales' => [
        'pricing' => 0.5,
        'quote' => 0.5,
        'purchase' => 0.4,
        'buy' => 0.4,
        'cost' => 0.3,
        'demo' => 0.4,
        'trial' => 0.4,
        'enterprise' => 0.3,
        'license' => 0.4,
        'subscription' => 0.4,
      ],
      'general' => [
        'question' => 0.3,
        'inquiry' => 0.3,
        'information' => 0.2,
        'about' => 0.1,
        'hello' => 0.1,
        'contact' => 0.2,
      ],
    ];

    return $keywords[$category] ?? [];
  }

  /**
   * Analyze sentiment for category matching.
   */
  private function analyzeSentiment(string $text, string $category): float {
    $urgencyWords = ['urgent', 'asap', 'immediately', 'critical', 'emergency'];
    $positiveWords = ['great', 'excellent', 'love', 'amazing', 'wonderful'];
    $negativeWords = ['hate', 'terrible', 'awful', 'frustrated', 'angry'];

    $score = 0.0;

    foreach ($urgencyWords as $word) {
      if (strpos($text, $word) !== FALSE) {
        $score += ($category === 'support') ? 0.2 : -0.1;
      }
    }

    foreach ($negativeWords as $word) {
      if (strpos($text, $word) !== FALSE) {
        $score += ($category === 'support') ? 0.15 : -0.05;
      }
    }

    foreach ($positiveWords as $word) {
      if (strpos($text, $word) !== FALSE) {
        $score += ($category === 'features' || $category === 'sales') ? 0.1 : 0.05;
      }
    }

    return $score;
  }

  /**
   * Analyze structural patterns in text.
   */
  private function analyzeStructuralPatterns(string $text, string $category): float {
    $score = 0.0;

    // Question patterns.
    if (preg_match('/\?/', $text)) {
      $score += ($category === 'general' || $category === 'support') ? 0.1 : 0.0;
    }

    // Length analysis.
    $wordCount = str_word_count($text);
    if ($wordCount > 100) {
      $score += ($category === 'support' || $category === 'features') ? 0.1 : 0.0;
    }

    // Technical terms.
    if (preg_match('/\b(api|database|server|code|technical)\b/i', $text)) {
      $score += ($category === 'support' || $category === 'features') ? 0.15 : 0.0;
    }

    return $score;
  }

  /**
   * Generate reasoning for the classification.
   */
  private function generateClassificationReasoning(string $category, float $confidence, array $keywordsFound): string {
    $reasoning = "Classified as '{$category}' with {$confidence}% confidence. ";

    if (!empty($keywordsFound[$category])) {
      $reasoning .= "Key indicators: " . implode(', ', $keywordsFound[$category]) . ". ";
    }

    if ($confidence < 0.5) {
      $reasoning .= "Low confidence suggests manual review may be needed.";
    }
    elseif ($confidence > 0.8) {
      $reasoning .= "High confidence classification based on strong keyword matches.";
    }

    return $reasoning;
  }

  /**
   * Suggest appropriate teams based on classification.
   */
  private function suggestTeams(string $category, float $confidence, array $structuredData): array {
    $teamMappings = [
      'support' => ['Technical Support', 'Customer Success'],
      'features' => ['Product Management', 'Engineering'],
      'sales' => ['Sales Team', 'Business Development'],
      'general' => ['Customer Service', 'General Inquiries'],
    ];

    $suggestedTeams = $teamMappings[$category] ?? ['General Inquiries'];

    // Add priority team based on confidence.
    if ($confidence > 0.8) {
      array_unshift($suggestedTeams, $suggestedTeams[0] . ' (Priority)');
    }

    return $suggestedTeams;
  }

  /**
   * Determine priority level.
   */
  private function determinePriorityLevel(string $category, string $text, array $structuredData): string {
    $urgencyWords = ['urgent', 'asap', 'immediately', 'critical', 'emergency'];
    $hasUrgency = FALSE;

    foreach ($urgencyWords as $word) {
      if (stripos($text, $word) !== FALSE) {
        $hasUrgency = TRUE;
        break;
      }
    }

    if ($hasUrgency) {
      return 'high';
    }

    if ($category === 'support') {
      return 'medium';
    }

    return 'normal';
  }

  /**
   * {@inheritdoc}
   */
  public function validateInputs(array $inputs): bool {
    // Validate that we have structured data.
    if (!isset($inputs['structured_data'])) {
      return FALSE;
    }

    // Structured data should be an array.
    if (!is_array($inputs['structured_data'])) {
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
        'structured_data' => [
          'type' => 'object',
          'title' => 'Structured Data',
          'description' => 'Processed form data for classification',
          'required' => TRUE,
        ],
        'raw_data' => [
          'type' => 'object',
          'title' => 'Raw Data',
          'description' => 'Original form data',
          'required' => FALSE,
        ],
        'submission_id' => [
          'type' => 'string',
          'title' => 'Submission ID',
          'description' => 'Unique submission identifier',
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
        'primary_category' => [
          'type' => 'string',
          'description' => 'Primary classification category',
        ],
        'confidence' => [
          'type' => 'number',
          'description' => 'Classification confidence score (0-1)',
        ],
        'category_scores' => [
          'type' => 'object',
          'description' => 'Scores for all categories',
        ],
        'classification_reasoning' => [
          'type' => 'string',
          'description' => 'Explanation of the classification decision',
        ],
        'suggested_teams' => [
          'type' => 'array',
          'description' => 'Recommended teams to handle this submission',
        ],
        'priority_level' => [
          'type' => 'string',
          'description' => 'Priority level (normal, medium, high)',
        ],
        'keywords_found' => [
          'type' => 'object',
          'description' => 'Keywords that influenced classification',
        ],
        'classified_at' => [
          'type' => 'string',
          'description' => 'Classification timestamp',
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
        'classificationMode' => [
          'type' => 'string',
          'title' => 'Classification Mode',
          'description' => 'Type of analysis to perform',
          'enum' => ['keyword_only', 'sentiment_analysis', 'full_analysis'],
          'default' => 'full_analysis',
        ],
        'confidenceThreshold' => [
          'type' => 'number',
          'title' => 'Confidence Threshold',
          'description' => 'Minimum confidence for classification (0-1)',
          'minimum' => 0,
          'maximum' => 1,
          'default' => 0.7,
        ],
        'categories' => [
          'type' => 'array',
          'title' => 'Available Categories',
          'description' => 'Categories to classify content into',
          'items' => [
            'type' => 'string',
          ],
          'default' => ['support', 'features', 'sales', 'general'],
        ],
      ],
    ];
  }

}
