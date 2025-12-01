# Integrating Prometheus Metrics into Services

## Overview
This guide shows how to add Prometheus metrics to your PHP and Node.js services.

## PHP Services Setup

### 1. Install Prometheus Client Library
```bash
cd services/product-catalog
composer require promphp/prometheus_client_php
```

### 2. Create Metrics Helper Class
Create `services/product-catalog/src/Metrics.php`:

```php
<?php

namespace App;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;

class Metrics {
    private static $instance = null;
    private $registry;
    private $httpRequestsTotal;
    private $httpRequestDurationSeconds;
    private $databaseQueryDurationSeconds;
    private $cacheHitsTotal;
    private $cacheMissesTotal;
    
    private function __construct() {
        // Use APC for storage (shared across requests)
        $this->registry = new CollectorRegistry(new APC());
        $this->initializeMetrics();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeMetrics() {
        // HTTP Requests Counter
        try {
            $this->httpRequestsTotal = $this->registry->getOrRegisterCounter(
                'http_requests_total',
                'Total HTTP requests',
                ['method', 'endpoint', 'status']
            );
        } catch (\Exception $e) {}
        
        // HTTP Request Duration Histogram
        try {
            $this->httpRequestDurationSeconds = $this->registry->getOrRegisterHistogram(
                'http_request_duration_seconds',
                'HTTP request duration in seconds',
                ['method', 'endpoint'],
                [0.01, 0.05, 0.1, 0.5, 1, 2, 5]
            );
        } catch (\Exception $e) {}
        
        // Database Query Duration Histogram
        try {
            $this->databaseQueryDurationSeconds = $this->registry->getOrRegisterHistogram(
                'database_query_duration_seconds',
                'Database query duration in seconds',
                ['query_type'],
                [0.001, 0.01, 0.05, 0.1, 0.5, 1]
            );
        } catch (\Exception $e) {}
        
        // Cache Hits Counter
        try {
            $this->cacheHitsTotal = $this->registry->getOrRegisterCounter(
                'cache_hits_total',
                'Total cache hits',
                ['cache_type']
            );
        } catch (\Exception $e) {}
        
        // Cache Misses Counter
        try {
            $this->cacheMissesTotal = $this->registry->getOrRegisterCounter(
                'cache_misses_total',
                'Total cache misses',
                ['cache_type']
            );
        } catch (\Exception $e) {}
    }
    
    public function recordRequest($method, $endpoint, $status, $duration) {
        if ($this->httpRequestsTotal) {
            $this->httpRequestsTotal->inc(['$method', '$endpoint', '$status']);
        }
        if ($this->httpRequestDurationSeconds) {
            $this->httpRequestDurationSeconds->observe($duration, ['$method', '$endpoint']);
        }
    }
    
    public function recordQueryDuration($queryType, $duration) {
        if ($this->databaseQueryDurationSeconds) {
            $this->databaseQueryDurationSeconds->observe($duration, ['$queryType']);
        }
    }
    
    public function recordCacheHit($cacheType = 'redis') {
        if ($this->cacheHitsTotal) {
            $this->cacheHitsTotal->inc(['$cacheType']);
        }
    }
    
    public function recordCacheMiss($cacheType = 'redis') {
        if ($this->cacheMissesTotal) {
            $this->cacheMissesTotal->inc(['$cacheType']);
        }
    }
    
    public function render() {
        return RenderTextFormat::render($this->registry);
    }
}
?>
```

### 3. Add Metrics Endpoint
Add to `services/product-catalog/index.php`:

```php
<?php
// ... existing code ...

use App\Metrics;

// Metrics endpoint
if ($_SERVER['REQUEST_URI'] === '/metrics') {
    header('Content-Type: ' . 'text/plain; version=0.0.4');
    echo Metrics::getInstance()->render();
    exit;
}

// Middleware to track metrics
$start = microtime(true);

try {
    // ... your route handling ...
    $status = 200;
} catch (Exception $e) {
    $status = 500;
}

$duration = microtime(true) - $start;
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

Metrics::getInstance()->recordRequest($method, $endpoint, $status, $duration);

// ... rest of code ...
?>
```

### 4. Track Database Queries
```php
<?php
use App\Metrics;

class ProductService {
    private $db;
    
    public function getProducts() {
        $start = microtime(true);
        
        try {
            $stmt = $this->db->query("SELECT * FROM products");
            $products = $stmt->fetchAll();
            
            $duration = microtime(true) - $start;
            Metrics::getInstance()->recordQueryDuration('SELECT', $duration);
            
            return $products;
        } catch (Exception $e) {
            $duration = microtime(true) - $start;
            Metrics::getInstance()->recordQueryDuration('SELECT', $duration);
            throw $e;
        }
    }
}
?>
```

### 5. Track Cache Operations
```php
<?php
use App\Metrics;

class CacheService {
    private $redis;
    
    public function get($key) {
        $value = $this->redis->get($key);
        
        if ($value !== null) {
            Metrics::getInstance()->recordCacheHit('redis');
        } else {
            Metrics::getInstance()->recordCacheMiss('redis');
        }
        
        return $value;
    }
}
?>
```

## Node.js Services Setup

### 1. Install Prometheus Client
```bash
npm install prom-client express
```

### 2. Setup Metrics Middleware
Create `middleware/metrics.js`:

```javascript
const promClient = require('prom-client');

// Create registry
const register = new promClient.Registry();

// Add default metrics
promClient.collectDefaultMetrics({ register });

// HTTP Requests Counter
const httpRequestsTotal = new promClient.Counter({
  name: 'http_requests_total',
  help: 'Total HTTP requests',
  labelNames: ['method', 'route', 'status'],
  registers: [register]
});

// HTTP Request Duration Histogram
const httpRequestDuration = new promClient.Histogram({
  name: 'http_request_duration_seconds',
  help: 'HTTP request duration in seconds',
  labelNames: ['method', 'route'],
  buckets: [0.01, 0.05, 0.1, 0.5, 1, 2, 5],
  registers: [register]
});

// Database Query Duration Histogram
const dbQueryDuration = new promClient.Histogram({
  name: 'database_query_duration_seconds',
  help: 'Database query duration in seconds',
  labelNames: ['query_type'],
  buckets: [0.001, 0.01, 0.05, 0.1, 0.5, 1],
  registers: [register]
});

// Cache Hit/Miss Counters
const cacheHitsTotal = new promClient.Counter({
  name: 'cache_hits_total',
  help: 'Total cache hits',
  labelNames: ['cache_type'],
  registers: [register]
});

const cacheMissesTotal = new promClient.Counter({
  name: 'cache_misses_total',
  help: 'Total cache misses',
  labelNames: ['cache_type'],
  registers: [register]
});

// Middleware to track metrics
function metricsMiddleware(req, res, next) {
  const start = Date.now();
  
  res.on('finish', () => {
    const duration = (Date.now() - start) / 1000;
    
    httpRequestsTotal.inc({
      method: req.method,
      route: req.route ? req.route.path : req.path,
      status: res.statusCode
    });
    
    httpRequestDuration.observe({
      method: req.method,
      route: req.route ? req.route.path : req.path
    }, duration);
  });
  
  next();
}

// Metrics endpoint
function metricsEndpoint(req, res) {
  res.set('Content-Type', register.contentType);
  res.end(register.metrics());
}

module.exports = {
  metricsMiddleware,
  metricsEndpoint,
  register,
  dbQueryDuration,
  cacheHitsTotal,
  cacheMissesTotal
};
```

### 3. Use in Express App
```javascript
const express = require('express');
const { metricsMiddleware, metricsEndpoint } = require('./middleware/metrics');

const app = express();

// Add metrics middleware
app.use(metricsMiddleware);

// Metrics endpoint
app.get('/metrics', metricsEndpoint);

// Your routes
app.get('/api/products', (req, res) => {
  // ... your code ...
});

app.listen(8001, () => console.log('Service running on port 8001'));
```

### 4. Track Database Queries
```javascript
const { dbQueryDuration } = require('./middleware/metrics');

async function queryDatabase(query) {
  const start = Date.now();
  
  try {
    const result = await db.query(query);
    const duration = (Date.now() - start) / 1000;
    
    dbQueryDuration.observe({
      query_type: 'SELECT'
    }, duration);
    
    return result;
  } catch (error) {
    const duration = (Date.now() - start) / 1000;
    dbQueryDuration.observe({
      query_type: 'SELECT'
    }, duration);
    throw error;
  }
}
```

### 5. Track Cache Operations
```javascript
const { cacheHitsTotal, cacheMissesTotal } = require('./middleware/metrics');

function getFromCache(key) {
  const value = cache.get(key);
  
  if (value) {
    cacheHitsTotal.inc({ cache_type: 'redis' });
  } else {
    cacheMissesTotal.inc({ cache_type: 'redis' });
  }
  
  return value;
}
```

## Testing Metrics Endpoint

```bash
# Test PHP service
curl http://localhost:8001/metrics

# Test Node service
curl http://localhost:8001/metrics

# View in Prometheus
# http://localhost:9090/targets - should show service as UP
```

## Custom Metrics Examples

### Track Inventory Stock Changes
```php
$inventoryChanges = $registry->getOrRegisterCounter(
    'inventory_stock_changed_total',
    'Total inventory stock changes',
    ['product_id', 'change_type'] // 'added', 'removed'
);

$inventoryChanges->inc(['$productId', 'added']);
```

### Track Sales Transactions
```php
$salesTotal = $registry->getOrRegisterCounter(
    'sales_total',
    'Total sales amount',
    ['product_category']
);

$salesTotal->inc(['Electronics'], $amount);
```

### Track API Errors
```php
$apiErrors = $registry->getOrRegisterCounter(
    'api_errors_total',
    'Total API errors',
    ['endpoint', 'error_type']
);

$apiErrors->inc(['/api/products', 'InvalidInput']);
```

## Monitoring Custom Metrics

Add to Grafana dashboards:

```promql
# Inventory changes rate
rate(inventory_stock_changed_total[5m])

# Sales by category
rate(sales_total[1h]) by (product_category)

# API error rate
rate(api_errors_total[5m]) by (error_type)
```

## Best Practices

1. **Use Meaningful Names**: `http_requests_total`, not `requests`
2. **Add Labels**: Always include relevant labels for filtering
3. **Avoid High Cardinality**: Don't label with user IDs or request IDs
4. **Use Appropriate Types**:
   - Counter: for increasing values
   - Gauge: for values that can go up/down
   - Histogram: for measurements (latency, sizes)
5. **Document Metrics**: Add help text explaining what each metric measures
