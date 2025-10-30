<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_frontdesk\Enum;

/**
 * Enum for frontdesk form steps.
 *
 * Represents the various steps in the frontdesk registration flow,
 * providing type-safe step identification throughout the application.
 */
enum FrontdeskStep: int {

  /**
   * Step 1: AI preference selection.
   */
  case AI_PREFERENCE = 1;

  /**
   * Step 2: Drupal.org username lookup.
   */
  case DRUPAL_USER_LOOKUP = 2;

  /**
   * Step 3: User information update/correction.
   */
  case USER_INFO_UPDATE = 3;

  /**
   * Step 4: Company name submission (optional).
   */
  case COMPANY_SUBMISSION = 4;

  /**
   * Step 5: Feedback submission.
   */
  case FEEDBACK_SUBMISSION = 5;

  /**
   * Step 6: Final registration submission.
   */
  case FINAL_SUBMISSION = 6;

  /**
   * Step 7: Coffee/wait screen (for users who want more information).
   */
  case COFFEE_WAIT_SCREEN = 7;

  /**
   * Step 8: Success/completion screen.
   */
  case SUCCESS_SCREEN = 8;

  /**
   * Gets a human-readable label for the step.
   *
   * @return string
   *   The step label.
   */
  public function label(): string {
    return match($this) {
      self::AI_PREFERENCE => "AI Preference",
      self::DRUPAL_USER_LOOKUP => "Drupal User Lookup",
      self::USER_INFO_UPDATE => "User Info Update",
      self::COMPANY_SUBMISSION => "Company Submission",
      self::FEEDBACK_SUBMISSION => "Feedback Submission",
      self::FINAL_SUBMISSION => "Final Submission",
      self::COFFEE_WAIT_SCREEN => "Coffee Wait Screen",
      self::SUCCESS_SCREEN => "Success Screen",
    };
  }

  /**
   * Checks if this step can be skipped based on prefilled data.
   *
   * @return bool
   *   TRUE if the step is skippable, FALSE otherwise.
   */
  public function isSkippable(): bool {
    return match($this) {
      self::DRUPAL_USER_LOOKUP,
      self::USER_INFO_UPDATE,
      self::COMPANY_SUBMISSION => TRUE,
      default => FALSE,
    };
  }

  /**
   * Checks if this step's data is optional (not required for submission).
   *
   * @return bool
   *   TRUE if the step data is optional, FALSE if required.
   */
  public function isOptional(): bool {
    return match($this) {
      self::COMPANY_SUBMISSION,
      self::FEEDBACK_SUBMISSION => TRUE,
      default => FALSE,
    };
  }

  /**
   * Converts an array of step enum cases to their integer values.
   *
   * @param array<self> $steps
   *   Array of FrontdeskStep enum cases.
   *
   * @return array<int>
   *   Array of integer step values.
   */
  public static function toIntArray(array $steps): array {
    return array_map(fn(self $step) => $step->value, $steps);
  }

}

