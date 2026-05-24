-- Payment Workflow Database Tables

-- Orders table (enhanced)
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending_payment', 'payment_submitted', 'payment_approved', 'delivery_info_sent', 'in_delivery', 'completed', 'cancelled') DEFAULT 'pending_payment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    artisan_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (artisan_id) REFERENCES artisans(id)
);

-- Payment receipts table
CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    receipt_image_path VARCHAR(500),
    receipt_link VARCHAR(500),
    payment_method ENUM('telebirr', 'bank_transfer') NOT NULL,
    bank_name VARCHAR(100),
    phone_number VARCHAR(20),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Delivery information table
CREATE TABLE IF NOT EXISTS delivery_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_location TEXT NOT NULL,
    delivery_date DATE NOT NULL,
    artisan_name VARCHAR(100),
    artisan_phone VARCHAR(20),
    artisan_location TEXT,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Delivery confirmations table
CREATE TABLE IF NOT EXISTS delivery_confirmations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_confirmed BOOLEAN DEFAULT FALSE,
    artisan_confirmed BOOLEAN DEFAULT FALSE,
    customer_confirmed_at TIMESTAMP NULL,
    artisan_confirmed_at TIMESTAMP NULL,
    both_confirmed_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Ratings table
CREATE TABLE IF NOT EXISTS ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    artisan_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (artisan_id) REFERENCES artisans(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Wallet transactions table
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    transaction_type ENUM('payment_received', 'artisan_payout', 'company_commission') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    recipient_type ENUM('company', 'artisan') NOT NULL,
    recipient_id INT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_type ENUM('customer', 'artisan', 'manager', 'admin') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('payment', 'delivery', 'rating', 'general') NOT NULL,
    order_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);