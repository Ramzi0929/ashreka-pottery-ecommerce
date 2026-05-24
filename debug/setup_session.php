<?php
session_start();
require_once '../config/database_enhanced.php';

// Set up test session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'customer';
$_SESSION['customer_id'] = 1;
$_SESSION['cart'] = [1 => 1]; // Test cart with product ID 1

echo json_encode(['success' => true, 'message' => 'Session setup complete']);
?>