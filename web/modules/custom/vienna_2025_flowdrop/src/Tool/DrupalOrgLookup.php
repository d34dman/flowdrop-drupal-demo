<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop\Tool;

use GuzzleHttp\Client;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;


/**
 * Executor for Factrial CRM.
 */
#[AsTool(
  name: 'drupal_username_lookup',
  description: 'Lookup Drupal Username and fetch details about the user.',
  method: 'fetchDrupalOrgUserInfo',
)]
class DrupalOrgLookup {


  /**
   * Fetches user information from Drupal.org API.
   *
   * @param string|null $username
   *   The Drupal.org username.
   *
   * @return array<string, string>|null
   *   User information array or NULL if not found.
   */
  public function fetchDrupalOrgUserInfo(?string $username): ?array {
    // Handle NULL or empty username
    if ($username === NULL || trim($username) === "") {
      return [
        "success" => FALSE,
        "message" => "No username provided",
        "firstName" => "",
        "lastName" => "",
        "data" => [],
      ];
    }
    // Fetch user data from Drupal.org API
    $url = "https://www.drupal.org/api-d7/user.json?name=" . urlencode($username);

    $client = new Client();
    $response = $client->get($url);

    if ($response->getStatusCode() !== 200) {
      return NULL;
    }

    $body = json_decode($response->getBody()->getContents(), TRUE);
    if (empty($body["list"]) || !is_array($body["list"])) {
      return NULL;
    }
    $userData = reset($body["list"]);
    return $userData;
  }

}
