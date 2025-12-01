# API Documentation

## Overview
The Inventory Tracker System uses a RESTful API with JSON for data exchange. All requests go through the API Gateway at `http://localhost:8000`.

## Base URL
```
http://localhost:8000
```

## Authentication
Currently, the API does not require authentication (to be implemented in production).

---

## Endpoints

### Product Catalog Service

#### 1. Get All Products
```http
GET /products
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "CPU-INTEL-I7",
      "name": "Intel Core i7-13700K",
      "category": "Processors",
      "price": 350.00,
      "description": "High-performance processor",
      "stock_threshold": 5,
      "created_at": "2024-12-01T10:00:00",
      "updated_at": "2024-12-01T10:00:00"
    }
  ],
  "count": 1
}
```

#### 2. Get Product by SKU
```http
GET /products/{sku}
```
**Example:**
```http
GET /products/CPU-INTEL-I7
```

#### 3. Create Product
```http
POST /products
Content-Type: application/json

{
  "sku": "GPU-RTX4070",
  "name": "NVIDIA RTX 4070",
  "category": "Graphics Cards",
  "price": 550.00,
  "description": "Mid-range gaming GPU",
  "stock_threshold": 3
}
```

#### 4. Update Product
```http
PUT /products
Content-Type: application/json

{
  "sku": "CPU-INTEL-I7",
  "name": "Intel Core i7-13700K",
  "category": "Processors",
  "price": 349.99,
  "description": "Updated description",
  "stock_threshold": 5
}
```

#### 5. Delete Product
```http
DELETE /products
Content-Type: application/json

{
  "sku": "CPU-INTEL-I7"
}
```

---

### Inventory Service

#### 1. Get All Inventory
```http
GET /inventory
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "CPU-INTEL-I7",
      "quantity": 12,
      "stock_threshold": 5,
      "reorder_point": 3,
      "last_updated": "2024-12-01T10:30:00",
      "name": "Intel Core i7-13700K",
      "price": 350.00
    }
  ],
  "count": 1
}
```

#### 2. Get Inventory by SKU
```http
GET /inventory/{sku}
```
**Example:**
```http
GET /inventory/CPU-INTEL-I7
```

#### 3. Update Stock (Reduce on Sale)
```http
PUT /inventory
Content-Type: application/json

{
  "sku": "CPU-INTEL-I7",
  "quantity": 2
}
```

#### 4. Restock Items (Add Inventory)
```http
POST /restock
Content-Type: application/json

{
  "sku": "CPU-INTEL-I7",
  "quantity": 50
}
```

#### 5. Get Low Stock Alerts
```http
GET /alerts
```
**Response:**
```json
{
  "success": true,
  "alerts": [
    {
      "sku": "GPU-NVIDIA-RTX4070",
      "name": "NVIDIA RTX 4070",
      "quantity": 1,
      "stock_threshold": 3,
      "deficit": 2
    }
  ],
  "count": 1
}
```

---

### Sales Service

#### 1. Get All Sales
```http
GET /sales
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "transaction_id": "TXN-20241201100000-abc123",
      "sku": "CPU-INTEL-I7",
      "quantity": 2,
      "unit_price": 350.00,
      "total": 700.00,
      "sale_date": "2024-12-01T10:00:00",
      "name": "Intel Core i7-13700K"
    }
  ],
  "count": 1
}
```

#### 2. Get Sale by Transaction ID
```http
GET /sales/{transaction_id}
```
**Example:**
```http
GET /sales/TXN-20241201100000-abc123
```

#### 3. Create Sale (Record Transaction)
```http
POST /sales
Content-Type: application/json

{
  "sku": "CPU-INTEL-I7",
  "quantity": 2
}
```
**Response:**
```json
{
  "success": true,
  "message": "Sale recorded",
  "transaction_id": "TXN-20241201100000-abc123",
  "total": 700.00
}
```

#### 4. Get Sales Report
```http
GET /report
```
**Response:**
```json
{
  "success": true,
  "report": [
    {
      "sale_date": "2024-12-01",
      "transaction_count": 5,
      "total_quantity": 12,
      "total_revenue": 3500.00
    }
  ]
}
```

---

## Health Checks

### Check Service Health
```http
GET /health
```
**Response:**
```json
{
  "status": "healthy",
  "service": "api-gateway",
  "timestamp": "2024-12-01T10:00:00Z"
}
```

---

## Error Responses

### 404 Not Found
```json
{
  "success": false,
  "error": "Endpoint not found"
}
```

### 400 Bad Request
```json
{
  "success": false,
  "error": "Insufficient stock"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "error": "Internal server error",
  "message": "Database connection failed"
}
```

---

## Event Messages

### SaleCreated Event
Published to RabbitMQ when a sale is recorded:
```json
{
  "event_type": "SaleCreated",
  "transaction_id": "TXN-20241201100000-abc123",
  "sku": "CPU-INTEL-I7",
  "quantity": 2,
  "timestamp": "2024-12-01T10:00:00Z"
}
```

### RestockReceived Event
Published to RabbitMQ when inventory is restocked:
```json
{
  "event_type": "RestockReceived",
  "sku": "CPU-INTEL-I7",
  "quantity": 50,
  "timestamp": "2024-12-01T10:30:00Z"
}
```

---

## Rate Limiting
Currently not implemented. To be added in production.

## CORS
The API Gateway allows CORS requests from all origins. This should be restricted in production.

## Timeouts
- Service timeout: 10 seconds
- Database timeout: 30 seconds
