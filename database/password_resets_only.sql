-- Password Resets Table for Login Page
CREATE TABLE IF NOT EXISTS `password_resets` (
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