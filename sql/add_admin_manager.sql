-- Insert Admin and Manager users into users table
-- Run this SQL in your database to add admin and manager accounts

-- Add Admin user
INSERT INTO `users` (`email`, `phone`, `password`, `role`, `status`, `name`, `father_name`, `grandfather_name`) VALUES
('admin@ashrekapottery.com', '+251911000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'System Admin', 'Admin Father', 'Admin Grandfather');

-- Add Manager user  
INSERT INTO `users` (`email`, `phone`, `password`, `role`, `status`, `name`, `father_name`, `grandfather_name`) VALUES
('manager@ashrekapottery.com', '+251911000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active', 'System Manager', 'Manager Father', 'Manager Grandfather');

-- Default password for both accounts is: password
-- Change these passwords after first login for security

-- You can also add them manually with these credentials:
-- Admin Login: admin@ashrekapottery.com / password
-- Manager Login: manager@ashrekapottery.com / password