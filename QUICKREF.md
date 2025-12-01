# Quick Reference Guide

## Service Ports

| Service | Port | Description |
|---------|------|-------------|
| Admin Dashboard | 3000 | Web interface |
| API Gateway | 8000 | REST API endpoint |
| Product Catalog | 8001 | Product microservice |
| Inventory Service | 8002 | Inventory microservice |
| Sales Service | 8003 | Sales microservice |
| MySQL | 3306 | Database server |
| Redis | 6379 | Cache server |
| RabbitMQ | 5672 | Message broker |
| RabbitMQ UI | 15672 | Management interface |

## Common Commands

### Starting Services
```bash
# Start all services
docker-compose up -d

# Start and rebuild
docker-compose up -d --build

# Start specific service
docker-compose up -d product-catalog
```

### Stopping Services
```bash
# Stop all services
docker-compose down

# Stop specific service
docker-compose stop inventory

# Stop and remove volumes
docker-compose down -v
```

### Viewing Logs
```bash
# All services
docker-compose logs

# Specific service (follow mode)
docker-compose logs -f sales

# Last 100 lines
docker-compose logs --tail=100 inventory

# Specific service for past 5 minutes
docker-compose logs --since 5m product-catalog
```

### Service Status
```bash
# List running services
docker-compose ps

# Check service health
docker ps --all --format "table {{.Names}}\t{{.Status}}"
```

## Database Commands

### Connect to MySQL
```bash
docker exec -it inventory_mysql mysql -u inventory_user -p inventory_db
```

### Backup Database
```bash
docker exec inventory_mysql mysqldump -u inventory_user -p inventory_db > backup.sql
```

### Restore Database
```bash
docker exec -i inventory_mysql mysql -u inventory_user -p inventory_db < backup.sql
```

### View Database Tables
```bash
docker exec -it inventory_mysql mysql -u inventory_user -p inventory_db -e "SHOW TABLES;"
```

### Query Data
```bash
docker exec -it inventory_mysql mysql -u inventory_user -p inventory_db -e "SELECT * FROM products;"
```

## API Quick Tests

### Health Check
```bash
curl http://localhost:8000/health
```

### Get All Products
```bash
curl http://localhost:8000/products | jq
```

### Get All Inventory
```bash
curl http://localhost:8000/inventory | jq
```

### Create Product
```bash
curl -X POST http://localhost:8000/products \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST-001","name":"Test Product","price":99.99}'
```

### Record Sale
```bash
curl -X POST http://localhost:8000/sales \
  -H "Content-Type: application/json" \
  -d '{"sku":"CPU-INTEL-I7","quantity":1}'
```

### Restock Item
```bash
curl -X POST http://localhost:8000/restock \
  -H "Content-Type: application/json" \
  -d '{"sku":"CPU-INTEL-I7","quantity":10}'
```

### Get Low Stock Alerts
```bash
curl http://localhost:8000/alerts | jq
```

## Troubleshooting

### Services Not Starting
```bash
# Check what's blocking ports
netstat -tulpn | grep 8000

# Check Docker daemon
docker info

# Clear Docker cache and rebuild
docker-compose down
docker system prune -a
docker-compose up -d --build
```

### Database Connection Error
```bash
# Check MySQL is running
docker exec inventory_mysql ping

# Check connectivity from a service
docker exec product_catalog_service curl mysql:3306

# View MySQL logs
docker logs inventory_mysql
```

### API Returns 503
```bash
# Check if services are running
docker-compose ps

# Check logs of the service
docker logs -f product_catalog_service

# Restart the service
docker-compose restart product-catalog
```

### Redis Connection Issue
```bash
# Check Redis
docker exec inventory_redis redis-cli ping

# View Redis logs
docker logs inventory_redis
```

### High Memory Usage
```bash
# Check container resource usage
docker stats

# Limit container memory in docker-compose.yml
# Add: mem_limit: 512m
```

## Environment Variables

All environment variables are defined in `docker-compose.yml` but can be overridden by `.env` file.

Key variables:
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` - Database
- `REDIS_HOST`, `REDIS_PORT` - Cache
- `RABBITMQ_HOST`, `RABBITMQ_PORT`, `RABBITMQ_USER`, `RABBITMQ_PASSWORD` - Message Broker
- `API_GATEWAY_PORT` - API Gateway port
- `APP_ENV` - Application environment (development/production)
- `LOG_LEVEL` - Logging level (debug/info/warning/error)

## Sample Data

The database is automatically populated with:
- 10 products (CPUs, GPUs, RAM, etc.)
- 10 inventory records with varying stock levels
- Sample low-stock alerts configured

To reset sample data:
```bash
# Connect to MySQL
docker exec -it inventory_mysql mysql -u inventory_user -p inventory_db

# In MySQL prompt:
TRUNCATE TABLE sales;
TRUNCATE TABLE inventory;
TRUNCATE TABLE products;

# Then re-run init-db.sql
```

## Performance Tips

1. **Database Indexing**: Already configured for common queries
2. **Redis Caching**: Reduce database load for frequently accessed data
3. **Connection Pooling**: Use persistent connections
4. **Query Optimization**: Use EXPLAIN to analyze queries
5. **Monitor**: Use `docker stats` to monitor resource usage

## Security Notes

⚠️ **Development Only Configuration**

For production, implement:
- Change default RabbitMQ credentials
- Use strong database passwords
- Enable SSL/TLS for connections
- Add API authentication (JWT)
- Implement rate limiting
- Run behind a reverse proxy
- Enable audit logging
- Use secrets management

## File Locations

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Service orchestration |
| `.env` | Environment variables |
| `config/init-db.sql` | Database schema |
| `docs/API.md` | API documentation |
| `docs/DEPLOYMENT.md` | Deployment guide |
| `docs/DEVELOPMENT.md` | Development guide |
| `README.md` | Project overview |

## Support

For issues:
1. Check logs: `docker-compose logs`
2. Review docs: Check `docs/` folder
3. Test endpoints: Use curl or Postman
4. Check connectivity: Verify ports are accessible

## Next Steps

1. **Explore Dashboard**: http://localhost:3000
2. **Test API**: Try curl commands above
3. **Add Products**: Use Products page or API
4. **Record Sales**: Create transactions
5. **Monitor Stock**: Check alerts and inventory
6. **Read Documentation**: Review docs/ folder
