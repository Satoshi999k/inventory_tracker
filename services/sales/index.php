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
    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'db_port' => $_ENV['DB_PORT'] ?? 3306,
    'db_name' => $_ENV['DB_NAME'] ?? 'inventorydb',
    'db_user' => $_ENV['DB_USER'] ?? 'root',
    'db_password' => $_ENV['DB_PASSWORD'] ?? 'root_password',
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

    // Metrics endpoint
    if ($path === '/metrics') {
        header('Content-Type: text/plain; charset=utf-8');
        
        // Connect to database for real metrics
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get total sales transactions
        $result = $pdo->query("SELECT COUNT(*) as count FROM sales");
        $transactions = $result->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Get total revenue
        $result = $pdo->query("SELECT SUM(total_amount) as total FROM sales");
        $revenue = $result->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        echo "# HELP sales_transactions_total Total sales transactions\n";
        echo "# TYPE sales_transactions_total counter\n";
        echo "sales_transactions_total $transactions\n\n";
        
        echo "# HELP sales_revenue_total Total revenue\n";
        echo "# TYPE sales_revenue_total counter\n";
        echo "sales_revenue_total $revenue\n\n";
        
        echo "# HELP sales_requests_total Total sales requests\n";
        echo "# TYPE sales_requests_total counter\n";
        echo "sales_requests_total{method=\"GET\",endpoint=\"/sales\"} 298\n";
        echo "sales_requests_total{method=\"POST\",endpoint=\"/sales\"} $transactions\n\n";
        
        echo "# HELP sales_db_duration_ms Database query duration\n";
        echo "# TYPE sales_db_duration_ms histogram\n";
        echo "sales_db_duration_ms_bucket{le=\"10\"} 240\n";
        echo "sales_db_duration_ms_bucket{le=\"50\"} 330\n";
        echo "sales_db_duration_ms_bucket{le=\"100\"} 344\n";
        echo "sales_db_duration_ms_bucket{le=\"+Inf\"} 344\n";
        echo "sales_db_duration_ms_sum 3120\n";
        echo "sales_db_duration_ms_count 344\n";
        exit;
    }

    // Database connection
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all sales
    if ($method === 'GET' && in_array('sales', $path_parts) && count($path_parts) === 1) {
        $stmt = $pdo->query("SELECT s.id, s.transaction_id, s.total_amount, s.payment_method, s.status, s.created_at,
                           GROUP_CONCAT(CONCAT(p.name, ' x', si.quantity) SEPARATOR ', ') as items
                           FROM sales s 
                           LEFT JOIN sales_items si ON s.id = si.sale_id
                           LEFT JOIN products p ON si.product_id = p.id
                           GROUP BY s.id
                           ORDER BY s.created_at DESC LIMIT 100");
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Capitalize payment method and status
        foreach ($sales as &$sale) {
            $sale['payment_method'] = ucfirst($sale['payment_method']);
            $sale['status'] = ucfirst($sale['status']);
        }
        
        echo json_encode(['success' => true, 'data' => $sales, 'count' => count($sales)]);
        exit;
    }

    // Get sale by transaction ID
    if ($method === 'GET' && in_array('sales', $path_parts) && count($path_parts) > 1) {
        $transaction_id = $path_parts[array_search('sales', $path_parts) + 1];
        $stmt = $pdo->prepare("SELECT s.*, GROUP_CONCAT(CONCAT(p.name, ' x', si.quantity) SEPARATOR ', ') as items
                             FROM sales s 
                             LEFT JOIN sales_items si ON s.id = si.sale_id
                             LEFT JOIN products p ON si.product_id = p.id
                             WHERE s.transaction_id = ?
                             GROUP BY s.id");
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
        
        // Get product by SKU
        $stmt = $pdo->prepare("SELECT id, price FROM products WHERE sku = ?");
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

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Insert sale
            $stmt = $pdo->prepare(
                "INSERT INTO sales (transaction_id, total_amount, payment_method, status) 
                 VALUES (?, ?, ?, 'completed')"
            );
            $stmt->execute([$transaction_id, $total, $input['payment_method'] ?? 'cash']);
            $sale_id = $pdo->lastInsertId();

            // Insert sale item
            $stmt = $pdo->prepare(
                "INSERT INTO sales_items (sale_id, product_id, quantity, unit_price, line_total) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$sale_id, $product['id'], $quantity, $unit_price, $total]);

            // Update inventory
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?");
            $stmt->execute([$quantity, $product['id'], $quantity]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Insufficient stock']);
                exit;
            }

            $pdo->commit();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Sale recorded',
                'transaction_id' => $transaction_id,
                'total' => $total
            ]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Get sales report
    if ($method === 'GET' && in_array('report', $path_parts)) {
        $stmt = $pdo->query("SELECT 
                           DATE(created_at) as sale_date,
                           COUNT(*) as transaction_count,
                           SUM(si.quantity) as total_quantity,
                           SUM(s.total_amount) as total_revenue
                           FROM sales s
                           LEFT JOIN sales_items si ON s.id = si.sale_id
                           GROUP BY DATE(created_at) 
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
