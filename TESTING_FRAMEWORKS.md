# Testing Strategy - PHPUnit vs Jest vs JUnit

## Overview

Your Inventory Tracker system uses multiple testing frameworks for different components:

| Component | Framework | Tests | Command |
|-----------|-----------|-------|---------|
| **Backend Services** (PHP) | PHPUnit | 27 unit | `composer test` |
| **API Integration** | Custom PHP | 14 tests | `php integration-tests.php` |
| **End-to-End** | Custom PHP | 12 tests | `php e2e-tests.php` |
| **Frontend** (Node.js) | Jest | 20+ tests | `npm test` |
| **Performance** | Apache Bench | 5 scenarios | `performance-tests.bat/sh` |
| **Monitoring** | Custom PHP | 6 checks | `php monitoring.php` |

---

## Backend Testing - PHPUnit

**Why:** PHPUnit is the standard for PHP testing. Your PHP microservices need it.

### Install PHPUnit
```bash
cd services/inventory
composer install
```

### Run Backend Tests
```bash
# All tests for a service
composer test

# Specific test file
php vendor/bin/phpunit tests/InventoryTest.php

# All services at once
run-all-tests.bat  # Windows
bash run-all-tests.sh  # Linux/macOS
```

### PHPUnit Test Types
- ✅ **Unit Tests** (27 total)
  - Product CRUD operations
  - Inventory management
  - Stock calculations
  - Sales transactions

- ✅ **Integration Tests** (14 total)
  - Service-to-service communication
  - API endpoint interactions
  - Database consistency

- ✅ **E2E Tests** (12 total)
  - Complete workflows
  - User scenarios
  - Data integrity checks

---

## Frontend Testing - Jest

**Why:** Jest tests your Node.js/Express frontend server and JavaScript functionality.

### Setup Jest
```bash
cd frontend
npm install
```

### Run Frontend Tests
```bash
# Run all tests
npm test

# Watch mode (rerun on changes)
npm run test:watch

# Coverage report
npm run test:coverage
```

### Jest Test Types
- ✅ **Server Tests** (6 tests)
  - Health checks
  - Static file serving
  - API proxy routes
  - Error handling
  - Security headers

- ✅ **UI/JavaScript Tests** (20+ tests)
  - Form validation
  - User interactions
  - Data processing
  - Authentication
  - Error handling

---

## Backend Testing - ALL 74 Tests

```bash
# Install all dependencies
cd services/product-catalog && composer install
cd ../inventory && composer install
cd ../sales && composer install
cd ../..

# Run complete test suite
run-all-tests.bat  # Windows
bash run-all-tests.sh  # Linux/macOS
```

**Expected Results:**
```
Product Catalog Service: 8 tests ✓
Inventory Service: 9 tests ✓
Sales Service: 10 tests ✓
Integration Tests: 14 tests ✓
E2E Tests: 12 tests ✓
Performance Tests: 5 scenarios ✓
Monitoring: 6 checks ✓
────────────────────────
Total: 64 tests ✓
```

---

## Comparing Test Frameworks

### PHPUnit (Backend - PHP)
```php
class InventoryTest extends TestCase {
    public function testReduceStockOnSale() {
        $qty = 50;
        // ... test code ...
        $this->assertEquals(40, $newQty);
    }
}
```
✅ Perfect for PHP services
✅ Built-in database fixtures
✅ Easy CI/CD integration

### Jest (Frontend - JavaScript)
```javascript
describe('Frontend Tests', () => {
  test('should handle API requests', async () => {
    const response = await fetch('/api/products');
    expect(response.ok).toBe(true);
  });
});
```
✅ Perfect for Node.js/Express
✅ Built-in mocking & coverage
✅ Fast execution

### JUnit (Java)
❌ **Not needed** - Your project doesn't use Java

---

## Running Complete Test Suite

### Windows
```batch
REM Backend tests
run-all-tests.bat

REM Frontend tests
cd frontend
npm test
cd ..

REM Performance tests
performance-tests.bat
```

### Linux/macOS
```bash
# Backend tests
bash run-all-tests.sh

# Frontend tests
cd frontend
npm test
cd ..

# Performance tests
bash performance-tests.sh
```

---

## Test Coverage Goals

| Level | Framework | Current | Target |
|-------|-----------|---------|--------|
| Unit | PHPUnit | 27 | 30 |
| Integration | PHPUnit | 14 | 15 |
| E2E | Custom PHP | 12 | 12 |
| Frontend | Jest | 26 | 30 |
| Performance | Custom | 5 | 5 |
| **Total** | **Mixed** | **84** | **92** |

---

## Recommended Testing Workflow

### Daily Development
```bash
# Test your changes (5 min)
npm test              # Frontend (Jest)
composer test         # Current service (PHPUnit)
```

### Before Commit
```bash
# Full backend test suite (10 min)
run-all-tests.bat
```

### Before Deployment
```bash
# Complete validation (15 min)
run-all-tests.bat
cd frontend && npm test
php performance-tests.php
php monitoring.php
```

---

## Troubleshooting Tests

### PHPUnit Issues
```bash
# Database connection failed?
mysql -u root -e "CREATE DATABASE inventory_test;"

# Composer not found?
# Add PHP/Composer to PATH or use full path

# Tests hang?
# Kill MySQL and restart: net start MySQL80 (Windows)
```

### Jest Issues
```bash
# Module not found?
npm install --save-dev supertest

# Tests timeout?
# Increase timeout: jest --testTimeout=10000

# Port already in use?
# Change port in server.js
```

---

## Next Steps

1. **Install all dependencies:**
   ```bash
   cd services/inventory && composer install
   npm install  # in frontend
   ```

2. **Run all tests:**
   ```bash
   run-all-tests.bat  # or .sh for Linux
   ```

3. **Fix any failures** - Check error messages and adjust code

4. **Add CI/CD** - Integrate into GitHub Actions or GitLab CI

---

## Summary

✅ **PHPUnit** = Backend PHP services (27 tests)
✅ **Jest** = Frontend Node.js (26 tests)  
✅ **Custom** = Integration/E2E/Performance (31 tests)
❌ **JUnit** = Not needed (Java framework)

**Total Tests: 84** across all frameworks

For questions, check `HOW_TO_RUN_TESTS.md` or specific test files.
