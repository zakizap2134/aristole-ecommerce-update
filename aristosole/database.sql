USE aristosole_db;

-- =====================================================
-- TABLE 1: users (Stores user accounts)
-- =====================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 2: brands
-- =====================================================
CREATE TABLE brands (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(80) NOT NULL UNIQUE
);

-- =====================================================
-- TABLE 3: categories
-- =====================================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(80) NOT NULL UNIQUE
);

-- =====================================================
-- TABLE 4: products (With INDEX on SKU - Requirement)
-- =====================================================
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    category_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    unit_price DECIMAL(10,2) NOT NULL,
    stock_qty INT NOT NULL DEFAULT 0,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    INDEX idx_sku (sku)  -- INDEX for faster search
);

-- =====================================================
-- TABLE 5: orders
-- =====================================================
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status ENUM('pending', 'paid', 'shipped', 'cancelled') DEFAULT 'paid',
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
);

-- =====================================================
-- TABLE 6: order_items
-- =====================================================
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    CHECK (quantity > 0)
);

-- =====================================================
-- TABLE 7: payments (For transaction completeness)
-- =====================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    payment_method VARCHAR(50) DEFAULT 'card',
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- =====================================================
-- SQL TRIGGER: Automatically reduce stock when order item is inserted
-- This prevents overselling
-- =====================================================
DELIMITER //

CREATE TRIGGER trg_reduce_stock
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;
    
    -- Get current stock
    SELECT stock_qty INTO current_stock 
    FROM products WHERE product_id = NEW.product_id;
    
    -- Check if enough stock
    IF current_stock < NEW.quantity THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Insufficient stock for this product';
    END IF;
    
    -- Reduce stock
    UPDATE products 
    SET stock_qty = stock_qty - NEW.quantity 
    WHERE product_id = NEW.product_id;
END//

DELIMITER ;

-- =====================================================
-- SQL STORED PROCEDURE: Complete checkout with transaction
-- Uses BEGIN, COMMIT, ROLLBACK
-- =====================================================
DELIMITER //

CREATE PROCEDURE sp_checkout(
    IN p_user_id INT,
    IN p_total_amount DECIMAL(10,2)
)
proc_label: BEGIN
    DECLARE v_order_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    -- START TRANSACTION (Requirement)
    START TRANSACTION;
    
    -- Insert into orders
    INSERT INTO orders (user_id, total_amount, order_status)
    VALUES (p_user_id, p_total_amount, 'paid');
    
    SET v_order_id = LAST_INSERT_ID();
    
    -- Insert into payments
    INSERT INTO payments (order_id, payment_method, amount, payment_status)
    VALUES (v_order_id, 'card', p_total_amount, 'completed');
    
    -- COMMIT transaction (Requirement)
    COMMIT;
    
    SELECT v_order_id AS order_id;
END//

DELIMITER ;

-- =====================================================
-- SQL VIEW: Manager dashboard report
-- Joins 3+ tables and uses SUM, COUNT, GROUP BY
-- =====================================================
CREATE VIEW vw_sales_report AS
SELECT 
    p.product_id,
    p.product_name,
    b.brand_name,
    c.category_name,
    COALESCE(SUM(oi.quantity), 0) AS total_units_sold,
    COALESCE(SUM(oi.quantity * oi.price), 0) AS total_revenue,
    p.stock_qty AS current_stock
FROM products p
INNER JOIN brands b ON p.brand_id = b.brand_id
INNER JOIN categories c ON p.category_id = c.category_id
LEFT JOIN order_items oi ON p.product_id = oi.product_id
GROUP BY p.product_id, p.product_name, b.brand_name, c.category_name, p.stock_qty
ORDER BY total_revenue DESC;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Brands
INSERT INTO brands (brand_name) VALUES 
('Nike'), ('Adidas'), ('Puma'), ('New Balance'), ('Reebok');

-- Categories
INSERT INTO categories (category_name) VALUES 
('Running'), ('Casual'), ('Basketball'), ('Training'), ('Walking');

-- Admin user (password: password123)
-- Hash generated using password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (full_name, email, password_hash, role) VALUES 
('Administrator', 'admin@aristosole.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample customer
INSERT INTO users (full_name, email, password_hash, role) VALUES 
('John Doe', 'customer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

-- Products
INSERT INTO products (brand_id, category_id, sku, product_name, description, unit_price, stock_qty) VALUES
(1, 1, 'NK-RUN-001', 'Nike Air Zoom Pegasus', 'Premium running shoe with responsive cushioning', 5499.00, 30),
(1, 2, 'NK-CAS-001', 'Nike Dunk Low', 'Classic low-top sneaker', 6299.00, 25),
(2, 1, 'AD-RUN-001', 'Adidas Ultraboost', 'Energy-returning running shoes', 7999.00, 20),
(2, 3, 'AD-BBK-001', 'Adidas Harden Vol 7', 'Professional basketball shoe', 8999.00, 15),
(3, 2, 'PU-CAS-001', 'Puma Suede Classic', 'Iconic lifestyle sneaker', 4599.00, 40);

-- Demo order to show view working
INSERT INTO orders (user_id, total_amount, order_status) VALUES (2, 13498.00, 'paid');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (1, 1, 1, 5499.00);
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (1, 3, 1, 7999.00);
INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES (1, 'card', 13498.00, 'completed');

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
SELECT '=== DATABASE SETUP COMPLETE ===' AS Status;
SELECT COUNT(*) AS Total_Users FROM users;
SELECT COUNT(*) AS Total_Brands FROM brands;
SELECT COUNT(*) AS Total_Categories FROM categories;
SELECT COUNT(*) AS Total_Products FROM products;
SELECT COUNT(*) AS Total_Orders FROM orders;
SELECT 'Trigger exists:' AS Info, TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'aristosole_db';
SELECT 'Stored Procedure exists:' AS Info, ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = 'aristosole_db' AND ROUTINE_TYPE = 'PROCEDURE';
SELECT 'View exists:' AS Info, TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'aristosole_db';