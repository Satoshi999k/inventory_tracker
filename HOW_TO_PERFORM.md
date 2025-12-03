# How to Perform - Prometheus & Grafana Practical Guide

## 📋 Table of Contents
1. [Start the Monitoring Stack](#start)
2. [Access Prometheus](#prometheus)
3. [Access Grafana](#grafana)
4. [Write PromQL Queries](#promql)
5. [Create Dashboards](#dashboards)
6. [View Alerts](#alerts)
7. [Troubleshooting](#troubleshooting)

---

## 🚀 Start the Monitoring Stack {#start}

### Method 1: Docker Command Line (Recommended)

**Step 1: Open Terminal/Command Prompt**
```bash
cd d:\xampp\htdocs\inventorytracker
```

**Step 2: Start the Stack**
```bash
docker-compose -f docker-compose.monitoring.yml up -d
```

**Step 3: Wait for Services to Start**
- Services start in background
- Wait 10-15 seconds for full initialization

**Step 4: Verify Services Are Running**
```bash
docker-compose -f docker-compose.monitoring.yml ps
```

You should see:
```
CONTAINER ID   IMAGE                   STATUS
xxx            prom/prometheus         Up 2 minutes
xxx            grafana/grafana         Up 2 minutes
xxx            prom/alertmanager       Up 2 minutes
```

### Method 2: Windows Batch File

**Step 1: Locate the File**
- Go to: `d:\xampp\htdocs\inventorytracker`
- Find: `START_MONITORING.bat`

**Step 2: Double-Click the File**
- File runs in Command Prompt window
- Shows services starting
- Automatically opens browser to Grafana (might need manual open)

**Step 3: Wait for Initialization**
- Terminal shows "Services Starting..."
- Wait for "Services Ready!" message

### Verify Services Are Running

**Check in Terminal:**
```bash
# See all running containers
docker ps

# See just monitoring containers
docker ps | grep -E "prometheus|grafana|alertmanager"
```

**Check with curl:**
```bash
# Prometheus is ready
curl http://localhost:9090

# Grafana is ready
curl http://localhost:3001

# AlertManager is ready
curl http://localhost:9093
```

---

## 🔍 Access Prometheus {#prometheus}

### Opening Prometheus

**Step 1: Open Browser**
- Go to: `http://localhost:9090`

**Step 2: You Should See**
- "Prometheus" logo at top
- Search box in middle
- Tabs: "Graph", "Console", "Alerts"

### Running Your First Query

**Step 1: Click on "Graph" Tab** (if not already selected)

**Step 2: Click in the Search Box**

**Step 3: Type a Query**
```promql
up
```

**Step 4: Press Enter or Click "Execute"**

**Step 5: View Results**
- Shows 5 metrics (one for each service)
- Value = 1 means service is UP
- Value = 0 means service is DOWN

### Example Queries to Try

**Query 1: Check All Services Health**
```promql
up
```
- Shows status of all services
- Should see 5 results (all value=1)

**Query 2: API Request Rate**
```promql
rate(gateway_requests_total[5m])
```
- Shows requests per second
- [5m] = average over last 5 minutes
- Click "Graph" tab to see chart

**Query 3: Error Rate Percentage**
```promql
(sum(rate(gateway_errors_total[5m])) / sum(rate(gateway_requests_total[5m]))) * 100
```
- Shows error percentage
- Divide: errors / total requests
- Multiply by 100 for percentage

**Query 4: Database Latency (95th percentile)**
```promql
histogram_quantile(0.95, gateway_latency_ms_bucket)
```
- Shows 95th percentile latency
- histogram_quantile = calculate percentile
- gateway_latency_ms_bucket = latency histogram

**Query 5: Inventory Status**
```promql
inventory_items_total
```
- Shows current inventory count
- Should return 283

**Query 6: Low Stock Items**
```promql
inventory_low_stock_items
```
- Shows items below threshold
- Alert condition if > 0

### Understanding the Interface

**Top Section: Query**
- Type your PromQL query here
- Click "Execute" button to run
- Press Enter as shortcut

**Left Sidebar:**
- "Alerts" - View configured alerts
- "Status" - System status & targets

**Graph Tab:**
- Shows data as line chart
- Blue line = metric value over time
- Can hover to see exact values

**Console Tab:**
- Shows raw data in table format
- More detailed view

### Advanced: Graph Options

**Time Range**
- Top right: "5m" (5 minutes), "1h", "1d"
- Click to change history displayed
- Longer = older data

**Refresh Rate**
- Top right: "Off", "5s", "10s"
- Auto-refresh the query results

---

## 📊 Access Grafana {#grafana}

### Opening Grafana

**Step 1: Open Browser**
- Go to: `http://localhost:3001`

**Step 2: Login Screen**
- You should see login form
- Default credentials:
  - Email: `admin`
  - Password: `admin123`

**Step 3: Click Login Button**

**Step 4: Skip Password Change** (if prompted)
- Grafana may ask to change password
- Click "Skip" if you don't want to now

### View Pre-built Dashboard

**Step 1: After Login, You're on Home Page**

**Step 2: Find Dashboard**
- Look for: "Inventory Tracker - System Monitoring"
- OR click hamburger menu (☰) → Dashboards → Browse

**Step 3: Click Dashboard Name**
- Opens pre-built dashboard
- Shows 4 panels with metrics

**Step 4: Explore the Panels**
- **Panel 1**: API Gateway Request Rate (line chart)
- **Panel 2**: Total Inventory Items (gauge)
- **Panel 3**: Low Stock Items (gauge - red if > 0)
- **Panel 4**: Total Sales Transactions (number)

### Understanding the Dashboard

**Top Right Controls:**
- **Refresh icon** (↻) - Manually refresh data
- **Time range** (e.g., "Last hour") - Change history window
- **Auto-refresh** (off by default) - Set 5s, 10s, 30s, etc.

**Panel Controls** (hover over any panel):
- **Title** - Click to edit
- **Menu (⋮)** - Edit, duplicate, delete
- **Refresh** - Refresh this panel only
- **Inspect** - See raw data

**Legend** (below charts):
- Shows metric names
- Click to hide/show lines
- Right-click for options

---

## ✍️ Write PromQL Queries {#promql}

### Where to Write Queries

**Option 1: Prometheus UI**
- http://localhost:9090
- Top search box
- Good for testing/learning

**Option 2: Grafana Dashboard**
- When creating new panel
- Click "Prometheus" datasource
- Write in query box

### Basic Query Structure

**Format:**
```promql
metric_name{label="value"}
```

**Examples:**
```promql
# Just the metric
gateway_requests_total

# With label filter
gateway_requests_total{method="GET"}

# Multiple filters
gateway_requests_total{method="GET", service="gateway"}
```

### Common Query Patterns

**Pattern 1: Rate of Change**
```promql
rate(metric_name[5m])
```
- Changes per second
- [5m] = over last 5 minutes
- Use for: request rates, error rates

**Pattern 2: Increase Over Time**
```promql
increase(metric_name[1h])
```
- Total increase
- [1h] = over last 1 hour
- Use for: total revenue per hour, total sales per day

**Pattern 3: Percentile Calculation**
```promql
histogram_quantile(0.95, metric_bucket)
```
- 0.95 = 95th percentile (or 0.50 for median)
- Use for: latency, response times

**Pattern 4: Sum Aggregation**
```promql
sum(metric_name)
```
- Add all values together
- Use for: total requests, total errors

**Pattern 5: Division/Math**
```promql
(numerator_metric / denominator_metric) * 100
```
- Calculate percentage, ratios
- Use for: error rate %, success rate %

### Real-World Query Examples

**Example 1: Requests Per Second**
```promql
sum(rate(gateway_requests_total[5m]))
```
**What it does:**
- `gateway_requests_total` = total requests counter
- `rate(...[5m])` = convert to per-second rate over 5 min
- `sum(...)` = add all request rates together

**Example 2: Error Rate %**
```promql
(sum(rate(gateway_errors_total[5m])) / sum(rate(gateway_requests_total[5m]))) * 100
```
**What it does:**
- Errors per second / Total requests per second
- Multiply by 100 for percentage
- Shows: what % of requests are errors

**Example 3: Average Request Latency**
```promql
avg(rate(gateway_latency_ms_sum[5m]) / rate(gateway_latency_ms_count[5m]))
```
**What it does:**
- Sum of latencies / Count of requests
- Shows average latency in milliseconds

**Example 4: Inventory Value**
```promql
inventory_items_total
```
**What it does:**
- Directly query the metric
- Shows: current inventory item count

**Example 5: Top Services by Request Volume**
```promql
topk(5, sum by (job) (rate(gateway_requests_total[5m])))
```
**What it does:**
- Group by job/service
- Get top 5 by request rate
- Shows which services handle most traffic

### Debugging Queries

**If query returns "No data":**
1. Check metric name is correct
2. Try simpler query: just `metric_name`
3. Go to Prometheus → Status → Targets
4. Check which metrics are available

**If query returns error:**
1. Check syntax (especially brackets/parentheses)
2. Simplify: remove aggregations first
3. Test in Prometheus first (easier to debug)

---

## 📈 Create Dashboards {#dashboards}

### Create a New Dashboard

**Step 1: Open Grafana**
- http://localhost:3001

**Step 2: Click "+" in Left Sidebar**
- Select "Dashboard"

**Step 3: Click "Add Panel"**

**Step 4: You're in Panel Editor**

### Add First Panel

**Step 1: Select Datasource**
- Click "Prometheus" dropdown (usually pre-selected)
- Select "Prometheus"

**Step 2: Write PromQL Query**
```promql
rate(gateway_requests_total[5m])
```

**Step 3: Choose Visualization**
- Click "Visualization" button (top right)
- Options: Graph, Stat, Gauge, Table, Pie Chart
- Select: "Time series" (default, good for trends)

**Step 4: Set Panel Title**
- Click "Panel title" textbox
- Type: "API Request Rate"

**Step 5: Apply**
- Click "Apply" button (top right)

**Step 6: Save Dashboard**
- Click "Save" (top right)
- Name: "My First Dashboard"
- Click "Save"

### Add More Panels to Dashboard

**Step 1: Click "Add Panel"** (again)

**Step 2: Repeat Steps 1-5** with different query:
```promql
histogram_quantile(0.95, gateway_latency_ms_bucket)
```
- Title: "API Latency (P95)"
- Visualization: Time series

**Step 3: Add Third Panel**
Query:
```promql
inventory_low_stock_items
```
- Title: "Low Stock Items"
- Visualization: Gauge (shows current value)

**Step 4: Save Dashboard**
- Changes auto-save
- Or click "Save" again

### Dashboard Layout

**Moving Panels:**
- Click panel title
- Drag to new position
- Release to drop

**Resizing Panels:**
- Bottom-right corner of panel has resize handle
- Drag to resize

**Editing Panel:**
- Click panel title → Edit
- Change query or visualization
- Click Apply

### Popular Visualizations

| Visualization | Use For | Example |
|---------------|---------|---------|
| **Time Series** | Trends over time | Request rate, latency |
| **Stat** | Current value | Total inventory items |
| **Gauge** | Ranges (0-100) | Low stock items alert |
| **Table** | Detailed data | List of products |
| **Pie Chart** | Distribution | % of each service |
| **Bar Chart** | Comparisons | Requests by method |

---

## 🔔 View Alerts {#alerts}

### View Alerts in Prometheus

**Step 1: Open Prometheus**
- http://localhost:9090

**Step 2: Click "Alerts" Tab** (top menu)

**Step 3: You See:**
- List of alert rules
- Current status: FIRING, PENDING, or inactive
- Examples: LowStockAlert, HighErrorRate

**Click on Alert:**
- See condition: `inventory_low_stock_items > 0`
- See expression: the PromQL query
- See current state

### View Alerts in AlertManager

**Step 1: Open AlertManager**
- http://localhost:9093

**Step 2: You See:**
- Active alerts section
- Alert groups
- Each alert with: name, labels, firing since

**Step 3: Click Alert**
- See full details
- See routing (where it would be sent)
- See labels

### Understanding Alerts

**Alert States:**
- **Inactive** - Condition not met, all good
- **Pending** - Condition met, waiting for duration (e.g., 5 min)
- **FIRING** - Alert is active, action needed ⚠️

**Alert Example: Low Stock**
```
Alert Name: LowStockAlert
Condition: inventory_low_stock_items > 0
For: 5 minutes
Status: FIRING ⚠️

Message: "Low stock items detected"
```

### How Alerts Work

```
1. Prometheus runs: inventory_low_stock_items > 0
2. If TRUE:
   - Goes to PENDING state
   - Waits 5 minutes
3. If still TRUE after 5 min:
   - Goes to FIRING state
4. AlertManager receives:
   - Routes to configured receiver (webhook, Slack, etc.)
5. When condition becomes FALSE:
   - Alert resolves
```

---

## 🔧 Troubleshooting {#troubleshooting}

### Problem: Can't Access Prometheus (http://localhost:9090)

**Symptom:** Connection refused

**Solution:**
1. Check container is running:
   ```bash
   docker ps | grep prometheus
   ```

2. If not running, check logs:
   ```bash
   docker-compose -f docker-compose.monitoring.yml logs prometheus
   ```

3. Restart:
   ```bash
   docker-compose -f docker-compose.monitoring.yml restart prometheus
   ```

### Problem: No Data in Grafana

**Symptom:** Panels show "No data"

**Solution:**
1. Check Prometheus has data:
   - Go to http://localhost:9090
   - Run query: `up`
   - Should show 5 results

2. Check datasource connection:
   - Grafana → Settings (gear) → Data Sources
   - Click "Prometheus"
   - Click "Test" button
   - Should see "Data source is working"

3. Try different time range:
   - Click time picker (top right)
   - Select "Last hour" or "Last 6 hours"

### Problem: Metrics Not Appearing in Prometheus

**Symptom:** Query returns "No data"

**Solution:**
1. Check metric name:
   ```bash
   # Verify endpoint is working
   curl http://localhost:8000/metrics
   curl http://localhost:8001/metrics
   curl http://localhost:8002/metrics
   curl http://localhost:8003/metrics
   ```

2. Check Prometheus is scraping:
   - Go to http://localhost:9090/targets
   - Look for red/orange targets (DOWN)
   - Check configuration in `monitoring/prometheus.yml`

3. Wait for first scrape:
   - Prometheus scrapes every 10 seconds
   - Give it 30 seconds after starting
   - Then try query again

### Problem: Can't Login to Grafana

**Symptom:** Wrong password error

**Solution:**
1. Default credentials:
   - Username: `admin`
   - Password: `admin123`

2. If still wrong, reset:
   ```bash
   # Stop container
   docker-compose -f docker-compose.monitoring.yml stop grafana
   
   # Remove volume (erases data)
   docker volume rm inventory_tracker_grafana_data
   
   # Restart
   docker-compose -f docker-compose.monitoring.yml up -d
   ```
   - Now login with: admin / admin123

### Problem: Dashboard Not Showing Pre-built Dashboard

**Symptom:** Inventory Tracker dashboard not listed

**Solution:**
1. Check if Prometheus datasource is configured:
   - Settings → Data Sources
   - Click "Prometheus"
   - Click "Save & Test"

2. Create dashboard manually:
   - See "Create Dashboards" section above

3. Import pre-built:
   - Click "+" → Import
   - Paste JSON from: `monitoring/grafana/provisioning/dashboards/inventory-dashboard.json`

### Problem: Alerts Not Firing

**Symptom:** Alert stays INACTIVE even when condition should be TRUE

**Solution:**
1. Check alert rule syntax:
   - Edit: `monitoring/alert_rules.yml`
   - Make sure PromQL is correct

2. Reload Prometheus:
   ```bash
   docker-compose -f docker-compose.monitoring.yml restart prometheus
   ```

3. Check condition manually:
   - Go to Prometheus
   - Run the alert condition query
   - Verify it returns data

4. Check duration:
   - Alert must be TRUE for full "for" duration
   - E.g., LowStockAlert: must be TRUE for 5 minutes

---

## 📞 Quick Reference

| Task | How To |
|------|--------|
| **Start Monitoring** | `docker-compose -f docker-compose.monitoring.yml up -d` |
| **Open Prometheus** | http://localhost:9090 |
| **Open Grafana** | http://localhost:3001 (admin/admin123) |
| **Open AlertManager** | http://localhost:9093 |
| **Check Services Running** | `docker-compose -f docker-compose.monitoring.yml ps` |
| **View Logs** | `docker-compose -f docker-compose.monitoring.yml logs prometheus` |
| **Stop Everything** | `docker-compose -f docker-compose.monitoring.yml down` |
| **Restart Service** | `docker-compose -f docker-compose.monitoring.yml restart prometheus` |

---

**You now have a complete practical guide to use Prometheus and Grafana!** 🚀
