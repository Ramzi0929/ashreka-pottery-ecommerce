<?php
// Test PaymentWorkflowController Response - Updated
session_start();

// Set up test session data
$_SESSION['user_id'] = 15;
$_SESSION['role'] = 'customer';
$_SESSION['customer_id'] = 13;
$_SESSION['cart'] = [1 => 1];

echo "<h2>Testing PaymentWorkflowController - Updated</h2>";

// Test syntax first
echo "<h3>1. Syntax Check</h3>";
$syntax_check = shell_exec('php -l ../controllers/PaymentWorkflowController.php 2>&1');
echo "<pre>" . htmlspecialchars($syntax_check) . "</pre>";

if (strpos($syntax_check, 'No syntax errors') !== false) {
    echo "<span style='color:green'>✓ Syntax OK</span><br><br>";
    
    // Now test the actual request
    echo "<h3>2. Request Test</h3>";
    $_POST['action'] = 'send_payment_instructions';
    $_POST['order_id'] = 999;
    $_POST['email'] = 'test@example.com';
    $_POST['phone'] = '+251911234567';
    $_POST['payment_method'] = 'telebirr';
    $_POST['bank_name'] = '';
    $_POST['total_amount'] = 100;
    
    echo "<div style='border:1px solid #ccc; padding:10px; background:#f9f9f9;'>";
    ob_start();
    try {
        include '../controllers/PaymentWorkflowController.php';
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage();
    }
    $output = ob_get_clean();
    
    echo "Raw Output: " . htmlspecialchars($output);
    echo "</div>";
    
    if (strpos($output, '{') === 0) {
        echo "<span style='color:green'>✓ Valid JSON response</span>";
    } else {
        echo "<span style='color:red'>✗ Not JSON - still has errors</span>";
    }
} else {
    echo "<span style='color:red'>✗ Syntax errors found</span>";
}
?>