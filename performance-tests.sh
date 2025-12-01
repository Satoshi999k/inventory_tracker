#!/bin/bash
# Performance Testing Script
# Tests load and stress on the API
# Usage: ./performance-tests.sh

echo ""
echo "🚀 PERFORMANCE TESTING SUITE"
echo "=============================================="
echo ""

API_URL="http://localhost:8000"
RESULTS_FILE="performance-results.json"

# Check if wrk is installed, otherwise use ab
if ! command -v wrk &> /dev/null; then
    if ! command -v ab &> /dev/null; then
        echo "⚠️  Neither 'wrk' nor 'ab' found. Install Apache Bench or wrk."
        echo "    Ubuntu: sudo apt-get install apache2-utils"
        echo "    macOS: brew install wrk"
        exit 1
    fi
    USE_AB=true
else
    USE_AB=false
fi

echo "📊 Test 1: API Gateway Health Check"
echo "---"
curl -s "$API_URL/health" | jq '.' 2>/dev/null || echo "Failed to connect"
echo ""

echo "📊 Test 2: GET /products (Baseline)"
echo "---"
if [ "$USE_AB" = true ]; then
    ab -n 100 -c 10 -q "$API_URL/products" 2>/dev/null | grep -E "Requests per second|Mean time per request|Failed requests"
else
    wrk -t2 -c10 -d10s "$API_URL/products"
fi
echo ""

echo "📊 Test 3: GET /inventory (Baseline)"
echo "---"
if [ "$USE_AB" = true ]; then
    ab -n 100 -c 10 -q "$API_URL/inventory" 2>/dev/null | grep -E "Requests per second|Mean time per request|Failed requests"
else
    wrk -t2 -c10 -d10s "$API_URL/inventory"
fi
echo ""

echo "📊 Test 4: High Concurrency Test"
echo "---"
echo "Simulating 50 concurrent requests..."
if [ "$USE_AB" = true ]; then
    ab -n 500 -c 50 -q "$API_URL/products" 2>/dev/null | grep -E "Requests per second|Mean time per request|Failed requests"
else
    wrk -t4 -c50 -d20s "$API_URL/products"
fi
echo ""

echo "📊 Test 5: Sustained Load Test"
echo "---"
echo "Running 30-second sustained load..."
if [ "$USE_AB" = true ]; then
    ab -t 30 -c 20 -q "$API_URL/inventory" 2>/dev/null | grep -E "Requests per second|Mean time per request|Failed requests"
else
    wrk -t4 -c20 -d30s "$API_URL/inventory"
fi
echo ""

echo "✅ Performance tests complete"
echo ""
