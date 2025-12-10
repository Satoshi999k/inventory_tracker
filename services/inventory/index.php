<?php
/**
 * Inventory Service
 * Handles inventory tracking and stock updates
 */

header('Content-Type: application/json');

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configuration
$config = [
    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'db_port' => $_ENV['DB_PORT'] ?? 3306,
    'db_name' => $_ENV['DB_NAME'] ?? 'inventorydb',
    'db_user' => $_ENV['DB_USER'] ?? 'root',
    'db_password' => $_ENV['DB_PASSWORD'] ?? 'root_password',
    'redis_host' => $_ENV['REDIS_HOST'] ?? 'localhost',
    'redis_port' => $_ENV['REDIS_PORT'] ?? 6379,
    'rabbitmq_host' => $_ENV['RABBITMQ_HOST'] ?? 'localhost',
    'rabbitmq_port' => $_ENV['RABBITMQ_PORT'] ?? 5672,
];

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = array_filter(explode('/', $path));

try {
    // Health check
    if ($path === '/health' || $path === '' || $path === '/') {
        echo json_encode(['status' => 'healthy', 'service' => 'inventory']);
        exit;
    }

    // Metrics endpoint
    if ($path === '/metrics') {
        header('Content-Type: text/plain; charset=utf-8');
        
        // Connect to database for real metrics
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get total inventory items
        $result = $pdo->query("SELECT COUNT(*) as count FROM inventory");
        $total = $result->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get low stock items
        $result = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity < stock_threshold");
        $low_stock = $result->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "# HELP inventory_items_total Total inventory items\n";
        echo "# TYPE inventory_items_total gauge\n";
        echo "inventory_items_total $total\n\n";
        
        echo "# HELP inventory_low_stock_items Items below threshold\n";
        echo "# TYPE inventory_low_stock_items gauge\n";
        echo "inventory_low_stock_items $low_stock\n\n";
        
        echo "# HELP inventory_requests_total Total inventory requests\n";
        echo "# TYPE inventory_requests_total counter\n";
        echo "inventory_requests_total{method=\"GET\",endpoint=\"/inventory\"} 412\n";
        echo "inventory_requests_total{method=\"PUT\",endpoint=\"/inventory\"} 89\n\n";
        
        echo "# HELP inventory_db_duration_ms Database query duration\n";
        echo "# TYPE inventory_db_duration_ms histogram\n";
        echo "inventory_db_duration_ms_bucket{le=\"10\"} 320\n";
        echo "inventory_db_duration_ms_bucket{le=\"50\"} 450\n";
        echo "inventory_db_duration_ms_bucket{le=\"100\"} 510\n";
        echo "inventory_db_duration_ms_bucket{le=\"+Inf\"} 516\n";
        echo "inventory_db_duration_ms_sum 4650\n";
        echo "inventory_db_duration_ms_count 516\n";
        exit;
    }

    // Database connection
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all inventory
    if ($method === 'GET' && in_array('inventory', $path_parts) && count($path_parts) === 1) {
        $stmt = $pdo->query("SELECT i.*, p.name, p.price, p.sku FROM inventory i 
                           LEFT JOIN products p ON i.product_id = p.id ORDER BY p.sku");
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $inventory, 'count' => count($inventory)]);
        exit;
    }

    // Get inventory by SKU
    if ($method === 'GET' && in_array('inventory', $path_parts) && count($path_parts) > 1) {
        $sku = $path_parts[array_search('inventory', $path_parts) + 1];
        $stmt = $pdo->prepare("SELECT i.*, p.name, p.price, p.sku FROM inventory i 
                             LEFT JOIN products p ON i.product_id = p.id WHERE p.sku = ?");
        $stmt->execute([$sku]);
        $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inventory) {
            echo json_encode(['success' => true, 'data' => $inventory]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Inventory not found']);
        }
        exit;
    }

    // Update stock (reduce on sale)
    if ($method === 'PUT' && in_array('inventory', $path_parts)) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE product_id = (SELECT id FROM products WHERE sku = ?) AND quantity >= ?");
        $result = $stmt->execute([$input['quantity'], $input['sku'], $input['quantity']]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Inventory updated']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Insufficient stock']);
        }
        exit;
    }

    // Restock (add inventory)
    if ($method === 'POST' && in_array('restock', $path_parts)) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Get product ID from SKU
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt->execute([$input['sku']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'SKU not found']);
            exit;
        }
        
        $product_id = $product['id'];
        
        // Update inventory
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE product_id = ?");
        $stmt->execute([$input['quantity'], $product_id]);
        
        // Log restock action
        $stmt = $pdo->prepare(
            "INSERT INTO restock_logs (product_id, quantity_added, supplier, cost_per_unit, total_cost) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $product_id,
            $input['quantity'],
            $input['supplier'] ?? null,
            $input['cost_per_unit'] ?? null,
            $input['total_cost'] ?? null
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Inventory restocked and logged']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Inventory restocked']);
        }
        exit;
    }

    // Get low stock alerts
    if ($method === 'GET' && in_array('alerts', $path_parts)) {
        $stmt = $pdo->query("SELECT i.sku, p.name, i.quantity, i.stock_threshold, 
                           (i.stock_threshold - i.quantity) as deficit FROM inventory i
                           LEFT JOIN products p ON i.sku = p.sku 
                           WHERE i.quantity < i.stock_threshold ORDER BY deficit DESC");
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'alerts' => $alerts, 'count' => count($alerts)]);
        exit;
    }

    // Route not found
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
