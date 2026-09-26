<?php

declare(strict_types=1);

namespace Drupal\fd_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\flowdrop_workflow\Entity\FlowDropWorkflow;

/**
 * The "Start here" front page: the demo workflows in learning order.
 */
final class StartController extends ControllerBase {

  /**
   * Sections in reading order: workflow id => fallback description.
   *
   * The fallback is used only when the workflow has no description of its own.
   */
  private const SECTIONS = [
    'Learn the canvas, one idea per level' => [
      'level_0_1_echo' => '',
      'level_0_2_echo_but_lowercase' => '',
      'level_1_3_pick_your_transform' => '',
      'level_1_4_polite_greeter_templating' => '',
      'level_1_5_conditional_reply' => '',
      'level_2_6_simple_ai_chat' => '',
      'level_2_7_style_controlled_ai_reply' => 'Let the user pick a tone, then shape the AI reply with a prompt template.',
      'level_3_9_url_summariser' => '',
    ],
    'Build an LLM chat' => [
      'your_first_llm_call' => 'The smallest possible model call: a chat input straight into a chat model.',
      'giving_your_llm_chat_a_memory' => 'The same chat, now with conversation history so it remembers earlier turns.',
    ],
    'Agents' => [
      'lets_create_a_reasoner' => 'Build a reasoning loop by hand: the model decides, calls a calculator tool, and loops until it has an answer.',
    ],
    'Showcase: dri.es over MCP' => [
      'dri_es_photo_finder' => '',
      'dri_es_chat_agent' => '',
    ],
    'Building blocks' => [
      'react_agent' => 'The reusable ReAct loop that "Chat with dri.es" runs as a sub-workflow. Open it in the editor to see inside.',
      'react_agent_with_tools' => 'A ReAct loop with its own toolbox (web fetch), built to be called from another workflow. Open it in the editor to see inside.',
      'http_get' => '',
      'flowdrop_chat_processor' => '',
    ],
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
   * Renders the page.
   */
  public function page(): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['fd-demo-start']],
      '#attached' => ['library' => ['fd_demo/start']],
      '#cache' => [
        'tags' => ['config:flowdrop_workflow_list'],
        'contexts' => ['user.permissions'],
        // The key banner reflects the environment, which has no cache tag.
        'max-age' => 0,
      ],
    ];

    $build['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['fd-demo-start__intro']],
      '#value' => $this->t('This site is a FlowDrop playground. Work through the levels in order: each one adds a single idea. <strong>Try it</strong> opens the Playground, where you chat with the workflow and watch every node run. <strong>Open editor</strong> shows the canvas, where you can change it.'),
    ];

    if ($this->currentUser()->isAnonymous()) {
      $build['login'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Log in first. From the project directory run <code>ddev drush uli</code> and open the link it prints.'),
      ];
      return $build;
    }

    $build['key'] = $this->keyBanner();

    $workflows = FlowDropWorkflow::loadMultiple();
    foreach (self::SECTIONS as $title => $items) {
      $rows = [];
      foreach ($items as $id => $fallback) {
        if (isset($workflows[$id])) {
          $rows[] = $this->row($workflows[$id], $fallback);
        }
      }
      if (!$rows) {
        continue;
      }
      $build[] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $title,
      ];
      $build[] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Workflow'),
          $this->t('What it shows'),
          $this->t('Needs'),
          $this->t('Operations'),
        ],
        '#rows' => $rows,
      ];
    }

    return $build;
  }

  /**
   * Says whether the Anthropic key is there, and how to add it if not.
   */
  private function keyBanner(): array {
    $key = $this->entityTypeManager()->getStorage('key')->load('anthropic_key');
    $present = $key && trim((string) $key->getKeyValue()) !== '';

    if ($present) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['fd-demo-start__key', 'fd-demo-start__key--ok']],
        '#value' => $this->t('<strong>Anthropic API key found.</strong> Every workflow on this page can run.'),
      ];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['fd-demo-start__key', 'fd-demo-start__key--missing']],
      '#value' => $this->t('<strong>No Anthropic API key yet.</strong> Everything marked <em>offline</em> works without one, so start there. To unlock the workflows marked <em>AI key</em>, run this in the project directory, then reload this page:<pre>@commands</pre>', [
        '@commands' => implode("\n", [
          'echo "ANTHROPIC_KEY=sk-ant-..." >> .ddev/.env',
          'ddev restart',
          'ddev drush cr',
        ]),
      ]),
    ];
  }

  /**
   * Builds one table row.
   */
  private function row(FlowDropWorkflow $workflow, string $fallback): array {
    $id = (string) $workflow->id();
    $node_types = array_map(
      static fn(string $name): string => str_replace('flowdrop_node_type.flowdrop_node_type.', '', $name),
      $workflow->getDependencies()['config'] ?? [],
    );

    $badges = [];
    if ($this->usesAny($node_types, self::AI_NODE_TYPE_PREFIXES)) {
      $badges[] = $this->badge($this->t('AI key'), 'ai');
    }
    if ($this->usesAny($node_types, self::NETWORK_NODE_TYPE_PREFIXES)) {
      $badges[] = $this->badge($this->t('internet'), 'net');
    }
    if (!$badges) {
      $badges[] = $this->badge($this->t('offline'), 'offline');
    }

    $links = [];
    if (!in_array($id, self::EDITOR_ONLY, TRUE)) {
      $try = Url::fromRoute('flowdrop_playground.workflow.session.add', ['flowdrop_workflow' => $id]);
      if ($try->access()) {
        $links['try'] = ['title' => $this->t('Try it'), 'url' => $try];
      }
    }
    $edit = Url::fromRoute('flowdrop.workflow.editor', ['flowdrop_workflow' => $id]);
    if ($edit->access()) {
      $links['edit'] = ['title' => $this->t('Open editor'), 'url' => $edit];
    }

    $description = trim($workflow->getDescription()) ?: $fallback;
    $description = ucfirst(preg_replace('/^Goal:\s*/', '', $description));

    return [
      ['data' => ['#markup' => '<strong>' . $workflow->label() . '</strong>']],
      $description,
      ['data' => $badges],
      ['data' => ['#type' => 'operations', '#links' => $links]],
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

  /**
   * A small coloured label.
   */
  private function badge($text, string $variant): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['fd-demo-start__badge', 'fd-demo-start__badge--' . $variant]],
      '#value' => $text,
      '#suffix' => ' ',
    ];
  }

}
