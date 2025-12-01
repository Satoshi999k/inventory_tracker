<?php

namespace Tests;

/** @noinspection PhpUnusedAliasInspection */
use PHPUnit\Framework\TestCase;

/**
 * SalesTest Class
 * @covers \Sales\Transaction
 */

class SalesTest extends TestCase
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
            
            // Cleanup
            $this->pdo->exec("TRUNCATE TABLE sales");
            $this->pdo->exec("TRUNCATE TABLE products");
            
            // Insert test product
            $stmt = $this->pdo->prepare("INSERT INTO products (sku, name, price) VALUES (?, ?, ?)");
            $stmt->execute(['TEST-SKU-001', 'Test Product', 100.00]);
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test: Create a sale transaction
     */
    public function testCreateSaleTransaction(): void
    {
        $txnId = 'TXN-' . date('YmdHis') . '-001';
        $sku = 'TEST-SKU-001';
        $quantity = 5;
        $unitPrice = 100.00;
        $total = $quantity * $unitPrice;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        $result = $stmt->execute([$txnId, $sku, $quantity, $unitPrice, $total]);
        
        $this->assertTrue($result);
        
        // Verify
        $stmt = $this->pdo->prepare("SELECT * FROM sales WHERE transaction_id = ?");
        $stmt->execute([$txnId]);
        $sale = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertIsNotEmpty($sale);
        $this->assertEquals($quantity, $sale['quantity']);
        $this->assertEquals($total, $sale['total']);
    }
    
    /**
     * Test: Calculate sale total correctly
     */
    public function testCalculateSaleTotal(): void
    {
        $txnId = 'TXN-CALC-001';
        $sku = 'TEST-SKU-001';
        $quantity = 3;
        $unitPrice = 100.00;
        $expectedTotal = 300.00;
        
        $total = $quantity * $unitPrice;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$txnId, $sku, $quantity, $unitPrice, $total]);
        
        // Verify calculation
        $stmt = $this->pdo->prepare("SELECT total FROM sales WHERE transaction_id = ?");
        $stmt->execute([$txnId]);
        $sale = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($expectedTotal, (float)$sale['total']);
    }
    
    /**
     * Test: Validate product exists before sale
     */
    public function testValidateProductExistsBeforeSale(): void
    {
        $validSku = 'TEST-SKU-001';
        $invalidSku = 'NONEXISTENT-SKU';
        
        // Check if product exists
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE sku = ?");
        $stmt->execute([$validSku]);
        $validProduct = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertIsNotEmpty($validProduct);
        
        // Check invalid product
        $stmt->execute([$invalidSku]);
        $invalidProduct = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNull($invalidProduct);
    }
    
    /**
     * Test: Get sale by transaction ID
     */
    public function testGetSaleByTransactionId(): void
    {
        $txnId = 'TXN-RETRIEVE-001';
        $sku = 'TEST-SKU-001';
        $quantity = 2;
        $unitPrice = 100.00;
        $total = 200.00;
        
        // Create sale
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$txnId, $sku, $quantity, $unitPrice, $total]);
        
        // Retrieve sale
        $stmt = $this->pdo->prepare("SELECT * FROM sales WHERE transaction_id = ?");
        $stmt->execute([$txnId]);
        $sale = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertIsNotEmpty($sale);
        $this->assertEquals($txnId, $sale['transaction_id']);
        $this->assertEquals($sku, $sale['sku']);
    }
    
    /**
     * Test: List all sales
     */
    public function testListAllSales(): void
    {
        $sales = [
            ['TXN-LIST-001', 'TEST-SKU-001', 1, 100.00, 100.00],
            ['TXN-LIST-002', 'TEST-SKU-001', 2, 100.00, 200.00],
            ['TXN-LIST-003', 'TEST-SKU-001', 3, 100.00, 300.00],
        ];
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        
        foreach ($sales as $sale) {
            $stmt->execute($sale);
        }
        
        // Get all sales
        $stmt = $this->pdo->query("SELECT * FROM sales ORDER BY transaction_id");
        $allSales = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(3, $allSales);
    }
    
    /**
     * Test: Unique transaction ID constraint
     */
    public function testUniqueTransactionIdConstraint(): void
    {
        $txnId = 'TXN-UNIQUE-001';
        $sku = 'TEST-SKU-001';
        
        // Insert first sale
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$txnId, $sku, 1, 100.00, 100.00]);
        
        // Try duplicate transaction ID
        $this->expectException(\PDOException::class);
        $stmt->execute([$txnId, $sku, 2, 100.00, 200.00]);
    }
    
    /**
     * Test: Calculate daily sales
     */
    public function testCalculateDailySales(): void
    {
        $today = date('Y-m-d');
        $sales = [
            ['TXN-DAILY-001', 'TEST-SKU-001', 1, 100.00, 100.00],
            ['TXN-DAILY-002', 'TEST-SKU-001', 2, 100.00, 200.00],
            ['TXN-DAILY-003', 'TEST-SKU-001', 3, 100.00, 300.00],
        ];
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        
        foreach ($sales as $sale) {
            $stmt->execute($sale);
        }
        
        // Calculate daily total
        $stmt = $this->pdo->prepare(
            "SELECT SUM(total) as daily_total FROM sales WHERE DATE(sale_date) = ?"
        );
        $stmt->execute([$today]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals(600.00, (float)$result['daily_total']);
    }
    
    /**
     * Test: Calculate average order value
     */
    public function testCalculateAverageOrderValue(): void
    {
        $sales = [
            ['TXN-AOV-001', 'TEST-SKU-001', 1, 100.00, 100.00],
            ['TXN-AOV-002', 'TEST-SKU-001', 2, 100.00, 200.00],
            ['TXN-AOV-003', 'TEST-SKU-001', 1, 100.00, 100.00],
        ];
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        
        foreach ($sales as $sale) {
            $stmt->execute($sale);
        }
        
        // Calculate average
        $stmt = $this->pdo->query("SELECT AVG(total) as avg_order_value FROM sales");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $expectedAvg = (100 + 200 + 100) / 3;
        $this->assertEquals($expectedAvg, (float)$result['avg_order_value'], '', 0.01);
    }
    
    /**
     * Test: Get sales for specific product
     */
    public function testGetSalesForProduct(): void
    {
        $targetSku = 'TEST-SKU-001';
        
        // Insert sales for same product multiple times
        $stmt = $this->pdo->prepare(
            "INSERT INTO sales (transaction_id, sku, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute(['TXN-PROD-001', $targetSku, 1, 100.00, 100.00]);
        $stmt->execute(['TXN-PROD-002', $targetSku, 2, 100.00, 200.00]);
        
        // Get sales for product
        $stmt = $this->pdo->prepare("SELECT * FROM sales WHERE sku = ? ORDER BY transaction_id");
        $stmt->execute([$targetSku]);
        $productSales = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(2, $productSales);
    }
    
    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo->exec("TRUNCATE TABLE sales");
            $this->pdo->exec("TRUNCATE TABLE products");
        }
    }
}
