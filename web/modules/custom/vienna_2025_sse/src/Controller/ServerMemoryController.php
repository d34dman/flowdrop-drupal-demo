<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_sse\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Server Memory Controller - provides server memory information.
 */
final class ServerMemoryController extends ControllerBase {

  /**
   * Get server memory information from /proc/meminfo.
   */
  private function getServerMemoryInfo(): array {
    $meminfo = [];
    
    if (file_exists('/proc/meminfo')) {
      $handle = fopen('/proc/meminfo', 'r');
      if ($handle) {
        while (($line = fgets($handle)) !== false) {
          $line = trim($line);
          if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Extract numeric value and unit
            if (preg_match('/(\d+)\s*(\w*)/', $value, $matches)) {
              $numeric_value = (int) $matches[1];
              $unit = $matches[2] ?? 'kB';
              
              // Convert to MB for consistency
              if ($unit === 'kB') {
                $meminfo[$key] = round($numeric_value / 1024, 2);
              } else {
                $meminfo[$key] = $numeric_value;
              }
            }
          }
        }
        fclose($handle);
      }
    }
    
    return $meminfo;
  }

  /**
   * Builds a JSON response with server memory information.
   */
  public function __invoke(): JsonResponse {
    // Get PHP memory usage
    $php_memory_usage = round(memory_get_usage(true) / 1024 / 1024, 2);
    $php_memory_peak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    // Get server memory information
    $server_memory = $this->getServerMemoryInfo();
    
    $data = [
      "message" => "Server memory information",
      "timestamp" => time(),
      "php_memory_usage_mb" => $php_memory_usage,
      "php_memory_peak_mb" => $php_memory_peak,
      "server_memory" => $server_memory,
      "summary" => [
        "total_memory_mb" => $server_memory['MemTotal'] ?? 0,
        "available_memory_mb" => $server_memory['MemAvailable'] ?? 0,
        "free_memory_mb" => $server_memory['MemFree'] ?? 0,
        "used_memory_mb" => ($server_memory['MemTotal'] ?? 0) - ($server_memory['MemAvailable'] ?? 0),
        "memory_usage_percentage" => round((($server_memory['MemTotal'] ?? 0) - ($server_memory['MemAvailable'] ?? 0)) / ($server_memory['MemTotal'] ?? 1) * 100, 2)
      ]
    ];
    
    return new JsonResponse($data);
  }

}
