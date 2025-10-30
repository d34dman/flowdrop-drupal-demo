<?php

namespace Drupal\vienna_2025_flowdrop\DTO;

class Vienna2024UserDTO
{
  public function __construct(
    public string $firstName,
    public string $lastName,
    public string $company,
    public string $email,
    public string $drupalNickname,
    public string $message,
    public string $internalNote,
    public string $preference,
  ) {}

}
