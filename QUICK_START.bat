@echo off
REM ========================================
REM QUICK START - INVENTORY TRACKER
REM Just double-click this file to start!
REM ========================================

color 0A
title Inventory Tracker - Starting...

set XAMPP=d:\xampp
set PHP=%XAMPP%\php\php.exe
set PROJECT=d:\xampp\htdocs\inventorytracker

echo.
echo ========================================
echo  STARTING INVENTORY TRACKER SYSTEM
echo ========================================
echo.

REM Kill any existing PHP processes on these ports
echo Cleaning up old processes...
for /F "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr :3000') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr :8000') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr :8001') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr :8002') do taskkill /PID %%a /F >nul 2>&1
for /F "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr :8003') do taskkill /PID %%a /F >nul 2>&1

timeout /t 1 >nul

REM Start all services in parallel
echo Starting services...
echo.

start /MIN "Product Catalog" cmd /k "cd /d %PROJECT%\services\product-catalog && %PHP% -S localhost:8001"
start /MIN "Inventory Service" cmd /k "cd /d %PROJECT%\services\inventory && %PHP% -S localhost:8002"
start /MIN "Sales Service" cmd /k "cd /d %PROJECT%\services\sales && %PHP% -S localhost:8003"
start /MIN "API Gateway" cmd /k "cd /d %PROJECT%\api-gateway && %PHP% -S localhost:8000"
start /MIN "Frontend" cmd /k "cd /d %PROJECT%\frontend && %PHP% -S localhost:3000"

echo.
echo ========================================
echo  SERVICES STARTING...
echo ========================================
echo.
echo Services will open in background windows.
echo You can minimize or close them - they'll keep running.
echo.
echo Waiting 3 seconds for services to warm up...
timeout /t 3 >nul

REM Open browser
echo Opening browser...
start http://localhost:3000

echo.
echo Done! If browser doesn't open automatically, visit:
echo   http://localhost:3000
echo.
echo Login with:
echo   Email: admin@inventory.com
echo   Password: admin123
echo.
pause
