#!/bin/bash

# Vienna 2025 SSE Module - k6 Test Runner
# ========================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEST_FILE="k6-sse-test.js"
MEMORY_TEST_FILE="k6-memory-monitor.js"
BASE_URL="https://flowdrop-drupal-demo.ddev.site"
INFLUXDB_URL="http://localhost:8086/k6"

# Default values
VUS=3
DURATION="30s"
OUTPUT_FORMAT=""
TAGS=""
VERBOSE=false
MEMORY_TEST=false

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show usage
show_usage() {
    cat << EOF
Vienna 2025 SSE Module - k6 Test Runner

USAGE:
    $0 [OPTIONS]

OPTIONS:
    -v, --vus NUMBER          Number of virtual users (default: 3)
    -d, --duration DURATION    Test duration (default: 30s)
    -o, --output FORMAT       Output format: json, csv, influxdb (default: console)
    -t, --tags TAGS           Additional tags for InfluxDB (format: "key1=value1,key2=value2")
    --influxdb                Use InfluxDB output with default settings
    --memory                  Run memory monitoring test instead of SSE test
    --verbose                  Enable verbose output
    -h, --help                Show this help message

EXAMPLES:
    # Basic test
    $0

    # Test with 5 VUs for 1 minute
    $0 --vus 5 --duration 1m

    # Test with InfluxDB output
    $0 --influxdb

    # Test with custom tags
    $0 --influxdb --tags "environment=production,version=1.0"

    # Test with JSON output
    $0 --output json

    # Memory monitoring test
    $0 --memory --duration 60s

    # Memory monitoring with InfluxDB
    $0 --memory --influxdb --duration 5m

EOF
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if k6 is installed
    if ! command -v k6 &> /dev/null; then
        print_error "k6 is not installed. Please install k6 first."
        print_status "Visit: https://k6.io/docs/getting-started/installation/"
        exit 1
    fi
    
    # Check if test file exists
    if [ ! -f "$SCRIPT_DIR/$TEST_FILE" ]; then
        print_error "Test file not found: $SCRIPT_DIR/$TEST_FILE"
        exit 1
    fi
    
    # Check if DDEV site is accessible
    print_status "Checking DDEV site accessibility..."
    if ! curl -s --head "$BASE_URL" > /dev/null; then
        print_warning "Cannot reach DDEV site at $BASE_URL"
        print_status "Make sure DDEV is running: ddev start"
    else
        print_success "DDEV site is accessible"
    fi
    
    print_success "Prerequisites check completed"
}

# Function to build k6 command
build_k6_command() {
    local cmd="k6 run"
    
    # Add VUs and duration
    cmd="$cmd --vus $VUS --duration $DURATION"
    
    # Add output format
    if [ -n "$OUTPUT_FORMAT" ]; then
        case "$OUTPUT_FORMAT" in
            "json")
                cmd="$cmd --out json"
                ;;
            "csv")
                cmd="$cmd --out csv"
                ;;
            "influxdb")
                cmd="$cmd --out influxdb=$INFLUXDB_URL"
                if [ -n "$TAGS" ]; then
                    # Parse tags and add them
                    IFS=',' read -ra TAG_ARRAY <<< "$TAGS"
                    for tag in "${TAG_ARRAY[@]}"; do
                        cmd="$cmd --tag $tag"
                    done
                fi
                cmd="$cmd --tag run_id=$(date +%s) --tag category=vienna_2025_sse"
                ;;
        esac
    fi
    
    # Add verbose flag
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    
    # Add test file
    if [ "$MEMORY_TEST" = true ]; then
        cmd="$cmd $SCRIPT_DIR/$MEMORY_TEST_FILE"
    else
        cmd="$cmd $SCRIPT_DIR/$TEST_FILE"
    fi
    
    echo "$cmd"
}

# Function to run the test
run_test() {
    print_status "Starting k6 SSE performance test..."
    print_status "Configuration:"
    print_status "  - Virtual Users: $VUS"
    print_status "  - Duration: $DURATION"
    print_status "  - Output: ${OUTPUT_FORMAT:-console}"
    print_status "  - Base URL: $BASE_URL"
    
    if [ -n "$TAGS" ]; then
        print_status "  - Tags: $TAGS"
    fi
    
    echo
    
    # Build and execute command
    local cmd=$(build_k6_command)
    
    if [ "$VERBOSE" = true ]; then
        print_status "Executing: $cmd"
        echo
    fi
    
    # Execute the command
    eval "$cmd"
    
    local exit_code=$?
    
    if [ $exit_code -eq 0 ]; then
        print_success "Test completed successfully!"
    else
        print_error "Test failed with exit code: $exit_code"
        exit $exit_code
    fi
}

# Function to show test results summary
show_summary() {
    print_status "Test Summary:"
    if [ "$MEMORY_TEST" = true ]; then
        print_status "  - Memory Endpoint: $BASE_URL/vienna-2025-sse/server-memory"
        print_status "  - Test Duration: $DURATION"
        print_status "  - Virtual Users: $VUS"
        print_status "  - Check Interval: 5 seconds"
        print_status "  - Expected Checks: ~$(echo $DURATION | sed 's/s//' | awk '{print int($1/5)}') memory checks"
    else
        print_status "  - SSE Endpoint: $BASE_URL/vienna-2025-sse/sse"
        print_status "  - Test Duration: $DURATION"
        print_status "  - Virtual Users: $VUS"
        print_status "  - Expected Messages: ~$((VUS * 30)) (30 per VU)"
        print_status "  - Expected Memory: ~6-8MB per message"
    fi
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -v|--vus)
            VUS="$2"
            shift 2
            ;;
        -d|--duration)
            DURATION="$2"
            shift 2
            ;;
        -o|--output)
            OUTPUT_FORMAT="$2"
            shift 2
            ;;
        -t|--tags)
            TAGS="$2"
            shift 2
            ;;
        --influxdb)
            OUTPUT_FORMAT="influxdb"
            shift
            ;;
        --memory)
            MEMORY_TEST=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Main execution
main() {
    echo "🚀 Vienna 2025 SSE Module - k6 Test Runner"
    echo "=========================================="
    echo
    
    check_prerequisites
    show_summary
    echo
    
    run_test
    
    echo
    print_success "All done! 🎉"
}

# Run main function
main "$@"
