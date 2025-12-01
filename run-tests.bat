@echo off
REM Quick Test Runner for Windows
REM This script runs all tests in sequence

setlocal enabledelayedexpansion

echo.
echo ========================================
echo Inventory Tracker - Test Suite
echo ========================================
echo.

REM Check if Composer is installed
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer not found in PATH
    echo Please install Composer or add it to PATH
    pause
    exit /b 1
)

REM Check if PHP is installed
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found in PATH
    echo Make sure XAMPP is running and PHP is in PATH
    pause
    exit /b 1
)

echo [1/5] Testing Product Catalog Service...
cd services\product-catalog
if exist vendor\bin\phpunit.php (
    php vendor\bin\phpunit tests\ProductTest.php
) else (
    echo Composer dependencies not installed. Installing...
    composer install
    php vendor\bin\phpunit tests\ProductTest.php
)
cd ..\..

echo.
echo [2/5] Testing Inventory Service...
cd services\inventory
if exist vendor\bin\phpunit.php (
    php vendor\bin\phpunit tests\InventoryTest.php
) else (
    echo Composer dependencies not installed. Installing...
    composer install
    php vendor\bin\phpunit tests\InventoryTest.php
)
cd ..\..

echo.
echo [3/5] Testing Sales Service...
cd services\sales
if exist vendor\bin\phpunit.php (
    php vendor\bin\phpunit tests\SalesTest.php
) else (
    echo Composer dependencies not installed. Installing...
    composer install
    php vendor\bin\phpunit tests\SalesTest.php
)
cd ..\..

echo.
echo [4/5] Running Integration Tests...
php integration-tests.php

echo.
echo [5/5] Running E2E Tests...
php e2e-tests.php

echo.
echo ========================================
echo Test Suite Complete!
echo ========================================
echo.
pause
