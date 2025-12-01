<?php
/**
 * Data Integrity & Health Monitoring
 * Validates system data consistency and health
 * 
 * Run: php monitoring.php
 */

class SystemMonitor
{
    private $db;
    private $results = [];
    private $baseUrl = 'http://localhost:8000';
    
    public function __construct()
    {
        try {
            $this->db = new PDO(
                'mysql:host=localhost;port=3306;dbname=inventory_db',
                'root',
                ''
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "❌ Database connection failed: " . $e->getMessage() . "\n";
            $this->db = null;
        }
    }
    
    public function runFullDiagnostics()
    {
        echo "\n📊 SYSTEM HEALTH & INTEGRITY MONITORING\n";
        echo str_repeat("=", 70) . "\n\n";
        
        $this->checkServiceHealth();
        $this->validateDataIntegrity();
        $this->checkDatabaseHealth();
        $this->monitorPerformance();
        $this->printHealthReport();
    }
    
    private function checkServiceHealth()
    {
        echo "🔍 Service Health Checks\n";
        echo str_repeat("-", 70) . "\n";
        
        $services = [
            'API Gateway' => 'http://localhost:8000/health',
            'Product Service' => 'http://localhost:8001/health',
            'Inventory Service' => 'http://localhost:8002/health',
            'Sales Service' => 'http://localhost:8003/health',
        ];
        
        foreach ($services as $name => $url) {
            $status = $this->checkHealth($url);
            $statusText = $status ? '✅ UP' : '❌ DOWN';
            echo "  $statusText - $name\n";
            $this->results[] = [
                'check' => "$name Health",
                'status' => $status,
                'severity' => 'critical'
            ];
        }
        
        echo "\n";
    }
    
    private function validateDataIntegrity()
    {
        echo "🔒 Data Integrity Validation\n";
        echo str_repeat("-", 70) . "\n";
        
        if (!$this->db) {
            echo "  ⚠️  Database not available\n\n";
            return;
        }
        
        try {
            // Check 1: Product-Inventory foreign key integrity
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM inventory i WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = i.sku)"
            );
            $orphaned = $stmt->fetchColumn();
            $check1 = $orphaned == 0;
            echo ($check1 ? "  ✅" : "  ❌") . " - No orphaned inventory items ($orphaned found)\n";
            $this->results[] = ['check' => 'Foreign Key Integrity', 'status' => $check1, 'severity' => 'high'];
            
            // Check 2: No negative quantities
            $stmt = $this->db->query("SELECT COUNT(*) FROM inventory WHERE quantity < 0");
            $negativeQty = $stmt->fetchColumn();
            $check2 = $negativeQty == 0;
            echo ($check2 ? "  ✅" : "  ❌") . " - No negative stock quantities ($negativeQty found)\n";
            $this->results[] = ['check' => 'Negative Stock', 'status' => $check2, 'severity' => 'critical'];
            
            // Check 3: Unique SKUs in inventory
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM (SELECT sku, COUNT(*) as cnt FROM inventory GROUP BY sku HAVING cnt > 1) t"
            );
            $duplicates = $stmt->fetchColumn();
            $check3 = $duplicates == 0;
            echo ($check3 ? "  ✅" : "  ❌") . " - No duplicate SKUs in inventory ($duplicates found)\n";
            $this->results[] = ['check' => 'Unique SKU Constraint', 'status' => $check3, 'severity' => 'critical'];
            
            // Check 4: Sales reference valid products
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM sales s WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = s.sku)"
            );
            $invalidSales = $stmt->fetchColumn();
            $check4 = $invalidSales == 0;
            echo ($check4 ? "  ✅" : "  ❌") . " - All sales reference valid products ($invalidSales invalid)\n";
            $this->results[] = ['check' => 'Sales Referential Integrity', 'status' => $check4, 'severity' => 'high'];
            
            // Check 5: Unique transaction IDs
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM (SELECT transaction_id, COUNT(*) as cnt FROM sales GROUP BY transaction_id HAVING cnt > 1) t"
            );
            $dupTxns = $stmt->fetchColumn();
            $check5 = $dupTxns == 0;
            echo ($check5 ? "  ✅" : "  ❌") . " - No duplicate transaction IDs ($dupTxns found)\n";
            $this->results[] = ['check' => 'Transaction ID Uniqueness', 'status' => $check5, 'severity' => 'high'];
            
            // Check 6: Valid product prices
            $stmt = $this->db->query("SELECT COUNT(*) FROM products WHERE price < 0");
            $invalidPrices = $stmt->fetchColumn();
            $check6 = $invalidPrices == 0;
            echo ($check6 ? "  ✅" : "  ❌") . " - No invalid product prices ($invalidPrices found)\n";
            $this->results[] = ['check' => 'Product Price Validity', 'status' => $check6, 'severity' => 'medium'];
            
        } catch (Exception $e) {
            echo "  ❌ Error during integrity checks: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function checkDatabaseHealth()
    {
        echo "💾 Database Health\n";
        echo str_repeat("-", 70) . "\n";
        
        if (!$this->db) {
            echo "  ⚠️  Database not available\n\n";
            return;
        }
        
        try {
            // Check database size
            $stmt = $this->db->query(
                "SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()"
            );
            $size = $stmt->fetchColumn();
            $sizeKB = round($size / 1024, 2);
            echo "  📦 Database Size: $sizeKB KB\n";
            
            // Count records
            $stmt = $this->db->query("SELECT COUNT(*) FROM products");
            $productCount = $stmt->fetchColumn();
            echo "  📊 Products: $productCount\n";
            
            $stmt = $this->db->query("SELECT COUNT(*) FROM inventory");
            $inventoryCount = $stmt->fetchColumn();
            echo "  📊 Inventory Items: $inventoryCount\n";
            
            $stmt = $this->db->query("SELECT COUNT(*) FROM sales");
            $salesCount = $stmt->fetchColumn();
            echo "  📊 Sales Transactions: $salesCount\n";
            
            $stmt = $this->db->query("SELECT COUNT(*) FROM stock_alerts WHERE resolved = 0");
            $activeAlerts = $stmt->fetchColumn();
            echo "  🔔 Active Alerts: $activeAlerts\n";
            
            // Check low stock items
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM inventory WHERE quantity < stock_threshold"
            );
            $lowStock = $stmt->fetchColumn();
            echo "  ⚠️  Low Stock Items: $lowStock\n";
            
            $this->results[] = ['check' => 'Database Connectivity', 'status' => true, 'severity' => 'critical'];
            
        } catch (Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
            $this->results[] = ['check' => 'Database Connectivity', 'status' => false, 'severity' => 'critical'];
        }
        
        echo "\n";
    }
    
    private function monitorPerformance()
    {
        echo "⚡ Performance Metrics\n";
        echo str_repeat("-", 70) . "\n";
        
        if (!$this->db) return;
        
        try {
            // Average response time (if available in logs)
            echo "  📈 Recent Sales Trend:\n";
            
            // Last 24 hours
            $stmt = $this->db->query(
                "SELECT 
                    DATE(sale_date) as date,
                    COUNT(*) as transactions,
                    SUM(total) as revenue
                FROM sales 
                WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(sale_date)
                ORDER BY date DESC
                LIMIT 7"
            );
            
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($sales as $row) {
                $transactions = $row['transactions'];
                $revenue = number_format($row['revenue'], 2);
                echo "    {$row['date']}: $transactions transactions, PHP $revenue revenue\n";
            }
            
            // Average transaction value
            $stmt = $this->db->query("SELECT AVG(total) as avg_transaction FROM sales");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $avgTxn = number_format($result['avg_transaction'] ?? 0, 2);
            echo "\n  💰 Average Transaction Value: PHP $avgTxn\n";
            
        } catch (Exception $e) {
            echo "  ⚠️  Could not retrieve performance data: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function checkHealth($url)
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function printHealthReport()
    {
        echo str_repeat("=", 70) . "\n";
        echo "📋 HEALTH REPORT SUMMARY\n";
        echo str_repeat("=", 70) . "\n\n";
        
        $critical = array_filter($this->results, fn($r) => $r['severity'] === 'critical' && !$r['status']);
        $high = array_filter($this->results, fn($r) => $r['severity'] === 'high' && !$r['status']);
        $issues = array_filter($this->results, fn($r) => !$r['status']);
        
        if (empty($issues)) {
            echo "🎉 SYSTEM HEALTHY - All checks passed!\n";
        } else {
            echo "⚠️  SYSTEM ISSUES DETECTED\n";
            echo "  Critical Issues: " . count($critical) . "\n";
            echo "  High Priority Issues: " . count($high) . "\n";
            echo "\nFailed Checks:\n";
            foreach ($issues as $issue) {
                echo "  ❌ {$issue['check']} (Severity: {$issue['severity']})\n";
            }
        }
        
        echo "\n";
    }
}

$monitor = new SystemMonitor();
$monitor->runFullDiagnostics();
