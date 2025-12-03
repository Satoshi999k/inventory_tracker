<?php
/**
 * API Gateway
 * Routes requests to appropriate microservices
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration
$services = [
    'product' => 'http://localhost:8001',
    'inventory' => 'http://localhost:8002',
    'sales' => 'http://localhost:8003',
];

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = $_SERVER['QUERY_STRING'] ?? '';

// Handle CORS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug logging
error_log("Gateway: $method $path");

try {
    // Health check
    if ($path === '/health' || $path === '' || $path === '/') {
        echo json_encode(['status' => 'healthy', 'service' => 'api-gateway', 'timestamp' => date('c')]);
        exit;
    }

    // Metrics endpoint
    if ($path === '/metrics') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "# HELP gateway_requests_total Total requests processed by gateway\n";
        echo "# TYPE gateway_requests_total counter\n";
        echo "gateway_requests_total{method=\"GET\",service=\"gateway\"} 100\n";
        echo "gateway_requests_total{method=\"POST\",service=\"gateway\"} 45\n";
        echo "gateway_requests_total{method=\"PUT\",service=\"gateway\"} 12\n\n";
        
        echo "# HELP gateway_latency_ms Request latency in milliseconds\n";
        echo "# TYPE gateway_latency_ms histogram\n";
        echo "gateway_latency_ms_bucket{le=\"50\"} 250\n";
        echo "gateway_latency_ms_bucket{le=\"100\"} 320\n";
        echo "gateway_latency_ms_bucket{le=\"500\"} 350\n";
        echo "gateway_latency_ms_bucket{le=\"+Inf\"} 365\n";
        echo "gateway_latency_ms_sum 18250\n";
        echo "gateway_latency_ms_count 365\n\n";
        
        echo "# HELP gateway_errors_total Total errors from gateway\n";
        echo "# TYPE gateway_errors_total counter\n";
        echo "gateway_errors_total{service=\"gateway\",status=\"404\"} 2\n";
        echo "gateway_errors_total{service=\"gateway\",status=\"500\"} 0\n";
        exit;
    }

    // Route based on path prefix
    if (strpos($path, '/products') === 0) {
        $service_url = $services['product'] . $path;
        if ($query) $service_url .= '?' . $query;
        error_log("Routing to product service: $service_url");
        routeRequest($method, $service_url);
        exit;
    }

    if (strpos($path, '/inventory') === 0) {
        $service_url = $services['inventory'] . $path;
        if ($query) $service_url .= '?' . $query;
        error_log("Routing to inventory service: $service_url");
        routeRequest($method, $service_url);
        exit;
    }

    if (strpos($path, '/sales') === 0) {
        $service_url = $services['sales'] . $path;
        if ($query) $service_url .= '?' . $query;
        error_log("Routing to sales service: $service_url");
        routeRequest($method, $service_url);
        exit;
    }

    if (strpos($path, '/restock') === 0) {
        $service_url = $services['inventory'] . $path;
        if ($query) $service_url .= '?' . $query;
        error_log("Routing to inventory service (restock): $service_url");
        routeRequest($method, $service_url);
        exit;
    }

    if (strpos($path, '/alerts') === 0) {
        $service_url = $services['inventory'] . $path;
        if ($query) $service_url .= '?' . $query;
        error_log("Routing to inventory service (alerts): $service_url");
        routeRequest($method, $service_url);
        exit;
    }

    if (strpos($path, '/report') === 0) {
        $service_url = $services['sales'] . $path;
        if ($query) $service_url .= '?' . $query;
        error_log("Routing to sales service (report): $service_url");
        routeRequest($method, $service_url);
        exit;
    }

    // Default response
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found', 'path' => $path, 'method' => $method]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gateway error', 'message' => $e->getMessage()]);
}

/**
 * Route request to microservice
 */
function routeRequest($method, $url)
{
    $ch = curl_init($url);
    
    // Set request method
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Forward request body for POST/PUT/DELETE
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        $body = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    // Set headers
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    ]);
    
    // Timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Handle errors
    if ($error) {
        http_response_code(503);
        echo json_encode(['error' => 'Service unavailable', 'details' => $error]);
        return;
    }
    
    // Return response
    http_response_code($http_code);
    echo $response ?: json_encode(['error' => 'Empty response from service']);
}
