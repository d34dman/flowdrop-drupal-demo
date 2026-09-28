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
 * The "Start here" front page, written for someone who has never used FlowDrop.
 *
 * Reading order: what FlowDrop is (hero), the four words the page uses
 * (concepts), one guided run (first run), then the workflows in learning
 * order, the showcase, and a closed "For developers" section for the rest.
 *
 * Built from the FlowDrop UI components (grid, pill, action-link) plus this
 * module's own SDCs (hero, setup-callout, concepts, first-run, section,
 * path-step, workflow-card).
 */
final class StartController extends ControllerBase {

  /**
   * The tour video shown beside the concepts, or NULL for the placeholder.
   *
   * @todo No video yet (2026-09-28). The plan: a 30-second capture of the
   *   first run (Echo, but lowercase: run it, switch the Text Processor to
   *   uppercase in the editor, run it again), captions only, no voice.
   *
   * A path under the web root (e.g. '/modules/custom/fd_demo/media/tour.mp4')
   * or an absolute URL.
   */
  private const TOUR_VIDEO = NULL;

  /**
   * The workflows the guided first run opens: the run step, then the change.
   */
  private const FIRST_RUN = ['level_0_1_echo', 'level_0_2_echo_but_lowercase'];

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
      ], NULL);
      $main['login'] = $this->component('setup-callout', [
        'status' => 'info',
        'title' => $this->t('Log in first'),
        'message' => $this->t('From the project directory, run this and open the link it prints:'),
        'commands' => ['ddev drush uli'],
      ]);
      return $this->layout($main);
    }

    $workflows = FlowDropWorkflow::loadMultiple();
    $catalogue = $this->catalogue();

    $main['hero'] = $this->hero($logo, [
      'label' => $this->t('Run your first workflow'),
      'url' => '#fd-demo-first-run',
    ], isset($workflows['dri_es_chat_agent']) ? [
      'label' => $this->t('See what it can do'),
      'url' => '#fd-demo-showcase',
    ] : NULL);
    if (!$this->keyPresent()) {
      $main['key'] = $this->keyCallout();
    }
    $main['concepts'] = $this->concepts();
    $main['first_run'] = $this->firstRun($workflows);
    $main['path'] = $this->pathSection($workflows, $catalogue['path']);
    $main['showcase'] = $this->cardSection($workflows, $catalogue['showcase'], [
      'id' => 'fd-demo-showcase',
      'eyebrow' => $this->t('See what it can do'),
      'title' => $this->t('Two workflows against a live website'),
      'description' => $this->t("Both read from dri.es, Dries Buytaert's blog, over MCP: the standard way a site offers tools to AI and other software. One uses no AI at all; the other is an agent."),
    ], 'trigger');
    $main['developers'] = $this->developers($workflows, $catalogue['blocks']);

    return $this->layout(array_filter($main));
  }

  /**
   * The page copy, per workflow, in reading order.
   *
   * Each entry: title (replaces the workflow label), summary (one sentence on
   * the idea it shows), and for workflows you can run, try (what to type or
   * do) and see (what comes back). Every try/see pair was checked against a
   * real run; re-check it when a workflow changes.
   *
   * @return array<string, array<string, array<string, mixed>>>
   *   Keyed by section, then workflow id.
   */
  private function catalogue(): array {
    return [
      'path' => [
        'level_0_1_echo' => [
          'stage' => $this->t('Basics'),
          'title' => $this->t('Echo'),
          'summary' => $this->t('The smallest workflow there is: what goes in comes out.'),
          'try' => $this->t('Type <code>Hello FlowDrop</code> and press Send.'),
          'see' => $this->t('Each node lights up in turn, and your words come back unchanged.'),
        ],
        'level_0_2_echo_but_lowercase' => [
          'stage' => $this->t('Basics'),
          'title' => $this->t('Echo, but lowercase'),
          'summary' => $this->t('One node in the middle changes the text on its way through.'),
          'try' => $this->t('Type <code>HELLO World</code>.'),
          'see' => $this->t('<code>hello world</code>'),
        ],
        'level_1_3_pick_your_transform' => [
          'stage' => $this->t('Logic'),
          'title' => $this->t('Pick your transform'),
          'summary' => $this->t('The workflow pauses and asks you a question before it carries on.'),
          'try' => $this->t('Type any text, press Send, then choose <em>uppercase</em> or <em>lowercase</em> when asked.'),
          'see' => $this->t('Your text, in the case you picked.'),
        ],
        'level_1_4_polite_greeter_templating' => [
          'stage' => $this->t('Logic'),
          'title' => $this->t('Polite greeter'),
          'summary' => $this->t('A template drops your input into a sentence.'),
          'try' => $this->t('Type a name, like <code>Ada</code>.'),
          'see' => $this->t('<code>Hello Ada, welcome back!</code>'),
        ],
        'level_1_5_conditional_reply' => [
          'stage' => $this->t('Logic'),
          'title' => $this->t('Conditional reply'),
          'summary' => $this->t('An If/Else node sends your message down one of two paths.'),
          'try' => $this->t('Send a message with the word <code>Drupal</code> in it, then one without.'),
          'see' => $this->t('<code>Correct Password :)</code> the first time, <code>You shall not pass!</code> the second.'),
        ],
        'level_2_6_simple_ai_chat' => [
          'stage' => $this->t('AI'),
          'title' => $this->t('Simple AI chat'),
          'summary' => $this->t('Your message goes to Claude, and its answer comes back.'),
          'try' => $this->t('Ask anything, like <code>Explain Drupal in one sentence.</code>'),
          'see' => $this->t("Claude's answer. It forgets each message once it has replied."),
        ],
        'level_2_7_style_controlled_ai_reply' => [
          'stage' => $this->t('AI'),
          'title' => $this->t('Choose the tone'),
          'summary' => $this->t('You pick a tone, and a template turns it into instructions for the model.'),
          'try' => $this->t('Ask a question, press Send, then pick <em>formal</em>, <em>casual</em> or <em>pirate</em>.'),
          'see' => $this->t('The answer, in the tone you picked.'),
        ],
        'giving_your_llm_chat_a_memory' => [
          'stage' => $this->t('AI'),
          'title' => $this->t('A chat that remembers'),
          'summary' => $this->t('The conversation so far goes back to the model with every message.'),
          'try' => $this->t('Send <code>My name is Ada.</code> then ask <code>What is my name?</code>'),
          'see' => $this->t('It answers Ada. Simple AI chat, two steps back, cannot.'),
        ],
        'level_3_9_url_summariser' => [
          'stage' => $this->t('The web'),
          'title' => $this->t('Summarise a web page'),
          'summary' => $this->t('Fetch a page from the internet, turn it into text, and have Claude summarise it.'),
          'try' => $this->t('Press <strong>Run</strong>, enter a URL like <code>https://www.drupal.org/about</code>, then <strong>Approve</strong> the request.'),
          'see' => $this->t('A short summary of the page. FlowDrop asks first because the workflow reaches outside the site.'),
        ],
        'lets_create_a_reasoner' => [
          'stage' => $this->t('Agents'),
          'title' => $this->t('An agent with a calculator'),
          'summary' => $this->t('The agent loop, built by hand: the model decides to use a calculator, uses it, and loops until it can answer.'),
          'try' => $this->t('<code>What is 1234 * 5678, plus 99?</code>'),
          'see' => $this->t('<code>7,006,751</code>, and each calculator call it made.'),
        ],
      ],
      'showcase' => [
        'dri_es_photo_finder' => [
          'title' => $this->t('Find photos on dri.es (no AI)'),
          'summary' => $this->t('A form lists the photo albums on dri.es, fetched live, and shows your pick as a gallery. No AI model involved.'),
          'try' => $this->t('Press <strong>Run</strong>, pick an album, then <strong>Submit</strong>.'),
          'see' => $this->t('A gallery of photos from that album.'),
        ],
        'dri_es_chat_agent' => [
          'title' => $this->t('Chat with dri.es'),
          'summary' => $this->t("An agent that searches Dries Buytaert's blog and photos to answer your question."),
          'try' => $this->t('<code>What has Dries written about AI recently?</code>'),
          'see' => $this->t('An answer built from real dri.es posts, with titles and dates.'),
        ],
      ],
      'blocks' => [
        'react_agent' => [
          'summary' => $this->t('The reusable agent loop that "Chat with dri.es" runs inside it. Give it tools and a question, and it loops until it can answer.'),
        ],
        'react_agent_with_tools' => [
          'summary' => $this->t('The same loop with a web-fetch tool built in, made to be called from another workflow.'),
        ],
        'http_get' => [
          'summary' => $this->t('Give it a URL, get the page back.'),
        ],
        'flowdrop_chat_processor' => [
          'summary' => $this->t("The FlowDrop Chat assistant's own pipeline, rebuilt as a workflow so you can compare the two."),
        ],
      ],
    ];
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
        // The key banner reflects the environment, which has no cache tag,
        // and the Try it links carry a per-session CSRF token.
        'max-age' => 0,
      ],
      'main' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fd-dashboard-main', 'fd-dashboard-main--constrained', 'fd-demo-start']],
      ] + $main,
    ];
  }

  /**
   * The page hero: what FlowDrop is, two ways in, and the canvas illustration.
   */
  private function hero(string $logo, ?array $primary, ?array $secondary): array {
    return $this->component('hero', array_filter([
      'eyebrow' => $this->t('FlowDrop demo'),
      'title' => $this->t('Build AI workflows you can watch run.'),
      'lead' => $this->t('FlowDrop is a visual workflow builder for Drupal. You wire small boxes together on a canvas, and data flows through them from left to right. This site comes with ready-made workflows: <strong>run one, watch it, then change it.</strong>'),
      'logo_url' => '/' . $logo,
      'primary' => $primary,
      'secondary' => $secondary,
      'canvas_label' => $this->t('Choose the tone'),
    ]));
  }

  /**
   * How to add the Anthropic key. Only rendered while it is missing.
   */
  private function keyCallout(): array {
    return $this->component('setup-callout', [
      'status' => 'missing',
      'title' => $this->t('No Anthropic API key yet'),
      'message' => $this->t('The first run and everything marked <em>offline</em> work without one, so start there. To unlock the workflows marked <em>AI key needed</em>, run this in the project directory, then reload this page:'),
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
   * The four words the page uses, each tied to the button that opens it.
   */
  private function concepts(): array {
    $items = [
      [
        'term' => $this->t('Node'),
        'icon' => 'node-type',
        'text' => $this->t('One box that does one job: take your message, change some text, ask an AI model, show a reply.'),
      ],
      [
        'term' => $this->t('Workflow'),
        'icon' => 'workflow',
        'text' => $this->t("Nodes wired together. Each wire carries one node's output into the next node's input."),
      ],
      [
        'term' => $this->t('Playground'),
        'icon' => 'playground',
        'button' => $this->t('Try it'),
        'text' => $this->t('Where you run a workflow. You chat on the right; on the left, each node lights up as it runs.'),
      ],
      [
        'term' => $this->t('Editor'),
        'icon' => 'create',
        'button' => $this->t('Open editor'),
        'text' => $this->t('Where you change a workflow: add nodes, rewire them, adjust their settings, save.'),
      ],
    ];
    foreach ($items as &$item) {
      $item = array_map('strval', $item);
      $item['icon'] = $this->icons->getIcon($item['icon']) ?? '';
    }
    unset($item);

    return $this->component('section', [
      'id' => 'fd-demo-concepts',
      'eyebrow' => $this->t('New to FlowDrop?'),
      'title' => $this->t('Four words you will see everywhere'),
    ], [
      'content' => $this->component('concepts', array_filter([
        'items' => $items,
        'tour_url' => self::TOUR_VIDEO,
        'placeholder_label' => (string) $this->t('Placeholder'),
        'placeholder_title' => (string) $this->t('30-second tour video'),
        'placeholder_text' => (string) $this->t('Coming soon: a workflow being run, changed in the editor, and run again.'),
      ])),
    ]);
  }

  /**
   * The guided first run: run a workflow, run one with a step more, change it.
   */
  private function firstRun(array $workflows): ?array {
    [$run_id, $change_id] = self::FIRST_RUN;
    if (!isset($workflows[$run_id], $workflows[$change_id])) {
      return NULL;
    }
    $run = $this->describe($workflows[$run_id]);
    $change = $this->describe($workflows[$change_id]);

    $steps = [
      [
        'title' => $this->t('Run it'),
        'text' => $this->t('Open <strong>Echo</strong>, type <code>Hello FlowDrop</code> and press <strong>Send</strong>. On the left, each node lights up as it runs. On the right, your words come back.'),
        'action_label' => $this->t('Try Echo'),
        'action_url' => $run['try_url'],
        'action_kind' => 'try',
      ],
      [
        'title' => $this->t('Add a step'),
        'text' => $this->t('Open <strong>Echo, but lowercase</strong>. Same chat, one more node in the middle: a Text Processor. Type <code>HELLO World</code> and you get <code>hello world</code> back.'),
        'action_label' => $this->t('Try it'),
        'action_url' => $change['try_url'],
        'action_kind' => 'try',
      ],
      [
        'title' => $this->t('Change it'),
        'text' => $this->t('Open that workflow in the editor. Click the <strong>⚙</strong> on the Text Processor node, set <strong>Operation</strong> to <code>uppercase</code>, press <strong>Save</strong>, and try it again. Set it back to <code>lowercase</code> when you are done.'),
        'action_label' => $this->t('Open editor'),
        'action_url' => $change['edit_url'],
        'action_kind' => 'edit',
      ],
    ];
    foreach ($steps as &$step) {
      $step = array_map('strval', array_filter($step, static fn($v) => $v !== NULL));
    }
    unset($step);

    return $this->component('section', [
      'id' => 'fd-demo-first-run',
      'eyebrow' => $this->t('Start here: three minutes, no AI key needed'),
      'title' => $this->t('Your first run'),
    ], [
      'content' => $this->component('first-run', [
        'steps' => $steps,
        'outro' => (string) $this->t('That is the whole loop: run, watch, change. Each workflow below adds one idea to it.'),
      ]),
    ]);
  }

  /**
   * The learning path: numbered steps, one idea each.
   */
  private function pathSection(array $workflows, array $entries): ?array {
    $steps = [];
    $number = 0;
    foreach ($entries as $id => $entry) {
      if (!isset($workflows[$id])) {
        continue;
      }
      $info = $this->describe($workflows[$id]);
      $steps[$id] = $this->component('path-step', $this->cardProps($workflows[$id], $info, $entry) + [
        'number' => (string) ++$number,
        'stage' => (string) $entry['stage'],
      ]);
    }
    if (!$steps) {
      return NULL;
    }

    return $this->component('section', [
      'id' => 'fd-demo-path',
      'eyebrow' => $this->t('Learn it'),
      'title' => $this->t('One new idea per step'),
      'description' => $this->t('Each workflow adds one idea to the one before. The ones marked offline run without an AI key.'),
    ], [
      'content' => ['#type' => 'html_tag', '#tag' => 'ol', '#attributes' => ['class' => ['fd-demo-path']], 'steps' => $steps],
    ]);
  }

  /**
   * A section of workflow cards.
   */
  private function cardSection(array $workflows, array $entries, array $section, string $icon): ?array {
    $grid = $this->cardGrid($workflows, $entries, $icon);
    return $grid ? $this->component('section', $section, ['content' => $grid]) : NULL;
  }

  /**
   * A grid of workflow cards, or NULL when none of the workflows exist.
   */
  private function cardGrid(array $workflows, array $entries, string $icon): ?array {
    $cards = [];
    foreach ($entries as $id => $entry) {
      if (!isset($workflows[$id])) {
        continue;
      }
      $info = $this->describe($workflows[$id]);
      $cards[$id] = $this->component('workflow-card', $this->cardProps($workflows[$id], $info, $entry) + [
        'icon' => $this->icons->getIcon($icon),
      ]);
    }
    if (!$cards) {
      return NULL;
    }
    return $this->component('grid', ['variant' => 'cards', 'stagger' => TRUE], ['default' => $cards], 'flowdrop_ui_components');
  }

  /**
   * Props shared by path-step and workflow-card.
   */
  private function cardProps(FlowDropWorkflow $workflow, array $info, array $entry): array {
    return array_filter([
      'title' => (string) ($entry['title'] ?? $workflow->label()),
      'description' => isset($entry['summary']) ? (string) $entry['summary'] : $info['description'],
      'try_text' => isset($entry['try']) ? (string) $entry['try'] : NULL,
      'see_text' => isset($entry['see']) ? (string) $entry['see'] : NULL,
      'needs' => $info['needs'],
      'node_count' => $info['node_count'],
      'try_url' => $info['try_url'],
      'edit_url' => $info['edit_url'],
      'try_label' => (string) $this->t('Try it'),
      'edit_label' => (string) $this->t('Open editor'),
    ], static fn($v) => $v !== NULL && $v !== '');
  }

  /**
   * "For developers": the building blocks and the rest of FlowDrop, closed.
   */
  private function developers(array $workflows, array $blocks): array {
    $content = [];
    $grid = $this->cardGrid($workflows, $blocks, 'structure');
    if ($grid) {
      $content['blocks_title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Building blocks'),
        '#attributes' => ['class' => ['fd-demo-subheading']],
      ];
      $content['blocks'] = $grid;
    }
    $content['explore_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('The rest of FlowDrop'),
      '#attributes' => ['class' => ['fd-demo-subheading']],
    ];
    $content['explore'] = $this->explore();

    return $this->component('section', [
      'id' => 'fd-demo-developers',
      'eyebrow' => $this->t('For developers'),
      'title' => $this->t('Under the hood'),
      'description' => $this->t('Sub-workflows the others call, and where FlowDrop keeps its runs, node types and keys.'),
      'collapsible' => TRUE,
    ], ['content' => $content]);
  }

  /**
   * Links to the rest of FlowDrop, as action-link cards.
   */
  private function explore(): array {
    $links = [
      ['entity.flowdrop_workflow.collection', $this->t('All workflows'), $this->t('Every workflow on this site, including the ones not listed here.'), 'workflow'],
      ['entity.flowdrop_workflow.add_form', $this->t('Create a workflow'), $this->t('Start from an empty canvas.'), 'create'],
      ['flowdrop.dashboard', $this->t('FlowDrop dashboard'), $this->t('Everything FlowDrop manages, in one place.'), 'category'],
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

    return $this->component('grid', ['variant' => 'actions', 'stagger' => TRUE], ['default' => $items], 'flowdrop_ui_components');
  }

  /**
   * What the page needs to know about one workflow.
   */
  private function describe(FlowDropWorkflow $workflow): array {
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

    // Try it goes straight into a new Playground session. Access is that of
    // the add-session form, which has the same permission; the direct route's
    // CSRF check would fail here, outside a request for it.
    $try = NULL;
    if (!in_array($id, self::EDITOR_ONLY, TRUE)
      && Url::fromRoute('flowdrop_playground.workflow.session.add', ['flowdrop_workflow' => $id])->access()) {
      $try = Url::fromRoute('fd_demo.try', ['flowdrop_workflow' => $id])->toString();
    }
    $url = Url::fromRoute('flowdrop.workflow.editor', ['flowdrop_workflow' => $id]);
    $edit = $url->access() ? $url->toString() : NULL;

    $description = trim((string) $workflow->getDescription());
    $description = ucfirst(preg_replace('/^Goal:\s*/', '', $description));

    return [
      'description' => $description,
      'needs' => $needs,
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
