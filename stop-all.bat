@echo off
REM =============================================================
REM Inventory Tracker - Stop All Services
REM =============================================================
REM This batch file stops all Docker services for the system
REM =============================================================

echo.
echo ========================================
echo  INVENTORY TRACKER - Stopping Services
echo ========================================
echo.

REM Change to project directory
cd /d "%~dp0"

echo Stopping all containers...
docker-compose down

echo.
echo ========================================
echo  Services Stopped Successfully!
echo ========================================
echo.

pause
