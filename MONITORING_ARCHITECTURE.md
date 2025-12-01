# Monitoring Architecture Diagram

## Overall System Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        INVENTORY TRACKER SYSTEM                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  SERVICES LAYER                                                             │
│  ┌───────────────┐  ┌──────────────┐  ┌────────────┐  ┌───────────────┐  │
│  │ API Gateway   │  │ Product      │  │ Inventory  │  │ Sales Service │  │
│  │ Port 8000     │  │ Catalog      │  │ Service    │  │ Port 8003     │  │
│  │ :8000/metrics │  │ Port 8001    │  │ Port 8002  │  │ :8003/metrics │  │
│  │               │  │ :8001/metrics│  │:8002/metrics  │               │  │
│  └───────────────┘  └──────────────┘  └────────────┘  └───────────────┘  │
│                                                                             │
│  INFRASTRUCTURE LAYER                                                       │
│  ┌──────────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │ MySQL Database   │  │ Redis Cache  │  │ RabbitMQ     │               │
│  │ Port 3306        │  │ Port 6379    │  │ Port 5672    │               │
│  └──────────────────┘  └──────────────┘  └──────────────┘               │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                              ↓ (Metrics Export)
┌─────────────────────────────────────────────────────────────────────────────┐
│                        MONITORING STACK                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  METRIC COLLECTION                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐  │
│  │ PROMETHEUS (Port 9090)                                             │  │
│  │ • Scrapes /metrics endpoints every 15 seconds                     │  │
│  │ • Time-series database (TSDB)                                    │  │
│  │ • Evaluates alert rules every 15 seconds                         │  │
│  │ • Stores metrics for 15 days (default)                          │  │
│  │ • PromQL query engine                                            │  │
│  │ • UI at http://localhost:9090                                   │  │
│  │   - Graph: Query & visualize metrics                            │  │
│  │   - Targets: See scraped services                               │  │
│  │   - Alerts: View alert rules & status                           │  │
│  └────────────────────────────────────────────────────────────────────┘  │
│                    ↓ Metrics                ↓ Alerts                       │
│  VISUALIZATION          ALERT MANAGEMENT                                  │
│  ┌──────────────────┐   ┌────────────────────────────────────────────┐   │
│  │ GRAFANA          │   │ ALERTMANAGER (Port 9093)                   │   │
│  │ Port 3001        │   │ • Receives alerts from Prometheus          │   │
│  │                  │   │ • Groups similar alerts                    │   │
│  │ Features:        │   │ • De-duplicates                            │   │
│  │ • Dashboards     │   │ • Routes to receivers:                     │   │
│  │ • Visualizations │   │   - Webhook (custom)                      │   │
│  │ • Annotations    │   │   - Email                                  │   │
│  │ • Alerts         │   │   - Slack                                  │   │
│  │ • User mgmt      │   │   - PagerDuty                             │   │
│  │                  │   │ • Silences                                 │   │
│  │ UI:              │   │ • Inhibition rules                         │   │
│  │ admin/admin123   │   │ • Web UI at http://localhost:9093         │   │
│  │                  │   │   - View active alerts                    │   │
│  │ http://localhost │   │   - Manage silences                       │   │
│  │ :3001            │   └────────────────────────────────────────────┘   │
│  │                  │                                                     │
│  └──────────────────┘                                                     │
│                                                                             │
│  SYSTEM MONITORING                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐  │
│  │ NODE EXPORTER (Port 9100)                                          │  │
│  │ • System metrics: CPU, Memory, Disk, Network                      │  │
│  │ • Process information                                              │  │
│  │ • File descriptor metrics                                          │  │
│  │ • Metrics at http://localhost:9100/metrics                        │  │
│  └────────────────────────────────────────────────────────────────────┘  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Data Flow

```
SERVICE METRICS COLLECTION
═══════════════════════════════════

API Request
    │
    ├─→ Request Handler
    │       ↓
    │   [Middleware Records Metrics]
    │       • http_requests_total++
    │       • http_request_duration_seconds observe
    │       • Requests/errors by endpoint
    │       ↓
    │   Response Sent
    │       ↓
    │   [/metrics endpoint available]
    │
    └─→ curl http://service:port/metrics
            │
            ├─ http_requests_total{method="GET",status="200"} 1234
            ├─ http_request_duration_seconds_bucket{...} 567
            ├─ http_request_duration_seconds_sum{...} 234.5
            └─ database_query_duration_seconds{...} 0.023


PROMETHEUS SCRAPING
═══════════════════════════════════

Every 15 seconds:
    │
    ├─→ Prometheus Scraper
    │       ↓
    │   For each configured job:
    │   ├─ GET http://api-gateway:8000/metrics
    │   ├─ GET http://product-catalog:8001/metrics
    │   ├─ GET http://inventory:8002/metrics
    │   ├─ GET http://sales:8003/metrics
    │   └─ GET http://node-exporter:9100/metrics
    │       ↓
    │   [Parse Prometheus format]
    │   [Attach labels (job, instance)]
    │   ↓
    │   [Store in TSDB]
    │       • Metric name + labels = unique series
    │       • Timestamp + value stored
    │       ↓


ALERT EVALUATION
═══════════════════════════════════

Every 15 seconds:
    │
    ├─→ Prometheus Alert Engine
    │       ↓
    │   For each alert rule:
    │   ├─ Evaluate PromQL expression
    │   ├─ Compare to threshold
    │   ├─ Check duration (for, e.g., 5m)
    │       ↓
    │   If condition met:
    │   ├─ Alert transitions to FIRING
    │   ├─ Send to AlertManager
    │   │   ↓
    │   │   AlertManager receives alert
    │   │   ├─ Extract labels (severity, service, etc)
    │   │   ├─ Find matching route (default, critical, warning)
    │   │   ├─ Group with similar alerts
    │   │   ├─ Wait group_interval (10s) for grouping
    │   │   ├─ Format alert message
    │   │   └─ Send to receiver (webhook, email, Slack)
    │   │
    │   └─ Prometheus marks as PENDING
    │       └─ After 'for' duration expires → FIRING


GRAFANA VISUALIZATION
═══════════════════════════════════

User opens Dashboard:
    │
    ├─→ Grafana Frontend
    │       ↓
    │   For each panel:
    │   ├─ Get PromQL query
    │   ├─ Query Prometheus datasource
    │   │   ├─ http://prometheus:9090/api/v1/query_range
    │   │   ├─ Send query + time range
    │   │   └─ Receive time-series data
    │   │       ↓
    │   ├─ Apply transformations
    │   ├─ Format for visualization
    │   └─ Render:
    │       • Graph (line chart)
    │       • Stat (single value)
    │       • Gauge (speed meter)
    │       • Table (rows/columns)
    │       • Heatmap (density)
    │
    └─→ Real-time Updates (auto-refresh every 30s)
```

## Metric Types

```
COUNTER - Only increases or resets
═══════════════════════════════════

http_requests_total{method="GET"} = 1234
    → Increases by 1 for each request
    → Used for cumulative counts
    → Can only go up or reset to 0


GAUGE - Can go up or down
═══════════════════════════════════

node_memory_MemAvailable_bytes = 4294967296
    → Can increase or decrease
    → Used for current values
    → CPU usage, memory, connections


HISTOGRAM - Distribution of measurements
═══════════════════════════════════════════

http_request_duration_seconds_bucket{le="0.1"} = 100
http_request_duration_seconds_bucket{le="0.5"} = 450
http_request_duration_seconds_bucket{le="1.0"} = 920
http_request_duration_seconds_bucket{le="+Inf"} = 1000
    → Measures latency/size distribution
    → Buckets show count ≤ le (less than or equal)
    → Used for percentile calculations (p50, p95, p99)
    → _sum: total duration
    → _count: total observations
```

## Alert Rules Flow

```
RULE DEFINITION (rules.yml)
═══════════════════════════════════

alert: HighErrorRate
expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
for: 5m
labels:
  severity: critical
annotations:
  summary: "High error rate detected"


EVALUATION TIMELINE
═══════════════════════════════════

t=0:00  expr evaluates to 0.03 (3%)        → Condition FALSE
t=0:15  expr evaluates to 0.04 (4%)        → Condition FALSE
t=0:30  expr evaluates to 0.06 (6%) ✓      → Condition TRUE, PENDING starts
t=0:45  expr evaluates to 0.07 (7%) ✓      → Still TRUE
t=1:00  expr evaluates to 0.08 (8%) ✓      → Still TRUE (5 minutes reached)
                                            → Alert transitions to FIRING ✓
                                            → Sent to AlertManager ✓


ALERTMANAGER ROUTING
═══════════════════════════════════

HighErrorRate Alert received
    │
    ├─ Check routing tree
    │   ├─ Match: severity: critical
    │   └─ Route to: critical receiver
    │       ├─ PagerDuty (on-call engineer)
    │       └─ Email (ops team)
    │
    ├─ Group with similar alerts
    │   ├─ Group by: [alertname, service]
    │   └─ Wait 10s for more alerts
    │
    ├─ Send grouped alert:
    │   {
    │     "status": "firing",
    │     "alerts": [
    │       {
    │         "labels": {
    │           "alertname": "HighErrorRate",
    │           "severity": "critical"
    │         },
    │         "annotations": {
    │           "summary": "High error rate detected"
    │         }
    │       }
    │     ]
    │   }
    │
    └─→ Webhook/Email/PagerDuty/Slack
```

## Component Communication

```
PROMETHEUS SCRAPE CONFIGURATION
═════════════════════════════════════

prometheus.yml:
    scrape_configs:
      - job_name: 'api-gateway'
        static_configs:
          - targets: ['api-gateway:8000']
        metrics_path: '/metrics'
        scrape_interval: 10s

When Prometheus scrapes:
    GET http://api-gateway:8000/metrics HTTP/1.1

Response (Prometheus text format):
    # HELP http_requests_total Total HTTP requests
    # TYPE http_requests_total counter
    http_requests_total{method="GET",endpoint="/api/products",status="200"} 1234
    http_requests_total{method="POST",endpoint="/api/products",status="201"} 45


GRAFANA DATASOURCE QUERY
═════════════════════════════════════

Grafana → Query Prometheus:
    GET /api/v1/query_range?
        query=rate(http_requests_total[5m])
        start=2024-12-01T10:00:00Z
        end=2024-12-01T11:00:00Z
        step=60s

Prometheus response:
    {
      "status": "success",
      "data": {
        "resultType": "matrix",
        "result": [
          {
            "metric": {"method":"GET","endpoint":"/api/products"},
            "values": [
              [1701425400, "12.5"],
              [1701425460, "13.2"],
              [1701425520, "12.8"]
            ]
          }
        ]
      }
    }

Grafana renders graph with line connecting values.
```

## Service Health Matrix

```
┌──────────────────┬──────────┬───────────┬────────────┬──────────────┐
│ Service          │ Status   │ Requests  │ Error Rate │ Response Time│
├──────────────────┼──────────┼───────────┼────────────┼──────────────┤
│ API Gateway      │ UP ✓     │ 45.2 req/s│ 0.5%       │ 123ms (p95)  │
│ Product Catalog  │ UP ✓     │ 12.1 req/s│ 0.0%       │ 87ms (p95)   │
│ Inventory        │ UP ✓     │ 15.8 req/s│ 2.3%       │ 156ms (p95)  │
│ Sales            │ UP ✓     │ 5.4 req/s │ 0.2%       │ 234ms (p95)  │
│ Database (MySQL) │ UP ✓     │ 156 conn  │ -          │ 12ms (avg)   │
│ Cache (Redis)    │ UP ✓     │ 89% hit   │ -          │ 2ms (avg)    │
└──────────────────┴──────────┴───────────┴────────────┴──────────────┘

This matrix is visualized in Grafana dashboards.
```

## Key Concepts

```
LABELS - Metric dimensions
═══════════════════════════════════
http_requests_total{method="GET",endpoint="/api/products",status="200"}
                     ^^^^^^     ^^^^^^^^^^^^^^^^^^^^^^^^^      ^^^^^^
                    Labels describe what was measured

Same metric, different labels = different series
    → Query by label filters data
    → Group by labels aggregates data


TIME SERIES - Sequence of data points
═══════════════════════════════════════
Timestamp          Value
1701425400         123
1701425460         125
1701425520         128
1701425580         124
...

Each metric + label combination = separate time series
1000 metrics × 10 labels = 10,000 time series


PromQL - Query language
═══════════════════════════════════════
rate(http_requests_total[5m])
    → Get counter increase rate over 5 minutes
    → Converts counter to requests-per-second

histogram_quantile(0.95, http_request_duration_seconds_bucket)
    → Calculate 95th percentile of response times
    → Shows how fast 95% of requests complete
```
