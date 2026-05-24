-- Payment Confirmations Table for Quick Upload System
CREATE TABLE IF NOT EXISTS payment_confirmations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    confirm_code VARCHAR(6) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    payment_method ENUM('telebirr', 'bank_transfer') NOT NULL,
    bank_name VARCHAR(100),
    receipt_image_path VARCHAR(500),
    receipt_link VARCHAR(500),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);