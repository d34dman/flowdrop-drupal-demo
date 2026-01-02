<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Presents meeting time options to users for selection.
 *
 * This node creates a user interface for selecting from available
 * meeting times and captures the user's choice for further processing.
 */
#[FlowDropNodeProcessor(
  id: "user_choice_presenter",
  label: new TranslatableMarkup("User Choice Presenter"),
  type: "default",
  supportedTypes: ["default"],
  category: "ui",
  description: "Present meeting time options to users and capture their selection",
  version: "1.0.0",
  tags: ["ui", "user-choice", "scheduling", "interaction"]
)]
class UserChoicePresenter extends AbstractFlowDropNodeProcessor {

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
  protected function process(ParameterBagInterface $params): array {
    $suggestedTimes = $params->getArray('suggested_meeting_times', []);
    $submissionId = $params->getString('submission_id', 'unknown');
    $priorityLevel = $params->getString('priority_level', 'normal');

    $presentationMode = $params->getString('presentationMode', 'interactive');
    $autoSelectBest = $params->getBool('autoSelectBest', FALSE);
    $includeDeclineOption = $params->getBool('includeDeclineOption', TRUE);

    // Generate user interface options.
    $presentationData = $this->generatePresentationData($suggestedTimes, $presentationMode, $includeDeclineOption);

    // Handle auto-selection if configured.
    $selectedOption = NULL;
    if ($autoSelectBest && !empty($suggestedTimes)) {
      $selectedOption = $this->autoSelectBestOption($suggestedTimes, $priorityLevel);
    }

    $this->getLogger()->info('User choice presentation prepared', [
      'submission_id' => $submissionId,
      'options_count' => count($suggestedTimes),
      'presentation_mode' => $presentationMode,
      'auto_selected' => $selectedOption !== NULL,
    ]);

    return [
      'submission_id' => $submissionId,
      'presentation_data' => $presentationData,
      'available_options' => $suggestedTimes,
      'selected_option' => $selectedOption,
      'presentation_mode' => $presentationMode,
      'user_interaction_required' => $selectedOption === NULL,
      'presentation_url' => $this->generatePresentationUrl($submissionId),
      'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
      'created_at' => date('Y-m-d H:i:s'),
    ];
  }

  /**
   * Generate presentation data for the user interface.
   */
  private function generatePresentationData(array $suggestedTimes, string $mode, bool $includeDecline): array {
    $presentationData = [
      'title' => 'Select Your Preferred Meeting Time',
      'description' => 'Please choose from the available meeting times below, or let us know if none work for you.',
      'options' => [],
      'actions' => [],
    ];

    // Format meeting time options.
    foreach ($suggestedTimes as $index => $timeOption) {
      $presentationData['options'][] = [
        'option_id' => $timeOption['option_id'],
        'display_text' => $this->formatTimeOptionForDisplay($timeOption),
        'detailed_info' => $this->generateDetailedTimeInfo($timeOption),
        'is_priority' => $timeOption['is_priority'] ?? FALSE,
        'action_button' => [
          'text' => 'Select This Time',
          'value' => $timeOption['option_id'],
          'style' => $timeOption['is_priority'] ? 'primary' : 'secondary',
        ],
      ];
    }

    // Add additional actions.
    if ($includeDecline) {
      $presentationData['actions'][] = [
        'action_id' => 'decline_all',
        'text' => 'None of these times work for me',
        'style' => 'link',
        'description' => 'Request alternative times or provide your availability',
      ];
    }

    $presentationData['actions'][] = [
      'action_id' => 'request_callback',
      'text' => 'Request a callback instead',
      'style' => 'secondary',
      'description' => 'Have someone call you at your preferred time',
    ];

    // Customize based on presentation mode.
    if ($mode === 'email') {
      $presentationData = $this->customizeForEmail($presentationData);
    }
    elseif ($mode === 'sms') {
      $presentationData = $this->customizeForSms($presentationData);
    }

    return $presentationData;
  }

  /**
   * Format time option for user display.
   */
  private function formatTimeOptionForDisplay(array $timeOption): string {
    $startTime = new \DateTime($timeOption['start_time']);
    $member = $timeOption['available_member'];

    $formattedTime = $startTime->format('l, F j \a\t g:i A');
    $duration = $timeOption['duration_minutes'];

    return "{$formattedTime} ({$duration} minutes) with {$member['name']} ({$member['role']})";
  }

  /**
   * Generate detailed information for a time option.
   */
  private function generateDetailedTimeInfo(array $timeOption): array {
    return [
      'date' => (new \DateTime($timeOption['start_time']))->format('Y-m-d'),
      'time' => (new \DateTime($timeOption['start_time']))->format('H:i'),
      'duration' => $timeOption['duration_minutes'] . ' minutes',
      'team_member' => [
        'name' => $timeOption['available_member']['name'],
        'role' => $timeOption['available_member']['role'],
        'email' => $timeOption['available_member']['email'],
      ],
      // Could be configurable.
      'meeting_type' => 'Video Call',
      'preparation_needed' => FALSE,
    ];
  }

  /**
   * Auto-select the best option based on criteria.
   */
  private function autoSelectBestOption(array $suggestedTimes, string $priorityLevel): ?array {
    if (empty($suggestedTimes)) {
      return NULL;
    }

    // Sort options by preference.
    $sortedOptions = $suggestedTimes;
    usort($sortedOptions, function ($a, $b) use ($priorityLevel) {
      // Prioritize high-priority items.
      if ($priorityLevel === 'high') {
        if ($a['is_priority'] && !$b['is_priority']) {
          return -1;
        }
        if (!$a['is_priority'] && $b['is_priority']) {
          return 1;
        }
      }

      // Then sort by earliest time.
      return strtotime($a['start_time']) - strtotime($b['start_time']);
    });

    return [
      'selected_option_id' => $sortedOptions[0]['option_id'],
      'selected_time' => $sortedOptions[0],
      'selection_reason' => 'Auto-selected based on earliest available time and priority',
      'auto_selected' => TRUE,
    ];
  }

  /**
   * Customize presentation for email.
   */
  private function customizeForEmail(array $presentationData): array {
    $presentationData['email_subject'] = 'Meeting Time Selection Required';
    $presentationData['email_template'] = 'meeting_time_selection';
    $presentationData['call_to_action'] = 'Click the link below to view your options and make your selection.';

    return $presentationData;
  }

  /**
   * Customize presentation for SMS.
   */
  private function customizeForSms(array $presentationData): array {
    // Simplify for SMS.
    $presentationData['sms_message'] = 'Meeting times available. Reply with option number or visit link to select.';
    $presentationData['short_options'] = [];

    foreach ($presentationData['options'] as $index => $option) {
      $presentationData['short_options'][] = [
        'number' => $index + 1,
        'text' => (new \DateTime($option['detailed_info']['date'] . ' ' . $option['detailed_info']['time']))->format('M j g:iA'),
      ];
    }

    return $presentationData;
  }

  /**
   * Generate presentation URL.
   */
  private function generatePresentationUrl(string $submissionId): string {
    // In a real implementation, this would generate a secure, tokenized URL.
    return "https://example.com/meeting-selection/{$submissionId}?token=" . bin2hex(random_bytes(16));
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        // Input parameters.
        'suggested_meeting_times' => [
          'type' => 'array',
          'title' => 'Suggested Meeting Times',
          'description' => 'Available meeting time options',
          'flowdrop' => [
            'configurable' => FALSE,
            'connectable' => TRUE,
            'required' => TRUE,
          ],
        ],
        'submission_id' => [
          'type' => 'string',
          'title' => 'Submission ID',
          'description' => 'Unique submission identifier',
          'flowdrop' => [
            'configurable' => FALSE,
            'connectable' => TRUE,
            'required' => FALSE,
          ],
        ],
        'priority_level' => [
          'type' => 'string',
          'title' => 'Priority Level',
          'description' => 'Priority level of the request',
          'flowdrop' => [
            'configurable' => FALSE,
            'connectable' => TRUE,
            'required' => FALSE,
          ],
        ],
        // Config parameters.
        'presentationMode' => [
          'type' => 'string',
          'title' => 'Presentation Mode',
          'description' => 'How to present options to the user',
          'enum' => ['interactive', 'email', 'sms'],
          'default' => 'interactive',
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'autoSelectBest' => [
          'type' => 'boolean',
          'title' => 'Auto-Select Best Option',
          'description' => 'Automatically select the best available option',
          'default' => FALSE,
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'includeDeclineOption' => [
          'type' => 'boolean',
          'title' => 'Include Decline Option',
          'description' => 'Allow users to decline all suggested times',
          'default' => TRUE,
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
        'submission_id' => [
          'type' => 'string',
          'description' => 'Unique submission identifier',
        ],
        'presentation_data' => [
          'type' => 'object',
          'description' => 'Formatted data for user presentation',
        ],
        'available_options' => [
          'type' => 'array',
          'description' => 'All available meeting time options',
        ],
        'selected_option' => [
          'type' => 'mixed',
          'description' => 'Auto-selected option (if applicable)',
        ],
        'presentation_mode' => [
          'type' => 'string',
          'description' => 'Mode of presentation (interactive, email, sms)',
        ],
        'user_interaction_required' => [
          'type' => 'boolean',
          'description' => 'Whether user interaction is required',
        ],
        'presentation_url' => [
          'type' => 'string',
          'description' => 'URL for user to make selection',
        ],
        'expires_at' => [
          'type' => 'string',
          'description' => 'When the selection opportunity expires',
        ],
        'created_at' => [
          'type' => 'string',
          'description' => 'When the presentation was created',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $inputs): bool {
    // Validate that we have suggested meeting times.
    if (empty($inputs['suggested_meeting_times'])) {
      return FALSE;
    }

    // Validate that suggested times is an array.
    if (!is_array($inputs['suggested_meeting_times'])) {
      return FALSE;
    }

    return TRUE;
  }

}
