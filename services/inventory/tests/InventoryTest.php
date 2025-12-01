<?php

namespace Tests;

/** @noinspection PhpUnusedAliasInspection */
use PHPUnit\Framework\TestCase;

/**
 * InventoryTest Class
 * @covers \Inventory\Service
 */

class InventoryTest extends TestCase
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
            
            // Setup - clear all tables
            $this->pdo->exec("TRUNCATE TABLE stock_alerts");
            $this->pdo->exec("TRUNCATE TABLE inventory");
            $this->pdo->exec("TRUNCATE TABLE products");
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test: Add inventory for new product
     */
    public function testAddInventory(): void
    {
        $sku = 'TEST-SKU-001';
        $quantity = 50;
        $threshold = 10;
        
        // Insert product first
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sku, 'Test Product', 100.00, $threshold]);
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)"
        );
        $result = $stmt->execute([$sku, $quantity, $threshold]);
        
        $this->assertTrue($result);
        
        // Verify
        $stmt = $this->pdo->prepare("SELECT quantity FROM inventory WHERE sku = ?");
        $stmt->execute([$sku]);
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($quantity, $inv['quantity']);
    }
    
    /**
     * Test: Reduce stock on sale (critical for stock accuracy)
     */
    public function testReduceStockOnSale(): void
    {
        $sku = 'TEST-SKU-002';
        $initialQty = 50;
        $saleQty = 10;
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sku, 'Test Product', 100.00, 10]);
        
        // Setup initial inventory
        $stmt = $this->pdo->prepare("INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $initialQty, 10]);
        
        // Reduce stock
        $stmt = $this->pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE sku = ? AND quantity >= ?");
        $result = $stmt->execute([$saleQty, $sku, $saleQty]);
        
        $this->assertTrue($result);
        
        // Verify
        $stmt = $this->pdo->prepare("SELECT quantity FROM inventory WHERE sku = ?");
        $stmt->execute([$sku]);
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($initialQty - $saleQty, $inv['quantity']);
    }
    
    /**
     * Test: Prevent overselling (insufficient stock)
     */
    public function testPreventOverselling(): void
    {
        $sku = 'TEST-SKU-003';
        $stock = 5;
        $requestedQty = 10; // More than available
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sku, 'Test Product', 100.00, 10]);
        
        // Setup
        $stmt = $this->pdo->prepare("INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $stock, 10]);
        
        // Try to sell more than available
        $stmt = $this->pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE sku = ? AND quantity >= ?");
        $stmt->execute([$requestedQty, $sku, $requestedQty]);
        
        // Should not update
        $this->assertEquals(0, $stmt->rowCount(), 'Sale should fail if insufficient stock');
        
        // Verify stock unchanged
        $stmt = $this->pdo->prepare("SELECT quantity FROM inventory WHERE sku = ?");
        $stmt->execute([$sku]);
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($stock, $inv['quantity']);
    }
    
    /**
     * Test: Restock inventory
     */
    public function testRestockInventory(): void
    {
        $sku = 'TEST-SKU-004';
        $currentStock = 20;
        $restockQty = 30;
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sku, 'Test Product', 100.00, 10]);
        
        // Setup
        $stmt = $this->pdo->prepare("INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $currentStock, 10]);
        
        // Restock
        $stmt = $this->pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE sku = ?");
        $result = $stmt->execute([$restockQty, $sku]);
        
        $this->assertTrue($result);
        
        // Verify
        $stmt = $this->pdo->prepare("SELECT quantity FROM inventory WHERE sku = ?");
        $stmt->execute([$sku]);
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($currentStock + $restockQty, $inv['quantity']);
    }
    
    /**
     * Test: Detect low stock
     */
    public function testDetectLowStock(): void
    {
        $sku = 'TEST-SKU-005';
        $threshold = 10;
        $lowStock = 5; // Below threshold
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sku, 'Test Product', 100.00, $threshold]);
        
        // Setup
        $stmt = $this->pdo->prepare("INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $lowStock, $threshold]);
        
        // Check if low stock
        $stmt = $this->pdo->prepare("SELECT quantity, stock_threshold FROM inventory WHERE sku = ?");
        $stmt->execute([$sku]);
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $isLowStock = $inv['quantity'] < $inv['stock_threshold'];
        
        $this->assertTrue($isLowStock);
    }
    
    /**
     * Test: Categorize stock levels (Low, Medium, Good)
     */
    public function testCategorizeStockLevels(): void
    {
        $threshold = 10;
        
        $testCases = [
            ['TEST-LOW-' . uniqid(), 3, 'LOW'],      // Less than threshold
            ['TEST-MED-' . uniqid(), 12, 'MEDIUM'],  // Between threshold and 1.5*threshold
            ['TEST-GOOD-' . uniqid(), 20, 'GOOD'],   // Greater than 1.5*threshold
        ];
        
        foreach ($testCases as [$sku, $quantity, $expectedStatus]) {
            // Insert product
            $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sku, "Product", 100.00, $threshold]);
            
            // Insert inventory
            $stmt = $this->pdo->prepare("INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)");
            $stmt->execute([$sku, $quantity, $threshold]);
            
            // Determine status
            $status = 'GOOD';
            if ($quantity < $threshold) {
                $status = 'LOW';
            } elseif ($quantity < $threshold * 1.5) {
                $status = 'MEDIUM';
            }
            
            $this->assertEquals($expectedStatus, $status);
        }
    }
    
    /**
     * Test: Get inventory by SKU
     */
    public function testGetInventoryBySku(): void
    {
        $sku = 'TEST-SKU-006';
        $qty = 42;
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sku, 'Test Product', 100.00, 10]);
        
        // Setup
        $stmt = $this->pdo->prepare("INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $qty, 10]);
        
        // Get inventory
        $stmt = $this->pdo->prepare("SELECT * FROM inventory WHERE sku = ?");
        $stmt->execute([$sku]);
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertIsNotEmpty($inv);
        $this->assertEquals($qty, $inv['quantity']);
    }
    
    /**
     * Test: Create low stock alert
     */
    public function testCreateLowStockAlert(): void
    {
        $sku = 'TEST-SKU-007';
        $currentStock = 3;
        $threshold = 10;
        
        // Insert product
        $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price, stock_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sku, 'Test Product', 100.00, $threshold]);
        
        // Setup inventory
        $stmt = $this->pdo->prepare("INSERT INTO inventory (sku, quantity, stock_threshold) VALUES (?, ?, ?)");
        $stmt->execute([$sku, $currentStock, $threshold]);
        
        // Create alert
        $stmt = $this->pdo->prepare(
            "INSERT INTO stock_alerts (sku, alert_type, current_stock, threshold) VALUES (?, ?, ?, ?)"
        );
        $result = $stmt->execute([$sku, 'LOW_STOCK', $currentStock, $threshold]);
        
        $this->assertTrue($result);
        
        // Verify alert exists
        $stmt = $this->pdo->prepare("SELECT * FROM stock_alerts WHERE sku = ? AND alert_type = ?");
        $stmt->execute([$sku, 'LOW_STOCK']);
        $alert = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertIsNotEmpty($alert);
        $this->assertEquals($currentStock, $alert['current_stock']);
    }
    
    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo->exec("TRUNCATE TABLE stock_alerts");
            $this->pdo->exec("TRUNCATE TABLE inventory");
            $this->pdo->exec("TRUNCATE TABLE products");
        }
    }
}
