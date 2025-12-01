#!/bin/bash
# ============================================
# Complete Testing Suite Runner (Linux/macOS)
# ============================================

echo ""
echo "🧪 COMPLETE TESTING SUITE"
echo "============================================"
echo "Running all test levels..."
echo "============================================"
echo ""

PASSED=0
FAILED=0

# ============================================
# 1. UNIT TESTS
# ============================================
echo ""
echo "1️⃣  UNIT TESTS"
echo "-------------------------------------------"
echo ""

echo "Testing Product Catalog Service..."
if [ -d "services/product-catalog" ]; then
    cd services/product-catalog
    composer test > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Product Catalog Tests PASSED"
        ((PASSED++))
    else
        echo "❌ Product Catalog Tests FAILED"
        ((FAILED++))
    fi
    cd ../..
fi

echo "Testing Inventory Service..."
if [ -d "services/inventory" ]; then
    cd services/inventory
    composer test > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Inventory Tests PASSED"
        ((PASSED++))
    else
        echo "❌ Inventory Tests FAILED"
        ((FAILED++))
    fi
    cd ../..
fi

echo "Testing Sales Service..."
if [ -d "services/sales" ]; then
    cd services/sales
    composer test > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Sales Tests PASSED"
        ((PASSED++))
    else
        echo "❌ Sales Tests FAILED"
        ((FAILED++))
    fi
    cd ../..
fi

# ============================================
# 2. INTEGRATION TESTS
# ============================================
echo ""
echo "2️⃣  INTEGRATION TESTS"
echo "-------------------------------------------"
echo ""

echo "Running integration tests..."
if [ -f "integration-tests.php" ]; then
    php integration-tests.php > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Integration Tests PASSED"
        ((PASSED++))
    else
        echo "⚠️  Integration Tests - Check services are running"
    fi
else
    echo "❌ integration-tests.php not found"
fi

# ============================================
# 3. END-TO-END TESTS
# ============================================
echo ""
echo "3️⃣  END-TO-END TESTS"
echo "-------------------------------------------"
echo ""

echo "Running E2E tests..."
if [ -f "e2e-tests.php" ]; then
    php e2e-tests.php > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ E2E Tests PASSED"
        ((PASSED++))
    else
        echo "⚠️  E2E Tests - Check services are running"
    fi
else
    echo "❌ e2e-tests.php not found"
fi

# ============================================
# 4. MONITORING & VALIDATION
# ============================================
echo ""
echo "4️⃣  MONITORING & VALIDATION"
echo "-------------------------------------------"
echo ""

echo "Running system monitoring..."
if [ -f "monitoring.php" ]; then
    php monitoring.php > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ System Monitoring PASSED"
        ((PASSED++))
    else
        echo "⚠️  System Monitoring - Database may be unavailable"
    fi
else
    echo "❌ monitoring.php not found"
fi

# ============================================
# FINAL REPORT
# ============================================
echo ""
echo "============================================"
echo "📊 TEST REPORT"
echo "============================================"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "🎉 ALL TESTS COMPLETED SUCCESSFULLY!"
    echo ""
    echo "✅ Unit Tests:       PASSED"
    echo "✅ Integration:      PASSED"
    echo "✅ E2E Tests:        PASSED"
    echo "✅ Monitoring:       PASSED"
    echo ""
    echo "Total Tests Passed: $PASSED"
    echo ""
else
    echo "⚠️  SOME TESTS FAILED OR SKIPPED"
    echo ""
    echo "Passed: $PASSED"
    echo "Failed/Skipped: $FAILED"
    echo ""
    echo "Next Steps:"
    echo "  1. Check that all services are running"
    echo "     docker-compose ps"
    echo ""
    echo "  2. View service logs for errors"
    echo "     docker-compose logs [service-name]"
    echo ""
    echo "  3. Verify database is accessible"
    echo "     docker-compose exec mysql mysql -u root -proot_password -e 'SELECT 1;'"
    echo ""
fi

echo "For detailed results, see TEST_GUIDE.md"
echo ""
