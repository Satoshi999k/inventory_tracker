#!/bin/bash

# Quick Test Runner for Linux/macOS
# This script runs all tests in sequence

echo ""
echo "========================================"
echo "Inventory Tracker - Test Suite"
echo "========================================"
echo ""

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "ERROR: Composer not found in PATH"
    echo "Please install Composer"
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found in PATH"
    exit 1
fi

# Test Product Catalog Service
echo "[1/5] Testing Product Catalog Service..."
cd services/product-catalog
if [ -f vendor/bin/phpunit ]; then
    php vendor/bin/phpunit tests/ProductTest.php
else
    echo "Installing dependencies..."
    composer install
    php vendor/bin/phpunit tests/ProductTest.php
fi
cd ../..

echo ""
echo "[2/5] Testing Inventory Service..."
cd services/inventory
if [ -f vendor/bin/phpunit ]; then
    php vendor/bin/phpunit tests/InventoryTest.php
else
    echo "Installing dependencies..."
    composer install
    php vendor/bin/phpunit tests/InventoryTest.php
fi
cd ../..

echo ""
echo "[3/5] Testing Sales Service..."
cd services/sales
if [ -f vendor/bin/phpunit ]; then
    php vendor/bin/phpunit tests/SalesTest.php
else
    echo "Installing dependencies..."
    composer install
    php vendor/bin/phpunit tests/SalesTest.php
fi
cd ../..

echo ""
echo "[4/5] Running Integration Tests..."
php integration-tests.php

echo ""
echo "[5/5] Running E2E Tests..."
php e2e-tests.php

echo ""
echo "========================================"
echo "Test Suite Complete!"
echo "========================================"
echo ""
