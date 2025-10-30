<?php

namespace Drupal\factorial_crm\DTO;

/**
 * Used to create a new lead in Factorial CRM.
 */
class Lead {
  public function __construct(
    public string $firstName,
    public string $lastName,
    public string $company,
    public string $email,
    public string $drupalNickname = '',
    public string $leadSource = '',
    public string $message = '',
    public string $internalNote = '',
  ) {
  }
}
