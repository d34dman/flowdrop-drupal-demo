<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Drupal\flowdrop_demo\Service\ContentService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads content from the site for batch processing.
 *
 * This node fetches content based on specified criteria (content type,
 * status, etc.) and outputs it as a structured dataset for further
 * processing in the workflow.
 */
#[FlowDropNodeProcessor(
  id: "content_loader",
  label: new TranslatableMarkup("Content Loader"),
  type: "tool",
  supportedTypes: ["tool", "default"],
  category: "content",
  description: "Load content from the site for batch processing",
  version: "1.0.0",
  tags: ["content", "drupal", "batch", "loader"]
)]
class ContentLoader extends AbstractFlowDropNodeProcessor {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected ContentService $contentService,
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
      $container->get('flowdrop_demo.content_service')
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
  protected function process(ParameterBagInterface $params): array {
    $contentType = $params->getString('contentType', 'article');
    $status = $params->getString('status', 'published');
    $limit = $params->getInt('limit', 50);
    $fields = $params->getArray('fields', ['title', 'body']);

    // Simulate loading content.
    $content = $this->contentService->loadContent($contentType, $status, $limit, $fields);

    $this->getLogger()->info('Content loaded successfully', [
      'content_type' => $contentType,
      'count' => count($content),
      'status' => $status,
    ]);

    return [
      'content_items' => $content,
      'total_count' => count($content),
      'content_type' => $contentType,
      'loaded_at' => date('Y-m-d H:i:s'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $inputs): bool {
    // Content loader can work with or without input filters.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        // Input parameters.
        'tool' => [
          'type' => 'tool',
          'title' => 'Tool',
          'description' => 'Available Tools',
          'flowdrop' => [
            'configurable' => FALSE,
            'connectable' => TRUE,
            'required' => FALSE,
          ],
        ],
        'filters' => [
          'type' => 'object',
          'title' => 'Additional Filters',
          'description' => 'Additional filtering criteria',
          'flowdrop' => [
            'configurable' => FALSE,
            'connectable' => TRUE,
            'required' => FALSE,
          ],
        ],
        // Config parameters.
        'nodeType' => [
          'type' => 'select',
          'title' => 'Node Type',
          'description' => 'Choose the visual representation for this node',
          'default' => 'tool',
          'enum' => ['tool', 'default'],
          'enumNames' => ['Tool Node (with metadata port)', 'Default Node (standard ports)'],
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'contentType' => [
          'type' => 'string',
          'title' => 'Content Type',
          'description' => 'The content type to load',
          'enum' => ['article', 'page', 'blog_post', 'news'],
          'default' => 'article',
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'status' => [
          'type' => 'string',
          'title' => 'Publication Status',
          'description' => 'Filter by publication status',
          'enum' => ['published', 'unpublished', 'all'],
          'default' => 'published',
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'limit' => [
          'type' => 'integer',
          'title' => 'Limit',
          'description' => 'Maximum number of items to load',
          'minimum' => 1,
          'maximum' => 1000,
          'default' => 50,
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'fields' => [
          'type' => 'array',
          'title' => 'Fields to Load',
          'description' => 'Which fields to include in the output',
          'items' => [
            'type' => 'string',
            'enum' => ['title', 'body', 'summary', 'author', 'created', 'tags'],
          ],
          'default' => ['title', 'body'],
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
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
        'content_items' => [
          'type' => 'array',
          'description' => 'Array of loaded content items',
        ],
        'total_count' => [
          'type' => 'integer',
          'description' => 'Total number of items loaded',
        ],
        'content_type' => [
          'type' => 'string',
          'description' => 'The content type that was loaded',
        ],
        'loaded_at' => [
          'type' => 'string',
          'description' => 'Timestamp when content was loaded',
        ],
      ],
    ];
  }

}
