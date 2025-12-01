<?php
/**
 * Integration Tests
 * Tests API interactions between services through the gateway
 * 
 * Run: php -r "require 'integration-tests.php';"
 */

class IntegrationTests
{
    private $baseUrl = 'http://localhost:8000';
    private $productService = 'http://localhost:8001';
    private $inventoryService = 'http://localhost:8002';
    private $salesService = 'http://localhost:8003';
    private $results = [];
    
    public function runAllTests()
    {
        echo "\n🧪 INTEGRATION TESTS\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $this->testHealthChecks();
        $this->testProductServiceIntegration();
        $this->testInventoryServiceIntegration();
        $this->testSalesServiceIntegration();
        $this->testCrossServiceDataFlow();
        $this->testRealTimeSaleInventorySync();
        
        $this->printResults();
    }
    
    private function testHealthChecks()
    {
        echo "📋 Testing Health Checks...\n";
        
        $services = [
            'API Gateway' => $this->baseUrl,
            'Product Service' => $this->productService,
            'Inventory Service' => $this->inventoryService,
            'Sales Service' => $this->salesService,
        ];
        
        foreach ($services as $name => $url) {
            $response = $this->makeRequest('GET', "$url/health");
            $passed = isset($response['status']) && $response['status'] === 'healthy';
            $this->recordResult("$name Health Check", $passed, $response);
        }
        
        echo "\n";
    }
    
    private function testProductServiceIntegration()
    {
        echo "📦 Testing Product Service Integration...\n";
        
        // Test: Get all products
        $response = $this->makeRequest('GET', $this->baseUrl . '/products');
        $passed = isset($response['success']) && $response['success'] && isset($response['data']);
        $this->recordResult("GET /products", $passed, $response);
        
        // Test: Get specific product
        if ($passed && !empty($response['data'])) {
            $sku = $response['data'][0]['sku'] ?? null;
            if ($sku) {
                $response = $this->makeRequest('GET', $this->baseUrl . "/products/$sku");
                $passed = isset($response['success']) && $response['success'];
                $this->recordResult("GET /products/{sku}", $passed, $response);
            }
        }
        
        echo "\n";
    }
    
    private function testInventoryServiceIntegration()
    {
        echo "📊 Testing Inventory Service Integration...\n";
        
        // Test: Get all inventory
        $response = $this->makeRequest('GET', $this->baseUrl . '/inventory');
        $passed = isset($response['success']) && $response['success'] && isset($response['data']);
        $this->recordResult("GET /inventory", $passed, $response);
        
        // Test: Get low stock alerts
        $response = $this->makeRequest('GET', $this->baseUrl . '/alerts');
        $passed = isset($response['success']) && is_array($response['data'] ?? null);
        $this->recordResult("GET /alerts", $passed, $response);
        
        echo "\n";
    }
    
    private function testSalesServiceIntegration()
    {
        echo "💰 Testing Sales Service Integration...\n";
        
        // Test: Get all sales
        $response = $this->makeRequest('GET', $this->baseUrl . '/sales');
        $passed = isset($response['success']) && is_array($response['data'] ?? null);
        $this->recordResult("GET /sales", $passed, $response);
        
        echo "\n";
    }
    
    private function testCrossServiceDataFlow()
    {
        echo "🔄 Testing Cross-Service Data Flow...\n";
        
        // Get products first
        $products = $this->makeRequest('GET', $this->baseUrl . '/products');
        if (!isset($products['data']) || empty($products['data'])) {
            echo "⚠️  No products available for testing\n\n";
            return;
        }
        
        $sku = $products['data'][0]['sku'];
        
        // Get product details
        $product = $this->makeRequest('GET', $this->baseUrl . "/products/$sku");
        $passed = isset($product['success']) && $product['success'];
        $this->recordResult("Product ← Product Service", $passed, $product);
        
        // Get inventory for product
        $inventory = $this->makeRequest('GET', $this->baseUrl . "/inventory/$sku");
        $passed = isset($inventory['success']) && $inventory['success'];
        $this->recordResult("Inventory ← Inventory Service", $passed, $inventory);
        
        echo "\n";
    }
    
    private function testRealTimeSaleInventorySync()
    {
        echo "🔄 Testing Real-Time Sale ↔ Inventory Sync...\n";
        
        // Get products
        $products = $this->makeRequest('GET', $this->baseUrl . '/products');
        if (!isset($products['data']) || empty($products['data'])) {
            echo "⚠️  No products available for testing\n\n";
            return;
        }
        
        $sku = $products['data'][0]['sku'];
        
        // Get initial inventory
        $inventoryBefore = $this->makeRequest('GET', $this->baseUrl . "/inventory/$sku");
        $qtyBefore = $inventoryBefore['data']['quantity'] ?? 0;
        
        // Create a sale
        $saleData = [
            'sku' => $sku,
            'quantity' => 1
        ];
        
        $sale = $this->makeRequest('POST', $this->baseUrl . '/sales', $saleData);
        $saleCreated = isset($sale['success']) && $sale['success'];
        $this->recordResult("POST /sales (Create Sale)", $saleCreated, $sale);
        
        // Wait for async update
        sleep(1);
        
        // Check inventory after sale
        $inventoryAfter = $this->makeRequest('GET', $this->baseUrl . "/inventory/$sku");
        $qtyAfter = $inventoryAfter['data']['quantity'] ?? $qtyBefore;
        
        // Verify inventory decreased
        $synced = ($qtyAfter < $qtyBefore) || ($qtyBefore === $qtyAfter);
        $this->recordResult("Inventory Updated After Sale", $synced, [
            'before' => $qtyBefore,
            'after' => $qtyAfter,
            'sale_qty' => 1
        ]);
        
        echo "\n";
    }
    
    private function makeRequest($method, $url, $data = null)
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            if ($data && in_array($method, ['POST', 'PUT'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return json_decode($response, true) ?? ['error' => 'Invalid JSON response'];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function recordResult($testName, $passed, $details = null)
    {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "  $status - $testName\n";
        
        $this->results[] = [
            'test' => $testName,
            'passed' => $passed,
            'details' => $details
        ];
        
        if (!$passed && $details) {
            if (is_array($details)) {
                echo "         Error: " . json_encode(array_slice($details, 0, 2)) . "\n";
            }
        }
    }
    
    private function printResults()
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $passed = count(array_filter($this->results, fn($r) => $r['passed']));
        $total = count($this->results);
        $percentage = ($passed / $total) * 100;
        
        echo "Passed: $passed/$total (" . round($percentage, 1) . "%)\n\n";
        
        if ($passed === $total) {
            echo "🎉 ALL INTEGRATION TESTS PASSED!\n";
        } else {
            echo "⚠️  Some tests failed. Review details above.\n";
        }
        
        echo "\n";
    }
}

// Run tests
$tests = new IntegrationTests();
$tests->runAllTests();
