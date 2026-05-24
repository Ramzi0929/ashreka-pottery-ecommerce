-- Bank SMS Codes Table
CREATE TABLE IF NOT EXISTS bank_sms_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_name VARCHAR(100) NOT NULL UNIQUE,
    sms_code VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert 3 working Ethiopian banks
INSERT INTO bank_sms_codes (bank_name, sms_code, is_active) VALUES
('Commercial Bank of Ethiopia', '*847#', TRUE),
('Dashen Bank', '*804#', TRUE),
('Awash Bank', '*847#', TRUE)
ON DUPLICATE KEY UPDATE 
    sms_code = VALUES(sms_code),
    is_active = VALUES(is_active);