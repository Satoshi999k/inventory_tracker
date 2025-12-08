@echo off
REM Inventory Tracker - Start All Services with Docker Compose
REM Double-click this file to start all services

setlocal enabledelayedexpansion

set BASE=D:\xampp1\htdocs\inventorytracker

color 0A
title Inventory Tracker - Starting Services with Docker...

cd /d "%BASE%"

echo.
echo ========================================
echo   Inventory Tracker - Starting Services
echo ========================================
echo.
echo Starting Docker Compose services...
echo.

REM Start all services with Docker Compose
docker-compose up -d

echo.
echo ========================================
echo   All services are starting!
echo ========================================
echo.
echo Waiting for services to be ready...
timeout /t 5 /nobreak

echo.
echo Services running on:
echo   - Frontend:           http://localhost:3000
echo   - API Gateway:        http://localhost:8000
echo   - Product Service:    http://localhost:8001
echo   - Inventory Service:  http://localhost:8002
echo   - Sales Service:      http://localhost:8003
echo   - RabbitMQ Admin:     http://localhost:15672 (guest/guest)
echo   - phpMyAdmin:         http://localhost:8888 (inventory_user / inventory_pass)
echo   - MySQL:              localhost:3307
echo   - Redis:              localhost:6379
echo.
echo Open your browser and go to:
echo   http://localhost:3000
echo.
echo Database Access:
echo   - phpMyAdmin: http://localhost:8888
echo   - Username: inventory_user
echo   - Password: inventory_pass
echo.
echo To view logs:
echo   docker-compose logs -f
echo.
echo To stop all services:
echo   docker-compose down
echo.
pause
echo.
pause