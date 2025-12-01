<?php
/**
 * Test Bootstrap File
 * Sets up test environment and database
 */

define('TESTS_DIR', __DIR__);
define('ROOT_DIR', dirname(TESTS_DIR));

// Load PHPUnit stubs for IDE support
if (file_exists(TESTS_DIR . '/phpunit-stubs.php')) {
    require_once TESTS_DIR . '/phpunit-stubs.php';
}

// Autoload
require_once ROOT_DIR . '/vendor/autoload.php';

// Test database setup
function setupTestDatabase() {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 3306;
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    
    try {
        $pdo = new PDO("mysql:host=$host;port=$port", $user, $password);
        
        // Create test database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS inventory_test");
        
        // Switch to test database
        $pdo->exec("USE inventory_test");
        
        // Create products table
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sku VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100),
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            stock_threshold INT DEFAULT 10,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_sku (sku),
            INDEX idx_category (category)
        )");
        
        return $pdo;
    } catch (PDOException $e) {
        echo "Database setup failed: " . $e->getMessage() . "\n";
        return null;
    }
}

// Initialize test database
setupTestDatabase();
