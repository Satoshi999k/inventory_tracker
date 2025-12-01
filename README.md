# Inventory Tracking System - Microservices Architecture

A comprehensive inventory management solution for small computer shops using a microservices architecture with real-time synchronization between sales, inventory, and product catalog services.

## Project Overview

This system automates inventory tracking with the following core components:

- **Product Catalog Service**: Manages product details (SKU, name, description, price)
- **Inventory Service**: Tracks stock levels and updates in real-time
- **Sales Service**: Records transactions and triggers inventory updates
- **API Gateway**: Unified entry point for all client requests
- **Admin Interface**: Web-based dashboard for managing products and monitoring stock
- **Message Broker**: RabbitMQ for event-driven communication
- **Databases**: MySQL for persistence, Redis for caching

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Admin Interface (Frontend)               │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                      API Gateway                             │
└────┬───────────────────┬──────────────────┬────────────────┘
     │                   │                  │
┌────▼──────┐     ┌──────▼────────┐   ┌────▼─────────┐
│  Product   │     │   Inventory   │   │    Sales     │
│  Catalog   │     │    Service    │   │   Service    │
│  Service   │     └─────┬─────────┘   └────┬─────────┘
└────┬──────┘           │                    │
     │         ┌────────▼────────────────────┘
     │         │  RabbitMQ Message Broker
     │         │
┌────▼─────────▼──────┐
│   MySQL Database    │
└─────────────────────┘
     ▲
     │
┌────┴─────────┐
│ Redis Cache  │
└──────────────┘
```

## Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.0+ (for local development without Docker)
- MySQL 8.0+
- Redis
- RabbitMQ

### Installation

1. **Clone or navigate to the project:**
   ```bash
   cd d:\xampp\htdocs\inventorytracker
   ```

2. **Start all services with Docker Compose:**
   ```bash
   docker-compose up -d
   ```

3. **Access the services:**
   - Admin Interface: `http://localhost:3000`
   - API Gateway: `http://localhost:8000`
   - RabbitMQ Management: `http://localhost:15672` (guest/guest)

### Service Endpoints

| Service | Port | Endpoint |
|---------|------|----------|
| API Gateway | 8000 | http://localhost:8000 |
| Product Catalog | 8001 | http://localhost:8001 |
| Inventory | 8002 | http://localhost:8002 |
| Sales | 8003 | http://localhost:8003 |
| Frontend | 3000 | http://localhost:3000 |
| RabbitMQ Admin | 15672 | http://localhost:15672 |
| MySQL | 3306 | localhost:3306 |
| Redis | 6379 | localhost:6379 |

## Project Structure

```
inventorytracker/
├── services/
│   ├── product-catalog-service/
│   ├── inventory-service/
│   └── sales-service/
├── api-gateway/
├── frontend/
│   └── admin-interface/
├── databases/
│   └── init.sql
├── config/
├── docs/
├── docker-compose.yml
└── README.md
```

## API Documentation

API documentation is available via Swagger/OpenAPI. Once services are running, visit:
- `http://localhost:8000/api/docs`

## Error Handling & Monitoring

- **Health Checks**: Each service exposes a `/health` endpoint
- **Logging**: Centralized logging via ELK Stack (planned)
- **Monitoring**: Prometheus and Grafana integration (planned)
- **Circuit Breaker**: Implemented for fault tolerance
- **Retries**: Exponential backoff with idempotency keys

## Event-Driven Communication

### Published Events

- **SaleCreated**: Triggered when a sale is recorded
- **SaleUpdated**: Triggered when a sale is modified
- **RestockReceived**: Triggered when stock is replenished
- **StockLevelWarning**: Triggered when stock falls below threshold

## Development

### Building Individual Services

```bash
# Product Catalog Service
cd services/product-catalog-service
docker build -t product-catalog-service .

# Inventory Service
cd services/inventory-service
docker build -t inventory-service .

# Sales Service
cd services/sales-service
docker build -t sales-service .
```

### Running Tests

```bash
# Tests will be added for each service
docker-compose exec product-catalog-service vendor/bin/phpunit
docker-compose exec inventory-service vendor/bin/phpunit
docker-compose exec sales-service vendor/bin/phpunit
```

## Database Schema

Database initialization scripts are in `databases/init.sql`. They create:
- `products` table
- `inventory` table
- `sales` table
- `sales_items` table
- `restock_logs` table

## Technology Stack

| Component | Technology |
|-----------|-----------|
| Backend Services | PHP (Native/Laravel) |
| Web Server | Apache/Nginx |
| Databases | MySQL, Redis |
| Message Broker | RabbitMQ |
| Frontend | HTML, CSS, JavaScript |
| Containerization | Docker & Docker Compose |
| Monitoring | Prometheus, Grafana |
| API Documentation | Swagger/OpenAPI |

## Contributing

1. Create a feature branch
2. Make your changes
3. Test thoroughly
4. Submit a pull request

## Troubleshooting

### Common Issues

**Port already in use:**
```bash
# Change port mappings in docker-compose.yml or stop conflicting services
docker ps
docker stop <container_id>
```

**Database connection errors:**
- Ensure MySQL service is running: `docker-compose logs mysql`
- Check credentials in docker-compose.yml

**RabbitMQ connection issues:**
- Verify RabbitMQ is running: `docker-compose logs rabbitmq`
- Check message broker URL in service configurations

## Future Enhancements

- [ ] User authentication and authorization
- [ ] Advanced reporting and analytics
- [ ] Mobile application
- [ ] AI-powered demand forecasting
- [ ] Supplier integration
- [ ] Multi-location support

## Support

For issues or questions, please create an issue in the project repository.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
# inventory_tracker
