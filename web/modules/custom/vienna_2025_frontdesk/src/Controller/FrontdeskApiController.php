<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_frontdesk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\vienna_2025_frontdesk\Service\FrontdeskProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for frontdesk form submissions.
 *
 * This controller handles HTTP communication and delegates all business logic
 * to the FrontdeskProcessor service.
 */
final class FrontdeskApiController extends ControllerBase {

  /**
   * Constructs a FrontdeskApiController object.
   *
   * @param \Drupal\vienna_2025_frontdesk\Service\FrontdeskProcessor $processor
   *   The frontdesk processor service.
   */
  public function __construct(
    private readonly FrontdeskProcessor $processor,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get("vienna_2025_frontdesk.processor"),
    );
  }

  /**
   * Handles AI preference submission (step 1).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with success status.
   */
  public function submitAiPreference(Request $request): JsonResponse {
    // The first step is submitting AI Preference.
    // So we start a new workflow.
    $initialized = $this->processor->initialize();
    if (!$initialized) {
      return new JsonResponse([
        "success" => FALSE,
        "message" => "Failed to initialize workflow. Please try again.",
      ], 500);
    }

    $data = json_decode($request->getContent(), TRUE);
    $response = $this->processor->processAiPreference($data ?? []);

    $statusCode = $response["success"] ? 200 : 400;
    return new JsonResponse($response, $statusCode);
  }

  /**
   * Looks up user information by Drupal.org username (step 2).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with user information.
   */
  public function lookupDrupalUser(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $response = $this->processor->processDrupalUserLookup($data ?? []);

    $statusCode = $response["success"] ? 200 : 400;
    return new JsonResponse($response, $statusCode);
  }

  /**
   * Updates user information (step 3 - if user corrects data).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with success status.
   */
  public function updateUserInfo(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $response = $this->processor->processUserInfoUpdate($data ?? []);

    $statusCode = $response["success"] ? 200 : 400;
    return new JsonResponse($response, $statusCode);
  }

  /**
   * Submits company name (step 4).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with success status.
   */
  public function submitCompany(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $response = $this->processor->processCompanySubmission($data ?? []);

    $statusCode = $response["success"] ? 200 : 400;
    return new JsonResponse($response, $statusCode);
  }

  /**
   * Submits feedback (step 5).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with success status.
   */
  public function submitFeedback(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $response = $this->processor->processFeedbackSubmission($data ?? []);

    $statusCode = $response["success"] ? 200 : 400;
    return new JsonResponse($response, $statusCode);
  }

  /**
   * Submits final registration with "want more" preference (step 6).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with success status and target step.
   */
  public function submitFinal(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $response = $this->processor->processFinalSubmission($data ?? []);

    // Determine status code based on success and whether it's a server error
    $statusCode = 200;
    if (!$response["success"]) {
      // If the error message indicates server error, use 500, otherwise 400
      $statusCode = (isset($response["message"]) &&
                     str_contains($response["message"], "Failed to submit data")) ? 500 : 400;
    }

    return new JsonResponse($response, $statusCode);
  }

}

