# Prometheus & Grafana Quick Reference

## Starting Monitoring

**Windows:**
```bash
start-monitoring.bat
```

**Linux/Mac:**
```bash
bash start-monitoring.sh
```

**Manual:**
```bash
docker-compose -f docker-compose.monitoring.yml up -d
```

## Access URLs
| Service | URL | Credentials |
|---------|-----|-------------|
| Prometheus | http://localhost:9090 | (none) |
| Grafana | http://localhost:3001 | admin/admin123 |
| AlertManager | http://localhost:9093 | (none) |
| Node Exporter | http://localhost:9100/metrics | (none) |

## Essential PromQL Queries

### Request Metrics
```promql
# Total requests per second
rate(http_requests_total[5m])

# Requests by status code
rate(http_requests_total[5m]) by (status)

# Error rate percentage
(rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m])) * 100
```

### Response Time
```promql
# Average response time
rate(http_request_duration_seconds_sum[5m]) / rate(http_request_duration_seconds_count[5m])

# 50th percentile (median)
histogram_quantile(0.50, rate(http_request_duration_seconds_bucket[5m]))

# 95th percentile
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))

# 99th percentile
histogram_quantile(0.99, rate(http_request_duration_seconds_bucket[5m]))
```

### Service Health
```promql
# Service availability (1=up, 0=down)
up{job="api-gateway"}

# All services status
up{job=~"product-catalog-service|inventory-service|sales-service"}

# Request rate by service
rate(http_requests_total[5m]) by (job)
```

### System Resources
```promql
# CPU usage
100 - (avg by (instance) (rate(node_cpu_seconds_total{mode="idle"}[5m])) * 100)

# Memory usage percentage
100 * (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes))

# Memory available GB
node_memory_MemAvailable_bytes / 1024 / 1024 / 1024

# Disk usage percentage
100 * (1 - (node_filesystem_avail_bytes / node_filesystem_size_bytes))

# Disk free GB
node_filesystem_avail_bytes / 1024 / 1024 / 1024
```

### Database
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

# Memory usage MB
redis_used_memory_bytes / 1024 / 1024
```

## Creating Grafana Panels

1. Open Grafana: http://localhost:3001
2. Login: admin / admin123
3. Click "+" → "Dashboard"
4. Click "Add new panel"
5. Enter PromQL query
6. Choose visualization type
7. Configure axes, units, thresholds
8. Save dashboard

### Panel Types
- **Graph**: Time series data (lines)
- **Stat**: Single number (gauge)
- **Gauge**: Speed meter display
- **Table**: Tabular data
- **Heatmap**: Heat map visualization
- **Alert List**: Current alerts

## Alert Rules

Configured alerts in `monitoring/rules.yml`:

| Alert | Condition | Severity |
|-------|-----------|----------|
| High Error Rate | >5% for 5m | CRITICAL |
| High Latency | p95 > 1s | WARNING |
| Service Down | 0% uptime | CRITICAL |
| High Memory | >85% | WARNING |
| Low Disk | <10% free | CRITICAL |
| High Requests | >1000 req/s | WARNING |

## Troubleshooting

**No metrics data?**
- Check Prometheus → Targets (http://localhost:9090/targets)
- Verify service metrics endpoints: `curl http://localhost:8001/metrics`

**Grafana won't connect to Prometheus?**
- Go to Settings → Data Sources → Prometheus
- Click "Test" button
- Check URL: `http://prometheus:9090`

**Services not scraping?**
- Edit `monitoring/prometheus.yml`
- Restart Prometheus: `docker-compose -f docker-compose.monitoring.yml restart prometheus`

**Memory usage high?**
- Check retention: `docker logs inventorytracker-prometheus`
- Reduce retention in compose file

## Performance Tips

1. **Query Range**: Use appropriate time ranges (last 5m, 1h, 24h)
2. **Aggregation**: Use `by()` clause to reduce series cardinality
3. **Alerting**: Set reasonable thresholds to avoid alert fatigue
4. **Storage**: Monitor disk usage with node exporter metrics
5. **Dashboard**: Limit panels per dashboard to maintain performance

## Common Patterns

### Check if service is healthy
```promql
up{job="product-catalog-service"} == 1
```

### Find services with errors
```promql
rate(http_requests_total{status=~"5.."}[5m]) > 0
```

### Identify slow endpoints
```promql
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m])) > 0.5
```

### Monitor cache efficiency
```promql
rate(redis_keyspace_hits_total[5m]) / (rate(redis_keyspace_hits_total[5m]) + rate(redis_keyspace_misses_total[5m]))
```

## Stopping Monitoring Stack
```bash
docker-compose -f docker-compose.monitoring.yml down
```

## Viewing Logs
```bash
# Prometheus logs
docker logs inventorytracker-prometheus

# Grafana logs
docker logs inventorytracker-grafana

# AlertManager logs
docker logs inventorytracker-alertmanager
```

## More Resources
- Full Guide: `MONITORING_SETUP.md`
- Prometheus Docs: https://prometheus.io/docs
- Grafana Docs: https://grafana.com/docs
- PromQL Docs: https://prometheus.io/docs/prometheus/latest/querying/basics/
