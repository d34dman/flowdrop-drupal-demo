<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for content triage and classification.
 *
 * This service provides functionality for automatically
 * classifying and triaging form submissions and other content.
 */
class TriageService {

  public function __construct(
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Classify content into predefined categories.
   *
   * @param string $content
   *   Content to classify.
   * @param array $categories
   *   Available categories.
   *
   * @return array
   *   Classification results.
   */
  public function classifyContent(string $content, array $categories): array {
    $this->loggerFactory->get('flowdrop_demo')->info('Classifying content', [
      'content_length' => strlen($content),
      'categories' => $categories,
    ]);

    // Simulate content classification.
    return $this->performClassification($content, $categories);
  }

  /**
   * Suggest team assignments based on classification.
   *
   * @param string $category
   *   Primary category.
   * @param float $confidence
   *   Classification confidence.
   *
   * @return array
   *   Team assignment suggestions.
   */
  public function suggestTeamAssignment(string $category, float $confidence): array {
    $teamMappings = $this->getTeamMappings();
    $suggestions = $teamMappings[$category] ?? ['General Support'];

    // Adjust suggestions based on confidence.
    if ($confidence < 0.6) {
      array_unshift($suggestions, 'Manual Review Required');
    }

    return [
      'suggested_teams' => $suggestions,
      'confidence' => $confidence,
      'requires_review' => $confidence < 0.6,
    ];
  }

  /**
   * Determine priority level based on content analysis.
   *
   * @param string $content
   *   Content to analyze.
   * @param string $category
   *   Content category.
   *
   * @return string
   *   Priority level (low, normal, high, urgent).
   */
  public function determinePriority(string $content, string $category): string {
    $urgentKeywords = ['urgent', 'critical', 'emergency', 'asap', 'immediately'];
    $highKeywords = ['important', 'priority', 'issue', 'problem', 'broken'];

    $content = strtolower($content);

    foreach ($urgentKeywords as $keyword) {
      if (strpos($content, $keyword) !== FALSE) {
        return 'urgent';
      }
    }

    foreach ($highKeywords as $keyword) {
      if (strpos($content, $keyword) !== FALSE) {
        return 'high';
      }
    }

    // Category-based priority.
    if ($category === 'support') {
      return 'normal';
    }
    elseif ($category === 'sales') {
      return 'high';
    }

    return 'normal';
  }

  /**
   * Perform content classification.
   */
  private function performClassification(string $content, array $categories): array {
    $scores = [];
    $content = strtolower($content);

    // Define keyword patterns for each category.
    $categoryPatterns = [
      'support' => ['help', 'problem', 'issue', 'bug', 'error', 'not working', 'broken'],
      'features' => ['feature', 'enhancement', 'suggestion', 'improvement', 'request', 'new'],
      'sales' => ['pricing', 'quote', 'purchase', 'buy', 'cost', 'demo', 'trial'],
      'general' => ['question', 'inquiry', 'information', 'about'],
    ];

    // Score each category.
    foreach ($categories as $category) {
      $score = 0.0;
      $patterns = $categoryPatterns[$category] ?? [];

      foreach ($patterns as $pattern) {
        if (strpos($content, $pattern) !== FALSE) {
          $score += 0.2;
        }
      }

      // Add some randomness to simulate ML uncertainty.
      $score += (rand(0, 100) / 1000);
      $scores[$category] = min(1.0, $score);
    }

    // Determine primary category.
    arsort($scores);
    $primaryCategory = array_key_first($scores);
    $confidence = $scores[$primaryCategory];

    return [
      'primary_category' => $primaryCategory,
      'confidence' => $confidence,
      'all_scores' => $scores,
      'classification_method' => 'keyword_matching',
    ];
  }

  /**
   * Get team mappings configuration.
   */
  private function getTeamMappings(): array {
    return [
      'support' => ['Technical Support', 'Customer Success'],
      'features' => ['Product Management', 'Engineering'],
      'sales' => ['Sales Team', 'Business Development'],
      'general' => ['Customer Service', 'General Inquiries'],
    ];
  }

}
