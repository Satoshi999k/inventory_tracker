@echo off
REM =============================================================
REM Inventory Tracker - Start All Services
REM =============================================================
REM This batch file starts all Docker services for the system
REM =============================================================

echo.
echo ========================================
echo  INVENTORY TRACKER - Starting Services
echo ========================================
echo.

REM Change to project directory
cd /d "%~dp0"

REM Check if Docker is running
docker ps > nul 2>&1
if errorlevel 1 (
    echo ERROR: Docker is not running!
    echo Please start Docker Desktop and try again.
    pause
    exit /b 1
)

echo [1/2] Stopping any existing containers...
docker-compose down --remove-orphans 2>nul

echo [2/2] Starting all services...
docker-compose up -d --build

REM Wait for services to be ready
echo.
echo Waiting for services to start (15 seconds)...
timeout /t 15 /nobreak

echo.
echo ========================================
echo  Services Started Successfully!
echo ========================================
echo.
echo Frontend:        http://localhost:3000
echo API Gateway:     http://localhost:8000
echo Product Service: http://localhost:8001
echo Inventory Service: http://localhost:8002
echo Sales Service:   http://localhost:8003
echo RabbitMQ Admin:  http://localhost:15672 (guest/guest)
echo phpMyAdmin:      http://localhost:8888 (inventory_user/inventory_pass)
echo MySQL:           localhost:3307
echo Redis:           localhost:6379
echo.
echo Press any key to view service status...
pause

REM Show service status
echo.
echo ========================================
echo  Current Service Status
echo ========================================
echo.
docker-compose ps

echo.
echo ========================================
echo.
echo To stop all services, run: stop.bat
echo To view logs, run: docker-compose logs -f
echo.
pause
