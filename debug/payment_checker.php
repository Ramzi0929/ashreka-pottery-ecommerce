<?php
// Payment System Error Checker
session_start();
require_once '../config/database_enhanced.php';

echo "<h2>Payment System Error Checker</h2>";
echo "<style>body{font-family:Arial;} .error{color:red;} .success{color:green;} .warning{color:orange;}</style>";

// 1. Check Database Tables
echo "<h3>1. Database Tables Check</h3>";

$tables = [
    'payment_confirmations' => "SELECT * FROM payment_confirmations LIMIT 1",
    'payment_receipts' => "SELECT * FROM payment_receipts LIMIT 1", 
    'bank_sms_codes' => "SELECT * FROM bank_sms_codes LIMIT 1",
    'orders' => "SELECT * FROM orders LIMIT 1",
    'users' => "SELECT * FROM users LIMIT 1"
];

foreach ($tables as $table => $query) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        echo "<span class='success'>✓ Table '$table' exists</span><br>";
    } catch (Exception $e) {
        echo "<span class='error'>✗ Table '$table' missing: " . $e->getMessage() . "</span><br>";
    }
}

// 2. Check Bank SMS Codes
echo "<h3>2. Bank SMS Codes Check</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM bank_sms_codes WHERE is_active = 1");
    $stmt->execute();
    $banks = $stmt->fetchAll();
    
    if (empty($banks)) {
        echo "<span class='error'>✗ No active bank SMS codes found</span><br>";
    } else {
        echo "<span class='success'>✓ Found " . count($banks) . " active banks</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Bank codes error: " . $e->getMessage() . "</span><br>";
}

// 3. Check PHPMailer
echo "<h3>3. PHPMailer Check</h3>";
$phpmailer_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($phpmailer_path)) {
    echo "<span class='success'>✓ PHPMailer found</span><br>";
} else {
    echo "<span class='error'>✗ PHPMailer not found</span><br>";
}

// 4. Check Controllers
echo "<h3>4. Controllers Check</h3>";
$controller_path = __DIR__ . '/../controllers/PaymentWorkflowController.php';
if (file_exists($controller_path)) {
    echo "<span class='success'>✓ PaymentWorkflowController found</span><br>";
} else {
    echo "<span class='error'>✗ PaymentWorkflowController missing</span><br>";
}

// 5. Check Session
echo "<h3>5. Session Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<span class='success'>✓ User logged in: {$_SESSION['role']}</span><br>";
} else {
    echo "<span class='warning'>⚠ No user session</span><br>";
}

// 6. Check Cart
echo "<h3>6. Cart Check</h3>";
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo "<span class='warning'>⚠ Cart is empty</span><br>";
} else {
    echo "<span class='success'>✓ Cart has " . count($cart) . " items</span><br>";
}

echo "<h3>Access this checker at:</h3>";
echo "<a href='http://localhost/ashreka-pottery-system%20advanced/debug/payment_checker.php'>http://localhost/ashreka-pottery-system%20advanced/debug/payment_checker.php</a>";

?>