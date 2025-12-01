@echo off
REM Start ALL Services - Double click this file!
color 0A
title Inventory Tracker Starting...

set PHP=d:\xampp\php\php.exe
set BASE=d:\xampp\htdocs\inventorytracker

echo Killing old PHP processes...
taskkill /F /IM php.exe >nul 2>&1
timeout /t 1 >nul

echo Starting all services in background...

REM Start all 5 services
start "Product Catalog" cmd /k "cd /d %BASE%\services\product-catalog && %PHP% -S localhost:8001"
start "Inventory" cmd /k "cd /d %BASE%\services\inventory && %PHP% -S localhost:8002"
start "Sales" cmd /k "cd /d %BASE%\services\sales && %PHP% -S localhost:8003"
start "API Gateway" cmd /k "cd /d %BASE%\api-gateway && %PHP% -S localhost:8000 gateway.php"
start "Frontend" cmd /k "cd /d %BASE%\frontend && %PHP% -S localhost:3000 -t public router.php"

echo Services are starting...
timeout /t 3 >nul
start http://localhost:3000

echo.
echo Done! Open http://localhost:3000 in your browser.
echo You can close this window - services will keep running.
exit