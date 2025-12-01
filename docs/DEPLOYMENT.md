# Deployment Guide

## Prerequisites
- Docker & Docker Compose installed
- 4GB RAM minimum
- Port access: 3000, 5672, 6379, 8000-8003, 15672, 3306

## Quick Start

### 1. Clone/Extract Project
```bash
cd inventorytracker
```

### 2. Start All Services
```bash
docker-compose up -d
```

### 3. Verify Services
```bash
docker-compose ps
```

### 4. Access Services
| Service | URL | Purpose |
|---------|-----|---------|
| Admin Dashboard | http://localhost:3000 | Web UI |
| API Gateway | http://localhost:8000 | API requests |
| RabbitMQ Management | http://localhost:15672 | Message broker UI |
| MySQL | localhost:3306 | Database |
| Redis | localhost:6379 | Cache |

## Production Deployment

### Environment Configuration
Create `.env` file:
```bash
cp .env.example .env
```

Edit environment variables for production:
```env
# Database
DB_HOST=production-mysql-host
DB_PORT=3306
DB_NAME=inventory_db
DB_USER=prod_user
DB_PASSWORD=secure_password_here

# Cache
REDIS_HOST=production-redis-host
REDIS_PORT=6379

# Message Broker
RABBITMQ_HOST=production-rabbitmq-host
RABBITMQ_PORT=5672
RABBITMQ_USER=prod_user
RABBITMQ_PASSWORD=secure_password_here

# Application
APP_ENV=production
APP_DEBUG=false
```

### Docker Deployment

**Update docker-compose.yml for production:**
```yaml
# Use separate hosts for services
services:
  mysql:
    # ... add restart policy and health checks
    restart: unless-stopped
    
  redis:
    restart: unless-stopped
    
  rabbitmq:
    restart: unless-stopped
```

**Deploy:**
```bash
docker-compose -f docker-compose.yml up -d
```

### Backup & Recovery

**Backup Database:**
```bash
docker exec inventory_mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD inventory_db > backup.sql
```

**Restore Database:**
```bash
docker exec -i inventory_mysql mysql -u root -p$MYSQL_ROOT_PASSWORD inventory_db < backup.sql
```

### Monitoring

**View Logs:**
```bash
# All services
docker-compose logs

# Specific service
docker-compose logs -f product-catalog

# Last 100 lines
docker-compose logs --tail=100 inventory
```

**Check Health:**
```bash
curl http://localhost:8000/health
curl http://localhost:8001/health
curl http://localhost:8002/health
curl http://localhost:8003/health
```

### Scaling

To run multiple instances of a service:
```yaml
# In docker-compose.yml
product-catalog-1:
  # ... service definition

product-catalog-2:
  # ... service definition (different port)
```

### Troubleshooting

**Services not starting:**
```bash
docker-compose down
docker-compose up -d --build
```

**Database connection error:**
```bash
docker exec inventory_mysql mysql -u inventory_user -p inventory_db -e "SELECT 1"
```

**RabbitMQ connection error:**
```bash
docker exec inventory_rabbitmq rabbitmq-diagnostics -q ping
```

**Check service logs:**
```bash
docker logs inventory_mysql
docker logs inventory_rabbitmq
docker logs product_catalog_service
```

## Performance Optimization

1. **Database Indexing**
   - Already configured in init-db.sql
   - Review slow query logs regularly

2. **Redis Caching**
   - Implement product cache
   - Cache inventory lookups
   - Set TTL of 5 minutes

3. **Connection Pooling**
   - Configure MySQL connection pool
   - Adjust based on load

4. **Message Queue Tuning**
   - Increase RabbitMQ worker threads
   - Adjust queue sizes

## Security Hardening

1. **API Gateway**
   - Add authentication (JWT)
   - Implement rate limiting
   - Validate all inputs

2. **Database**
   - Change default passwords
   - Restrict MySQL user permissions
   - Enable encrypted connections

3. **Network**
   - Use Docker custom networks
   - Disable public port access
   - Use firewall rules

4. **Secrets Management**
   - Use Docker secrets
   - Don't commit passwords
   - Rotate credentials regularly

## Maintenance

**Daily:**
- Monitor service health
- Check error logs

**Weekly:**
- Backup database
- Review performance metrics

**Monthly:**
- Update dependencies
- Security patches
- Performance optimization

## Disaster Recovery

**RTO (Recovery Time Objective):** < 1 hour
**RPO (Recovery Point Objective):** < 1 hour

**Recovery Steps:**
1. Stop all services: `docker-compose down`
2. Restore database from backup
3. Restart services: `docker-compose up -d`
4. Verify health: `curl http://localhost:8000/health`
