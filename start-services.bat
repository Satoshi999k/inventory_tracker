@echo off
REM Start all Inventory Tracking Services

echo ========================================
echo Starting Inventory Tracking System
echo ========================================

REM Set Erlang home for RabbitMQ
set ERLANG_HOME=D:\erlang\Erlang OTP

REM Start Product Catalog Service
echo.
echo [1/4] Starting Product Catalog Service on port 8001...
start "Product Catalog Service" cmd /k "cd /d D:\xampp\htdocs\inventorytracker\services\product-catalog && php -S localhost:8001 index.php"
timeout /t 2 /nobreak

REM Start Inventory Service
echo [2/4] Starting Inventory Service on port 8002...
start "Inventory Service" cmd /k "cd /d D:\xampp\htdocs\inventorytracker\services\inventory && php -S localhost:8002 index.php"
timeout /t 2 /nobreak

REM Start Sales Service
echo [3/4] Starting Sales Service on port 8003...
start "Sales Service" cmd /k "cd /d D:\xampp\htdocs\inventorytracker\services\sales && php -S localhost:8003 index.php"
timeout /t 2 /nobreak

REM Start API Gateway
echo [4/4] Starting API Gateway on port 8000...
start "API Gateway" cmd /k "cd /d D:\xampp\htdocs\inventorytracker\api-gateway && php -S localhost:8000 gateway.php"
timeout /t 2 /nobreak

REM Start Frontend
echo [5/5] Starting Frontend Admin Dashboard on port 3000...
start "Frontend Dashboard" cmd /k "cd /d D:\xampp\htdocs\inventorytracker\frontend && D:\xampp\php\php.exe -S localhost:3000"

echo.
echo ========================================
echo All services started!
echo ========================================
echo.
echo Access the dashboard at: http://localhost:3000
echo.
echo Services running:
echo   - Product Catalog:  http://localhost:8001
echo   - Inventory:        http://localhost:8002
echo   - Sales:            http://localhost:8003
echo   - API Gateway:      http://localhost:8000
echo   - Frontend:         http://localhost:3000
echo   - RabbitMQ:         Running (Windows Service)
echo.
pause
