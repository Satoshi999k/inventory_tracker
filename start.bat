@echo off
REM Inventory Tracker - Start All Services
REM Double-click this file to start all servers

setlocal enabledelayedexpansion

set PHP=D:\xampp\php\php.exe
set BASE=D:\xampp\htdocs\inventorytracker

color 0A
title Inventory Tracker - Starting Services...

cd /d "%BASE%"

echo.
echo ========================================
echo   Inventory Tracker - Starting Services
echo ========================================
echo.

REM Kill any existing PHP processes
echo [*] Stopping any existing services...
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM Start API Gateway (Port 8000)
echo [1/5] Starting API Gateway on port 8000...
start "API Gateway - Port 8000" cmd /k "%PHP% -S 0.0.0.0:8000 -t api-gateway\"

REM Start Product Catalog Service (Port 8001)
echo [2/5] Starting Product Service on port 8001...
start "Product Service - Port 8001" cmd /k "%PHP% -S 0.0.0.0:8001 -t services\product-catalog\"

REM Start Inventory Service (Port 8002)
echo [3/5] Starting Inventory Service on port 8002...
start "Inventory Service - Port 8002" cmd /k "%PHP% -S 0.0.0.0:8002 -t services\inventory\"

REM Start Sales Service (Port 8003)
echo [4/5] Starting Sales Service on port 8003...
start "Sales Service - Port 8003" cmd /k "%PHP% -S 0.0.0.0:8003 -t services\sales\"

REM Start Frontend Server (Port 3000)
echo [5/5] Starting Frontend Server on port 3000...
start "Frontend Server - Port 3000" cmd /k "%PHP% -S 0.0.0.0:3000 -t frontend\public\"

REM Wait for services to start
timeout /t 3 /nobreak >nul

echo.
echo ========================================
echo   All services are starting!
echo ========================================
echo.
echo Open your browser and go to:
echo   http://localhost:3000
echo.
echo Services running on:
echo   - Frontend:         http://localhost:3000
echo   - API Gateway:      http://127.0.0.1:8000
echo   - Product Service:  http://127.0.0.1:8001
echo   - Inventory Service: http://127.0.0.1:8002
echo   - Sales Service:    http://127.0.0.1:8003
echo.
echo You can close this window - all services will keep running.
echo To stop all services, run stop.bat or press Ctrl+C in service windows.
echo.
pause