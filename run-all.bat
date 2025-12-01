@echo off
setlocal enabledelayedexpansion

REM Start all services in one window for easier management
cls
echo ========================================
echo Inventory Tracking System - Full Start
echo ========================================
echo.

REM Start Product Catalog
echo Starting Product Catalog on port 8001...
start "Product Catalog" cmd /k "cd /d d:\xampp\htdocs\inventorytracker\services\product-catalog && d:\xampp\php\php.exe -S localhost:8001 index.php"
timeout /t 1 /nobreak

REM Start Inventory Service
echo Starting Inventory Service on port 8002...
start "Inventory Service" cmd /k "cd /d d:\xampp\htdocs\inventorytracker\services\inventory && d:\xampp\php\php.exe -S localhost:8002 index.php"
timeout /t 1 /nobreak

REM Start Sales Service
echo Starting Sales Service on port 8003...
start "Sales Service" cmd /k "cd /d d:\xampp\htdocs\inventorytracker\services\sales && d:\xampp\php\php.exe -S localhost:8003 index.php"
timeout /t 1 /nobreak

REM Start API Gateway
echo Starting API Gateway on port 8000...
start "API Gateway" cmd /k "cd /d d:\xampp\htdocs\inventorytracker\api-gateway && d:\xampp\php\php.exe -S localhost:8000 gateway.php"
timeout /t 2 /nobreak

REM Open browser
echo Opening browser at http://localhost:3000...
timeout /t 2 /nobreak
start http://localhost:3000

echo.
echo ========================================
echo All services started!
echo ========================================
echo Dashboard:     http://localhost:3000
echo API Gateway:   http://localhost:8000
echo Product Svc:   http://localhost:8001
echo Inventory Svc: http://localhost:8002
echo Sales Svc:     http://localhost:8003
echo ========================================
