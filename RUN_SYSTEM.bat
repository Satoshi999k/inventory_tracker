@echo off
REM ========================================
REM Computer Shop Inventory Tracker
REM AUTO-START ALL SERVICES
REM Double-click this file to start the system
REM ========================================

title Inventory Tracker - Starting All Services...
color 0A

REM Define paths
set XAMPP_PATH=d:\xampp
set PROJECT_PATH=d:\xampp\htdocs\inventorytracker
set PHP=%XAMPP_PATH%\php\php.exe

echo.
echo =========================================
echo INVENTORY TRACKER - AUTO START SYSTEM
echo =========================================
echo.
echo Starting all 5 services in background...
echo Services will run even after you close this window
echo.

REM Check if PHP exists
if not exist "%PHP%" (
    echo ERROR: PHP not found at %PHP%
    echo Please make sure XAMPP is installed at d:\xampp
    pause
    exit /b 1
)

REM Kill any existing PHP processes on these ports
echo Cleaning up old processes...
for /F "tokens=5" %%a in ('netstat -ano ^| findstr :3000') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano ^| findstr :8000') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano ^| findstr :8001') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano ^| findstr :8002') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano ^| findstr :8003') do taskkill /PID %%a /F >nul 2>&1

timeout /t 1 /nobreak >nul

REM Start services in background (minimized) - ALL AT ONCE for speed
echo Starting all services in parallel...

start /MIN "Product Catalog" cmd /k "cd /d %PROJECT_PATH%\services\product-catalog && %PHP% -S localhost:8001"
start /MIN "Inventory Service" cmd /k "cd /d %PROJECT_PATH%\services\inventory && %PHP% -S localhost:8002"
start /MIN "Sales Service" cmd /k "cd /d %PROJECT_PATH%\services\sales && %PHP% -S localhost:8003"
start /MIN "API Gateway" cmd /k "cd /d %PROJECT_PATH%\api-gateway && %PHP% -S localhost:8000"
start /MIN "Frontend Server" cmd /k "cd /d %PROJECT_PATH%\frontend && %PHP% -S localhost:3000 router.php"

timeout /t 2 /nobreak >nul

echo.
echo =========================================
echo SUCCESS! All services are starting...
echo =========================================
echo.
echo Services running on:
echo   - Frontend:        http://localhost:3000
echo   - API Gateway:     http://localhost:8000
echo   - Product Catalog: http://localhost:8001
echo   - Inventory:       http://localhost:8002
echo   - Sales:           http://localhost:8003
echo.
echo Opening browser automatically...
timeout /t 3 /nobreak >nul

REM Open browser to the application
start http://localhost:3000

echo.
echo Done! Services will continue running in background.
echo You can close this window - services will keep working.
echo.
pause
