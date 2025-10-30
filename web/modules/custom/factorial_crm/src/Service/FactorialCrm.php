<?php

declare(strict_types=1);

namespace Drupal\factorial_crm\Service;

use Drupal\factorial_crm\DTO\Lead;
use Psr\Log\LoggerInterface;

/**
 * Service to submit a new lead to Factorial CRM.
 */
final class FactorialCrm
{
    /**
     * Lead source for DrupalCon Vienna 2025.
     */
    private const LEAD_SOURCE = 'FAIR_E_G_DRUPALCON_VIENNA_2025'; // it's an enum, check the entity definition.

    /**
     * Constructs a FactorialCrm service.
     *
     * @param \Psr\Log\LoggerInterface $logger
     *   The logger service.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Submit a new lead to Factorial CRM.
     *
     * @param \Drupal\factorial_crm\DTO\Lead $lead
     *   The lead data to submit.
     *
     * @throws \Exception
     *   If the submission fails.
     */
    public function submitLead(Lead $lead): void
    {
      $this->logger->info('Processing lead submission', [
        'email' => $lead->email,
        'company' => $lead->company,
      ]);

      // This is a mock implementation for demo purposes.
      // No actual submission to Factorial CRM is done
      sleep(10);
    }


}
