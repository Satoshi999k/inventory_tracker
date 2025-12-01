# Docker Commands for Monitoring

## Quick Command Reference

### Starting & Stopping

```bash
# Start monitoring stack
docker-compose -f docker-compose.monitoring.yml up -d

# Start monitoring + main services together
docker-compose -f docker-compose.yml -f docker-compose.monitoring.yml up -d

# Stop monitoring stack
docker-compose -f docker-compose.monitoring.yml down

# Stop all and remove volumes (WARNING: deletes data)
docker-compose -f docker-compose.monitoring.yml down -v

# View logs
docker-compose -f docker-compose.monitoring.yml logs -f

# View specific service logs
docker-compose -f docker-compose.monitoring.yml logs -f prometheus
docker-compose -f docker-compose.monitoring.yml logs -f grafana
docker-compose -f docker-compose.monitoring.yml logs -f alertmanager
```

### Container Management

```bash
# List running monitoring containers
docker-compose -f docker-compose.monitoring.yml ps

# Restart specific service
docker-compose -f docker-compose.monitoring.yml restart prometheus
docker-compose -f docker-compose.monitoring.yml restart grafana

# View container details
docker inspect inventorytracker-prometheus
docker inspect inventorytracker-grafana

# Execute command in container
docker exec inventorytracker-prometheus cat /etc/prometheus/prometheus.yml
docker exec inventorytracker-grafana grafana-cli admin list-users
```

### Container Logs

```bash
# Prometheus logs (last 100 lines)
docker logs inventorytracker-prometheus --tail 100

# Grafana logs (live)
docker logs -f inventorytracker-grafana

# AlertManager logs
docker logs inventorytracker-alertmanager

# Node Exporter logs
docker logs inventorytracker-node-exporter

# All monitoring logs at once
docker-compose -f docker-compose.monitoring.yml logs
```

### Volume Management

```bash
# List monitoring volumes
docker volume ls | grep inventorytracker

# Inspect prometheus data volume
docker volume inspect inventorytracker_prometheus_data

# Check volume size
docker system df --verbose

# Backup Prometheus data
docker run --rm \
  -v inventorytracker_prometheus_data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar czf /backup/prometheus-backup.tar.gz -C / data

# Restore Prometheus data
docker run --rm \
  -v inventorytracker_prometheus_data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar xzf /backup/prometheus-backup.tar.gz -C /
```

### Network Management

```bash
# List networks
docker network ls | grep inventorytracker

# Inspect monitoring network
docker network inspect inventorytracker_inventorynet

# Check service DNS resolution
docker exec inventorytracker-prometheus nslookup prometheus
docker exec inventorytracker-grafana nslookup grafana
```

### Configuration Updates

```bash
# Edit Prometheus config
nano monitoring/prometheus.yml

# Reload Prometheus config (without restarting)
docker exec inventorytracker-prometheus \
  kill -HUP 1

# Edit Alert rules
nano monitoring/rules.yml

# Reload rules
docker exec inventorytracker-prometheus \
  kill -HUP 1

# Edit AlertManager config
nano monitoring/alertmanager.yml

# Reload AlertManager
docker-compose -f docker-compose.monitoring.yml restart alertmanager

# Verify config files
docker exec inventorytracker-prometheus \
  /bin/prometheus --config.file=/etc/prometheus/prometheus.yml --dry-run
```

### Health Checks

```bash
# Check Prometheus health
curl -s http://localhost:9090/-/healthy

# Check Prometheus ready
curl -s http://localhost:9090/-/ready

# Check if Prometheus is collecting metrics
curl -s http://localhost:9090/api/v1/targets | jq '.data.activeTargets'

# Check Grafana health
curl -s http://localhost:3001/api/health

# Check AlertManager health
curl -s http://localhost:9093/-/healthy

# Test metrics endpoint of service
curl http://localhost:8001/metrics
```

### Metric Inspection

```bash
# Query Prometheus directly
curl 'http://localhost:9090/api/v1/query?query=up'

# Get specific metric
curl 'http://localhost:9090/api/v1/query?query=http_requests_total'

# Get metric with time range
curl 'http://localhost:9090/api/v1/query_range?query=rate(http_requests_total[5m])&start=1701425400&end=1701429000&step=60'

# Get targets status
curl -s http://localhost:9090/api/v1/targets | jq

# Get alert status
curl -s http://localhost:9090/api/v1/alerts | jq
```

### Database Inspection

```bash
# Interactive Prometheus shell
docker exec -it inventorytracker-prometheus sh

# View Prometheus data directory
docker exec inventorytracker-prometheus ls -lh /prometheus

# Check Prometheus configuration
docker exec inventorytracker-prometheus cat /etc/prometheus/prometheus.yml

# Check alert rules
docker exec inventorytracker-prometheus cat /etc/prometheus/rules.yml

# View Grafana configuration
docker exec inventorytracker-grafana cat /etc/grafana/grafana.ini

# List Grafana datasources
docker exec inventorytracker-grafana grafana-cli admin list-users
```

### Performance Monitoring

```bash
# Check container resource usage
docker stats inventorytracker-prometheus
docker stats inventorytracker-grafana

# View detailed resource info
docker inspect inventorytracker-prometheus \
  --format='Memory: {{json .HostConfig.Memory}}, CPUs: {{json .HostConfig.CpuPeriod}}'

# Monitor in real-time
docker stats --no-stream=false

# Check disk usage
docker system df

# Cleanup unused resources
docker system prune

# Cleanup monitoring stack resources
docker-compose -f docker-compose.monitoring.yml down
docker volume prune
docker image prune
```

### Backup & Restore

```bash
# Backup all monitoring volumes
docker-compose -f docker-compose.monitoring.yml exec prometheus \
  tar czf /prometheus/backup.tar.gz /prometheus

# Backup Grafana dashboards
docker exec inventorytracker-grafana \
  grafana-cli admin export-dashboard > grafana-dashboard-backup.json

# Backup configuration files
tar czf monitoring-config-backup.tar.gz monitoring/

# Full backup script
#!/bin/bash
mkdir -p backups
docker-compose -f docker-compose.monitoring.yml exec prometheus \
  tar czf /prometheus/backup-$(date +%Y%m%d).tar.gz /prometheus
docker-compose -f docker-compose.monitoring.yml exec grafana \
  grafana-cli admin export-dashboard > backups/grafana-$(date +%Y%m%d).json
tar czf backups/config-$(date +%Y%m%d).tar.gz monitoring/
echo "Backup completed"
```

### Development & Debugging

```bash
# Build custom image (if modifying Dockerfile)
docker build -t inventorytracker-prometheus:latest monitoring/prometheus

# Run Prometheus in debug mode
docker run -it --rm \
  -p 9090:9090 \
  -v $(pwd)/monitoring/prometheus.yml:/etc/prometheus/prometheus.yml \
  prom/prometheus:latest \
  --log.level=debug

# Run Grafana in debug mode
docker run -it --rm \
  -p 3001:3000 \
  -e GF_LOG_LEVEL=debug \
  grafana/grafana:latest

# Check environment variables in container
docker exec inventorytracker-prometheus env | sort

# Interactive shell in container
docker exec -it inventorytracker-prometheus /bin/sh

# Copy files to/from container
docker cp prometheus.yml inventorytracker-prometheus:/etc/prometheus/
docker cp inventorytracker-prometheus:/etc/prometheus/prometheus.yml ./

# View complete container configuration
docker inspect inventorytracker-prometheus --format='{{json . }}' | jq
```

### Scaling & Performance

```bash
# Increase Prometheus memory limit
# Edit docker-compose.monitoring.yml and set:
# mem_limit: 2g
# Then recreate container:
docker-compose -f docker-compose.monitoring.yml up -d --force-recreate prometheus

# Configure resource limits
# In docker-compose.monitoring.yml:
# deploy:
#   resources:
#     limits:
#       cpus: '1'
#       memory: 512M
#     reservations:
#       cpus: '0.5'
#       memory: 256M

# View running containers performance
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"
```

### Cleanup

```bash
# Remove stopped containers
docker container prune

# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# Remove unused networks
docker network prune

# Full system cleanup (WARNING: removes untagged images)
docker system prune -a --volumes

# Remove specific monitoring container
docker rm -f inventorytracker-prometheus

# Remove monitoring volumes
docker volume rm inventorytracker_prometheus_data
docker volume rm inventorytracker_grafana_data
docker volume rm inventorytracker_alertmanager_data
```

### Troubleshooting Commands

```bash
# Check port availability
netstat -an | grep 9090
netstat -an | grep 3001
netstat -an | grep 9093

# On Linux
lsof -i :9090
lsof -i :3001
lsof -i :9093

# Check DNS resolution
docker exec inventorytracker-prometheus nslookup prometheus
docker exec inventorytracker-grafana nslookup prometheus

# Test connectivity between containers
docker exec inventorytracker-grafana curl -s http://prometheus:9090/-/healthy

# View network bridge
docker network inspect inventorytracker_inventorynet

# Check if ports are open
curl http://localhost:9090
curl http://localhost:3001
curl http://localhost:9093

# Docker daemon logs (Linux)
journalctl -u docker.service

# Docker daemon logs (Windows)
# Event Viewer → Windows Logs → Application
```

### Advanced Monitoring

```bash
# Export Prometheus metrics in JSON
curl -s 'http://localhost:9090/api/v1/query?query=up' | jq

# Get disk usage statistics
docker exec inventorytracker-prometheus du -sh /prometheus

# Monitor Prometheus itself
curl -s http://localhost:9090/metrics | head -20

# Check Grafana version
curl -s http://localhost:3001/api/health | jq '.version'

# List Grafana users
docker exec inventorytracker-grafana grafana-cli admin list-users

# Create Grafana user
docker exec inventorytracker-grafana grafana-cli admin create-user username password email true

# Get Prometheus version
docker exec inventorytracker-prometheus /bin/prometheus --version
```

## Useful Aliases

Add to your `.bashrc` or `.bash_profile`:

```bash
# Monitoring shortcuts
alias mon-up='docker-compose -f docker-compose.monitoring.yml up -d'
alias mon-down='docker-compose -f docker-compose.monitoring.yml down'
alias mon-logs='docker-compose -f docker-compose.monitoring.yml logs -f'
alias mon-ps='docker-compose -f docker-compose.monitoring.yml ps'
alias prom-curl='curl -s http://localhost:9090'
alias graf-curl='curl -s http://localhost:3001'
alias alert-curl='curl -s http://localhost:9093'

# View monitoring health
alias mon-health='echo "Prometheus: $(curl -s http://localhost:9090/-/healthy)" && echo "Grafana: $(curl -s http://localhost:3001/api/health)" && echo "AlertManager: $(curl -s http://localhost:9093/-/healthy)"'
```

## Common Workflows

### Daily Check
```bash
# Is everything running?
docker-compose -f docker-compose.monitoring.yml ps

# Are metrics being collected?
curl -s http://localhost:9090/api/v1/targets | jq '.data.activeTargets | length'

# Any active alerts?
curl -s http://localhost:9090/api/v1/alerts | jq '.data.alerts'
```

### Adding New Service Metrics
```bash
# 1. Edit prometheus.yml
nano monitoring/prometheus.yml

# 2. Add job_name section:
# - job_name: 'new-service'
#   static_configs:
#     - targets: ['new-service:8004']

# 3. Reload Prometheus
docker exec inventorytracker-prometheus kill -HUP 1

# 4. Verify in UI
curl http://localhost:9090/api/v1/targets
```

### Update Alert Rules
```bash
# 1. Edit rules file
nano monitoring/rules.yml

# 2. Validate syntax
docker exec inventorytracker-prometheus \
  /bin/prometheus --config.file=/etc/prometheus/prometheus.yml --dry-run

# 3. Reload
docker exec inventorytracker-prometheus kill -HUP 1

# 4. Check alerts
curl http://localhost:9090/api/v1/rules
```
