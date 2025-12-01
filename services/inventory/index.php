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
    'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
    'db_port' => $_ENV['DB_PORT'] ?? 3306,
    'db_name' => $_ENV['DB_NAME'] ?? 'inventory_db',
    'db_user' => $_ENV['DB_USER'] ?? 'root',
    'db_password' => $_ENV['DB_PASSWORD'] ?? '',
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

    // Database connection
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all inventory
    if ($method === 'GET' && in_array('inventory', $path_parts) && count($path_parts) === 1) {
        $stmt = $pdo->query("SELECT i.*, p.name, p.price FROM inventory i 
                           LEFT JOIN products p ON i.sku = p.sku ORDER BY i.sku");
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $inventory, 'count' => count($inventory)]);
        exit;
    }

    // Get inventory by SKU
    if ($method === 'GET' && in_array('inventory', $path_parts) && count($path_parts) > 1) {
        $sku = $path_parts[array_search('inventory', $path_parts) + 1];
        $stmt = $pdo->prepare("SELECT i.*, p.name, p.price FROM inventory i 
                             LEFT JOIN products p ON i.sku = p.sku WHERE i.sku = ?");
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
        
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE sku = ? AND quantity >= ?");
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
        
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE sku = ?");
        $stmt->execute([$input['quantity'], $input['sku']]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Inventory restocked']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'SKU not found']);
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
