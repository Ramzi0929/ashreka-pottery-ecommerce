<?php
session_start();
require_once '../config/database_enhanced.php';

// Set test session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'customer';
$_SESSION['customer_id'] = 1;
$_SESSION['cart'] = [1 => 1];

echo "<h1>🔍 CHECKOUT DIAGNOSTICS</h1>";

// 1. Check PaymentWorkflowController
echo "<h2>1. PaymentWorkflowController Test</h2>";
if (file_exists('../controllers/PaymentWorkflowController.php')) {
    echo "✅ File exists<br>";
    
    // Test email sending
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
    try {
        include '../controllers/PaymentWorkflowController.php';
        $output = ob_get_clean();
        echo "Controller output: <pre>" . htmlspecialchars($output) . "</pre>";
        
        $result = json_decode($output, true);
        if ($result) {
            if ($result['success']) {
                echo "✅ Email sending works<br>";
            } else {
                echo "❌ Email failed: " . $result['message'] . "<br>";
            }
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ PaymentWorkflowController.php missing<br>";
}

// 2. Check orders API
echo "<h2>2. Orders API Test</h2>";
if (file_exists('../api/orders.php')) {
    echo "✅ File exists<br>";
    
    $_POST = ['action' => 'create_order'];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    ob_start();
    try {
        include '../api/orders.php';
        $output = ob_get_clean();
        echo "Orders API output: <pre>" . htmlspecialchars($output) . "</pre>";
        
        $result = json_decode($output, true);
        if ($result) {
            if ($result['success']) {
                echo "✅ Order creation works<br>";
            } else {
                echo "❌ Order failed: " . $result['message'] . "<br>";
            }
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ orders.php missing<br>";
}

// 3. Check database tables
echo "<h2>3. Database Check</h2>";
try {
    $tables = ['orders', 'order_items', 'payment_confirmations', 'customers', 'products'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Table '$table': $count rows<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// 4. Check email config
echo "<h2>4. Email Configuration</h2>";
if (file_exists('../config/email_config.php')) {
    echo "✅ Email config exists<br>";
    include_once '../config/email_config.php';
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "✅ PHPMailer available<br>";
    } else {
        echo "❌ PHPMailer not found<br>";
    }
} else {
    echo "❌ Email config missing<br>";
}

// 5. Check file permissions
echo "<h2>5. File Permissions</h2>";
$dirs = ['../uploads/', '../uploads/receipts/'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo is_writable($dir) ? "✅" : "❌";
        echo " Directory $dir<br>";
    } else {
        echo "⚠️ Directory $dir missing<br>";
    }
}

echo "<h2>🎯 NEXT STEPS</h2>";
echo "1. Check browser console for JavaScript errors<br>";
echo "2. Check Apache error log: /xampp/apache/logs/error.log<br>";
echo "3. Enable PHP error reporting in checkout.php<br>";
?>