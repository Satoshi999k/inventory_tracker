@echo off
REM This script runs all Inventory Tracker services automatically

setlocal enabledelayedexpansion

set PHP=d:\xampp\php\php.exe
set BASE=d:\xampp\htdocs\inventorytracker

REM Start Product Catalog (8001)
start "ProductCatalog-8001" cmd /k "cd /d %BASE%\services\product-catalog && %PHP% -S localhost:8001 index.php"
ping localhost -n 2 > nul

REM Start Inventory (8002)
start "Inventory-8002" cmd /k "cd /d %BASE%\services\inventory && %PHP% -S localhost:8002 index.php"
ping localhost -n 2 > nul

REM Start Sales (8003)
start "Sales-8003" cmd /k "cd /d %BASE%\services\sales && %PHP% -S localhost:8003 index.php"
ping localhost -n 2 > nul

REM Start API Gateway (8000)
start "APIGateway-8000" cmd /k "cd /d %BASE%\api-gateway && %PHP% -S localhost:8000 gateway.php"
ping localhost -n 2 > nul

REM Start Frontend (3000)
start "Frontend-3000" cmd /k "cd /d %BASE%\frontend && %PHP% -S localhost:3000 router.php"

REM Done
timeout /t 2
exit
