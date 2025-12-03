@echo off
REM Start Prometheus and Grafana for monitoring
color 0B
title Starting Monitoring Stack...

echo.
echo ========================================
echo  Inventory Tracker - Monitoring Setup
echo ========================================
echo.

REM Check if Docker is installed
docker --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Docker is not installed or not in PATH
    echo.
    echo Please install Docker from: https://www.docker.com/products/docker-desktop
    echo.
    pause
    exit /b 1
)

echo Docker found. Starting monitoring services...
echo.

REM Start the monitoring stack
docker-compose -f docker-compose.monitoring.yml up -d

echo.
echo ========================================
echo  Services Starting...
echo ========================================
echo.
echo Waiting 15 seconds for services to initialize...
timeout /t 15 >nul

echo.
echo ========================================
echo  Monitoring Stack Ready!
echo ========================================
echo.
echo Access the services at:
echo.
echo   Prometheus (Metrics):   http://localhost:9090
echo   Grafana (Dashboard):    http://localhost:3001
echo   AlertManager (Alerts):  http://localhost:9093
echo.
echo Default Grafana Credentials:
echo   Username: admin
echo   Password: admin123
echo.
echo Main Inventory System:  http://localhost:3000
echo.
echo ========================================
echo.
echo Note: Press Ctrl+C to stop services
echo.
pause
