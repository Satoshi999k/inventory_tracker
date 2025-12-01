<?php
/**
 * Test Bootstrap File - Sales Service
 */

define('TESTS_DIR', __DIR__);
define('ROOT_DIR', dirname(TESTS_DIR));

// Load PHPUnit stubs for IDE support
if (file_exists(TESTS_DIR . '/phpunit-stubs.php')) {
    require_once TESTS_DIR . '/phpunit-stubs.php';
}

require_once ROOT_DIR . '/vendor/autoload.php';

function setupSalesTestDatabase() {
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
            price DECIMAL(10,2) NOT NULL
        )");
        
        // Create sales table
        $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
            id INT PRIMARY KEY AUTO_INCREMENT,
            transaction_id VARCHAR(100) UNIQUE NOT NULL,
            sku VARCHAR(50) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sku) REFERENCES products(sku) ON DELETE CASCADE
        )");
        
        return $pdo;
    } catch (PDOException $e) {
        echo "Database setup failed: " . $e->getMessage() . "\n";
        return null;
    }
}

setupSalesTestDatabase();
