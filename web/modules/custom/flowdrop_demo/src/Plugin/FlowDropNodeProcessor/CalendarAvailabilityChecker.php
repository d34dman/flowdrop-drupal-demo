<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\flowdrop\Attribute\FlowDropNodeProcessor;
use Drupal\flowdrop\Plugin\FlowDropNodeProcessor\AbstractFlowDropNodeProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop\DTO\ParameterBagInterface;
use Drupal\flowdrop_demo\Service\CalendarService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks team member availability in Google Calendar.
 *
 * This node integrates with Google Calendar API to check availability
 * of team members and suggest optimal meeting times.
 */
#[FlowDropNodeProcessor(
  id: "calendar_availability_checker",
  label: new TranslatableMarkup("Calendar Availability Checker"),
  type: "tool",
  supportedTypes: ["tool", "default"],
  category: "integrations",
  description: "Check Google Calendar availability for team members",
  version: "1.0.0",
  tags: ["calendar", "google", "availability", "scheduling"]
)]
class CalendarAvailabilityChecker extends AbstractFlowDropNodeProcessor {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected CalendarService $calendarService,
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
      $container->get('flowdrop_demo.calendar_service')
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
    $suggestedTeams = $params->getArray('suggested_teams', []);
    $submissionId = $params->getString('submission_id', 'unknown');
    $priorityLevel = $params->getString('priority_level', 'normal');

    $timeZone = $params->getString('timeZone', 'America/New_York');
    $lookAheadDays = $params->getInt('lookAheadDays', 7);
    $meetingDuration = $params->getInt('meetingDuration', 30);
    $workingHours = $params->getArray('workingHours', ['start' => '09:00', 'end' => '17:00']);

    // Get team member information.
    $teamMembers = $this->getTeamMembers($suggestedTeams);

    // Check availability for each team member.
    $availabilityResults = $this->checkTeamAvailability($teamMembers, $timeZone, $lookAheadDays, $meetingDuration, $workingHours);

    // Generate meeting time suggestions.
    $suggestedTimes = $this->generateMeetingTimeSuggestions($availabilityResults, $priorityLevel, $meetingDuration);

    $this->getLogger()->info('Calendar availability checked', [
      'submission_id' => $submissionId,
      'teams_checked' => count($suggestedTeams),
      'members_checked' => count($teamMembers),
      'available_slots' => count($suggestedTimes),
    ]);

    return [
      'submission_id' => $submissionId,
      'team_members' => $teamMembers,
      'availability_results' => $availabilityResults,
      'suggested_meeting_times' => $suggestedTimes,
      'priority_level' => $priorityLevel,
      'meeting_duration' => $meetingDuration,
      'checked_at' => date('Y-m-d H:i:s'),
      'calendar_metadata' => [
        'time_zone' => $timeZone,
        'look_ahead_days' => $lookAheadDays,
        'working_hours' => $workingHours,
      ],
    ];
  }

  /**
   * Get team members for suggested teams.
   */
  private function getTeamMembers(array $suggestedTeams): array {
    $teamMemberMap = [
      'Technical Support' => [
        ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'role' => 'Senior Support Engineer'],
        ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'role' => 'Support Specialist'],
      ],
      'Customer Success' => [
        ['name' => 'Carol Davis', 'email' => 'carol@example.com', 'role' => 'Customer Success Manager'],
        ['name' => 'David Wilson', 'email' => 'david@example.com', 'role' => 'Account Manager'],
      ],
      'Product Management' => [
        ['name' => 'Eve Brown', 'email' => 'eve@example.com', 'role' => 'Product Manager'],
        ['name' => 'Frank Miller', 'email' => 'frank@example.com', 'role' => 'Senior Product Manager'],
      ],
      'Engineering' => [
        ['name' => 'Grace Lee', 'email' => 'grace@example.com', 'role' => 'Lead Developer'],
        ['name' => 'Henry Chen', 'email' => 'henry@example.com', 'role' => 'Software Engineer'],
      ],
      'Sales Team' => [
        ['name' => 'Ivy Taylor', 'email' => 'ivy@example.com', 'role' => 'Sales Representative'],
        ['name' => 'Jack Anderson', 'email' => 'jack@example.com', 'role' => 'Sales Manager'],
      ],
    ];

    $members = [];
    foreach ($suggestedTeams as $team) {
      $cleanTeam = str_replace(' (Priority)', '', $team);
      if (isset($teamMemberMap[$cleanTeam])) {
        $members = array_merge($members, $teamMemberMap[$cleanTeam]);
      }
    }

    // Remove duplicates based on email.
    $uniqueMembers = [];
    foreach ($members as $member) {
      $uniqueMembers[$member['email']] = $member;
    }

    return array_values($uniqueMembers);
  }

  /**
   * Check availability for team members.
   */
  private function checkTeamAvailability(array $teamMembers, string $timeZone, int $lookAheadDays, int $duration, array $workingHours): array {
    $results = [];

    foreach ($teamMembers as $member) {
      $availability = $this->calendarService->checkAvailability(
        $member['email'],
        $timeZone,
        $lookAheadDays,
        $duration,
        $workingHours
      );

      $results[$member['email']] = [
        'member' => $member,
        'availability' => $availability,
        'next_available' => $availability['next_available'] ?? NULL,
        'busy_periods' => $availability['busy_periods'] ?? [],
        'free_slots' => $availability['free_slots'] ?? [],
      ];
    }

    return $results;
  }

  /**
   * Generate meeting time suggestions.
   */
  private function generateMeetingTimeSuggestions(array $availabilityResults, string $priorityLevel, int $duration): array {
    $suggestions = [];

    // Collect all free slots from all team members.
    $allFreeSlots = [];
    foreach ($availabilityResults as $memberEmail => $result) {
      foreach ($result['free_slots'] as $slot) {
        $allFreeSlots[] = [
          'start_time' => $slot['start'],
          'end_time' => $slot['end'],
          'member_email' => $memberEmail,
          'member_name' => $result['member']['name'],
          'member_role' => $result['member']['role'],
        ];
      }
    }

    // Sort by start time.
    usort($allFreeSlots, function ($a, $b) {
      return strtotime($a['start_time']) - strtotime($b['start_time']);
    });

    // Generate suggestions based on priority.
    $maxSuggestions = $priorityLevel === 'high' ? 5 : 3;
    $suggestionCount = 0;

    foreach ($allFreeSlots as $slot) {
      if ($suggestionCount >= $maxSuggestions) {
        break;
      }

      $startTime = new \DateTime($slot['start_time']);
      $endTime = clone $startTime;
      $endTime->add(new \DateInterval("PT{$duration}M"));

      $suggestions[] = [
        'option_id' => 'option_' . ($suggestionCount + 1),
        'start_time' => $startTime->format('Y-m-d H:i:s'),
        'end_time' => $endTime->format('Y-m-d H:i:s'),
        'duration_minutes' => $duration,
        'available_member' => [
          'name' => $slot['member_name'],
          'email' => $slot['member_email'],
          'role' => $slot['member_role'],
        ],
        'formatted_time' => $startTime->format('l, F j, Y \a\t g:i A'),
        'is_priority' => $priorityLevel === 'high',
      ];

      $suggestionCount++;
    }

    return $suggestions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams(array $inputs): bool {
    // Validate that we have suggested teams.
    if (!isset($inputs['suggested_teams'])) {
      return FALSE;
    }

    // Suggested teams should be an array.
    if (!is_array($inputs['suggested_teams'])) {
      return FALSE;
    }

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
        'suggested_teams' => [
          'type' => 'array',
          'title' => 'Suggested Teams',
          'description' => 'Teams that should handle this request',
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
        'timeZone' => [
          'type' => 'string',
          'title' => 'Time Zone',
          'description' => 'Time zone for calendar operations',
          'default' => 'America/New_York',
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'lookAheadDays' => [
          'type' => 'integer',
          'title' => 'Look Ahead Days',
          'description' => 'Number of days to look ahead for availability',
          'minimum' => 1,
          'maximum' => 30,
          'default' => 7,
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'meetingDuration' => [
          'type' => 'integer',
          'title' => 'Meeting Duration (minutes)',
          'description' => 'Default meeting duration in minutes',
          'minimum' => 15,
          'maximum' => 240,
          'default' => 30,
          'flowdrop' => [
            'configurable' => TRUE,
            'connectable' => FALSE,
            'required' => FALSE,
          ],
        ],
        'workingHours' => [
          'type' => 'object',
          'title' => 'Working Hours',
          'description' => 'Working hours configuration',
          'default' => ['start' => '09:00', 'end' => '17:00'],
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
        'submission_id' => [
          'type' => 'string',
          'description' => 'Unique submission identifier',
        ],
        'team_members' => [
          'type' => 'array',
          'description' => 'Team members checked for availability',
        ],
        'availability_results' => [
          'type' => 'object',
          'description' => 'Detailed availability results for each team member',
        ],
        'suggested_meeting_times' => [
          'type' => 'array',
          'description' => 'Suggested meeting time options',
        ],
        'priority_level' => [
          'type' => 'string',
          'description' => 'Priority level of the request',
        ],
        'meeting_duration' => [
          'type' => 'integer',
          'description' => 'Meeting duration in minutes',
        ],
        'checked_at' => [
          'type' => 'string',
          'description' => 'Timestamp when availability was checked',
        ],
        'calendar_metadata' => [
          'type' => 'object',
          'description' => 'Calendar configuration metadata',
        ],
      ],
    ];
  }

}
