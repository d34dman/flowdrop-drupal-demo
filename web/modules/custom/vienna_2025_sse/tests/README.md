# Vienna 2025 SSE Module - Testing

This directory contains all testing resources for the Vienna 2025 SSE module.

## Directory Structure

```
tests/
├── README.md                    # This file
└── performance/
    └── k6/
        ├── k6-sse-test.js       # SSE streaming test
        ├── k6-sse-comparison.js # SSE comparison test
        ├── k6-sse-performance.js # Advanced performance test
        ├── test-endpoints.sh    # Endpoint diagnostic script
        ├── run-tests.sh         # Basic test runner
        ├── run-with-influxdb.sh # Test runner with InfluxDB
        └── k6-testing-guide.md  # Testing documentation
```

## Performance Testing

The `performance/k6/` directory contains k6 performance tests for the SSE endpoints:

- **SSE Streaming Test**: Tests both Sleep SSE and Fiber SSE endpoints
- **SSE Comparison Test**: Direct comparison between Sleep vs Fiber approaches
- **Advanced Test**: Detailed performance analysis with custom metrics

## Running Tests

From the module root directory:

```bash
# SSE streaming test
k6 run tests/performance/k6/k6-sse-test.js

# SSE comparison test
k6 run tests/performance/k6/k6-sse-comparison.js

# Advanced performance test
k6 run tests/performance/k6/k6-sse-performance.js
```

## Test Endpoints

The tests validate these SSE endpoints:

- `/vienna-2025-sse/sleep-sse` - Sleep-based SSE (blocking approach)
- `/vienna-2025-sse/fiber-sse` - Fiber-based SSE (non-blocking approach)

## Documentation

See `performance/k6/k6-testing-guide.md` for detailed testing instructions and configuration options.
