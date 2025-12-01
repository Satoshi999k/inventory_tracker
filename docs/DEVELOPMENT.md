# Development Guide

## Project Structure
```
inventorytracker/
├── services/
│   ├── product-catalog/     # Product management microservice
│   │   ├── Dockerfile
│   │   ├── composer.json
│   │   └── index.php
│   ├── inventory/           # Inventory tracking microservice
│   │   ├── Dockerfile
│   │   ├── composer.json
│   │   └── index.php
│   └── sales/               # Sales transaction microservice
│       ├── Dockerfile
│       ├── composer.json
│       └── index.php
├── api-gateway/             # API routing and orchestration
│   ├── Dockerfile
│   └── gateway.php
├── frontend/                # Admin dashboard
│   ├── package.json
│   ├── server.js
│   └── public/
│       ├── index.html
│       ├── products.html
│       ├── inventory.html
│       ├── sales.html
│       ├── css/
│       │   └── style.css
│       └── js/
│           ├── api.js
│           ├── dashboard.js
│           ├── products.js
│           ├── inventory.js
│           └── sales.js
├── config/
│   ├── init-db.sql          # Database schema
│   └── .env.example
├── docs/
│   ├── API.md
│   ├── DEPLOYMENT.md
│   └── DEVELOPMENT.md
└── docker-compose.yml       # Service orchestration
```

## Development Setup

### Prerequisites
- PHP 8.2+
- Node.js 18+
- Docker & Docker Compose
- MySQL Client tools
- Git

### Local Development

**1. Start services:**
```bash
cd inventorytracker
docker-compose up -d
```

**2. Wait for services to be healthy (30-60 seconds)**
```bash
docker-compose ps
```

**3. Access services:**
- Dashboard: http://localhost:3000
- API: http://localhost:8000
- RabbitMQ UI: http://localhost:15672 (guest/guest)

**4. Test API:**
```bash
curl http://localhost:8000/health
```

### Modifying Services

#### Adding a New Endpoint to Product Catalog

**File:** `services/product-catalog/index.php`

```php
// Add new route handler
if ($method === 'GET' && in_array('products-by-category', $path_parts)) {
    $category = $path_parts[array_search('products-by-category', $path_parts) + 1];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY name");
    $stmt->execute([$category]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $products]);
    exit;
}
```

**Test:**
```bash
curl http://localhost:8000/products-by-category/Processors
```

#### Adding Frontend Feature

**File:** `frontend/public/products.html` (add button)
**File:** `frontend/public/js/products.js` (add handler)

```javascript
async function filterByCategory(category) {
    try {
        const response = await api.get(`/products-by-category/${category}`);
        const products = response.data || [];
        // Render filtered products
    } catch (error) {
        showAlert('Error filtering products', 'error');
    }
}
```

### Testing

#### Unit Testing

Create test file: `services/product-catalog/tests/ProductTest.php`

```php
<?php
class ProductTest {
    public function testCreateProduct() {
        // Test implementation
    }
    
    public function testGetProducts() {
        // Test implementation
    }
}
```

Run tests:
```bash
docker exec product_catalog_service php -m
```

#### API Testing

**Using curl:**
```bash
# Get all products
curl http://localhost:8000/products

# Create product
curl -X POST http://localhost:8000/products \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST-001","name":"Test Product","price":99.99}'

# Update product
curl -X PUT http://localhost:8000/products \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST-001","name":"Updated Product","price":89.99}'
```

#### Frontend Testing

Open browser console:
```javascript
// Test API call
fetch('http://localhost:8000/products')
  .then(r => r.json())
  .then(d => console.log(d))
```

### Debugging

#### View Service Logs
```bash
# Product Catalog Service
docker logs -f product_catalog_service

# Inventory Service
docker logs -f inventory_service

# Sales Service
docker logs -f sales_service

# API Gateway
docker logs -f api_gateway

# Frontend
docker logs -f admin_frontend
```

#### Database Debugging
```bash
# Connect to MySQL
docker exec -it inventory_mysql mysql -u inventory_user -p inventory_db

# Run SQL commands
SHOW TABLES;
SELECT * FROM products;
SELECT * FROM sales;
```

#### PHP Debugging

Add debug output in services:
```php
error_log('Debug: ' . json_encode($variable));
```

View logs:
```bash
docker exec product_catalog_service tail -f /var/log/apache2/error.log
```

### Common Development Tasks

#### Add New Database Table
1. Update `config/init-db.sql`
2. Restart MySQL: `docker-compose restart mysql`
3. Access database to verify

#### Add Redis Caching
```php
// In service
$redis = new Redis();
$redis->connect('redis', 6379);

// Check cache
$cached = $redis->get('products');
if ($cached) {
    echo $cached;
} else {
    // Fetch from DB
    $redis->set('products', json_encode($products), 300); // 5 min TTL
}
```

#### Add Message Queue Event
```php
// Publish event
$connection = new AMQPConnection([
    'host' => 'rabbitmq',
    'port' => 5672
]);
$connection->connect();
$channel = $connection->channel();

$message = new AMQPMessage(json_encode($event_data));
$channel->basic_publish($message, 'inventory.events');
```

### Performance Testing

**Load test API endpoint:**
```bash
# Using Apache Bench
ab -n 100 -c 10 http://localhost:8000/products

# Using wrk
wrk -t4 -c100 -d30s http://localhost:8000/products
```

### Deployment to Production

**Build optimized images:**
```bash
docker-compose build --compress
```

**Push to registry:**
```bash
docker tag inventorytracker_product-catalog:latest myregistry/product-catalog:1.0
docker push myregistry/product-catalog:1.0
```

## Code Standards

### PHP Standards
- PSR-12 for code style
- Use prepared statements for all DB queries
- Validate all inputs
- Log all errors

### JavaScript Standards
- Use async/await for API calls
- Proper error handling
- DRY principles
- Comment complex logic

### Git Workflow
```bash
# Feature branch
git checkout -b feature/new-feature

# Make changes and commit
git add .
git commit -m "Add new feature"

# Push and create PR
git push origin feature/new-feature
```

## Troubleshooting

### Service Won't Start
```bash
docker-compose logs [service-name]
docker-compose down && docker-compose up -d --build
```

### Database Connection Fails
```bash
docker exec inventory_mysql ping
docker exec product_catalog_service curl -f http://mysql:3306 || echo "Failed"
```

### API Returns 503
- Check if downstream service is running
- Check Docker logs
- Verify network connectivity

### Frontend Not Loading
- Check port 3000 is accessible
- Verify Node.js dependencies installed
- Check browser console for errors
