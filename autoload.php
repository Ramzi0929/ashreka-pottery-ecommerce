<?php
// Autoloader for Ashreka Pottery System

spl_autoload_register(function ($className) {
    $directories = [
        'controllers/',
        'models/',
        'includes/',
        'config/'
    ];
    
    foreach ($directories as $directory) {
        $file = __DIR__ . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load configuration
require_once __DIR__ . '/config/database_enhanced.php';
require_once __DIR__ . '/includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Africa/Addis_Ababa');

// Error reporting (disable in production)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CSRF protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['csrf_token'])) {
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
?>