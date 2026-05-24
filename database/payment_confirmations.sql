-- Payment Confirmations Table
CREATE TABLE IF NOT EXISTS `payment_confirmations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `confirm_code` VARCHAR(6) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order` (`order_id`),
  KEY `idx_code_expiry` (`confirm_code`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;