@echo off
REM Inventory Tracker - Stop All Services with Docker Compose

color 0C
title Stopping Inventory Tracker Services...

cd /d "D:\xampp\htdocs\inventorytracker"

echo.
echo ========================================
echo   Stopping All Services...
echo ========================================
echo.

echo Stopping Docker Compose services...
docker-compose down

echo.
echo ========================================
echo   All services have been stopped
echo ========================================
echo.
pause
