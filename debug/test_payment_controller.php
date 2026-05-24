<?php
// Test PaymentWorkflowController Response
session_start();

// Set up test session data
$_SESSION['user_id'] = 15;
$_SESSION['role'] = 'customer';
$_SESSION['customer_id'] = 13;
$_SESSION['cart'] = [1 => 1]; // Product ID 1, quantity 1

echo "<h2>Testing PaymentWorkflowController</h2>";

// Simulate the exact request from checkout
$_POST['action'] = 'send_payment_instructions';
$_POST['order_id'] = 999;
$_POST['email'] = 'test@example.com';
$_POST['phone'] = '+251911234567';
$_POST['payment_method'] = 'telebirr';
$_POST['bank_name'] = '';
$_POST['total_amount'] = 100;

echo "<h3>Request Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Response from PaymentWorkflowController:</h3>";
echo "<div style='border:1px solid #ccc; padding:10px; background:#f9f9f9;'>";

// Capture the output
ob_start();
try {
    include '../controllers/PaymentWorkflowController.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
$output = ob_get_clean();

echo "Raw Output: " . htmlspecialchars($output);
echo "</div>";

echo "<h3>Analysis:</h3>";
if (strpos($output, '{') === 0) {
    echo "<span style='color:green'>✓ Looks like valid JSON</span>";
} else {
    echo "<span style='color:red'>✗ Not JSON - contains HTML/PHP errors</span>";
}
?>