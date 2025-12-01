@echo off
REM Inventory Tracker - Monitoring Stack Quick Start

echo.
echo 🚀 Starting Prometheus ^& Grafana Monitoring Stack...
echo.

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not installed. Please install Docker Desktop first.
    pause
    exit /b 1
)

REM Check if Docker Compose is available
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker Compose is not available. Please install Docker Desktop with Compose.
    pause
    exit /b 1
)

REM Start monitoring stack
echo 📦 Starting monitoring containers...
docker-compose -f docker-compose.monitoring.yml up -d

REM Wait for services to be ready
echo ⏳ Waiting for services to start...
timeout /t 10 /nobreak

REM Check if services are running
echo.
echo ✅ Monitoring Stack Started!
echo.
echo 📊 Access Points:
echo    • Prometheus: http://localhost:9090
echo    • Grafana: http://localhost:3001 (admin/admin123)
echo    • AlertManager: http://localhost:9093
echo    • Node Exporter: http://localhost:9100/metrics
echo.
echo 🔗 Quick Links:
echo    • Prometheus Targets: http://localhost:9090/targets
echo    • Prometheus Alerts: http://localhost:9090/alerts
echo    • Grafana Dashboards: http://localhost:3001/d
echo.
echo 📖 For detailed setup and PromQL examples, see MONITORING_SETUP.md
echo.
echo To stop monitoring stack: docker-compose -f docker-compose.monitoring.yml down
echo.
pause
