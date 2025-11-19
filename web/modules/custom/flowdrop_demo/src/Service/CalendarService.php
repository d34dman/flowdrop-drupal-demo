<?php

declare(strict_types=1);

namespace Drupal\flowdrop_demo\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for Google Calendar integration.
 *
 * This service simulates Google Calendar API interactions for
 * checking availability and creating calendar events.
 */
class CalendarService {

  public function __construct(
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected ClientInterface $httpClient,
  ) {}

  /**
   * Check availability for a team member.
   *
   * @param string $email
   *   Team member email address.
   * @param string $timeZone
   *   Time zone for the calendar check.
   * @param int $lookAheadDays
   *   Number of days to look ahead.
   * @param int $duration
   *   Meeting duration in minutes.
   * @param array $workingHours
   *   Working hours configuration.
   *
   * @return array
   *   Availability information.
   */
  public function checkAvailability(string $email, string $timeZone, int $lookAheadDays, int $duration, array $workingHours): array {
    // Simulate API call to Google Calendar
    // In real implementation, this would use Google Calendar API.
    $this->loggerFactory->get('flowdrop_demo')->info('Checking calendar availability', [
      'email' => $email,
      'time_zone' => $timeZone,
      'look_ahead_days' => $lookAheadDays,
    ]);

    // Generate simulated availability data.
    return $this->generateSimulatedAvailability($email, $timeZone, $lookAheadDays, $duration, $workingHours);
  }

  /**
   * Create a calendar event.
   *
   * @param array $eventData
   *   Event data including attendees, time, etc.
   *
   * @return array
   *   Created event information.
   */
  public function createEvent(array $eventData): array {
    // Simulate event creation.
    $this->loggerFactory->get('flowdrop_demo')->info('Creating calendar event', [
      'title' => $eventData['title'] ?? 'Meeting',
      'attendees' => count($eventData['attendees'] ?? []),
    ]);

    return [
      'event_id' => 'event_' . bin2hex(random_bytes(8)),
      'created_at' => date('Y-m-d H:i:s'),
      'calendar_url' => 'https://calendar.google.com/event?eid=' . bin2hex(random_bytes(16)),
      'status' => 'confirmed',
    ];
  }

  /**
   * Generate simulated availability data.
   */
  private function generateSimulatedAvailability(string $email, string $timeZone, int $lookAheadDays, int $duration, array $workingHours): array {
    $freeSlots = [];
    $busyPeriods = [];
    $now = new \DateTime('now', new \DateTimeZone($timeZone));

    // Generate availability for each day.
    for ($day = 0; $day < $lookAheadDays; $day++) {
      $currentDay = clone $now;
      $currentDay->add(new \DateInterval("P{$day}D"));

      // Skip weekends for business availability.
      if (in_array($currentDay->format('w'), ['0', '6'])) {
        continue;
      }

      // Generate some busy periods (simulated meetings)
      $dayBusyPeriods = $this->generateBusyPeriods($currentDay, $workingHours);
      $busyPeriods = array_merge($busyPeriods, $dayBusyPeriods);

      // Generate free slots between busy periods.
      $dayFreeSlots = $this->generateFreeSlots($currentDay, $workingHours, $dayBusyPeriods, $duration);
      $freeSlots = array_merge($freeSlots, $dayFreeSlots);
    }

    // Find next available slot.
    $nextAvailable = !empty($freeSlots) ? $freeSlots[0]['start'] : NULL;

    return [
      'email' => $email,
      'time_zone' => $timeZone,
      'free_slots' => $freeSlots,
      'busy_periods' => $busyPeriods,
      'next_available' => $nextAvailable,
      'total_free_slots' => count($freeSlots),
      'checked_at' => $now->format('Y-m-d H:i:s'),
    ];
  }

  /**
   * Generate simulated busy periods for a day.
   */
  private function generateBusyPeriods(\DateTime $day, array $workingHours): array {
    $busyPeriods = [];
    $startHour = (int) substr($workingHours['start'], 0, 2);
    $endHour = (int) substr($workingHours['end'], 0, 2);

    // Randomly generate 1-3 busy periods per day.
    $numBusyPeriods = rand(1, 3);

    for ($i = 0; $i < $numBusyPeriods; $i++) {
      $busyStart = clone $day;
      $busyStart->setTime(rand($startHour, $endHour - 2), rand(0, 59));

      $busyEnd = clone $busyStart;
      $busyEnd->add(new \DateInterval('PT' . rand(30, 120) . 'M'));

      $busyPeriods[] = [
        'start' => $busyStart->format('Y-m-d H:i:s'),
        'end' => $busyEnd->format('Y-m-d H:i:s'),
        'title' => 'Existing Meeting',
      ];
    }

    return $busyPeriods;
  }

  /**
   * Generate free slots for a day.
   */
  private function generateFreeSlots(\DateTime $day, array $workingHours, array $busyPeriods, int $duration): array {
    $freeSlots = [];
    $startHour = (int) substr($workingHours['start'], 0, 2);
    $endHour = (int) substr($workingHours['end'], 0, 2);

    // Create working day start and end times.
    $dayStart = clone $day;
    $dayStart->setTime($startHour, 0);

    $dayEnd = clone $day;
    $dayEnd->setTime($endHour, 0);

    // Sort busy periods by start time.
    usort($busyPeriods, function ($a, $b) {
      return strtotime($a['start']) - strtotime($b['start']);
    });

    $currentTime = clone $dayStart;

    // Find gaps between busy periods.
    foreach ($busyPeriods as $busyPeriod) {
      $busyStart = new \DateTime($busyPeriod['start']);

      // Check if there's a gap before this busy period.
      if ($currentTime < $busyStart) {
        $gapDuration = ($busyStart->getTimestamp() - $currentTime->getTimestamp()) / 60;

        if ($gapDuration >= $duration) {
          $slotEnd = clone $currentTime;
          $slotEnd->add(new \DateInterval("PT{$duration}M"));

          $freeSlots[] = [
            'start' => $currentTime->format('Y-m-d H:i:s'),
            'end' => $slotEnd->format('Y-m-d H:i:s'),
            'duration_minutes' => $duration,
          ];
        }
      }

      // Move current time to end of busy period.
      $currentTime = new \DateTime($busyPeriod['end']);
    }

    // Check for free time after the last busy period.
    if ($currentTime < $dayEnd) {
      $remainingDuration = ($dayEnd->getTimestamp() - $currentTime->getTimestamp()) / 60;

      if ($remainingDuration >= $duration) {
        $slotEnd = clone $currentTime;
        $slotEnd->add(new \DateInterval("PT{$duration}M"));

        $freeSlots[] = [
          'start' => $currentTime->format('Y-m-d H:i:s'),
          'end' => $slotEnd->format('Y-m-d H:i:s'),
          'duration_minutes' => $duration,
        ];
      }
    }

    return $freeSlots;
  }

}
