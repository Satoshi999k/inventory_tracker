# Prometheus & Grafana Monitoring Guide

## Overview
This system uses Prometheus for metrics collection and Grafana for visualization to monitor your Inventory Tracker system in real-time.

## Architecture

```
┌─────────────────────────────────────┐
│   Microservices                      │
│  ┌──────────────────────────────┐   │
│  │ - API Gateway (8000)         │   │
│  │ - Product Catalog (8001)     │   │
│  │ - Inventory (8002)           │   │
│  │ - Sales (8003)               │   │
│  │ - Frontend (3000)            │   │
│  └──────────────────────────────┘   │
└────────────────┬────────────────────┘
                 │ /metrics endpoints
                 ↓
         ┌──────────────────┐
         │  Prometheus      │
         │  (9090)          │
         └────────┬─────────┘
                  │ time-series data
                  ↓
         ┌──────────────────┐
         │  Grafana         │
         │  (3001)          │
         └──────────────────┘
                  │
              Dashboard & Alerts
```

## Quick Start

### Option 1: Using Docker (Recommended)

```bash
# Start Prometheus, Grafana, and AlertManager
docker-compose -f docker-compose.monitoring.yml up -d

# Services will be available at:
# - Prometheus: http://localhost:9090
# - Grafana: http://localhost:3001
# - AlertManager: http://localhost:9093
```

### Option 2: Manual Installation

#### Install Prometheus
1. Download from: https://prometheus.io/download/
2. Extract to a location
3. Update `prometheus.yml` with the configuration provided
4. Run: `./prometheus --config.file=prometheus.yml`
5. Access at: http://localhost:9090

#### Install Grafana
1. Download from: https://grafana.com/grafana/download
2. Install following platform-specific instructions
3. Access at: http://localhost:3001
4. Default credentials: admin / admin

## Metrics Being Collected

### API Gateway Metrics
- `gateway_requests_total` - Total requests processed
- `gateway_latency_ms` - Request latency histogram
- `gateway_errors_total` - Total errors by status code

### Product Catalog Metrics
- `product_requests_total` - Total product requests by method/endpoint
- `product_count` - Total products in catalog
- `product_db_duration_ms` - Database query duration

### Inventory Metrics
- `inventory_items_total` - Total items in inventory
- `inventory_low_stock_items` - Items below threshold
- `inventory_requests_total` - Total inventory requests
- `inventory_db_duration_ms` - Database query duration

### Sales Metrics
- `sales_transactions_total` - Total sales transactions
- `sales_revenue_total` - Total revenue
- `sales_requests_total` - Total sales requests
- `sales_db_duration_ms` - Database query duration

## Accessing Prometheus

1. Open http://localhost:9090
2. Use the query editor to write PromQL queries:

### Example Queries

**Request Rate (requests per second):**
```promql
rate(gateway_requests_total[5m])
```

**Error Rate:**
```promql
rate(gateway_errors_total[5m])
```

**Database Latency (95th percentile):**
```promql
histogram_quantile(0.95, gateway_latency_ms_bucket)
```

**Total Inventory Value:**
```promql
inventory_items_total
```

**Low Stock Alert Status:**
```promql
inventory_low_stock_items
```

## Accessing Grafana

1. Open http://localhost:3001
2. Login with: admin / admin123
3. Add Prometheus as datasource:
   - URL: http://prometheus:9090 (Docker) or http://localhost:9090 (local)
4. Import the pre-built dashboard or create custom ones

### Pre-built Dashboards Include:
- **API Gateway Performance** - Request rates, latency, error rates
- **Inventory Status** - Stock levels, low stock alerts
- **Sales Analytics** - Revenue, transaction count, trends
- **System Health** - Service uptime, database performance

## Alerts

Automatic alerts have been configured for:
- **Low Stock Items** - When items fall below threshold
- **No Sales** - When no sales occur for 1+ hours
- **High DB Latency** - When 95th percentile latency > 500ms
- **High Error Rate** - When error rate > 5%
- **Service Down** - When any service is unreachable

View alerts in:
- Prometheus: http://localhost:9090/alerts
- AlertManager: http://localhost:9093

## Custom Dashboards

To create custom dashboards in Grafana:

1. Click "Create" → "Dashboard"
2. Click "Add Panel"
3. Select "Prometheus" as datasource
4. Write queries for metrics you want to track
5. Customize visualization type (graph, gauge, table, etc.)
6. Save dashboard

### Example: Sales Revenue Trend
```promql
increase(sales_revenue_total[1h])
```

### Example: Service Health Status
```promql
up{job=~"product-catalog|inventory|sales|api-gateway"}
```

## Performance Tuning

### Adjust Scrape Intervals
Edit `prometheus.yml`:
```yaml
global:
  scrape_interval: 15s  # Change this (lower = more data, higher resource usage)
  evaluation_interval: 15s
```

### Adjust Data Retention
Run Prometheus with:
```bash
./prometheus --storage.tsdb.retention.time=30d
```

## Troubleshooting

### Prometheus isn't scraping metrics
1. Check if services are running: `http://localhost:8000/metrics`
2. Verify Prometheus configuration for correct targets
3. Check Prometheus logs for errors
4. Ensure firewall allows connections to service ports

### Grafana isn't showing data
1. Verify Prometheus datasource is connected
2. Try manual refresh (Ctrl+Shift+R)
3. Check Prometheus has collected metrics: http://localhost:9090

### Alerts not firing
1. Check AlertManager is running: http://localhost:9093
2. Verify alert rules are loaded: http://localhost:9090/rules
3. Check receiver webhook is accessible

## Commands Reference

```bash
# Start monitoring stack
docker-compose -f docker-compose.monitoring.yml up -d

# Stop monitoring stack
docker-compose -f docker-compose.monitoring.yml down

# View logs
docker-compose -f docker-compose.monitoring.yml logs prometheus
docker-compose -f docker-compose.monitoring.yml logs grafana

# Restart a service
docker-compose -f docker-compose.monitoring.yml restart prometheus
```

## Additional Resources

- Prometheus Docs: https://prometheus.io/docs/
- Grafana Docs: https://grafana.com/docs/
- PromQL Query Language: https://prometheus.io/docs/prometheus/latest/querying/basics/
- Alerting Rules: https://prometheus.io/docs/prometheus/latest/configuration/alerting_rules/

## Support

For issues or questions about monitoring setup, check the logs:
```bash
# Service-specific metrics
curl http://localhost:8000/metrics  # API Gateway
curl http://localhost:8001/metrics  # Product Catalog
curl http://localhost:8002/metrics  # Inventory
curl http://localhost:8003/metrics  # Sales
```
