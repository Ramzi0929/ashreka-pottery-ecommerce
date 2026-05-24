-- Pre-registration OTP system tables
CREATE TABLE IF NOT EXISTS `pre_registration_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `grandfather_name` varchar(100) NOT NULL,
  `contact_type` enum('phone','email') NOT NULL,
  `contact_value` varchar(255) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `blocked_until` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_value` (`contact_value`),
  KEY `expires_at` (`expires_at`),
  KEY `is_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;