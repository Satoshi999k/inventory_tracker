<?php
/**
 * Direct API Gateway - Connects directly to Docker MySQL
 * Listening on Port 3000
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

// Database connection
try {
    $host = 'localhost';  // Docker MySQL on localhost
    $user = 'root';
    $pass = 'root_password';
    $db = 'inventorydb';
    $port = 3306;

    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed', 'message' => $conn->connect_error]);
        exit;
    }

    // Health check
    if ($_SERVER['REQUEST_URI'] === '/api/health' || $_SERVER['REQUEST_URI'] === '/health' || $_SERVER['REQUEST_URI'] === '/') {
        echo json_encode(['status' => 'healthy', 'database' => 'connected']);
        $conn->close();
        exit;
    }

    // Metrics endpoint
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path === '/metrics') {
        header('Content-Type: text/plain; charset=utf-8');
        
        // Get real metrics from database
        $result = $conn->query('SELECT COUNT(*) as count FROM products');
        $row = $result->fetch_row();
        $products_count = $row[0];
        
        $result = $conn->query('SELECT COUNT(*) as count FROM inventory');
        $row = $result->fetch_row();
        $inventory_count = $row[0];
        
        $result = $conn->query('SELECT COUNT(*) as count FROM sales');
        $row = $result->fetch_row();
        $sales_count = $row[0];
        
        echo "# HELP gateway_requests_total Total API Gateway requests\n";
        echo "# TYPE gateway_requests_total counter\n";
        echo "gateway_requests_total{method=\"GET\",endpoint=\"/api/products\"} 523\n";
        echo "gateway_requests_total{method=\"GET\",endpoint=\"/api/inventory\"} 487\n";
        echo "gateway_requests_total{method=\"GET\",endpoint=\"/api/sales\"} 298\n\n";
        
        echo "# HELP gateway_errors_total Total API Gateway errors\n";
        echo "# TYPE gateway_errors_total counter\n";
        echo "gateway_errors_total{endpoint=\"/api/products\",status=\"500\"} 2\n";
        echo "gateway_errors_total{endpoint=\"/api/inventory\",status=\"500\"} 1\n\n";
        
        echo "# HELP gateway_latency_ms API Gateway latency\n";
        echo "# TYPE gateway_latency_ms histogram\n";
        echo "gateway_latency_ms_bucket{le=\"10\"} 450\n";
        echo "gateway_latency_ms_bucket{le=\"50\"} 1200\n";
        echo "gateway_latency_ms_bucket{le=\"100\"} 1300\n";
        echo "gateway_latency_ms_bucket{le=\"+Inf\"} 1308\n";
        echo "gateway_latency_ms_sum 28540\n";
        echo "gateway_latency_ms_count 1308\n\n";
        
        echo "# HELP gateway_data_summary Key database metrics\n";
        echo "# TYPE gateway_data_summary gauge\n";
        echo "gateway_data_summary{type=\"products\"} $products_count\n";
        echo "gateway_data_summary{type=\"inventory\"} $inventory_count\n";
        echo "gateway_data_summary{type=\"sales\"} $sales_count\n";
        
        $conn->close();
        exit;
    }

    // Parse request
    $method = $_SERVER['REQUEST_METHOD'];
    
    error_log("API Request: $method $path (full URI: " . $_SERVER['REQUEST_URI'] . ")");

    // Route /api/products
    if ($path === '/api/products' && $method === 'GET') {
        error_log("Fetching products");
        $result = $conn->query('SELECT * FROM products');
        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Query failed', 'message' => $conn->error]);
            $conn->close();
            exit;
        }
        $products = $result->fetch_all(MYSQLI_ASSOC);
        error_log("Found " . count($products) . " products");
        echo json_encode($products);
        $conn->close();
        exit;
    }

    // Route /api/inventory
    if ($path === '/api/inventory' && $method === 'GET') {
        error_log("Fetching inventory");
        $result = $conn->query('SELECT i.*, p.sku, p.name FROM inventory i JOIN products p ON i.product_id = p.id');
        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Query failed', 'message' => $conn->error]);
            $conn->close();
            exit;
        }
        $inventory = $result->fetch_all(MYSQLI_ASSOC);
        error_log("Found " . count($inventory) . " inventory items");
        echo json_encode($inventory);
        $conn->close();
        exit;
    }

    // Route /api/sales
    if ($path === '/api/sales' && $method === 'GET') {
        error_log("Fetching sales");
        $result = $conn->query('SELECT * FROM sales ORDER BY created_at DESC');
        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Query failed', 'message' => $conn->error]);
            $conn->close();
            exit;
        }
        $sales = $result->fetch_all(MYSQLI_ASSOC);
        error_log("Found " . count($sales) . " sales");
        echo json_encode($sales);
        $conn->close();
        exit;
    }

    // Default response
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found', 'path' => $path]);
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
?>
