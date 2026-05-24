-- Payment Flow Database Updates
-- Add new fields to support the enhanced payment flow

-- Add phone number field to payments table for SMS notifications
ALTER TABLE `payments` 
ADD COLUMN `customer_phone` VARCHAR(20) DEFAULT NULL AFTER `selected_bank`,
ADD COLUMN `sms_sent` TINYINT(1) DEFAULT 0 AFTER `customer_phone`,
ADD COLUMN `sms_message` VARCHAR(255) DEFAULT NULL AFTER `sms_sent`;

-- Update payment_receipts table to include more details
ALTER TABLE `payment_receipts` 
ADD COLUMN `payment_method` ENUM('telebirr', 'bank_transfer') DEFAULT 'bank_transfer' AFTER `bank_name`,
ADD COLUMN `admin_notes` TEXT DEFAULT NULL AFTER `status`,
ADD COLUMN `reviewed_by` INT(11) DEFAULT NULL AFTER `admin_notes`,
ADD COLUMN `reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `reviewed_by`;

-- Add foreign key for reviewed_by
ALTER TABLE `payment_receipts` 
ADD CONSTRAINT `fk_payment_receipts_reviewed_by` 
FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Create bank_sms_codes table for storing bank-specific SMS codes
CREATE TABLE IF NOT EXISTS `bank_sms_codes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bank_name` VARCHAR(50) NOT NULL,
  `sms_code` VARCHAR(50) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bank_name` (`bank_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default bank SMS codes
INSERT INTO `bank_sms_codes` (`bank_name`, `sms_code`) VALUES
('Commercial Bank of Ethiopia (CBE)', 'cbe1234567'),
('Awash Bank', 'awash7654321'),
('Birhan Bank', 'birhan32145678')
ON DUPLICATE KEY UPDATE 
sms_code = VALUES(sms_code);

-- Update payments table to support new payment methods
ALTER TABLE `payments` 
MODIFY COLUMN `payment_method` ENUM('telebirr', 'bank_transfer', 'chapa') DEFAULT 'telebirr';

-- Add receipt upload directory path to payment_receipts
ALTER TABLE `payment_receipts` 
ADD COLUMN `receipt_directory` VARCHAR(255) DEFAULT 'assets/uploads/receipts/' AFTER `receipt_link`;