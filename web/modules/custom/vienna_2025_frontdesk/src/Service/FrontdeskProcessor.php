<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_frontdesk\Service;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\factorial_crm\DTO\Lead;
use Drupal\factorial_crm\Service\FactorialCrm;
use Drupal\vienna_2025_flowdrop\DTO\Vienna2024UserDTO;
use Drupal\vienna_2025_flowdrop\FlowdropExecutor;
use Drupal\vienna_2025_frontdesk\Enum\FrontdeskStep;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service for processing frontdesk form submissions and responses.
 *
 * This service handles all business logic related to the frontdesk process,
 * including CRM lookups, API calls, data validation, and response building.
 */
final class FrontdeskProcessor {

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  public function __construct(
    private readonly FactorialCrm $factorialCrm,
    private readonly ClientFactory $httpClientFactory,
    private readonly SessionManagerInterface $sessionManager,
    private readonly RequestStack $requestStack,
    private readonly FlowdropExecutor $executor,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get("vienna_2025_frontdesk");
  }

  /**
   * Builds a standardized API response.
   *
   * @param bool $success
   *   Whether the operation was successful.
   * @param string|null $message
   *   Optional message to display to the user.
   * @param array<string, mixed> $data
   *   Optional data to return.
   * @param array<string, mixed> $prefilledData
   *   Optional prefilled form data from CRM.
   * @param array<\Drupal\vienna_2025_frontdesk\Enum\FrontdeskStep> $skipSteps
   *   Optional array of steps that can be skipped.
   *
   * @return array<string, mixed>
   *   Standardized response array.
   */
  public function buildResponse(
    bool $success,
    ?string $message = NULL,
    array $data = [],
    array $prefilledData = [],
    array $skipSteps = []
  ): array {
    $response = [
      "success" => $success,
    ];

    if ($message !== NULL) {
      $response["message"] = $message;
    }

    if (!empty($data)) {
      $response["data"] = $data;
    }

    if (!empty($prefilledData)) {
      $response["prefilled"] = $prefilledData;
    }

    if (!empty($skipSteps)) {
      $response["skipSteps"] = FrontdeskStep::toIntArray($skipSteps);
    }

    return $response;
  }

  /**
   * Gets the current session.
   *
   * @return \Symfony\Component\HttpFoundation\Session\SessionInterface|null
   *   The session interface or NULL if not available.
   */
  private function getSession(): ?SessionInterface {
    $request = $this->requestStack->getCurrentRequest();
    return $request?->getSession();
  }

  /**
   * Gets the current pipeline from session.
   *
   * @return \Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface|null
   *   The pipeline or NULL if not available.
   */
  private function getPipeline(): ?\Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface {
    $session = $this->getSession();
    if ($session === NULL) {
      return NULL;
    }

    $pipelineId = $session->get("frontdesk.pipeline");
    if ($pipelineId === NULL) {
      return NULL;
    }

    return $this->executor->loadPipeline($pipelineId);
  }

  /**
   * Sets data in the pipeline.
   *
   * @param string $key
   *   The data key.
   * @param mixed $value
   *   The value to store.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  private function setPipelineData(string $key, mixed $value): bool {
    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return FALSE;
    }

    $data = $pipeline->getInputData();
    $data[$key] = $value;
    $pipeline->setInputData($data);
    $pipeline->save();

    return TRUE;
  }

  /**
   * Gets data from the pipeline.
   *
   * @param string $key
   *   The data key.
   * @param mixed $default
   *   Default value if key doesn't exist.
   *
   * @return mixed
   *   The stored value or default.
   */
  private function getPipelineData(string $key, mixed $default = NULL): mixed {
    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return $default;
    }

    $data = $pipeline->getInputData();
    return $data[$key] ?? $default;
  }

  /**
   * Removes data from the pipeline.
   *
   * @param string $key
   *   The data key to remove.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  private function removePipelineData(string $key): bool {
    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return FALSE;
    }

    $data = $pipeline->getInputData();
    unset($data[$key]);
    $pipeline->setInputData($data);
    $pipeline->save();

    return TRUE;
  }

  /**
   * Loads user information from pipeline as Vienna2024UserDTO.
   *
   * @return \Drupal\vienna_2025_flowdrop\DTO\Vienna2024UserDTO|null
   *   The user DTO with data from pipeline, or NULL if pipeline not available.
   */
  public function loadUserInfoFromPipeline(): ?Vienna2024UserDTO {
    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return NULL;
    }

    // Get all data from pipeline
    $userInfo = $this->getPipelineData("user_info", []);
    $company = $this->getPipelineData("company", "");
    $drupalUsername = $this->getPipelineData("drupal_username", "");
    $feedback = $this->getPipelineData("feedback", "");
    $aiPreference = $this->getPipelineData("ai_preference", "");
    $sessionId = $this->getPipelineData("session_id", "");

    // Build internal note with metadata
    $internalNote = sprintf(
      "DrupalCamp Vienna 2025 Frontdesk\nSession ID: %s",
      $sessionId
    );

    // Create and return the DTO
    return new Vienna2024UserDTO(
      firstName: is_array($userInfo) && isset($userInfo["firstName"]) ? $userInfo["firstName"] : "",
      lastName: is_array($userInfo) && isset($userInfo["lastName"]) ? $userInfo["lastName"] : "",
      company: $company,
      email: is_array($userInfo) && isset($userInfo["email"]) ? $userInfo["email"] : "",
      drupalNickname: $drupalUsername,
      message: $feedback,
      internalNote: $internalNote,
      preference: $aiPreference,
    );
  }

  /**
   * Executes the pipeline with current data.
   *
   * This method resets all pipeline jobs to pending state before execution,
   * ensuring a clean run with the current input data.
   *
   * @return bool
   *   TRUE if execution was successful, FALSE otherwise.
   */
  private function executePipelineWithCurrentData(): bool {
    try {
      $pipeline = $this->getPipeline();
      if ($pipeline === NULL) {
        $this->logger->warning("Cannot execute pipeline: Pipeline not available");
        return FALSE;
      }

      $userDto = $this->loadUserInfoFromPipeline();
      if ($userDto === NULL) {
        $this->logger->warning("Cannot execute pipeline: Could not load user data");
        return FALSE;
      }

      // Reset all jobs to pending state before execution.
      // This ensures a clean execution with the latest input data.
      $reset_count = $this->executor->resetPipelineJobs($pipeline, TRUE);
      $this->logger->debug("Reset @count jobs for pipeline @pipeline_id", [
        "@count" => $reset_count,
        "@pipeline_id" => $pipeline->id(),
      ]);

      // Execute the pipeline with current user data.
      $this->executor->executePipeline($pipeline, $userDto);

      $this->logger->info("Pipeline executed successfully for pipeline ID: @pipeline_id", [
        "@pipeline_id" => $pipeline->id(),
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error("Failed to execute pipeline: @message", [
        "@message" => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if user data exists in CRM by email or Drupal username.
   *
   * @param string|null $email
   *   The email address to check.
   * @param string|null $drupalUsername
   *   The Drupal.org username to check.
   *
   * @return array<string, mixed>|null
   *   User data from CRM if found, NULL otherwise.
   */
  public function checkCrmForUserData(?string $email = NULL, ?string $drupalUsername = NULL): ?array {
    // TODO: Implement actual CRM lookup
    // This is a placeholder that can be extended to check:
    // - Factorial CRM database
    // - Drupal user database
    // - External CRM API

    // Example return structure:
    // return [
    //   'firstName' => 'John',
    //   'lastName' => 'Doe',
    //   'email' => 'john.doe@example.com',
    //   'company' => 'Acme Corp',
    //   'drupalUsername' => 'johndoe',
    // ];

    return NULL;
  }

  /**
   * Initializes a new pipeline for the frontdesk workflow.
   *
   * @return bool
   *   TRUE if initialization was successful, FALSE otherwise.
   */
  public function initialize(): bool {
    try {
      $session = $this->getSession();
      if ($session === NULL) {
        $this->logger->error("Cannot initialize: Session not available");
        return FALSE;
      }

      $workflow = $this->executor->loadWorkflow("trial_02");
      if ($workflow === NULL) {
        $this->logger->error("Cannot initialize: Workflow 'trial_02' not found");
        return FALSE;
      }

      $pipeline = $this->executor->generateNewPipeline($workflow);
      $pipeline->setInputData([]);
      $pipeline->save();
      $session->set("frontdesk.pipeline", $pipeline->id());

      $this->logger->info("Pipeline initialized successfully: @pipeline_id", [
        "@pipeline_id" => $pipeline->id(),
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error("Failed to initialize pipeline: @message", [
        "@message" => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Processes AI preference submission (step 1).
   *
   * @param array<string, mixed> $data
   *   The submitted form data.
   *
   * @return array<string, mixed>
   *   Response array with success status and any prefilled data.
   */
  public function processAiPreference(array $data): array {
    if (!isset($data["preference"])) {
      return $this->buildResponse(FALSE, "Missing preference");
    }

    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return $this->buildResponse(FALSE, "Pipeline not available");
    }

    // Store preference in pipeline for later use
    $this->setPipelineData("ai_preference", $data["preference"]);
    $this->setPipelineData("session_id", uniqid("frontdesk_", TRUE));

    // Execute pipeline with current data
    $this->executePipelineWithCurrentData();

    // Check if we have prefilled data from email/username
    $prefilledData = [];
    $skipSteps = [];

    if (isset($data["email"])) {
      $crmData = $this->checkCrmForUserData($data["email"]);
      if ($crmData !== NULL) {
        $prefilledData = $crmData;
        // If we have complete user data, we can skip user lookup and info steps
        if (!empty($crmData["firstName"]) && !empty($crmData["lastName"])
            && !empty($crmData["email"])) {
          $skipSteps = [
            FrontdeskStep::DRUPAL_USER_LOOKUP,
            FrontdeskStep::USER_INFO_UPDATE,
          ];
          // If we also have company data, skip that step too
          if (!empty($crmData["company"])) {
            $skipSteps[] = FrontdeskStep::COMPANY_SUBMISSION;
          }
        }
      }
    }

    return $this->buildResponse(TRUE, "", [], $prefilledData, $skipSteps);
  }

  /**
   * Fetches user information from Drupal.org API.
   *
   * @param string $username
   *   The Drupal.org username.
   *
   * @return array<string, string>|null
   *   User information array or NULL if not found.
   */
  private function fetchDrupalOrgUserInfo(string $username): ?array {
    try {
      // Fetch user data from Drupal.org API
      $url = "https://www.drupal.org/api-d7/user.json?name=" . urlencode($username);

      $client = $this->httpClientFactory->fromOptions();
      $response = $client->get($url);

      if ($response->getStatusCode() !== 200) {
        return NULL;
      }

      $body = json_decode($response->getBody()->getContents(), TRUE);

      if (empty($body["list"]) || !is_array($body["list"])) {
        return NULL;
      }

      $userData = reset($body["list"]);

      // Extract name parts
      $fullName = $userData["field_first_name"] ?? "";
      $nameParts = explode(" ", trim($fullName), 2);

      return [
        "firstName" => $nameParts[0] ?? "",
        "lastName" => $nameParts[1] ?? "",
        "email" => "", // Drupal.org API doesn't expose email, user must provide
      ];
    }
    catch (\Exception $e) {
      $this->logger->error(
        "Failed to fetch Drupal.org user info: @message",
        ["@message" => $e->getMessage()]
      );
      return NULL;
    }
  }

  /**
   * Processes Drupal.org username lookup (step 2).
   *
   * @param array<string, mixed> $data
   *   The submitted form data.
   *
   * @return array<string, mixed>
   *   Response array with user information or error.
   */
  public function processDrupalUserLookup(array $data): array {
    if (!isset($data["username"])) {
      return $this->buildResponse(FALSE, "Missing username");
    }

    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return $this->buildResponse(FALSE, "Pipeline not available");
    }

    $username = trim($data["username"]);

    // Store username in pipeline
    $this->setPipelineData("drupal_username", $username);

    // First check CRM for existing user data
    $crmData = $this->checkCrmForUserData(NULL, $username);
    $skipSteps = [];

    if ($crmData !== NULL) {
      // User found in CRM, use that data
      $userInfo = $crmData;
      $message = "Welcome back! We found your information in our system.";

      // Check if we can skip steps based on available data
      if (!empty($crmData["company"])) {
        $skipSteps[] = FrontdeskStep::COMPANY_SUBMISSION;
      }
    }
    else {
      // Fetch user information from Drupal.org API
      $userInfo = $this->fetchDrupalOrgUserInfo($username);

      if ($userInfo === NULL) {
        return $this->buildResponse(
          FALSE,
          "Could not find user information. Please check the username."
        );
      }

      $message = "Profile found! Please verify your information.";
    }

    // Store fetched info in pipeline
    $this->setPipelineData("user_info", $userInfo);

    // Execute pipeline with current data
    $this->executePipelineWithCurrentData();

    return $this->buildResponse(TRUE, $message, $userInfo, [], $skipSteps);
  }

  /**
   * Processes user information update (step 3).
   *
   * @param array<string, mixed> $data
   *   The submitted form data.
   *
   * @return array<string, mixed>
   *   Response array with success status.
   */
  public function processUserInfoUpdate(array $data): array {
    $required = ["firstName", "lastName", "email"];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        return $this->buildResponse(FALSE, "Missing required field: {$field}");
      }
    }

    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return $this->buildResponse(FALSE, "Pipeline not available");
    }

    // Store updated info in pipeline
    $userInfo = [
      "firstName" => $data["firstName"],
      "lastName" => $data["lastName"],
      "email" => $data["email"],
    ];
    $this->setPipelineData("user_info", $userInfo);

    // Check if we have company info in CRM
    $skipSteps = [];
    $crmData = $this->checkCrmForUserData($data["email"]);
    if ($crmData !== NULL && !empty($crmData["company"])) {
      $skipSteps[] = FrontdeskStep::COMPANY_SUBMISSION;
      $this->setPipelineData("company", $crmData["company"]);
    }

    // Execute pipeline with current data
    $this->executePipelineWithCurrentData();

    return $this->buildResponse(
      TRUE,
      "Information updated successfully",
      [],
      [],
      $skipSteps
    );
  }

  /**
   * Processes company name submission (step 4).
   *
   * @param array<string, mixed> $data
   *   The submitted form data.
   *
   * @return array<string, mixed>
   *   Response array with success status.
   */
  public function processCompanySubmission(array $data): array {
    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return $this->buildResponse(FALSE, "Pipeline not available");
    }

    // Company is optional, store even if empty
    $company = $data["company"] ?? "";
    $this->setPipelineData("company", $company);

    // Execute pipeline with current data
    $this->executePipelineWithCurrentData();

    return $this->buildResponse(TRUE, "");
  }

  /**
   * Processes feedback submission (step 5).
   *
   * @param array<string, mixed> $data
   *   The submitted form data.
   *
   * @return array<string, mixed>
   *   Response array with success status.
   */
  public function processFeedbackSubmission(array $data): array {
    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return $this->buildResponse(FALSE, "Pipeline not available");
    }

    $feedback = $data["feedback"] ?? "";

    // Store feedback in pipeline
    $this->setPipelineData("feedback", $feedback);

    // Execute pipeline with current data
    $this->executePipelineWithCurrentData();

    return $this->buildResponse(TRUE, "");
  }

  /**
   * Processes final registration submission (step 6).
   *
   * @param array<string, mixed> $data
   *   The submitted form data.
   *
   * @return array<string, mixed>
   *   Response array with success status and target step.
   */
  public function processFinalSubmission(array $data): array {
    $pipeline = $this->getPipeline();
    if ($pipeline === NULL) {
      return $this->buildResponse(FALSE, "Pipeline not available");
    }

    // Load user info from pipeline as DTO
    $userDto = $this->loadUserInfoFromPipeline();
    if ($userDto === NULL) {
      return $this->buildResponse(FALSE, "Could not load user information");
    }

    $wantMore = $data["wantMore"] ?? FALSE;

    // Determine which screen to show based on wantMore preference
    $targetStep = $wantMore
      ? FrontdeskStep::COFFEE_WAIT_SCREEN
      : FrontdeskStep::SUCCESS_SCREEN;

    // Create internal note with all metadata including wantMore preference
    $internalNote = sprintf(
      "%s\nWants to know more: %s",
      $userDto->internalNote,
      $wantMore ? "Yes" : "No"
    );

    // Submit to Factorial CRM
    try {
      // Create lead object
      $lead = new Lead(
        firstName: $userDto->firstName,
        lastName: $userDto->lastName,
        company: $userDto->company,
        email: $userDto->email,
        drupalNickname: $userDto->drupalNickname,
        leadSource: "DrupalCamp Vienna 2025 - Frontdesk App",
        message: $userDto->message,
        internalNote: $internalNote,
      );
      $this->factorialCrm->submitLead($lead);
      // Note: Pipeline data is preserved and will be cleared when initialize()
      // creates a new pipeline for the next submission.
    }
    catch (\Exception $e) {
      $this->logger->error(
        "Failed to submit lead: @message",
        ["@message" => $e->getMessage()]
      );
    }
    $message = $wantMore
      ? "Great! Someone from our team would like to connect with you as well. Please enjoy a coffee while you wait! ☕"
      : "";
    return $this->buildResponse(
      TRUE,
      $message,
      ["targetStep" => $targetStep->value]
    );
  }

}

