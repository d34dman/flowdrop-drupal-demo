<?php

declare(strict_types=1);

namespace Drupal\fd_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\flowdrop\Dashboard\DashboardIconRepositoryInterface;
use Drupal\flowdrop_workflow\Entity\FlowDropWorkflow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * The "Start here" front page: the demo workflows in learning order.
 *
 * Built from the FlowDrop UI components (grid, stat-card, pill, action-link)
 * plus this module's own SDCs (hero, setup-callout, section, path-step,
 * workflow-card).
 */
final class StartController extends ControllerBase {

  /**
   * Sections in reading order.
   *
   * Each section lists workflow id => fallback description. The fallback is
   * used only when the workflow has no description of its own. A section with
   * layout 'path' renders as the numbered learning path; 'cards' as a grid.
   */
  private const SECTIONS = [
    'learn' => [
      'eyebrow' => 'Step 1',
      'title' => 'Learn the canvas, one idea per level',
      'description' => 'Each level adds a single idea to the one before. Start at 0.1 and keep going.',
      'layout' => 'path',
      'icon' => 'workflow',
      'items' => [
        'level_0_1_echo' => '',
        'level_0_2_echo_but_lowercase' => '',
        'level_1_3_pick_your_transform' => '',
        'level_1_4_polite_greeter_templating' => '',
        'level_1_5_conditional_reply' => '',
        'level_2_6_simple_ai_chat' => '',
        'level_2_7_style_controlled_ai_reply' => 'Let the user pick a tone, then shape the AI reply with a prompt template.',
        'level_3_9_url_summariser' => '',
      ],
    ],
    'chat' => [
      'eyebrow' => 'Step 2',
      'title' => 'Build an LLM chat',
      'description' => 'From a single model call to a chat that remembers what you said.',
      'layout' => 'cards',
      'icon' => 'session',
      'items' => [
        'your_first_llm_call' => 'The smallest possible model call: a chat input straight into a chat model.',
        'giving_your_llm_chat_a_memory' => 'The same chat, now with conversation history so it remembers earlier turns.',
      ],
    ],
    'agents' => [
      'eyebrow' => 'Step 3',
      'title' => 'Agents',
      'description' => 'Let the model decide which tool to call, and loop until it has an answer.',
      'layout' => 'cards',
      'icon' => 'execute',
      'items' => [
        'lets_create_a_reasoner' => 'Build a reasoning loop by hand: the model decides, calls a calculator tool, and loops until it has an answer.',
      ],
    ],
    'showcase' => [
      'eyebrow' => 'Showcase',
      'title' => 'dri.es over MCP',
      'description' => 'Workflows that talk to a live MCP server, one with no AI at all and one agent.',
      'layout' => 'cards',
      'icon' => 'trigger',
      'items' => [
        'dri_es_photo_finder' => '',
        'dri_es_chat_agent' => '',
      ],
    ],
    'blocks' => [
      'eyebrow' => 'Under the hood',
      'title' => 'Building blocks',
      'description' => 'Sub-workflows and utilities the other workflows call. Open them in the editor to see inside.',
      'layout' => 'cards',
      'icon' => 'structure',
      'items' => [
        'react_agent' => 'The reusable ReAct loop that "Chat with dri.es" runs as a sub-workflow. Open it in the editor to see inside.',
        'react_agent_with_tools' => 'A ReAct loop with its own toolbox (web fetch), built to be called from another workflow. Open it in the editor to see inside.',
        'http_get' => '',
        'flowdrop_chat_processor' => '',
      ],
    ],
  ];

  /**
   * Stage names for the learning path, keyed by the level's major number.
   */
  private const STAGES = [
    '0' => 'Basics',
    '1' => 'Logic',
    '2' => 'AI',
    '3' => 'The web',
  ];

  /**
   * Node types that call a model, so the workflow needs an API key.
   */
  private const AI_NODE_TYPE_PREFIXES = [
    'flowdrop_ai_provider_',
    'flowdrop_node_processor_reason',
    'flowdrop_agents_',
    'flowdrop_workflow_react_agent',
  ];

  /**
   * Node types that reach out to the internet.
   */
  private const NETWORK_NODE_TYPE_PREFIXES = [
    'http_request',
    'mcp_',
  ];

  /**
   * Sub-workflows: they have no chat input, so the Playground is no use.
   */
  private const EDITOR_ONLY = ['react_agent', 'react_agent_with_tools'];

  /**
   * Whether the Anthropic key is configured; resolved once per request.
   */
  private ?bool $keyPresent = NULL;

  public function __construct(
    private readonly DashboardIconRepositoryInterface $icons,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get(DashboardIconRepositoryInterface::class));
  }

  /**
   * Renders the page.
   */
  public function page(): array {
    $main = [];
    $logo = $this->moduleHandler()->getModule('flowdrop_ui_components')->getPath() . '/assets/flowdrop-logo.svg';

    if ($this->currentUser()->isAnonymous()) {
      $main['hero'] = $this->hero($logo, [
        'label' => $this->t('Log in'),
        'url' => Url::fromRoute('user.login', [], ['query' => ['destination' => '/start']])->toString(),
      ]);
      $main['login'] = $this->component('setup-callout', [
        'status' => 'info',
        'title' => $this->t('Log in first'),
        'message' => $this->t('From the project directory, run this and open the link it prints:'),
        'commands' => ['ddev drush uli'],
      ]);
      return $this->layout($main);
    }

    $workflows = FlowDropWorkflow::loadMultiple();
    $sections = [];
    $stats = ['total' => 0, 'offline' => 0, 'ai' => 0, 'nodes' => 0];
    $first_try = NULL;

    foreach (self::SECTIONS as $section_id => $section) {
      $items = [];
      $step = 0;
      foreach ($section['items'] as $id => $fallback) {
        if (!isset($workflows[$id])) {
          continue;
        }
        $info = $this->describe($workflows[$id], $fallback);
        $stats['total']++;
        $stats['nodes'] += $info['node_count'];
        $stats['ai'] += (int) $info['needs_ai'];
        $stats['offline'] += (int) (!$info['needs_ai'] && !$info['needs_net']);
        $first_try ??= $info['try_url'];

        $items[$id] = $section['layout'] === 'path'
          ? $this->pathStep($workflows[$id], $info, ++$step)
          : $this->component('workflow-card', array_filter([
            'title' => $workflows[$id]->label(),
            'description' => $info['description'],
            'icon' => $this->icons->getIcon($section['icon']),
            'needs' => $info['needs'],
            'node_count' => $info['node_count'],
            'try_url' => $info['try_url'],
            'edit_url' => $info['edit_url'],
            'try_label' => $this->t('Try it'),
            'edit_label' => $this->t('Open editor'),
          ], static fn($v) => $v !== NULL));
      }
      if (!$items) {
        continue;
      }
      $sections[$section_id] = $this->component('section', [
        'id' => 'fd-demo-' . $section_id,
        'eyebrow' => $section['eyebrow'],
        'title' => $section['title'],
        'description' => $section['description'],
      ], [
        'content' => $section['layout'] === 'path'
          ? ['#type' => 'html_tag', '#tag' => 'ol', '#attributes' => ['class' => ['fd-demo-path']], 'steps' => $items]
          : $this->component('grid', ['variant' => 'cards', 'stagger' => TRUE], ['default' => $items], 'flowdrop_ui_components'),
      ]);
    }

    $main['hero'] = $this->hero($logo, $first_try ? [
      'label' => $this->t('Start with level 0.1'),
      'url' => $first_try,
    ] : NULL);
    $main['key'] = $this->keyCallout();
    $main['stats'] = $this->stats($stats);
    $main += $sections;
    $main['explore'] = $this->explore();

    return $this->layout($main);
  }

  /**
   * Wraps the page content in the FlowDrop dashboard layout.
   */
  private function layout(array $main): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['fd-dashboard-layout']],
      '#attached' => [
        'library' => [
          'flowdrop_ui_components/dashboard-layout',
          'flowdrop_ui_components/base',
          'fd_demo/start',
        ],
      ],
      '#cache' => [
        'tags' => ['config:flowdrop_workflow_list'],
        'contexts' => ['user.permissions'],
        // The key banner reflects the environment, which has no cache tag.
        'max-age' => 0,
      ],
      'main' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fd-dashboard-main', 'fd-dashboard-main--constrained', 'fd-demo-start']],
      ] + $main,
    ];
  }

  /**
   * The page hero: logo, pitch, calls to action and the canvas illustration.
   */
  private function hero(string $logo, ?array $primary): array {
    $secondary = $this->routeLink('entity.flowdrop_workflow.collection', $this->t('Browse all workflows'));
    return $this->component('hero', array_filter([
      'eyebrow' => $this->t('FlowDrop demo'),
      'title' => $this->t('Build AI workflows you can watch run.'),
      'lead' => $this->t('This site is a FlowDrop playground. <strong>Try it</strong> opens the Playground, where you chat with a workflow and watch every node run. <strong>Open editor</strong> shows the canvas, where you can change it.'),
      'logo_url' => '/' . $logo,
      'primary' => $primary,
      'secondary' => $secondary,
      'steps' => [
        ['title' => $this->t('Pick a workflow'), 'text' => $this->t('They get harder as you scroll.')],
        ['title' => $this->t('Try it'), 'text' => $this->t('Chat with it, see each node light up.')],
        ['title' => $this->t('Open the editor'), 'text' => $this->t('Change a node, run it again.')],
      ],
    ]));
  }

  /**
   * Says whether the Anthropic key is there, and how to add it if not.
   */
  private function keyCallout(): array {
    if ($this->keyPresent()) {
      return $this->component('setup-callout', [
        'status' => 'ok',
        'title' => $this->t('Anthropic API key found'),
        'message' => $this->t('Every workflow on this page can run.'),
      ]);
    }
    return $this->component('setup-callout', [
      'status' => 'missing',
      'title' => $this->t('No Anthropic API key yet'),
      'message' => $this->t('Everything marked <em>offline</em> works without one, so start there. To unlock the workflows marked <em>AI key</em>, run this in the project directory, then reload this page:'),
      'commands' => [
        'echo "ANTHROPIC_KEY=sk-ant-..." >> .ddev/.env',
        'ddev restart',
        'ddev drush cr',
      ],
      'copy_label' => $this->t('Copy'),
      'copied_label' => $this->t('Copied'),
    ]);
  }

  /**
   * The row of counters under the hero.
   */
  private function stats(array $stats): array {
    $cards = [
      'total' => ['label' => $this->t('Workflows to explore'), 'icon' => 'workflow', 'variant' => 'primary'],
      'offline' => ['label' => $this->t('Run offline, no key needed'), 'icon' => 'playground', 'variant' => 'success'],
      'ai' => ['label' => $this->keyPresent() ? $this->t('Use AI, key ready') : $this->t('Need an AI key'), 'icon' => 'execute', 'variant' => $this->keyPresent() ? 'primary' : 'warning'],
      'nodes' => ['label' => $this->t('Nodes on the canvas'), 'icon' => 'node-type', 'variant' => 'default'],
    ];
    $items = [];
    foreach ($cards as $key => $card) {
      $items[$key] = $this->component('stat-card', [
        'value' => (string) $stats[$key],
        'label' => $card['label'],
        'variant' => $card['variant'],
        'icon' => $this->icons->getIcon($card['icon']),
      ], [], 'flowdrop_ui_components');
    }
    return $this->component('grid', ['variant' => 'stats', 'stagger' => TRUE, 'gap' => 'md'], ['default' => $items], 'flowdrop_ui_components');
  }

  /**
   * Links to the rest of FlowDrop, as action-link cards.
   */
  private function explore(): array {
    $links = [
      ['flowdrop.dashboard', $this->t('FlowDrop dashboard'), $this->t('Everything FlowDrop manages, in one place.'), 'category'],
      ['entity.flowdrop_workflow.add_form', $this->t('Create a workflow'), $this->t('Start from an empty canvas.'), 'create'],
      ['entity.flowdrop_pipeline.collection', $this->t('Pipelines'), $this->t('Every run, its jobs and their outputs.'), 'pipeline'],
      ['entity.flowdrop_node_type.collection', $this->t('Node types'), $this->t('The building blocks you can drop on the canvas.'), 'node-type'],
      ['entity.key.collection', $this->t('Keys'), $this->t('Where the Anthropic API key lives.'), 'secret'],
    ];
    $items = [];
    foreach ($links as [$route, $title, $description, $icon]) {
      $link = $this->routeLink($route, $title);
      if ($link) {
        $items[$route] = $this->component('action-link', [
          'title' => (string) $title,
          'description' => (string) $description,
          'url' => $link['url'],
          'icon' => $this->icons->getIcon($icon),
        ], [], 'flowdrop_ui_components');
      }
    }
    $items['flowdrop.io'] = $this->component('action-link', [
      'title' => 'flowdrop.io',
      'description' => (string) $this->t('The project website.'),
      'url' => 'https://flowdrop.io',
      'external' => TRUE,
    ], [], 'flowdrop_ui_components');

    return $this->component('section', [
      'id' => 'fd-demo-explore',
      'eyebrow' => $this->t('Keep going'),
      'title' => $this->t('Explore FlowDrop'),
    ], [
      'content' => $this->component('grid', ['variant' => 'actions', 'stagger' => TRUE], ['default' => $items], 'flowdrop_ui_components'),
    ]);
  }

  /**
   * One numbered step of the learning path.
   */
  private function pathStep(FlowDropWorkflow $workflow, array $info, int $step): array {
    $label = (string) $workflow->label();
    $number = (string) $step;
    $stage = NULL;
    // "Level 1.3. Pick-your-transform" => number 1.3, stage Logic, title rest.
    if (preg_match('/^Level\s+((\d+)\.\d+)\.?\s*(.*)$/u', $label, $m)) {
      $number = $m[1];
      $stage = self::STAGES[$m[2]] ?? NULL;
      $label = $m[3];
    }
    return $this->component('path-step', array_filter([
      'number' => $number,
      'title' => $label,
      'stage' => $stage,
      'description' => $info['description'],
      'needs' => $info['needs'],
      'node_count' => $info['node_count'],
      'try_url' => $info['try_url'],
      'edit_url' => $info['edit_url'],
      'try_label' => $this->t('Try it'),
      'edit_label' => $this->t('Open editor'),
    ], static fn($v) => $v !== NULL));
  }

  /**
   * What the page needs to know about one workflow.
   */
  private function describe(FlowDropWorkflow $workflow, string $fallback): array {
    $id = (string) $workflow->id();
    $node_types = array_map(
      static fn(string $name): string => str_replace('flowdrop_node_type.flowdrop_node_type.', '', $name),
      $workflow->getDependencies()['config'] ?? [],
    );

    $needs_ai = $this->usesAny($node_types, self::AI_NODE_TYPE_PREFIXES);
    $needs_net = $this->usesAny($node_types, self::NETWORK_NODE_TYPE_PREFIXES);
    $needs = [];
    if ($needs_ai) {
      $needs[] = $this->keyPresent()
        ? ['label' => (string) $this->t('AI key'), 'variant' => 'primary']
        : ['label' => (string) $this->t('AI key needed'), 'variant' => 'warning'];
    }
    if ($needs_net) {
      $needs[] = ['label' => (string) $this->t('internet'), 'variant' => 'info'];
    }
    if (!$needs) {
      $needs[] = ['label' => (string) $this->t('offline'), 'variant' => 'success'];
    }

    $try = NULL;
    if (!in_array($id, self::EDITOR_ONLY, TRUE)) {
      $url = Url::fromRoute('flowdrop_playground.workflow.session.add', ['flowdrop_workflow' => $id]);
      $try = $url->access() ? $url->toString() : NULL;
    }
    $url = Url::fromRoute('flowdrop.workflow.editor', ['flowdrop_workflow' => $id]);
    $edit = $url->access() ? $url->toString() : NULL;

    $description = trim((string) $workflow->getDescription()) ?: $fallback;
    $description = ucfirst(preg_replace('/^Goal:\s*/', '', $description));

    return [
      'description' => $description,
      'needs' => $needs,
      'needs_ai' => $needs_ai,
      'needs_net' => $needs_net,
      'node_count' => count($workflow->getNodes() ?? []),
      'try_url' => $try,
      'edit_url' => $edit,
    ];
  }

  /**
   * Whether the Anthropic key entity exists and holds a value.
   */
  private function keyPresent(): bool {
    if ($this->keyPresent === NULL) {
      $key = $this->entityTypeManager()->getStorage('key')->load('anthropic_key');
      $this->keyPresent = $key && trim((string) $key->getKeyValue()) !== '';
    }
    return $this->keyPresent;
  }

  /**
   * A {label, url} pair for a route, or NULL when it is missing or forbidden.
   */
  private function routeLink(string $route, $label): ?array {
    try {
      $url = Url::fromRoute($route);
      return $url->access() ? ['label' => $label, 'url' => $url->toString()] : NULL;
    }
    catch (RouteNotFoundException) {
      return NULL;
    }
  }

  /**
   * An SDC render array.
   */
  private function component(string $name, array $props, array $slots = [], string $provider = 'fd_demo'): array {
    return [
      '#type' => 'component',
      '#component' => $provider . ':' . $name,
      '#props' => $props,
      '#slots' => $slots,
    ];
  }

  /**
   * Whether any node type starts with one of the prefixes.
   */
  private function usesAny(array $node_types, array $prefixes): bool {
    foreach ($node_types as $type) {
      foreach ($prefixes as $prefix) {
        if (str_starts_with($type, $prefix)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
