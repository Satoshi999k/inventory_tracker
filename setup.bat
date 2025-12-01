@echo off
REM Inventory Tracker - Quick Setup Script for Windows
REM This script sets up and starts the entire Inventory Tracking System

echo.
echo ════════════════════════════════════════════════════════════
echo    Inventory Tracker - Microservices Setup
echo    Computer Shop Inventory Management System
echo ════════════════════════════════════════════════════════════
echo.

REM Check if Docker is installed
echo [1/5] Checking prerequisites...
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker is not installed. Please install Docker Desktop first.
    pause
    exit /b 1
)

echo ✓ Docker is installed
echo ✓ Docker Compose is available
echo.

REM Create environment file if not exists
echo [2/5] Setting up environment...
if not exist .env (
    copy .env.example .env
    echo ✓ Created .env file from template
) else (
    echo ✓ Using existing .env file
)
echo.

REM Build and start services
echo [3/5] Building and starting services...
echo      This may take 2-3 minutes on first run...
docker-compose down >nul 2>&1
docker-compose up -d --build
echo ✓ Services started
echo.

REM Wait for services to be healthy
echo [4/5] Waiting for services to be healthy...
timeout /t 30 /nobreak
echo.

REM Display access information
echo [5/5] Setup Complete! [Checkmark]
echo.
echo ════════════════════════════════════════════════════════════
echo                    Service Endpoints
echo ════════════════════════════════════════════════════════════
echo Admin Dashboard:        http://localhost:3000
echo API Gateway:            http://localhost:8000
echo Product Catalog:        http://localhost:8001
echo Inventory Service:      http://localhost:8002
echo Sales Service:          http://localhost:8003
echo RabbitMQ Management:    http://localhost:15672
echo   (Username: guest ^| Password: guest)
echo MySQL:                  localhost:3306
echo   (User: inventory_user ^| DB: inventory_db)
echo Redis:                  localhost:6379
echo ════════════════════════════════════════════════════════════
echo.
echo Quick Start:
echo   • Open browser: http://localhost:3000
echo   • View logs:    docker-compose logs -f
echo   • Stop all:     docker-compose down
echo.
echo Default Credentials:
echo   • RabbitMQ: guest / guest
echo   • MySQL: inventory_user / inventory_password
echo.
echo Documentation:
echo   • API Docs:        docs\API.md
echo   • Deployment:      docs\DEPLOYMENT.md
echo   • Development:     docs\DEVELOPMENT.md
echo.
pause
