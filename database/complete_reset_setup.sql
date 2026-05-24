-- Complete Password Reset Database Setup

-- 1. Password Resets Table
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `contact` VARCHAR(100) NOT NULL,
  `reset_code` VARCHAR(6) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_reset` (`user_id`),
  KEY `idx_code_expiry` (`reset_code`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Bank SMS Codes Table (for payment system)
CREATE TABLE IF NOT EXISTS `bank_sms_codes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bank_name` VARCHAR(50) NOT NULL,
  `sms_code` VARCHAR(50) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert bank codes
INSERT INTO `bank_sms_codes` (`bank_name`, `sms_code`) VALUES
('CBE', 'cbe1234567'),
('Awash', 'awash7654321'),
('Birhan', 'birhan32145678');

-- 3. Test password reset entry (for testing)
INSERT INTO `password_resets` (`user_id`, `contact`, `reset_code`, `expires_at`) VALUES
(15, 'chipchips145@gmail.com', '659650', DATE_ADD(NOW(), INTERVAL 10 MINUTE));