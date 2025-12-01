<?php
/**
 * Product Catalog Service
 * Handles product management operations
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
];

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = array_filter(explode('/', $path));

// Route requests
try {
    // Health check
    if ($path === '/health' || $path === '' || $path === '/') {
        echo json_encode(['status' => 'healthy', 'service' => 'product-catalog']);
        exit;
    }

    // Database connection
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get products
    if ($method === 'GET' && in_array('products', $path_parts)) {
        $stmt = $pdo->query("SELECT id, sku, name, category, price, stock_threshold FROM products ORDER BY created_at DESC LIMIT 1000", PDO::FETCH_ASSOC);
        $products = $stmt->fetchAll();
        header('Cache-Control: public, max-age=30');
        echo json_encode(['success' => true, 'data' => $products, 'count' => count($products)]);
        exit;
    }

    // Get product by SKU
    if ($method === 'GET' && in_array('products', $path_parts) && count($path_parts) > 1) {
        $sku = $path_parts[array_search('products', $path_parts) + 1];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
        exit;
    }

    // Create product
    if ($method === 'POST' && in_array('products', $path_parts)) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare(
            "INSERT INTO products (sku, name, category, price, description, stock_threshold) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $input['sku'],
            $input['name'],
            $input['category'] ?? null,
            $input['price'],
            $input['description'] ?? null,
            $input['stock_threshold'] ?? 10
        ]);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Product created', 'id' => $pdo->lastInsertId()]);
        exit;
    }

    // Update product
    if ($method === 'PUT' && in_array('products', $path_parts)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $sku = $input['sku'];

        $stmt = $pdo->prepare(
            "UPDATE products SET name = ?, category = ?, price = ?, description = ?, stock_threshold = ? 
             WHERE sku = ?"
        );
        $stmt->execute([
            $input['name'],
            $input['category'] ?? null,
            $input['price'],
            $input['description'] ?? null,
            $input['stock_threshold'] ?? 10,
            $sku
        ]);

        echo json_encode(['success' => true, 'message' => 'Product updated']);
        exit;
    }

    // Delete product
    if ($method === 'DELETE' && in_array('products', $path_parts)) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE sku = ?");
        $stmt->execute([$input['sku']]);

        echo json_encode(['success' => true, 'message' => 'Product deleted']);
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
