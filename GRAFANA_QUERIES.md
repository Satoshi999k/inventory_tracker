# Grafana Dashboard Examples

This document contains ready-to-use PromQL queries and dashboard configurations.

## System Health Dashboard

### Panel 1: Service Status
```promql
up{job=~"product-catalog|inventory|sales|api-gateway|frontend"}
```
- Type: Table
- Shows which services are up (1) or down (0)

### Panel 2: Request Rate (Requests/sec)
```promql
sum(rate(gateway_requests_total[5m]))
```
- Type: Graph/Time Series
- Shows API traffic over time

### Panel 3: Error Rate
```promql
(sum(rate(gateway_errors_total[5m])) / sum(rate(gateway_requests_total[5m]))) * 100
```
- Type: Gauge
- Shows error percentage (red if > 5%)

### Panel 4: P95 Latency
```promql
histogram_quantile(0.95, rate(gateway_latency_ms_bucket[5m]))
```
- Type: Graph
- Shows 95th percentile response time

---

## Inventory Dashboard

### Panel 1: Total Inventory Items
```promql
inventory_items_total
```
- Type: Stat/Big Number
- Current total items in stock

### Panel 2: Low Stock Alert
```promql
inventory_low_stock_items
```
- Type: Gauge
- Red if > 0
- Shows items needing restock

### Panel 3: Inventory Requests
```promql
sum(rate(inventory_requests_total[5m]))
```
- Type: Graph
- Inventory API usage over time

### Panel 4: Database Latency
```promql
histogram_quantile(0.95, rate(inventory_db_duration_ms_bucket[5m]))
```
- Type: Graph
- Shows database query performance

---

## Sales Analytics Dashboard

### Panel 1: Total Transactions
```promql
sales_transactions_total
```
- Type: Stat
- Cumulative sales count

### Panel 2: Total Revenue
```promql
sales_revenue_total
```
- Type: Stat with currency formatting
- Total revenue in PHP

### Panel 3: Revenue Trend (Last 24h)
```promql
increase(sales_revenue_total[24h])
```
- Type: Graph
- Revenue increase rate

### Panel 4: Sales Per Hour
```promql
rate(sales_transactions_total[1h])
```
- Type: Graph
- Transaction rate per hour

### Panel 5: Average Order Value
```promql
sales_revenue_total / sales_transactions_total
```
- Type: Stat
- Average transaction value

---

## Product Catalog Dashboard

### Panel 1: Total Products
```promql
product_count
```
- Type: Stat
- Number of products

### Panel 2: Product Request Rate
```promql
sum(rate(product_requests_total[5m]))
```
- Type: Graph
- API usage trend

### Panel 3: Get vs Create Requests
```promql
rate(product_requests_total{method="GET"}[5m]) vs rate(product_requests_total{method="POST"}[5m])
```
- Type: Graph with legend
- Ratio of read vs write operations

### Panel 4: DB Performance
```promql
histogram_quantile(0.95, rate(product_db_duration_ms_bucket[5m]))
```
- Type: Graph
- Database query latency

---

## Performance Monitoring Dashboard

### Panel 1: All Service Latencies
```promql
{__name__=~".*_db_duration_ms_bucket", le="100"}
```
- Type: Table
- Compare performance across services

### Panel 2: Request Distribution
```promql
sum by (job) (rate(gateway_requests_total[5m]))
```
- Type: Pie Chart
- Shows which service handles most traffic

### Panel 3: Memory Usage (if available)
```promql
container_memory_usage_bytes
```
- Type: Graph
- System memory per container

### Panel 4: Network I/O
```promql
rate(container_network_receive_bytes_total[5m])
```
- Type: Graph
- Network throughput

---

## Alert Status Dashboard

### Panel 1: Active Alerts
```promql
ALERTS{alertstate="firing"}
```
- Type: Table
- Shows currently firing alerts

### Panel 2: Alert History
```promql
count by (alertname) (ALERTS)
```
- Type: Bar Chart
- Alert frequency

### Panel 3: Low Stock Timeline
```promql
inventory_low_stock_items > 0
```
- Type: Graph
- When low stock occurs

---

## Business Intelligence Dashboard

### Panel 1: Sales Growth
```promql
rate(sales_revenue_total[1h]) / rate(sales_revenue_total[24h:1h] offset 24h)
```
- Type: Gauge
- Shows day-over-day growth

### Panel 2: Product Popularity
```promql
topk(10, sum by (product) (sales_quantity))
```
- Type: Table
- Top-selling products

### Panel 3: Inventory Utilization
```promql
inventory_items_total / (product_count * 100)
```
- Type: Gauge
- Items per product average

### Panel 4: Revenue per Product
```promql
sales_revenue_total / product_count
```
- Type: Stat
- Average revenue per product

---

## Creating These Dashboards

1. In Grafana, go to **+** → **Dashboard**
2. Click **Add Panel**
3. Select **Prometheus** as datasource
4. Copy PromQL query from above
5. Choose visualization:
   - **Time Series**: For metrics over time
   - **Stat**: For current values
   - **Gauge**: For ranges (0-100)
   - **Table**: For detailed data
   - **Pie Chart**: For distribution
6. Save panel
7. Save dashboard with a name

## Advanced Queries

### Query all services at once
```promql
{__name__=~".*_requests_total"} > 0
```

### Calculate success rate
```promql
(1 - (sum(rate(gateway_errors_total[5m])) / sum(rate(gateway_requests_total[5m])))) * 100
```

### Find slowest endpoints
```promql
topk(5, histogram_quantile(0.95, gateway_latency_ms_bucket) > 100)
```

### Compare metrics across jobs
```promql
{__name__=~".*_db_duration_ms", le="50"} / on(job) group_left() rate(product_requests_total[5m])
```

---

## Dashboard Refresh Rates

Recommended refresh intervals:
- **Real-time monitoring**: 5s or 10s
- **Business dashboards**: 30s or 1m
- **Historical analysis**: 5m or more

Set in dashboard settings: **Refresh every: _**

---

## Tips & Tricks

1. **Use variables** for dynamic filtering:
   - Dashboard → Settings → Variables
   - Create variable: `job` with query `label_values(up, job)`
   - Use in queries: `{job="$job"}`

2. **Set alert thresholds** as alert rules in Prometheus
3. **Use dashboard templates** for consistent styling
4. **Export dashboards** as JSON for backup/sharing
5. **Use annotations** to mark important events

---

See **MONITORING_GUIDE.md** for more details!
