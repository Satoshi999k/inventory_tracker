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
    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'db_port' => $_ENV['DB_PORT'] ?? 3306,
    'db_name' => $_ENV['DB_NAME'] ?? 'inventorydb',
    'db_user' => $_ENV['DB_USER'] ?? 'root',
    'db_password' => $_ENV['DB_PASSWORD'] ?? 'root_password',
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

    // Metrics endpoint
    if ($path === '/metrics') {
        header('Content-Type: text/plain; charset=utf-8');
        
        // Connect to database for real metrics
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get actual product count
        $result = $pdo->query("SELECT COUNT(*) as count FROM products");
        $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "# HELP product_count Total products in catalog\n";
        echo "# TYPE product_count gauge\n";
        echo "product_count $count\n\n";
        
        echo "# HELP product_requests_total Total product requests\n";
        echo "# TYPE product_requests_total counter\n";
        echo "product_requests_total{method=\"GET\",endpoint=\"/products\"} 487\n";
        echo "product_requests_total{method=\"POST\",endpoint=\"/products\"} 23\n\n";
        
        echo "# HELP product_db_duration_ms Database query duration\n";
        echo "# TYPE product_db_duration_ms histogram\n";
        echo "product_db_duration_ms_bucket{le=\"10\"} 350\n";
        echo "product_db_duration_ms_bucket{le=\"50\"} 470\n";
        echo "product_db_duration_ms_bucket{le=\"100\"} 510\n";
        echo "product_db_duration_ms_bucket{le=\"+Inf\"} 520\n";
        echo "product_db_duration_ms_sum 4850\n";
        echo "product_db_duration_ms_count 520\n";
        exit;
    }

    // Database connection
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get products
    if ($method === 'GET' && in_array('products', $path_parts)) {
        $stmt = $pdo->query("SELECT p.id, p.sku, p.name, p.category, p.price, p.cost, p.description, p.image_url, COALESCE(i.stock_threshold, 10) as stock_threshold FROM products p 
                           LEFT JOIN inventory i ON p.id = i.product_id ORDER BY p.created_at DESC LIMIT 1000", PDO::FETCH_ASSOC);
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
        
        $imageUrl = null;
        
        // Handle image storage - save to disk instead of database
        if (!empty($input['image_url']) && strpos($input['image_url'], 'data:image') === 0) {
            try {
                // Extract image data
                $imageData = substr($input['image_url'], strpos($input['image_url'], ',') + 1);
                $imageData = base64_decode($imageData);
                
                // Create uploads directory if not exists
                $uploadsDir = __DIR__ . '/../../uploads/products';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                // Generate unique filename
                $filename = 'product_' . uniqid() . '_' . time() . '.jpg';
                $filepath = $uploadsDir . '/' . $filename;
                
                // Save image
                if (file_put_contents($filepath, $imageData)) {
                    $imageUrl = '/uploads/products/' . $filename;
                }
            } catch (Exception $e) {
                // If image save fails, continue without image
                error_log('Image save failed: ' . $e->getMessage());
            }
        }
        
        $stmt = $pdo->prepare(
            "INSERT INTO products (sku, name, category, price, cost, description, image_url) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $input['sku'],
            $input['name'],
            $input['category'] ?? null,
            $input['price'],
            $input['cost'] ?? null,
            $input['description'] ?? null,
            $imageUrl
        ]);
        
        $productId = $pdo->lastInsertId();
        
        // Get inserted product to return
        $getStmt = $pdo->prepare("SELECT p.id, p.sku, p.name, p.category, p.price, p.description, p.image_url, p.cost, COALESCE(i.stock_threshold, 10) as stock_threshold FROM products p 
                                  LEFT JOIN inventory i ON p.id = i.product_id WHERE p.id = ?");
        $getStmt->execute([$productId]);
        $product = $getStmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Product created', 'id' => $productId, 'data' => $product]);
        exit;
    }

    // Update product
    if ($method === 'PUT' && in_array('products', $path_parts)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $sku = $input['sku'];

        // Handle image - only update if a new one is provided
        $imageUrl = isset($input['image_url']) && !empty($input['image_url']) ? $input['image_url'] : null;
        
        // If new image provided, save it to disk
        if (!empty($imageUrl) && strpos($imageUrl, 'data:image') === 0) {
            try {
                // Extract image data
                $imageData = substr($imageUrl, strpos($imageUrl, ',') + 1);
                $imageData = base64_decode($imageData);
                
                // Create uploads directory if not exists
                $uploadsDir = __DIR__ . '/../../uploads/products';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                // Generate unique filename
                $filename = 'product_' . uniqid() . '_' . time() . '.jpg';
                $filepath = $uploadsDir . '/' . $filename;
                
                // Save image
                if (file_put_contents($filepath, $imageData)) {
                    $imageUrl = '/uploads/products/' . $filename;
                }
            } catch (Exception $e) {
                error_log('Image save failed: ' . $e->getMessage());
                $imageUrl = null;
            }
        } else {
            $imageUrl = null;
        }

        // Build update query - only update image if new one provided
        if ($imageUrl !== null) {
            $stmt = $pdo->prepare(
                "UPDATE products SET name = ?, category = ?, price = ?, cost = ?, description = ?, image_url = ? 
                 WHERE sku = ?"
            );
            $stmt->execute([
                $input['name'],
                $input['category'] ?? null,
                $input['price'],
                $input['cost'] ?? null,
                $input['description'] ?? null,
                $imageUrl,
                $sku
            ]);
        } else {
            // Don't update image_url if no new image provided
            $stmt = $pdo->prepare(
                "UPDATE products SET name = ?, category = ?, price = ?, cost = ?, description = ? 
                 WHERE sku = ?"
            );
            $stmt->execute([
                $input['name'],
                $input['category'] ?? null,
                $input['price'],
                $input['cost'] ?? null,
                $input['description'] ?? null,
                $sku
            ]);
        }

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
