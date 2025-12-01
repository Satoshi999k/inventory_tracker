# Prometheus & Grafana Monitoring Setup Guide

## Overview
This guide explains how to set up and use Prometheus and Grafana for monitoring your Inventory Tracker system.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Monitoring Stack                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Services (Metrics Exporters)                             │
│  ├── API Gateway (8000/metrics)                           │
│  ├── Product Catalog Service (8001/metrics)              │
│  ├── Inventory Service (8002/metrics)                    │
│  ├── Sales Service (8003/metrics)                        │
│  ├── Node Exporter (9100/metrics) - System metrics       │
│  └── MySQL (3306) - Database metrics                    │
│                                                             │
│  Prometheus (9090) - Time Series Database                 │
│  ├── Scrapes metrics from all services                   │
│  ├── Stores time-series data                            │
│  └── Evaluates alert rules                              │
│                                                             │
│  AlertManager (9093) - Alert Management                  │
│  ├── Processes alerts from Prometheus                   │
│  ├── Routes to destinations (webhook, email, etc)      │
│  └── De-duplicates and groups alerts                    │
│                                                             │
│  Grafana (3001) - Visualization & Dashboards            │
│  ├── Connects to Prometheus datasource                 │
│  ├── Displays dashboards and graphs                    │
│  └── Sends alerts to AlertManager                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Quick Start

### 1. Start Monitoring Stack
```bash
# Start only monitoring services
docker-compose -f docker-compose.monitoring.yml up -d

# Or start monitoring with your main services
docker-compose -f docker-compose.yml -f docker-compose.monitoring.yml up -d
```

### 2. Access Monitoring UIs
- **Prometheus**: http://localhost:9090
- **Grafana**: http://localhost:3001 (admin/admin123)
- **AlertManager**: http://localhost:9093
- **Node Exporter**: http://localhost:9100/metrics

## Key Components

### Prometheus (Port 9090)
Time-series database that scrapes metrics from your services.

**Features:**
- Pulls metrics every 15 seconds
- Stores 15 days of historical data by default
- Evaluates alert rules every 15 seconds
- PromQL for querying metrics

**Key Pages:**
- `/targets` - Shows status of all scraped services
- `/rules` - Shows configured alert rules
- `/graph` - Query and visualize metrics
- `/alerts` - Current alerts

**Example Queries:**
```promql
# Request rate over last 5 minutes
rate(http_requests_total[5m])

# Error rate percentage
rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m])

# Service availability
up{job="api-gateway"}

# Memory usage percentage
100 * (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes))

# Response time 95th percentile
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))

# Database connections
mysql_global_status_threads_connected
```

### Grafana (Port 3001)
Visualization platform for dashboards and alerts.

**Default Credentials:**
- Username: `admin`
- Password: `admin123`

**Features:**
- Beautiful dashboards
- Multiple visualization types (graphs, gauges, tables, heatmaps)
- Alert rules and notifications
- User management
- Data source management

**Setup Steps:**
1. Login with admin credentials
2. Prometheus datasource is auto-configured
3. Import pre-built dashboards from `monitoring/dashboards/`
4. Create custom dashboards

### AlertManager (Port 9093)
Handles alerts from Prometheus and routes them.

**Features:**
- Groups similar alerts
- De-duplicates alerts
- Routes to different receivers based on labels
- Webhook integration
- Email/Slack/PagerDuty support

**Configuration:**
Edit `monitoring/alertmanager.yml` to add:
- Email notifications
- Slack webhooks
- PagerDuty integration
- Custom routing rules

### Node Exporter (Port 9100)
Collects system metrics from the host.

**Metrics Include:**
- CPU usage
- Memory usage
- Disk I/O
- Network I/O
- Process information

## Important Metrics to Monitor

### Request Performance
```promql
# Request rate
rate(http_requests_total[5m])

# Error rate
rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m])

# Latency (p50, p95, p99)
histogram_quantile(0.50, rate(http_request_duration_seconds_bucket[5m]))
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))
histogram_quantile(0.99, rate(http_request_duration_seconds_bucket[5m]))
```

### Service Health
```promql
# Service availability
up{job="service_name"}

# Requests by service
rate(http_requests_total{job="service_name"}[5m])
```

### System Resources
```promql
# CPU usage percentage
100 - (avg by (instance) (rate(node_cpu_seconds_total{mode="idle"}[5m])) * 100)

# Memory usage percentage
100 * (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes))

# Disk usage percentage
100 * (1 - (node_filesystem_avail_bytes / node_filesystem_size_bytes))

# Disk I/O
rate(node_disk_read_bytes_total[5m])
rate(node_disk_write_bytes_total[5m])
```

### Database Metrics
```promql
# Active connections
mysql_global_status_threads_connected

# Queries per second
rate(mysql_global_status_questions[5m])

# Slow queries
mysql_global_status_slow_queries
```

### Redis Cache
```promql
# Cache hit rate
rate(redis_keyspace_hits_total[5m]) / (rate(redis_keyspace_hits_total[5m]) + rate(redis_keyspace_misses_total[5m]))

# Connected clients
redis_connected_clients

# Used memory
redis_used_memory_bytes
```

## Alert Rules

Pre-configured alerts are in `monitoring/rules.yml`:

1. **High API Gateway Error Rate** (>5% for 5m) - CRITICAL
2. **High API Gateway Latency** (p95 > 1s for 5m) - WARNING
3. **Service Down** - CRITICAL
4. **High Database Connections** (>80)
5. **High Memory Usage** (>85%)
6. **Low Disk Space** (<10% available)
7. **High Response Time** (p99 > 2s)
8. **Excessive Request Rate** (>1000 req/s)
9. **Low Cache Hit Rate** (<70%)

## Adding Metrics to Your Services

### PHP Services (Product, Inventory, Sales)
```php
<?php
// Add Prometheus metrics library
require_once __DIR__ . '/../vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Counter;
use Prometheus\Histogram;

// Initialize registry
$registry = new CollectorRegistry();

// Create metrics
$httpRequests = $registry->getOrRegisterCounter(
    'http_requests_total',
    'Total HTTP requests',
    ['method', 'endpoint', 'status']
);

$httpDuration = $registry->getOrRegisterHistogram(
    'http_request_duration_seconds',
    'HTTP request duration',
    ['method', 'endpoint'],
    [0.1, 0.5, 1, 2, 5]
);

// Record metrics
$start = microtime(true);
try {
    // Your endpoint logic
    $httpRequests->inc(['GET', '/api/products', '200']);
} catch (Exception $e) {
    $httpRequests->inc(['GET', '/api/products', '500']);
}
$duration = microtime(true) - $start;
$httpDuration->observe($duration, ['GET', '/api/products']);

// Expose metrics endpoint
if ($_SERVER['REQUEST_URI'] === '/metrics') {
    header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
    echo RenderTextFormat::render($registry);
    exit;
}
?>
```

### Node.js Services
```javascript
// Add Prometheus metrics library
const promClient = require('prom-client');

// Create registry
const register = new promClient.Registry();

// Create metrics
const httpRequestDuration = new promClient.Histogram({
  name: 'http_request_duration_seconds',
  help: 'Duration of HTTP requests in seconds',
  labelNames: ['method', 'route', 'status'],
  buckets: [0.1, 0.5, 1, 2, 5],
  registers: [register]
});

const httpRequestTotal = new promClient.Counter({
  name: 'http_requests_total',
  help: 'Total number of HTTP requests',
  labelNames: ['method', 'route', 'status'],
  registers: [register]
});

// Middleware to track metrics
app.use((req, res, next) => {
  const start = Date.now();
  res.on('finish', () => {
    const duration = (Date.now() - start) / 1000;
    httpRequestDuration.observe({
      method: req.method,
      route: req.path,
      status: res.statusCode
    }, duration);
    httpRequestTotal.inc({
      method: req.method,
      route: req.path,
      status: res.statusCode
    });
  });
  next();
});

// Metrics endpoint
app.get('/metrics', (req, res) => {
  res.set('Content-Type', register.contentType);
  res.end(register.metrics());
});
```

## Creating Custom Dashboards

### In Grafana:
1. Click "+" → "Dashboard"
2. Add panels:
   - **Graph**: Time-series data
   - **Stat**: Current value with gauge
   - **Table**: Tabular data
   - **Heatmap**: Density visualization
   - **Alert List**: Active alerts
3. For each panel:
   - Set title
   - Choose data source (Prometheus)
   - Write PromQL query
   - Configure visualization options
4. Save dashboard

### Example Panel Queries:

**Request Rate Panel:**
```promql
label_replace(
  rate(http_requests_total[5m]),
  "service",
  "$1",
  "job",
  "(.*)"
)
```

**Error Rate Gauge:**
```promql
rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m]) * 100
```

**Service Status Table:**
```promql
up{job=~"product-catalog-service|inventory-service|sales-service"}
```

## Troubleshooting

### Prometheus not scraping metrics
1. Check if services are running: `docker ps`
2. Check `/targets` page in Prometheus UI
3. Verify metrics endpoint: `curl http://localhost:8001/metrics`
4. Check `prometheus.yml` configuration

### Grafana not showing data
1. Verify Prometheus datasource is configured
2. Test datasource connection (Settings → Data Sources → Prometheus → Test)
3. Check if metrics exist in Prometheus
4. Verify PromQL query syntax

### Alerts not firing
1. Check alert rules in Prometheus (`/alerts` page)
2. Verify conditions are being met
3. Check AlertManager configuration
4. Test webhook URL if configured

### High memory usage
- Prometheus stores ~2GB per month by default
- Configure retention: Add `--storage.tsdb.retention.time=30d` to Prometheus
- Or set size limit: `--storage.tsdb.retention.size=5GB`

## Production Best Practices

1. **Data Retention**: Configure appropriate retention period
2. **Backup**: Backup Prometheus and Grafana volumes regularly
3. **Alerts**: Set up email/Slack notifications
4. **Security**: Use authentication and HTTPS in production
5. **Scaling**: Use remote storage for large deployments
6. **Documentation**: Keep dashboards and alerts documented
7. **Testing**: Test alert rules before deploying
8. **Monitoring**: Monitor your monitoring stack itself

## References
- Prometheus: https://prometheus.io/docs
- Grafana: https://grafana.com/docs
- AlertManager: https://prometheus.io/docs/alerting/latest/alertmanager/
- PromQL: https://prometheus.io/docs/prometheus/latest/querying/basics/
