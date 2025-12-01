<?php

namespace Tests;

/** @noinspection PhpUnusedAliasInspection */
use PHPUnit\Framework\TestCase;

/**
 * ProductTest Class
 * @covers \Products\Product
 */

class ProductTest extends TestCase
{
    private $pdo;
    
    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: 3306;
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';
        $dbname = 'inventory_test';
        
        try {
            $this->pdo = new \PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Clear products table
            $this->pdo->exec("TRUNCATE TABLE products");
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test: Create a new product
     */
    public function testCreateProduct(): void
    {
        $sku = 'TEST-CPU-001';
        $name = 'Test Intel i7';
        $price = 350.00;
        $category = 'Processors';
        $description = 'Test product description';
        $threshold = 5;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO products (sku, name, price, category, description, stock_threshold) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        $result = $stmt->execute([$sku, $name, $price, $category, $description, $threshold]);
        $this->assertTrue($result, 'Product insertion should succeed');
        
        // Verify product was created
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertIsNotEmpty($product, 'Product should exist in database');
        $this->assertEquals($name, $product['name']);
        $this->assertEquals($price, $product['price']);
    }
    
    /**
     * Test: Get product by SKU
     */
    public function testGetProductBySku(): void
    {
        $sku = 'TEST-GPU-002';
        $name = 'Test RTX 4070';
        $price = 550.00;
        
        // Insert test product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $name, $price]);
        
        // Retrieve product
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertIsNotEmpty($product);
        $this->assertEquals($sku, $product['sku']);
        $this->assertEquals($name, $product['name']);
    }
    
    /**
     * Test: Update product
     */
    public function testUpdateProduct(): void
    {
        $sku = 'TEST-RAM-003';
        $originalName = 'Original RAM';
        $newName = 'Updated RAM';
        $price = 150.00;
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $originalName, $price]);
        
        // Update product
        $stmt = $this->pdo->prepare("UPDATE products SET name = ? WHERE sku = ?");
        $result = $stmt->execute([$newName, $sku]);
        
        $this->assertTrue($result);
        $this->assertEquals(1, $stmt->rowCount());
        
        // Verify update
        $stmt = $this->pdo->prepare("SELECT name FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($newName, $product['name']);
    }
    
    /**
     * Test: Delete product
     */
    public function testDeleteProduct(): void
    {
        $sku = 'TEST-SSD-004';
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
        $stmt->execute([$sku, 'Test SSD', 80.00]);
        
        // Delete product
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE sku = ?");
        $result = $stmt->execute([$sku]);
        
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNull($product, 'Product should be deleted');
    }
    
    /**
     * Test: Duplicate SKU prevention
     */
    public function testUniqueSKUConstraint(): void
    {
        $sku = 'TEST-UNIQUE-001';
        
        // Insert first product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
        $stmt->execute([$sku, 'First Product', 100.00]);
        
        // Try to insert duplicate SKU
        $this->expectException(\PDOException::class);
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
        $stmt->execute([$sku, 'Duplicate Product', 200.00]);
    }
    
    /**
     * Test: List all products
     */
    public function testListAllProducts(): void
    {
        // Insert multiple products
        $products = [
            ['TEST-P1', 'Product 1', 100.00],
            ['TEST-P2', 'Product 2', 200.00],
            ['TEST-P3', 'Product 3', 300.00],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        
        // Get all products
        $stmt = $this->pdo->query("SELECT * FROM products ORDER BY sku");
        $allProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(3, $allProducts);
        $this->assertEquals('TEST-P1', $allProducts[0]['sku']);
    }
    
    /**
     * Test: Product price validation
     */
    public function testProductPriceValidation(): void
    {
        $sku = 'TEST-PRICE-001';
        $validPrice = 99.99;
        
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
        $result = $stmt->execute([$sku, 'Product', $validPrice]);
        
        $this->assertTrue($result);
        
        // Verify price precision
        $stmt = $this->pdo->prepare("SELECT price FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($validPrice, (float)$product['price']);
    }
    
    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo->exec("TRUNCATE TABLE products");
        }
    }
}
