<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_sse\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Simple SSE Controller - sends notifications every second.
 */
final class SimpleSseController extends ControllerBase {
  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Builds a simple SSE response that sends notifications every second.
   */
  public function __invoke(): StreamedResponse {
    {
      $this->messenger()->addStatus('Started streaming of SSE event');
      $response = new StreamedResponse(function () {
        foreach ($this->watchJobsInProgress() as $job) {
          $this->messenger()->addStatus('Event detected at ' . date('Y-m-d H:i:s'));
          // Send the notification
          $data = json_encode($job);
          echo "data: {$data}\n\n";
          StreamedResponse::closeOutputBuffers(0, true);
          flush();

          if (connection_aborted()) {
            break;
          }
          sleep(1);
        }

        // Send final message
        echo "data: " . json_encode(["message" => "stream_ended"]) . "\n\n";

        if (ob_get_level()) {
          ob_flush();
        }
        flush();
      });
      $response->headers->set('Content-Type', 'text/event-stream');
      $response->headers->set('Cache-Control', 'no-cache');
      $response->headers->set('Connection', 'keep-alive');
      $response->headers->set('X-Accel-Buffering', 'no');

      return $response;
    }
  }

  protected function watchJobsInProgress() {
    for ($counter = 0; $counter < 30; $counter++) {
      // Get PHP memory usage
      $php_memory_usage = round(memory_get_usage(true) / 1024 / 1024, 2);
      $php_memory_peak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

      yield [
        "message" => "ok",
        "timestamp" => time(),
        "counter" => $counter,
        "php_memory_usage_mb" => $php_memory_usage,
        "php_memory_peak_mb" => $php_memory_peak,
        "memory_usage_mb" => $php_memory_usage, // Keep for backward compatibility
      ];
      sleep(1);
    }
  }


}
