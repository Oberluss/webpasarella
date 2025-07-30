-- ==========================================
-- SISTEMA DE PEDIDOS - SCHEMA
-- ==========================================

-- Tabla principal de pedidos
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    
    -- Direcciones
    shipping_address JSON NOT NULL,
    billing_address JSON DEFAULT NULL,
    
    -- Información de pago
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_reference VARCHAR(100) DEFAULT NULL,
    
    -- Metadatos
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    -- Índices
    INDEX idx_user_orders (user_id),
    INDEX idx_order_status (status),
    INDEX idx_order_date (created_at)
);

-- Tabla de items del pedido
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    
    -- Información del producto al momento de la compra
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) DEFAULT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    
    -- Metadatos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    
    -- Índices
    INDEX idx_order_items (order_id),
    INDEX idx_product_items (product_id)
);

-- Vista para obtener pedidos con información del usuario
CREATE VIEW IF NOT EXISTS orders_with_user AS
SELECT 
    o.*,
    u.name as user_name,
    u.email as user_ema
