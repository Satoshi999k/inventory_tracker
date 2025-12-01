<?php
/**
 * Test Bootstrap File - Inventory Service
 */

define('TESTS_DIR', __DIR__);
define('ROOT_DIR', dirname(TESTS_DIR));

// Load PHPUnit stubs for IDE support
if (file_exists(TESTS_DIR . '/phpunit-stubs.php')) {
    require_once TESTS_DIR . '/phpunit-stubs.php';
}

require_once ROOT_DIR . '/vendor/autoload.php';

function setupInventoryTestDatabase() {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 3306;
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    
    try {
        $pdo = new PDO("mysql:host=$host;port=$port", $user, $password);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS inventory_test");
        $pdo->exec("USE inventory_test");
        
        // Create products table
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sku VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            stock_threshold INT DEFAULT 10
        )");
        
        // Create inventory table
        $pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sku VARCHAR(50) UNIQUE NOT NULL,
            quantity INT NOT NULL DEFAULT 0,
            stock_threshold INT NOT NULL DEFAULT 10,
            reorder_point INT DEFAULT 5,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (sku) REFERENCES products(sku) ON DELETE CASCADE
        )");
        
        // Create stock_alerts table
        $pdo->exec("CREATE TABLE IF NOT EXISTS stock_alerts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sku VARCHAR(50) NOT NULL,
            alert_type VARCHAR(50),
            current_stock INT,
            threshold INT,
            alert_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (sku) REFERENCES products(sku) ON DELETE CASCADE
        )");
        
        return $pdo;
    } catch (PDOException $e) {
        echo "Database setup failed: " . $e->getMessage() . "\n";
        return null;
    }
}

setupInventoryTestDatabase();
