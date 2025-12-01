# 🚀 Prometheus & Grafana - 5-Minute Quick Start

## Step 1: Start Monitoring (1 minute)

### Windows
```bash
start-monitoring.bat
```

### Linux/Mac
```bash
bash start-monitoring.sh
```

**Wait 10 seconds for services to start...**

---

## Step 2: Access Services (1 minute)

Open these in your browser:

### Prometheus
**URL**: http://localhost:9090
- Metrics collection & storage
- Click "Targets" to see what's being monitored
- Click "Graph" to write PromQL queries

### Grafana
**URL**: http://localhost:3001
- **Username**: admin
- **Password**: admin123
- Beautiful dashboards & visualization

### AlertManager
**URL**: http://localhost:9093
- Alert management
- View active alerts

---

## Step 3: Verify It's Working (1 minute)

### In Prometheus (http://localhost:9090)
1. Click the "Targets" tab
2. You should see:
   - ✅ api-gateway (UP)
   - ✅ product-catalog-service (UP)
   - ✅ inventory-service (UP)
   - ✅ sales-service (UP)
   - ✅ node-exporter (UP)

If any show "DOWN", check that your services are running.

### Test a Query
1. Click "Graph" tab
2. Paste: `http_requests_total`
3. Click Execute
4. You should see metrics!

---

## Step 4: Create Your First Dashboard (2 minutes)

### In Grafana (http://localhost:3001)

1. **Login**: admin / admin123

2. **Create Dashboard**:
   - Click "+" → "Dashboard"
   - Click "Add new panel"

3. **Add a Panel**:
   - **Title**: My First Panel
   - **Query**: `rate(http_requests_total[5m])`
   - **Visualization**: Graph
   - Click "Save"

4. **Save Dashboard**:
   - Click "Save" (top right)
   - Name: "My First Dashboard"

**Congratulations! 🎉 Your first dashboard is live!**

---

## 5 Essential Metrics to Visualize

Add these as panels to understand your system:

### 1. Request Rate (Requests/Second)
```promql
rate(http_requests_total[5m])
```
- Shows how busy your system is
- Should be steady during normal operations

### 2. Error Rate (%)
```promql
(rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m])) * 100
```
- Shows percentage of failed requests
- Should be near 0% normally
- Alert if > 5%

### 3. Response Time (95th Percentile)
```promql
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))
```
- Shows how fast 95% of requests complete
- Lower is better
- Alert if > 1 second

### 4. Service Health
```promql
up{job=~"product-catalog-service|inventory-service|sales-service|api-gateway"}
```
- Shows if services are running
- 1 = UP, 0 = DOWN
- Create alert if = 0

### 5. Memory Usage (%)
```promql
100 * (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes))
```
- Shows memory utilization
- Alert if > 85%

---

## Common Dashboard Types

### System Health Dashboard
Panels:
- Service status (green if up, red if down)
- CPU usage
- Memory usage
- Disk usage

### Application Performance Dashboard
Panels:
- Request rate
- Error rate
- Response time (p50, p95, p99)
- Requests by endpoint

### Database Dashboard
Panels:
- Active connections
- Queries per second
- Slow queries
- Connection pool

### Cache Dashboard
Panels:
- Cache hit rate
- Connected clients
- Memory usage
- Evicted keys

---

## What's Pre-Configured?

### ✅ 10 Alert Rules
When these conditions happen, you get alerts:
1. High error rate (>5%)
2. High latency (p95 > 1s)
3. Service down
4. High memory (>85%)
5. Low disk (<10%)
6. High DB connections (>80)
7. High response time (p99 > 2s)
8. Too many requests (>1000/s)
9. Low cache hits (<70%)
10. (And one more!)

### ✅ System Monitoring
- CPU usage
- Memory usage
- Disk usage
- Network I/O
- Process information

### ✅ Application Monitoring
- Request rate
- Error rate
- Response time (all percentiles)
- Requests by endpoint
- Requests by status code

---

## Stop Monitoring When Done

```bash
docker-compose -f docker-compose.monitoring.yml down
```

---

## Need Help?

### For Complete Setup Guide
→ Read: `MONITORING_SETUP.md`

### For Quick Commands Reference
→ Read: `MONITORING_QUICK_REF.md`

### For PromQL Query Examples (30+)
→ Read: `MONITORING_QUICK_REF.md` → PromQL section

### For Docker Commands
→ Read: `DOCKER_MONITORING_COMMANDS.md`

### For Adding Metrics to Services
→ Read: `METRICS_INTEGRATION.md`

### For Architecture & Diagrams
→ Read: `MONITORING_ARCHITECTURE.md`

---

## Troubleshooting

### "Services show DOWN in Prometheus"
- Make sure your services are running
- Check: `docker ps`

### "Grafana shows 'No data'"
- Verify Prometheus datasource
- Settings → Data Sources → Prometheus → Test
- Check if metrics exist in Prometheus

### "Can't access http://localhost:9090"
- Wait 10 seconds after start
- Check: `docker ps` (prometheus container running?)
- Try: `docker logs inventorytracker-prometheus`

### "Grafana login fails"
- Default: admin / admin123
- Try: `docker logs inventorytracker-grafana`

---

## Next Steps

1. ✅ Done: Started monitoring
2. ✅ Done: Accessed services
3. ✅ Done: Created dashboard
4. ⏭️ Next: Create more dashboards for your needs
5. ⏭️ Next: Configure email/Slack alerts
6. ⏭️ Next: Add metrics to your services
7. ⏭️ Next: Set up monitoring in production

---

## Quick Reference URLs

| What | URL |
|------|-----|
| Prometheus | http://localhost:9090 |
| Prometheus Targets | http://localhost:9090/targets |
| Prometheus Graph | http://localhost:9090/graph |
| Prometheus Alerts | http://localhost:9090/alerts |
| Grafana | http://localhost:3001 |
| Grafana Dashboards | http://localhost:3001/d |
| AlertManager | http://localhost:9093 |
| Node Exporter | http://localhost:9100/metrics |

---

## Most Used PromQL Queries (Copy & Paste)

### Current Requests Per Second
```promql
rate(http_requests_total[5m])
```

### Error Rate as Percentage
```promql
(rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m])) * 100
```

### Response Time 95th Percentile (seconds)
```promql
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))
```

### Is Service UP?
```promql
up{job="api-gateway"}
```

### Memory Used (%)
```promql
100 * (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes))
```

---

## Key Metrics Explained

**Request Rate**: How many requests per second (higher = busier)
**Error Rate**: What % of requests failed (lower = better)
**Response Time**: How long requests take (lower = faster)
**Service Health**: Is the service running? (1 = yes, 0 = no)
**Resource Usage**: How much CPU/memory/disk is used

---

## Production Checklist

- [ ] Monitoring runs at startup
- [ ] Dashboards created
- [ ] Alerts configured
- [ ] Team trained
- [ ] Notifications working
- [ ] Metrics integrated into services
- [ ] Backups configured
- [ ] Alert thresholds validated

---

**Status**: ✅ Ready to Monitor

**Next**: Go to http://localhost:3001 and create dashboards!

For detailed guides, see the documentation index: `MONITORING_INDEX.md`
