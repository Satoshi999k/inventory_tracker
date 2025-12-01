# How to Run Tests

This guide explains how to execute all tests in the inventory tracker system.

## Prerequisites

1. **XAMPP running** - Make sure Apache, MySQL, and PHP are all running
2. **Composer installed** - Install PHP dependencies first

## Step 1: Install Dependencies

Run this command in each service folder:

```bash
# For Product Catalog Service
cd services/product-catalog
composer install

# For Inventory Service
cd services/inventory
composer install

# For Sales Service
cd services/sales
composer install
```

## Step 2: Set Up Environment Variables (Optional)

If your database settings are different, create a `.env` file in each service:

```
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
```

## Step 3: Run Tests

### Option A: Run All Tests for a Service

```bash
# From the service directory
cd services/inventory
composer test
```

### Option B: Run Specific Test File

```bash
# Run InventoryTest.php only
cd services/inventory
php vendor/bin/phpunit tests/InventoryTest.php
```

### Option C: Run All Tests Across All Services

From the root directory:

```bash
# Windows
run-all-tests.bat

# Linux/macOS
bash run-all-tests.sh
```

## Step 4: View Test Results

After running tests, you'll see output like:

```
PHPUnit 10.0.0 by Sebastian Bergmann

..........................                         27 / 27 (100%)

Time: 00:00.234s

OK (27 tests, 90 assertions)
```

Green dots = passing tests
F = failed test
E = error

## Test Coverage

The system includes **74 total tests** across 5 levels:

| Level | Tests | File | Focus |
|-------|-------|------|-------|
| **Unit** | 27 | ProductTest, InventoryTest, SalesTest | Individual components |
| **Integration** | 14 | integration-tests.php | API interactions |
| **E2E** | 12 | e2e-tests.php | Complete workflows |
| **Performance** | 5 | performance-tests.bat/sh | Load testing |
| **Monitoring** | 6 | monitoring.php | Health checks |

## Individual Test Suites

### 1. Product Catalog Tests (8 tests)

```bash
cd services/product-catalog
composer test
```

**Tests:**
- Create a new product
- Get product by SKU
- Update product
- Delete product
- Duplicate SKU prevention
- List all products
- Product price validation

### 2. Inventory Tests (9 tests)

```bash
cd services/inventory
composer test
```

**Tests:**
- Add inventory for new product
- Reduce stock on sale
- Prevent overselling
- Restock inventory
- Detect low stock
- Categorize stock levels
- Get inventory by SKU
- Create low stock alert

### 3. Sales Tests (10 tests)

```bash
cd services/sales
composer test
```

**Tests:**
- Create a sale transaction
- Calculate sale total correctly
- Validate product exists before sale
- Get sale by transaction ID
- List all sales
- Unique transaction ID constraint
- Calculate daily sales
- Calculate average order value
- Get sales for specific product

### 4. Integration Tests (14 tests)

```bash
php integration-tests.php
```

Tests API endpoints communicating across services.

### 5. E2E Tests (12 tests)

```bash
php e2e-tests.php
```

Tests complete workflows from product creation to sale.

### 6. Performance Tests (5 scenarios)

```bash
# Windows
performance-tests.bat

# Linux/macOS
bash performance-tests.sh
```

Tests system under load.

### 7. Monitoring Tests (6 health checks)

```bash
php monitoring.php
```

Checks system health and connectivity.

## Troubleshooting

### Error: "composer: command not found"

**Solution:** Add Composer to PATH or use full path:
```bash
C:\path\to\composer.phar install
```

### Error: "PHP: command not found"

**Solution:** Add XAMPP PHP to PATH:
```bash
# Windows - Add to PATH:
C:\xampp\php

# Then restart terminal
```

### Error: "Database connection failed"

**Solution:** Check MySQL is running:
```bash
# Windows - From Command Prompt
net start MySQL80

# Or use XAMPP Control Panel to start MySQL
```

### Error: "Table doesn't exist"

**Solution:** Tests auto-create tables, but if they fail:
```bash
# Check if inventory_test database exists
mysql -u root -p -e "SHOW DATABASES;"

# If not, create it manually
mysql -u root -p -e "CREATE DATABASE inventory_test;"
```

### Red marks in VS Code

These are just editor warnings - tests will still run fine. The stub files help but may need editor restart.

**Solution:** Reload VS Code window
- Press `Ctrl+K Ctrl+W` or `Cmd+K Cmd+W`

## Expected Output

A successful test run should show:

```
✓ testAddInventory ... PASS
✓ testReduceStockOnSale ... PASS
✓ testPreventOverselling ... PASS
✓ testRestockInventory ... PASS
✓ testDetectLowStock ... PASS
✓ testCategorizeStockLevels ... PASS
✓ testGetInventoryBySku ... PASS
✓ testCreateLowStockAlert ... PASS

OK (8 tests passed)
```

## Quick Commands Reference

```bash
# Install all dependencies at once
for /d %f in (services\*) do (
  cd %f
  composer install
  cd ..\..
)

# Run all unit tests
composer test -d services/product-catalog
composer test -d services/inventory
composer test -d services/sales

# Run all integration tests
php integration-tests.php && php e2e-tests.php && php monitoring.php

# Clean test databases
mysql -u root -p -e "DROP DATABASE inventory_test;"
```

## Next Steps

1. **Run all tests** to verify installation
2. **Fix any failing tests** - check error messages
3. **Add more tests** as you build features
4. **Monitor performance** regularly

For detailed test code, see:
- `services/*/tests/*.php` - Unit tests
- `integration-tests.php` - Integration tests
- `e2e-tests.php` - End-to-end tests
