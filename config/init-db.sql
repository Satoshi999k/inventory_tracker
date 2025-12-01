-- Create Database
CREATE DATABASE IF NOT EXISTS inventory_db;
USE inventory_db;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    stock_threshold INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sku (sku),
    INDEX idx_category (category)
);

-- Inventory Table
CREATE TABLE IF NOT EXISTS inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) UNIQUE NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    stock_threshold INT NOT NULL DEFAULT 10,
    reorder_point INT DEFAULT 5,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sku) REFERENCES products(sku) ON DELETE CASCADE,
    INDEX idx_sku (sku),
    INDEX idx_quantity (quantity)
);

-- Sales Table
CREATE TABLE IF NOT EXISTS sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    sku VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sku) REFERENCES products(sku) ON DELETE CASCADE,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_sku (sku),
    INDEX idx_sale_date (sale_date)
);

-- Restock Events Log Table
CREATE TABLE IF NOT EXISTS restock_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    event_type VARCHAR(50),
    status VARCHAR(20) DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (sku) REFERENCES products(sku) ON DELETE CASCADE,
    INDEX idx_sku (sku),
    INDEX idx_status (status)
);

-- Stock Alerts Table
CREATE TABLE IF NOT EXISTS stock_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL,
    alert_type VARCHAR(50),
    current_stock INT,
    threshold INT,
    alert_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sku) REFERENCES products(sku) ON DELETE CASCADE,
    INDEX idx_sku (sku),
    INDEX idx_resolved (resolved)
);

-- Insert sample data
INSERT INTO products (sku, name, category, price, description, stock_threshold) VALUES
('CPU-INTEL-I7', 'Intel Core i7-13700K', 'Processors', 350.00, 'High-performance processor for gaming and workstations', 5),
('GPU-NVIDIA-RTX4070', 'NVIDIA RTX 4070', 'Graphics Cards', 550.00, 'Mid-range gaming GPU with DLSS support', 3),
('RAM-CORSAIR-32GB', 'Corsair Vengeance 32GB DDR5', 'Memory', 150.00, '32GB DDR5 RAM Kit at 5600MHz', 10),
('SSD-SAMSUNG-1TB', 'Samsung 980 Pro 1TB NVMe SSD', 'Storage', 80.00, 'Ultra-fast PCIe 4.0 NVMe SSD', 15),
('MB-ASUS-Z790', 'ASUS ROG STRIX Z790-E Gaming', 'Motherboards', 250.00, 'Premium Z790 motherboard for Intel 13th gen', 5),
('PSU-CORSAIR-850W', 'Corsair RM850x 850W', 'Power Supplies', 120.00, '80+ Gold certified modular PSU', 8),
('CASE-NZXT-H7', 'NZXT H7 Flow RGB', 'Cases', 120.00, 'Premium ATX case with excellent airflow', 6),
('COOLER-NOCTUA-NH-D15', 'Noctua NH-D15 CPU Cooler', 'Cooling', 90.00, 'Top-performing air cooler for high-end CPUs', 12),
('CABLE-MONITOR-HDMI', 'HDMI 2.1 Cable 2m', 'Cables', 15.00, 'High-speed HDMI 2.1 cable for 8K support', 50),
('KEYBOARD-LOGITECH-MX', 'Logitech MX Keys', 'Accessories', 99.00, 'Wireless mechanical keyboard', 20);

INSERT INTO inventory (sku, quantity, stock_threshold, reorder_point) VALUES
('CPU-INTEL-I7', 12, 5, 3),
('GPU-NVIDIA-RTX4070', 8, 3, 2),
('RAM-CORSAIR-32GB', 25, 10, 5),
('SSD-SAMSUNG-1TB', 40, 15, 8),
('MB-ASUS-Z790', 6, 5, 2),
('PSU-CORSAIR-850W', 18, 8, 4),
('CASE-NZXT-H7', 14, 6, 3),
('COOLER-NOCTUA-NH-D15', 35, 12, 6),
('CABLE-MONITOR-HDMI', 100, 50, 25),
('KEYBOARD-LOGITECH-MX', 45, 20, 10);
