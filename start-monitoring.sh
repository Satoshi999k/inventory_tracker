#!/bin/bash

# Inventory Tracker - Monitoring Stack Quick Start

echo "🚀 Starting Prometheus & Grafana Monitoring Stack..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Start monitoring stack
echo "📦 Starting monitoring containers..."
docker-compose -f docker-compose.monitoring.yml up -d

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 10

# Check if services are running
echo ""
echo "✅ Monitoring Stack Started!"
echo ""
echo "📊 Access Points:"
echo "   • Prometheus: http://localhost:9090"
echo "   • Grafana: http://localhost:3001 (admin/admin123)"
echo "   • AlertManager: http://localhost:9093"
echo "   • Node Exporter: http://localhost:9100/metrics"
echo ""
echo "🔗 Quick Links:"
echo "   • Prometheus Targets: http://localhost:9090/targets"
echo "   • Prometheus Alerts: http://localhost:9090/alerts"
echo "   • Grafana Dashboards: http://localhost:3001/d"
echo ""
echo "📖 For detailed setup and PromQL examples, see MONITORING_SETUP.md"
echo ""
echo "To stop monitoring stack: docker-compose -f docker-compose.monitoring.yml down"
