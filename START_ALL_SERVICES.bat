@echo off
title Inventory Tracker - All Services
color 0A

echo.
echo ====================================
echo Inventory Tracker - Service Startup
echo ====================================
echo.
echo Starting all microservices...
echo.

REM Get the path to php.exe
set PHP_PATH=d:\xampp\php\php.exe
set BASE_PATH=d:\xampp\htdocs\inventorytracker

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please check your XAMPP installation
    pause
    exit /b 1
)

echo [1/5] Starting Product Catalog Service (port 8001)...
start "Product Catalog (8001)" cmd /k "cd /d %BASE_PATH%\services\product-catalog && %PHP_PATH% -S localhost:8001 index.php"
timeout /t 2 /nobreak

echo [2/5] Starting Inventory Service (port 8002)...
start "Inventory (8002)" cmd /k "cd /d %BASE_PATH%\services\inventory && %PHP_PATH% -S localhost:8002 index.php"
timeout /t 2 /nobreak

echo [3/5] Starting Sales Service (port 8003)...
start "Sales (8003)" cmd /k "cd /d %BASE_PATH%\services\sales && %PHP_PATH% -S localhost:8003 index.php"
timeout /t 2 /nobreak

echo [4/5] Starting API Gateway (port 8000)...
start "API Gateway (8000)" cmd /k "cd /d %BASE_PATH%\api-gateway && %PHP_PATH% -S localhost:8000 gateway.php"
timeout /t 2 /nobreak

echo [5/5] Starting Frontend Server (port 3000)...
start "Frontend (3000)" cmd /k "cd /d %BASE_PATH%\frontend && %PHP_PATH% -S localhost:3000 router.php"

echo.
echo ====================================
echo All services starting...
echo ====================================
echo.
echo Services will open in separate windows:
echo   - Frontend (3000)
echo   - API Gateway (8000)
echo   - Product Catalog (8001)
echo   - Inventory (8002)
echo   - Sales (8003)
echo.
echo Open: http://localhost:3000
echo.
echo This window can be closed after services start.
echo.
pause
