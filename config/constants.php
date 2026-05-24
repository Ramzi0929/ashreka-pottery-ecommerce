<?php
// System Constants
define('SITE_NAME', 'Ashreka Pottery');
define('UPLOAD_PATH', 'assets/uploads/');
define('PRODUCT_IMAGE_PATH', UPLOAD_PATH . 'products/');
define('PROFILE_IMAGE_PATH', UPLOAD_PATH . 'profiles/');
define('HERITAGE_PATH', UPLOAD_PATH . 'heritage/');

// User Roles
define('ROLE_ARTISAN', 'artisan');
define('ROLE_CUSTOMER', 'customer');
define('ROLE_MANAGER', 'manager');
define('ROLE_ADMIN', 'admin');

// Product Status
define('PRODUCT_DRAFT', 'draft');
define('PRODUCT_PENDING', 'pending');
define('PRODUCT_APPROVED', 'approved');
define('PRODUCT_REJECTED', 'rejected');

// Order Status
define('ORDER_PENDING', 'pending');
define('ORDER_APPROVED', 'approved');
define('ORDER_REJECTED', 'rejected');
define('ORDER_IN_PROGRESS', 'in_progress');
define('ORDER_COMPLETED', 'completed');
define('ORDER_DELIVERED', 'delivered');

// Payment Status
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_PARTIAL', 'partial');
define('PAYMENT_COMPLETED', 'completed');

// File upload constraints
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'mov', 'avi']);
?>