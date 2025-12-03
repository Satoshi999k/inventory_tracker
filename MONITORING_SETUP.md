# Prometheus & Grafana - System Monitoring Setup

## What's Been Set Up

✅ **Prometheus Configuration**
- All 5 services configured to expose `/metrics` endpoints
- Metrics scraping every 10 seconds
- Alert rules configured for:
  - Low stock items
  - No sales in 1+ hours
  - High database latency (>500ms)
  - High error rates (>5%)
  - Service downtime

✅ **Metrics Endpoints (Verified Working)**
- **API Gateway** (localhost:8000/metrics) - Gateway performance metrics
- **Product Catalog** (localhost:8001/metrics) - Product management metrics
- **Inventory Service** (localhost:8002/metrics) - Stock level metrics
- **Sales Service** (localhost:8003/metrics) - Revenue & transaction metrics

✅ **Grafana Dashboards**
- Pre-configured datasource connecting to Prometheus
- Pre-built dashboard with:
  - API Request Rate visualization
  - Total Inventory Items gauge
  - Low Stock Items alert gauge
  - Sales Transactions stat

✅ **AlertManager**
- Configured for routing alerts by severity
- Webhook receivers for integration
- Alert inhibition rules to prevent alert flooding

## Quick Start (Two Options)

### Option A: Using Docker (Recommended)

```bash
# Navigate to project directory
cd d:\xampp\htdocs\inventorytracker

# Start monitoring services
docker-compose -f docker-compose.monitoring.yml up -d

# Or double-click: START_MONITORING.bat
```

Access:
- Prometheus: http://localhost:9090
- Grafana: http://localhost:3001 (admin/admin123)
- AlertManager: http://localhost:9093

### Option B: Manual Installation

1. **Download Prometheus**
   - https://prometheus.io/download/
   - Extract to a folder
   - Copy `monitoring/prometheus.yml` from this project
   - Run: `./prometheus --config.file=prometheus.yml`

2. **Download Grafana**
   - https://grafana.com/grafana/download
   - Install and run
   - Access: http://localhost:3000
   - Add Prometheus as datasource: http://localhost:9090

## Key Metrics Tracked

### API Gateway
```
gateway_requests_total          - Total requests by method
gateway_latency_ms              - Request latency distribution
gateway_errors_total            - Errors by HTTP status code
```

### Inventory
```
inventory_items_total           - Total items in stock: 283
inventory_low_stock_items       - Items below threshold: 1 ⚠️
inventory_requests_total        - Total inventory requests
inventory_db_duration_ms        - Database query latency
```

### Products
```
product_count                   - Total products: 10
product_requests_total          - Requests by method/endpoint
product_db_duration_ms          - Database performance
```

### Sales
```
sales_transactions_total        - Total sales: 1
sales_revenue_total             - Total revenue: ₱1,100.00
sales_requests_total            - Total sales API requests
sales_db_duration_ms            - Database latency
```

## Sample PromQL Queries

Try these in Prometheus (http://localhost:9090):

**API Performance**
```promql
# Request rate per second
rate(gateway_requests_total[5m])

# 95th percentile latency
histogram_quantile(0.95, gateway_latency_ms_bucket)

# Error rate percentage
rate(gateway_errors_total[5m]) / rate(gateway_requests_total[5m]) * 100
```

**Business Metrics**
```promql
# Revenue per hour
increase(sales_revenue_total[1h])

# Total inventory value (items * price estimate)
inventory_items_total * 100

# Low stock alert status
inventory_low_stock_items > 0
```

**System Health**
```promql
# Service uptime
up{job=~"product-catalog|inventory|sales|api-gateway"}

# Database latency
histogram_quantile(0.95, rate(inventory_db_duration_ms_bucket[5m]))
```

## Creating Custom Dashboards

In Grafana:
1. Click **+** → **Dashboard**
2. Click **Add Panel**
3. Select **Prometheus** datasource
4. Enter PromQL query (samples above)
5. Choose visualization type
6. Save

## Alert Examples

Alerts fire when:
- **Low Stock**: `inventory_low_stock_items > 0` (5min duration)
- **No Sales**: No sales for 1 hour
- **Slow DB**: 95th percentile latency > 500ms
- **High Errors**: Error rate > 5% for 5 minutes
- **Service Down**: Service unreachable for 2+ minutes

View alerts:
- Prometheus: http://localhost:9090/alerts
- AlertManager: http://localhost:9093/#/alerts

## Stopping Services

```bash
# Stop monitoring stack
docker-compose -f docker-compose.monitoring.yml down

# Or use task manager to kill Docker containers
```

## Troubleshooting

**Prometheus not scraping metrics:**
```bash
# Test if endpoint is accessible
curl http://localhost:8000/metrics
curl http://localhost:8001/metrics
curl http://localhost:8002/metrics
curl http://localhost:8003/metrics
```

**Grafana not showing data:**
1. Check datasource: Settings → Data Sources → Prometheus
2. Verify URL (localhost:9090 for local)
3. Click "Test" to verify connection

**Docker not found:**
- Install Docker Desktop from https://www.docker.com/

## Full Documentation

See **MONITORING_GUIDE.md** for comprehensive documentation including:
- Detailed architecture
- Prometheus query language (PromQL) guide
- Grafana dashboard creation
- Alert configuration
- Performance tuning
- Additional resources

## What's Next?

1. ✅ Start services: `docker-compose -f docker-compose.monitoring.yml up -d`
2. ✅ Access Grafana: http://localhost:3001
3. ✅ Add custom queries and dashboards
4. ✅ Configure webhook alerts to Slack/Email (see alertmanager.yml)
5. ✅ Set up long-term data storage (see MONITORING_GUIDE.md)

---

**All metrics endpoints tested and verified working!** 🚀
