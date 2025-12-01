<?php
/**
 * Sales Service
 * Handles sales transactions and triggers inventory updates
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
    'product_service_url' => $_ENV['PRODUCT_SERVICE_URL'] ?? 'http://localhost:8001',
    'inventory_service_url' => $_ENV['INVENTORY_SERVICE_URL'] ?? 'http://localhost:8002',
];

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = array_filter(explode('/', $path));

try {
    // Health check
    if ($path === '/health' || $path === '' || $path === '/') {
        echo json_encode(['status' => 'healthy', 'service' => 'sales']);
        exit;
    }

    // Database connection
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all sales
    if ($method === 'GET' && in_array('sales', $path_parts) && count($path_parts) === 1) {
        $stmt = $pdo->query("SELECT s.*, p.name FROM sales s 
                           LEFT JOIN products p ON s.sku = p.sku 
                           ORDER BY s.sale_date DESC LIMIT 100");
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $sales, 'count' => count($sales)]);
        exit;
    }

    // Get sale by transaction ID
    if ($method === 'GET' && in_array('sales', $path_parts) && count($path_parts) > 1) {
        $transaction_id = $path_parts[array_search('sales', $path_parts) + 1];
        $stmt = $pdo->prepare("SELECT s.*, p.name FROM sales s 
                             LEFT JOIN products p ON s.sku = p.sku 
                             WHERE s.transaction_id = ?");
        $stmt->execute([$transaction_id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sale) {
            echo json_encode(['success' => true, 'data' => $sale]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Sale not found']);
        }
        exit;
    }

    // Create sale (POST)
    if ($method === 'POST' && in_array('sales', $path_parts)) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate product exists
        $stmt = $pdo->prepare("SELECT price FROM products WHERE sku = ?");
        $stmt->execute([$input['sku']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            exit;
        }

        // Calculate total
        $unit_price = $product['price'];
        $quantity = $input['quantity'];
        $total = $unit_price * $quantity;
        $transaction_id = 'TXN-' . date('YmdHis') . '-' . uniqid();

        // Insert sale
        $stmt = $pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$transaction_id, $input['sku'], $quantity, $unit_price, $total]);

        // Update inventory (non-blocking - continue even if service is down)
        $inventory_url = $config['inventory_service_url'] . '/inventory';
        $update_data = json_encode(['sku' => $input['sku'], 'quantity' => $quantity]);
        
        $ch = curl_init($inventory_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $update_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        
        // Execute inventory update but don't fail if it times out
        $inventory_response = curl_exec($ch);
        $inventory_error = curl_error($ch);
        curl_close($ch);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Sale recorded',
            'transaction_id' => $transaction_id,
            'total' => $total,
            'inventory_updated' => empty($inventory_error)
        ]);
        exit;
    }

    // Get sales report
    if ($method === 'GET' && in_array('report', $path_parts)) {
        $stmt = $pdo->query("SELECT 
                           DATE(sale_date) as sale_date,
                           COUNT(*) as transaction_count,
                           SUM(quantity) as total_quantity,
                           SUM(total) as total_revenue
                           FROM sales 
                           GROUP BY DATE(sale_date) 
                           ORDER BY sale_date DESC 
                           LIMIT 30");
        $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'report' => $report]);
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
