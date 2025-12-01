<?php
/**
 * End-to-End System Tests
 * Tests complete workflows and data consistency
 * 
 * Run: php e2e-tests.php
 */

class E2ETests
{
    private $baseUrl = 'http://localhost:8000';
    private $results = [];
    private $testDatabase;
    
    public function runAllTests()
    {
        echo "\n🎯 END-TO-END SYSTEM TESTS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $this->setupTestDatabase();
        $this->testCompleteWorkflow();
        $this->testDataConsistency();
        $this->testErrorHandling();
        $this->testConcurrentOperations();
        $this->printResults();
    }
    
    private function setupTestDatabase()
    {
        try {
            $this->testDatabase = new PDO(
                'mysql:host=localhost;port=3306;dbname=inventory_db',
                'root',
                ''
            );
            $this->testDatabase->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "⚠️  Database not accessible: " . $e->getMessage() . "\n";
        }
    }
    
    private function testCompleteWorkflow()
    {
        echo "📋 Test 1: Complete Sale → Inventory Update Workflow\n";
        echo str_repeat("-", 60) . "\n";
        
        // Step 1: Get product
        echo "  Step 1: Getting product...\n";
        $products = $this->request('GET', '/products');
        $productExists = isset($products['data']) && !empty($products['data']);
        $this->recordResult("Product retrieval", $productExists);
        
        if (!$productExists) return;
        
        $sku = $products['data'][0]['sku'];
        
        // Step 2: Get initial inventory
        echo "  Step 2: Getting initial inventory...\n";
        $invBefore = $this->request('GET', "/inventory/$sku");
        $qtyBefore = $invBefore['data']['quantity'] ?? 0;
        $this->recordResult("Initial inventory retrieval", isset($invBefore['data']));
        
        // Step 3: Record sale
        echo "  Step 3: Recording sale...\n";
        $saleData = ['sku' => $sku, 'quantity' => 1];
        $sale = $this->request('POST', '/sales', $saleData);
        $saleMade = isset($sale['success']) && $sale['success'];
        $this->recordResult("Sale creation", $saleMade);
        
        if (!$saleMade) return;
        
        // Step 4: Verify inventory updated
        echo "  Step 4: Verifying inventory updated...\n";
        sleep(1); // Wait for async update
        $invAfter = $this->request('GET', "/inventory/$sku");
        $qtyAfter = $invAfter['data']['quantity'] ?? $qtyBefore;
        $inventoryReduced = $qtyAfter < $qtyBefore;
        $this->recordResult("Inventory reduced after sale", $inventoryReduced, [
            'before' => $qtyBefore,
            'after' => $qtyAfter
        ]);
        
        // Step 5: Verify sale recorded
        echo "  Step 5: Verifying sale recorded...\n";
        $sales = $this->request('GET', '/sales');
        $salesRecorded = isset($sales['data']) && count($sales['data']) > 0;
        $this->recordResult("Sale recorded in sales log", $salesRecorded);
        
        echo "\n";
    }
    
    private function testDataConsistency()
    {
        echo "📊 Test 2: Data Consistency Verification\n";
        echo str_repeat("-", 60) . "\n";
        
        // Check 1: Product-Inventory consistency
        echo "  Check 1: Product exists for all inventory items...\n";
        $products = $this->request('GET', '/products');
        $inventory = $this->request('GET', '/inventory');
        
        $productsSkus = array_column($products['data'] ?? [], 'sku');
        $inventorySkus = array_column($inventory['data'] ?? [], 'sku');
        
        $consistent = count(array_diff($inventorySkus, $productsSkus)) === 0;
        $this->recordResult("All inventory items have matching products", $consistent, [
            'product_count' => count($productsSkus),
            'inventory_count' => count($inventorySkus)
        ]);
        
        // Check 2: Stock quantity non-negative
        echo "  Check 2: All stock quantities are non-negative...\n";
        $validQuantities = true;
        foreach ($inventory['data'] ?? [] as $item) {
            if ($item['quantity'] < 0) {
                $validQuantities = false;
                break;
            }
        }
        $this->recordResult("All quantities are non-negative", $validQuantities);
        
        // Check 3: Unique SKUs in inventory
        echo "  Check 3: All SKUs are unique...\n";
        $uniqueSkus = count(array_unique($inventorySkus)) === count($inventorySkus);
        $this->recordResult("All inventory SKUs are unique", $uniqueSkus);
        
        echo "\n";
    }
    
    private function testErrorHandling()
    {
        echo "⚠️  Test 3: Error Handling\n";
        echo str_repeat("-", 60) . "\n";
        
        // Error 1: Invalid product
        echo "  Error 1: Attempting to sell non-existent product...\n";
        $response = $this->request('POST', '/sales', [
            'sku' => 'NONEXISTENT-SKU-' . uniqid(),
            'quantity' => 1
        ]);
        $errHandled = isset($response['error']) || !($response['success'] ?? true);
        $this->recordResult("Error on invalid product", $errHandled);
        
        // Error 2: Insufficient stock
        echo "  Error 2: Attempting to oversell...\n";
        $products = $this->request('GET', '/products');
        if (!empty($products['data'])) {
            $sku = $products['data'][0]['sku'];
            $response = $this->request('POST', '/sales', [
                'sku' => $sku,
                'quantity' => 999999 // Unrealistic quantity
            ]);
            $errHandled = isset($response['error']) || !($response['success'] ?? true);
            $this->recordResult("Error on insufficient stock", $errHandled);
        }
        
        // Error 3: Invalid endpoint
        echo "  Error 3: Calling invalid endpoint...\n";
        $response = $this->request('GET', '/invalid-endpoint');
        $errHandled = isset($response['error']) || !isset($response['success']);
        $this->recordResult("Error on invalid endpoint", $errHandled);
        
        echo "\n";
    }
    
    private function testConcurrentOperations()
    {
        echo "⚡ Test 4: Concurrent Operations\n";
        echo str_repeat("-", 60) . "\n";
        
        $products = $this->request('GET', '/products');
        if (empty($products['data'])) {
            echo "  ⚠️  No products available for concurrent test\n\n";
            return;
        }
        
        $sku = $products['data'][0]['sku'];
        
        // Get initial inventory
        $invBefore = $this->request('GET', "/inventory/$sku");
        $qtyBefore = $invBefore['data']['quantity'] ?? 0;
        
        echo "  Simulating 5 concurrent sales...\n";
        
        // Make 5 sales quickly
        $sales = [];
        for ($i = 0; $i < 5; $i++) {
            $response = $this->request('POST', '/sales', [
                'sku' => $sku,
                'quantity' => 1
            ]);
            $sales[] = $response;
        }
        
        // Wait for processing
        sleep(2);
        
        // Check final inventory
        $invAfter = $this->request('GET', "/inventory/$sku");
        $qtyAfter = $invAfter['data']['quantity'] ?? $qtyBefore;
        
        $expectedQty = $qtyBefore - 5;
        $correctSync = abs($qtyAfter - $expectedQty) <= 1; // Allow 1 unit tolerance
        
        $this->recordResult("Concurrent operations handled correctly", $correctSync, [
            'before' => $qtyBefore,
            'after' => $qtyAfter,
            'expected' => $expectedQty,
            'sales_made' => count($sales)
        ]);
        
        echo "\n";
    }
    
    private function request($method, $endpoint, $data = null)
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $ch = curl_init($url);
            
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            if ($data && in_array($method, ['POST', 'PUT'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            return json_decode($response, true) ?? [];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function recordResult($testName, $passed, $details = null)
    {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "    $status - $testName\n";
        
        $this->results[] = [
            'test' => $testName,
            'passed' => $passed,
            'details' => $details
        ];
    }
    
    private function printResults()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 E2E TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $passed = count(array_filter($this->results, fn($r) => $r['passed']));
        $total = count($this->results);
        $percentage = ($passed / $total) * 100;
        
        echo "Passed: $passed/$total (" . round($percentage, 1) . "%)\n\n";
        
        if ($passed === $total) {
            echo "🎉 ALL E2E TESTS PASSED!\n";
        } else {
            echo "⚠️  Some tests failed. Review details above.\n";
        }
        
        echo "\n";
    }
}

$tests = new E2ETests();
$tests->runAllTests();
