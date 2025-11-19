<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for content operations.
 *
 * This service handles loading and manipulating Drupal content
 * for demonstration workflows.
 */
class ContentService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Load content based on criteria.
   *
   * @param string $contentType
   *   The content type to load.
   * @param string $status
   *   Publication status filter.
   * @param int $limit
   *   Maximum number of items to load.
   * @param array $fields
   *   Fields to include in the output.
   *
   * @return array
   *   Array of content items.
   */
  public function loadContent(string $contentType, string $status, int $limit, array $fields): array {
    $this->loggerFactory->get('flowdrop_demo')->info('Loading content', [
      'content_type' => $contentType,
      'status' => $status,
      'limit' => $limit,
    ]);

    // Simulate loading content from Drupal.
    // In real implementation, this would use EntityTypeManager to load
    // actual content.
    return $this->generateSimulatedContent($contentType, $status, $limit, $fields);
  }

  /**
   * Update content with processed data.
   *
   * @param array $contentItems
   *   Content items to update.
   *
   * @return array
   *   Update results.
   */
  public function updateContent(array $contentItems): array {
    $updateResults = [];

    foreach ($contentItems as $item) {
      // Simulate content update.
      $updateResults[] = [
        'item_id' => $item['id'] ?? 'unknown',
        'status' => 'updated',
        'updated_at' => date('Y-m-d H:i:s'),
        'changes_made' => $item['changes_made'] ?? 0,
      ];
    }

    $this->loggerFactory->get('flowdrop_demo')->info('Content updated', [
      'items_updated' => count($updateResults),
    ]);

    return $updateResults;
  }

  /**
   * Generate simulated content for demonstration.
   */
  private function generateSimulatedContent(string $contentType, string $status, int $limit, array $fields): array {
    $content = [];

    $sampleTitles = [
      'Getting Started with XB Platform',
      'XB Integration Best Practices',
      'Advanced XB Features You Should Know',
      'Troubleshooting XB Connection Issues',
      'XB API Documentation Updates',
      'Building Custom XB Extensions',
      'XB Performance Optimization Tips',
      'XB Security Configuration Guide',
      'Migrating to XB from Legacy Systems',
      'XB Workflow Automation Examples',
    ];

    $sampleBodies = [
      'This comprehensive guide covers the fundamentals of working with XB. Learn how XB can streamline your workflow and improve productivity. XB offers powerful features for modern development teams.',
      'Discover the best practices for integrating XB into your existing systems. XB provides robust APIs and flexible configuration options that make integration straightforward.',
      'Explore advanced XB capabilities that can take your projects to the next level. From custom XB plugins to advanced XB scripting, these features unlock new possibilities.',
      'When XB encounters issues, this troubleshooting guide will help you resolve them quickly. Common XB problems and their solutions are covered in detail.',
      'Stay up to date with the latest XB API changes and improvements. The XB development team regularly releases updates to enhance functionality.',
    ];

    for ($i = 0; $i < min($limit, 10); $i++) {
      $item = [
        'id' => 'node_' . ($i + 1),
        'type' => $contentType,
        'status' => $status === 'all' ? (rand(0, 1) ? 'published' : 'unpublished') : $status,
      ];

      // Add requested fields.
      if (in_array('title', $fields)) {
        $item['title'] = $sampleTitles[$i % count($sampleTitles)];
      }

      if (in_array('body', $fields)) {
        $item['body'] = $sampleBodies[$i % count($sampleBodies)];
      }

      if (in_array('summary', $fields)) {
        $item['summary'] = substr($sampleBodies[$i % count($sampleBodies)], 0, 100) . '...';
      }

      if (in_array('author', $fields)) {
        $authors = ['John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Brown'];
        $item['author'] = $authors[$i % count($authors)];
      }

      if (in_array('created', $fields)) {
        $item['created'] = date('Y-m-d H:i:s', strtotime("-{$i} days"));
      }

      if (in_array('tags', $fields)) {
        $allTags = ['XB', 'tutorial', 'guide', 'integration', 'api', 'development'];
        $item['tags'] = array_slice($allTags, 0, rand(2, 4));
      }

      $content[] = $item;
    }

    return $content;
  }

}
