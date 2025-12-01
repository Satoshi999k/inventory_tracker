@echo off
REM Inventory Tracker - Auto Start on Boot
REM This file will be triggered by Windows Task Scheduler on startup

title Inventory Tracker Services - Starting...
color 0A

set PHP=d:\xampp\php\php.exe
set BASE=d:\xampp\htdocs\inventorytracker

echo.
echo Starting Inventory Tracker Services...
echo.

REM Check if services are already running
tasklist | find /i "php.exe" >nul
if %ERRORLEVEL% EQU 0 (
    echo Services already running, exiting...
    exit /b 0
)

REM Start all services in parallel (no waits - they start simultaneously)
echo Starting all services...

start /min "Product Catalog" cmd /k "cd /d %BASE%\services\product-catalog && %PHP% -S localhost:8001 index.php"
start /min "Inventory" cmd /k "cd /d %BASE%\services\inventory && %PHP% -S localhost:8002 index.php"
start /min "Sales" cmd /k "cd /d %BASE%\services\sales && %PHP% -S localhost:8003 index.php"
start /min "API Gateway" cmd /k "cd /d %BASE%\api-gateway && %PHP% -S localhost:8000 gateway.php"
start /min "Frontend" cmd /k "cd /d %BASE%\frontend && %PHP% -S localhost:3000 router.php"

echo.
echo All services started!
echo Visit: http://localhost:3000 (wait 3-5 seconds for services to warm up)
echo.
timeout /t 1 /nobreak > nul
exit /b 0
