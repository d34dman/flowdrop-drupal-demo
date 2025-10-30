import http from 'k6/http';
import { check, sleep } from 'k6';
import { Gauge, Counter } from 'k6/metrics';

// Custom metrics for memory monitoring
const memoryRequests = new Counter('memory_requests_total');
const serverMemoryTotal = new Gauge('server_memory_total_mb');
const serverMemoryAvailable = new Gauge('server_memory_available_mb');
const serverMemoryFree = new Gauge('server_memory_free_mb');
const serverMemoryUsed = new Gauge('server_memory_used_mb');
const serverMemoryUsagePercent = new Gauge('server_memory_usage_percent');
const phpMemoryUsage = new Gauge('php_memory_usage_mb');
const phpMemoryPeak = new Gauge('php_memory_peak_mb');

export let options = {
  vus: 1,
  duration: '60s',
  thresholds: {
    http_req_duration: ['p(95)<5000'], // 5 second timeout
    http_req_failed: ['rate<0.1'], // Less than 10% failures
    server_memory_usage_percent: ['value<90'], // Alert if memory usage > 90%
  },
};

const BASE_URL = 'https://flowdrop-drupal-demo.ddev.site';

export default function () {
  // Test the server memory endpoint
  const response = http.get(`${BASE_URL}/vienna-2025-sse/server-memory`, {
    headers: {
      'Accept': 'application/json',
    },
    timeout: '10s',
  });
  
  // Track request count
  memoryRequests.add(1);
  
  // Validate response
  const success = check(response, {
    'Memory endpoint status is 200': (r) => r.status === 200,
    'Memory endpoint has JSON content': (r) => {
      if (r.status !== 200) return false;
      try {
        JSON.parse(r.body);
        return true;
      } catch (e) {
        return false;
      }
    },
    'Memory endpoint response time < 5s': (r) => r.timings.duration < 5000,
  });
  
  if (success && response.status === 200) {
    try {
      const data = JSON.parse(response.body);
      
      // Log memory information
      console.log(`Memory Check - Total: ${data.summary.total_memory_mb}MB, Available: ${data.summary.available_memory_mb}MB, Used: ${data.summary.used_memory_mb}MB (${data.summary.memory_usage_percentage}%)`);
      console.log(`PHP Memory - Usage: ${data.php_memory_usage_mb}MB, Peak: ${data.php_memory_peak_mb}MB`);
      
      // Track server memory metrics
      if (data.summary.total_memory_mb) {
        serverMemoryTotal.add(data.summary.total_memory_mb);
      }
      if (data.summary.available_memory_mb) {
        serverMemoryAvailable.add(data.summary.available_memory_mb);
      }
      if (data.summary.free_memory_mb) {
        serverMemoryFree.add(data.summary.free_memory_mb);
      }
      if (data.summary.used_memory_mb) {
        serverMemoryUsed.add(data.summary.used_memory_mb);
      }
      if (data.summary.memory_usage_percentage) {
        serverMemoryUsagePercent.add(data.summary.memory_usage_percentage);
      }
      
      // Track PHP memory metrics
      if (data.php_memory_usage_mb) {
        phpMemoryUsage.add(data.php_memory_usage_mb);
      }
      if (data.php_memory_peak_mb) {
        phpMemoryPeak.add(data.php_memory_peak_mb);
      }
      
      // Validate memory data
      check(data, {
        'Server has total memory': (d) => d.summary.total_memory_mb > 0,
        'Server has available memory': (d) => d.summary.available_memory_mb > 0,
        'Memory usage is reasonable': (d) => d.summary.memory_usage_percentage < 95,
        'PHP memory is reasonable': (d) => d.php_memory_usage_mb < 100,
      });
      
    } catch (e) {
      console.error('Failed to parse memory response:', e);
    }
  }
  
  // Wait 5 seconds between memory checks
  sleep(5);
}

