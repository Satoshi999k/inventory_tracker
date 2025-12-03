# Quick Step-by-Step Visual Guide

## 🚀 STEP 1: START THE MONITORING STACK (1 minute)

```
┌─────────────────────────────────────────┐
│ Open Command Prompt / Terminal          │
│ (Windows: Press Win+R, type "cmd")      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│ $ cd d:\xampp\htdocs\inventorytracker   │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│ $ docker-compose -f                     │
│   docker-compose.monitoring.yml up -d   │
└─────────────────────────────────────────┘
                    ↓
              [Wait 15 seconds]
                    ↓
                ✅ DONE!
```

**Result:** 3 Docker containers started in background
- Prometheus (9090)
- Grafana (3001)
- AlertManager (9093)

---

## 📊 STEP 2: ACCESS GRAFANA DASHBOARD (5 minutes)

### Open Browser
```
Click here: http://localhost:3001
```

### You See Login Screen
```
┌─────────────────────────────────┐
│  GRAFANA LOGIN                  │
├─────────────────────────────────┤
│                                 │
│  Email: [admin_______________]  │
│                                 │
│  Password: [admin123__________]  │
│                                 │
│  ┌─────────────┐                │
│  │ Sign in     │                │
│  └─────────────┘                │
└─────────────────────────────────┘
```

### Enter Credentials
- **Email/Username:** `admin`
- **Password:** `admin123`
- Click **Sign in**

### View Pre-built Dashboard
```
You see menu on left:
  ☰ Grafana
     Home
     Dashboards ← Click here
     Explore
     Alerts
     
Click Dashboards → Browse → 
"Inventory Tracker - System Monitoring"
```

### You Now See Dashboard with 4 Panels:
```
┌────────────────────────────────────┐
│  Inventory Tracker Dashboard       │
├────────────────────────────────────┤
│                                    │
│  ┌──────────────┐  ┌────────────┐  │
│  │ API Gateway  │  │ Inventory  │  │
│  │ Request Rate │  │ Items      │  │
│  │              │  │ 283        │  │
│  │  📈 [chart] │  │  🎯 [dial] │  │
│  └──────────────┘  └────────────┘  │
│                                    │
│  ┌──────────────┐  ┌────────────┐  │
│  │ Low Stock    │  │ Sales      │  │
│  │ Items        │  │ Transactions│  │
│  │ 1 ⚠️         │  │ 1          │  │
│  │ 🎯 [dial]    │  │ 📊 [stat] │  │
│  └──────────────┘  └────────────┘  │
│                                    │
└────────────────────────────────────┘
```

**✅ SUCCESS!** You're now viewing real-time metrics!

---

## 🔍 STEP 3: RUN PROMETHEUS QUERIES (optional, 5 minutes)

### Open Prometheus
```
http://localhost:9090
```

### You See Query Page
```
┌────────────────────────────────────┐
│ Prometheus                         │
├────────────────────────────────────┤
│                                    │
│ Query: [search box________________]│
│        [Execute] [Clear]           │
│                                    │
│ Instant ◉  Range ( )               │
│                                    │
│ Results:                           │
│ [Graph/Console tabs]               │
│                                    │
└────────────────────────────────────┘
```

### Try Your First Query
1. Click in search box
2. Type: `up`
3. Press Enter
4. See 5 metrics (all value=1, meaning all services UP)

### Try Another Query
Type: `inventory_items_total`
Result: 283 (current inventory)

### Try Rate Query
```promql
rate(gateway_requests_total[5m])
```
Result: Requests per second (with graph)

---

## 📈 STEP 4: CREATE CUSTOM DASHBOARD (optional, 10 minutes)

### Go to Grafana
```
http://localhost:3001
```

### Create New Dashboard
```
Left sidebar:
  +  ← Click this
     Dashboard
     
Then: Add Panel
```

### Add First Panel
```
1. Datasource: Prometheus ✓
2. Query: rate(gateway_requests_total[5m])
3. Visualization: Time series (default)
4. Title: API Request Rate
5. Click Apply
```

### Add Second Panel
```
1. Click "Add Panel"
2. Datasource: Prometheus
3. Query: inventory_items_total
4. Visualization: Stat
5. Title: Total Inventory
6. Click Apply
```

### Save Dashboard
```
Top right: Save button
Name: "My Dashboard"
Click Save
```

**✅ SUCCESS!** Custom dashboard created!

---

## 🔔 STEP 5: VIEW ALERTS (optional, 3 minutes)

### In Prometheus
```
http://localhost:9090/alerts
```

Shows:
```
┌──────────────────────────────┐
│ ALERTS                       │
├──────────────────────────────┤
│ LowStockAlert                │
│ Status: INACTIVE (green)     │
│ Condition: items > threshold │
│                              │
│ NoSalesAlert                 │
│ Status: INACTIVE (green)     │
│ Condition: no sales > 1h    │
│                              │
│ HighDBLatency                │
│ Status: INACTIVE (green)     │
│ Condition: latency > 500ms   │
│                              │
│ HighErrorRate                │
│ Status: INACTIVE (green)     │
│ Condition: error rate > 5%   │
│                              │
│ ServiceDown                  │
│ Status: INACTIVE (green)     │
│ Condition: service down > 2m │
└──────────────────────────────┘
```

Green = Alert OK
Red = Alert FIRING (something wrong)

---

## ✨ QUICK REFERENCE QUERIES

Copy & paste these into Prometheus:

### System Health
```promql
# Which services are running?
up

# API request rate
rate(gateway_requests_total[5m])

# Error percentage
(sum(rate(gateway_errors_total[5m])) / sum(rate(gateway_requests_total[5m]))) * 100

# API response time
histogram_quantile(0.95, gateway_latency_ms_bucket)
```

### Inventory
```promql
# Total items
inventory_items_total

# Low stock items
inventory_low_stock_items

# Database speed
histogram_quantile(0.95, rate(inventory_db_duration_ms_bucket[5m]))
```

### Sales
```promql
# Total transactions
sales_transactions_total

# Total revenue
sales_revenue_total

# Transactions per hour
rate(sales_transactions_total[1h])
```

### Products
```promql
# Total products
product_count

# Product requests
rate(product_requests_total[5m])
```

---

## 🆘 QUICK TROUBLESHOOT

### Services Won't Start
```bash
# Check Docker is running
docker --version

# Check if containers exist
docker ps

# Remove and restart
docker-compose -f docker-compose.monitoring.yml down
docker-compose -f docker-compose.monitoring.yml up -d
```

### Can't Access Grafana
```
Wait 30 seconds, then try:
http://localhost:3001

If still no:
1. Open new terminal
2. docker-compose logs grafana
3. Look for errors
```

### No Data in Grafana
```
1. Go to: http://localhost:9090
2. Run query: up
3. Should see 5 results

If no results:
- Services not running (see: STEP 1)
- Wait 1 minute for metrics to collect
```

### Wrong Password
```
Default:
  Username: admin
  Password: admin123

If still wrong, reset Grafana:
docker-compose restart grafana

Then wait 10 seconds and retry.
```

---

## 🎯 COMMON TASKS

| What | How |
|------|-----|
| **Restart monitoring** | `docker-compose -f docker-compose.monitoring.yml restart` |
| **Stop monitoring** | `docker-compose -f docker-compose.monitoring.yml down` |
| **See logs** | `docker-compose -f docker-compose.monitoring.yml logs prometheus` |
| **Access Prometheus** | http://localhost:9090 |
| **Access Grafana** | http://localhost:3001 |
| **Access AlertManager** | http://localhost:9093 |
| **Check containers** | `docker-compose -f docker-compose.monitoring.yml ps` |

---

## 📱 Mobile View

All services support mobile:
- **Prometheus**: http://localhost:9090 (desktop best)
- **Grafana**: http://localhost:3001 (mobile friendly)
- **AlertManager**: http://localhost:9093 (desktop best)

---

## 🎓 WHAT YOU CAN NOW DO

✅ **Monitor in Real-Time**
- See API requests happening NOW
- Watch inventory levels change
- Track sales revenue

✅ **Create Custom Dashboards**
- Add any metric you want
- Use 40+ query examples
- Share with team

✅ **Get Alerts**
- Automatic low stock warning
- Service down notification
- Error rate alert

✅ **Analyze Data**
- Query with PromQL
- See trends over time
- Compare services

---

## 📚 NEXT STEPS

1. ✅ Start monitoring (STEP 1 above)
2. ✅ View dashboard (STEP 2 above)
3. ✅ Try queries (STEP 3 above)
4. ✅ Create dashboard (STEP 4 above)
5. ✅ Check alerts (STEP 5 above)
6. 📖 Read: GRAFANA_QUERIES.md (40+ examples)
7. 📖 Read: MONITORING_GUIDE.md (detailed reference)
8. 🎓 Configure alert webhooks (Slack/Email)

---

**You're ready to monitor your system!** 🚀

Start with STEP 1 above.
