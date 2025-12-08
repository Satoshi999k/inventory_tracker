-- Initialize Database Schema for Inventory Tracking System

-- Create Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL,
    cost DECIMAL(10, 2),
    image_url LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sku (sku),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Inventory Table
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT DEFAULT 0,
    stock_threshold INT DEFAULT 10,
    reorder_quantity INT DEFAULT 50,
    warehouse_location VARCHAR(100),
    last_restocked TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_inventory (product_id),
    INDEX idx_quantity (quantity),
    INDEX idx_threshold (stock_threshold)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Sales Table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    status VARCHAR(50) DEFAULT 'completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Sales Items Table (One-to-Many with Sales)
CREATE TABLE IF NOT EXISTS sales_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_sale_id (sale_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Restock Logs Table
CREATE TABLE IF NOT EXISTS restock_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_added INT NOT NULL,
    supplier VARCHAR(255),
    cost_per_unit DECIMAL(10, 2),
    total_cost DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Stock Movement Logs Table
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('sale', 'restock', 'adjustment', 'return') NOT NULL,
    quantity INT NOT NULL,
    reference_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Data for Testing
INSERT INTO products (sku, name, description, category, price, cost) VALUES
('SKU001', 'Intel Core i7', 'High-performance processor', 'CPU', 349.99, 280.00),
('SKU002', 'AMD Ryzen 5', 'Mid-range processor', 'CPU', 229.99, 180.00),
('SKU003', 'RTX 4070', 'Graphics card for gaming', 'GPU', 599.99, 480.00),
('SKU004', 'DDR5 32GB RAM', 'High-speed memory', 'RAM', 179.99, 140.00),
('SKU005', 'Samsung 1TB SSD', 'Fast storage device', 'Storage', 99.99, 75.00),
('SKU006', 'Mechanical Keyboard', 'RGB Gaming Keyboard', 'Peripherals', 89.99, 50.00),
('SKU007', 'Wireless Mouse', 'Ergonomic mouse', 'Peripherals', 49.99, 25.00),
('SKU008', 'Monitor 27" 4K', 'Ultra HD Display', 'Monitor', 399.99, 300.00),
('SKU009', 'Power Supply 750W', 'Gold-rated PSU', 'PSU', 99.99, 70.00),
('SKU010', 'CPU Cooler', 'Liquid cooling system', 'Cooling', 129.99, 90.00);

-- Populate Inventory for Products
INSERT INTO inventory (product_id, quantity, stock_threshold, reorder_quantity) 
SELECT id, FLOOR(RAND() * 100) + 10, 10, 50 FROM products;

-- Create indexes for better query performance
CREATE INDEX idx_sales_created_date ON sales(created_at);
CREATE INDEX idx_inventory_low_stock ON inventory(quantity, stock_threshold);
