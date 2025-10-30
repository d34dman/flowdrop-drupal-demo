import sse from 'k6/x/sse';
import { check, sleep } from 'k6';
import { Counter, Gauge } from 'k6/metrics';

// Custom metrics to track SSE messages, memory usage, and connection success
const sseMessagesReceived = new Counter('sse_messages_received');
const sseMemoryUsage = new Gauge('sse_memory_usage_mb');
const sseConnectionSuccess = new Counter('sse_connection_success');

export let options = {
  vus: 3,
  duration: '30s',
  thresholds: {
    http_req_duration: ['p(95)<10000'], // More reasonable threshold for SSE
    http_req_failed: ['rate<0.1'],
    sse_messages_received: ['count>0'], // Ensure we receive at least some messages
    sse_memory_usage_mb: ['value<10'], // Ensure memory usage stays reasonable
    sse_connection_success: ['count>0'], // Ensure at least one connection succeeds
  },
};

const BASE_URL = 'https://flowdrop-drupal-demo.ddev.site';

export default function () {
  const url = `${BASE_URL}/vienna-2025-sse/sse`;
  const params = {
    method: 'GET',
    headers: {
      'Accept': 'text/event-stream',
      'Cache-Control': 'no-cache',
    },
    tags: { 'sse_test': 'vienna_2025' }
  };

  const response = sse.open(url, params, function (client) {
    let messageCount = 0;
    let firstMessage = null;
    let lastMessage = null;

    client.on('open', function open() {
      console.log('SSE connection opened');
      // Track successful connection
      sseConnectionSuccess.add(1);
    });

    client.on('event', function (event) {
      messageCount++;
      // console.log(`Event received: id=${event.id}, name=${event.name}, data=${event.data}`);
      
      // Increment the custom metric for each SSE message received
      sseMessagesReceived.add(1);
      
      try {
        const data = JSON.parse(event.data);
        
        // Track PHP memory usage if available in the message
        if (data.memory_usage_mb !== undefined) {
          sseMemoryUsage.add(data.memory_usage_mb);
          console.log(`PHP Memory - Usage: ${data.memory_usage_mb}MB, Peak: ${data.php_memory_peak_mb || 'N/A'}MB`);
        }
        
        
        // Store first and last messages for validation
        if (firstMessage === null) {
          firstMessage = data;
        }
        lastMessage = data;
        
        // Check if this is the end message
        if (data.message === 'stream_ended') {
          console.log(`Stream ended after ${messageCount} messages`);
          client.close();
        }
      } catch (e) {
        console.error('Failed to parse SSE message:', event.data);
      }
    });

    client.on('error', function (e) {
      console.log('SSE connection error:', e.error());
    });
  });

  // Validate the response
  const success = check(response, {
    'SSE connection successful': (r) => r && r.status === 200,
    'SSE received messages': (r) => r && r.body && r.body.length > 0,
    'SSE messages have valid data': () => firstMessage && firstMessage.message === 'ok',
    'SSE messages have memory data': () => firstMessage && firstMessage.memory_usage_mb > 0,
  });

  if (success && response && response.body) {
    console.log(`SSE Test completed - Status: ${response.status}, Response time: ${response.timings.duration}ms, Messages received: ${messageCount}`);
  }
  
  sleep(2); // Wait between tests
}