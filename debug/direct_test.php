<?php
session_start();
require_once '../config/database_enhanced.php';

// Set up test session and data
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'customer';
$_SESSION['customer_id'] = 1;
$_SESSION['cart'] = [1 => 1];

echo "<h1>🔥 DIRECT SMS/EMAIL TEST</h1>";

// Test 1: Direct email sending
echo "<h2>Test 1: Email Sending</h2>";
$_POST = [
    'action' => 'send_payment_instructions',
    'order_id' => 99999,
    'email' => 'test@example.com',
    'phone' => '+251912345678',
    'payment_method' => 'telebirr',
    'total_amount' => 100
];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include '../controllers/PaymentWorkflowController.php';
$output = ob_get_clean();

echo "Result: <pre>" . htmlspecialchars($output) . "</pre>";

$result = json_decode($output, true);
if ($result && $result['success']) {
    echo "✅ EMAIL/SMS SYSTEM WORKS!<br>";
} else {
    echo "❌ EMAIL/SMS FAILED<br>";
}

// Test 2: Check what's in the database
echo "<h2>Test 2: Database Check</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM payment_confirmations ORDER BY created_at DESC LIMIT 5");
    $confirmations = $stmt->fetchAll();
    
    if ($confirmations) {
        echo "Recent confirmation codes:<br>";
        foreach ($confirmations as $conf) {
            echo "Order: {$conf['order_id']}, Code: {$conf['confirm_code']}, Email: {$conf['email']}<br>";
        }
    } else {
        echo "No confirmation codes found<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 CONCLUSION</h2>";
echo "If you see 'EMAIL/SMS SYSTEM WORKS!' above, then your backend is fine.<br>";
echo "The issue is in the checkout page frontend JavaScript or user flow.<br>";
echo "Check browser console for JavaScript errors when using the actual checkout page.";
?>