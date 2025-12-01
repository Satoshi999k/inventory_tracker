#!/bin/bash

# Inventory Tracker - Quick Setup Script
# This script sets up and starts the entire Inventory Tracking System

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║   Inventory Tracker - Microservices Setup                  ║"
echo "║   Computer Shop Inventory Management System                ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Check prerequisites
echo "[1/5] Checking prerequisites..."
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✓ Docker is installed ($(docker --version))"
echo "✓ Docker Compose is installed ($(docker-compose --version))"
echo ""

# Create environment file if not exists
echo "[2/5] Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ Created .env file from template"
else
    echo "✓ Using existing .env file"
fi
echo ""

# Build and start services
echo "[3/5] Building and starting services..."
echo "      This may take 2-3 minutes on first run..."
docker-compose down > /dev/null 2>&1 || true
docker-compose up -d --build
echo "✓ Services started"
echo ""

# Wait for services to be healthy
echo "[4/5] Waiting for services to be healthy..."
max_attempts=60
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if docker-compose exec -T mysql mysqladmin ping -h localhost &> /dev/null; then
        echo "✓ MySQL is healthy"
        break
    fi
    echo -n "."
    attempt=$((attempt + 1))
    sleep 1
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ Services failed to start. Check logs with: docker-compose logs"
    exit 1
fi

sleep 5  # Wait for other services
echo ""

# Display access information
echo "[5/5] Setup Complete! 🎉"
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                    Service Endpoints                       ║"
echo "╠════════════════════════════════════════════════════════════╣"
echo "║ Admin Dashboard:        http://localhost:3000               ║"
echo "║ API Gateway:            http://localhost:8000               ║"
echo "║ Product Catalog:        http://localhost:8001               ║"
echo "║ Inventory Service:      http://localhost:8002               ║"
echo "║ Sales Service:          http://localhost:8003               ║"
echo "║ RabbitMQ Management:    http://localhost:15672              ║"
echo "║   (Username: guest | Password: guest)                       ║"
echo "║ MySQL:                  localhost:3306                      ║"
echo "║   (User: inventory_user | DB: inventory_db)                 ║"
echo "║ Redis:                  localhost:6379                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "Quick Start:"
echo "  • Open browser: http://localhost:3000"
echo "  • View logs:    docker-compose logs -f"
echo "  • Stop all:     docker-compose down"
echo "  • Stop one:     docker-compose stop [service-name]"
echo ""
echo "Default Credentials:"
echo "  • RabbitMQ: guest / guest"
echo "  • MySQL User: inventory_user / inventory_password"
echo "  • Sample Products: Already loaded in database"
echo ""
echo "Documentation:"
echo "  • API Docs:        docs/API.md"
echo "  • Deployment:      docs/DEPLOYMENT.md"
echo "  • Development:     docs/DEVELOPMENT.md"
echo ""
echo "Need help? Check the README.md for more information."
echo ""
