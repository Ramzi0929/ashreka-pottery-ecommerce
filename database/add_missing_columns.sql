-- Add missing columns to payment_confirmations table
ALTER TABLE payment_confirmations 
ADD COLUMN payment_method ENUM('telebirr', 'bank_transfer') DEFAULT 'telebirr',
ADD COLUMN bank_name VARCHAR(100),
ADD COLUMN receipt_image_path VARCHAR(500),
ADD COLUMN receipt_link VARCHAR(500),
ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';