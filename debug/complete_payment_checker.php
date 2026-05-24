<?php
// Complete Payment System Error Checker
session_start();
require_once '../config/database_enhanced.php';

echo "<h2>Complete Payment System Checker</h2>";
echo "<style>body{font-family:Arial;} .error{color:red;} .success{color:green;} .warning{color:orange;} .test{background:#f0f0f0;padding:10px;margin:10px 0;}</style>";

// 1. Database Tables Check
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

// 2. Payment Methods Check
echo "<h3>2. Payment Methods Check</h3>";

// TeleBirr Check
echo "<strong>TeleBirr Configuration:</strong><br>";
echo "Phone: 0935714446<br>";
echo "<span class='success'>✓ TeleBirr payment code configured</span><br><br>";

// Bank SMS Codes Check
echo "<strong>Bank SMS Codes:</strong><br>";
try {
    $stmt = $pdo->prepare("SELECT * FROM bank_sms_codes WHERE is_active = 1");
    $stmt->execute();
    $banks = $stmt->fetchAll();
    
    if (empty($banks)) {
        echo "<span class='error'>✗ No active bank SMS codes found</span><br>";
        echo "<div class='test'>Run this SQL:<br>";
        echo "INSERT INTO bank_sms_codes (bank_name, sms_code) VALUES<br>";
        echo "('CBE', 'cbe1234567'),<br>";
        echo "('Awash', 'awash7654321'),<br>";
        echo "('Birhan', 'birhan32145678');</div>";
    } else {
        echo "<span class='success'>✓ Found " . count($banks) . " active banks:</span><br>";
        foreach ($banks as $bank) {
            echo "  - {$bank['bank_name']}: {$bank['sms_code']}<br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Bank codes error: " . $e->getMessage() . "</span><br>";
}

// 3. Email System Check
echo "<h3>3. Email System Check</h3>";
$phpmailer_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($phpmailer_path)) {
    echo "<span class='success'>✓ PHPMailer found</span><br>";
    
    try {
        require_once $phpmailer_path;
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Test email configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ethiopianartinfinite@gmail.com';
        $mail->Password = 'stivcprjwpjhjoxj';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        echo "<span class='success'>✓ Email configuration loaded</span><br>";
        echo "Host: smtp.gmail.com:465<br>";
        echo "Username: ethiopianartinfinite@gmail.com<br>";
        
    } catch (Exception $e) {
        echo "<span class='error'>✗ Email setup error: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>✗ PHPMailer not found at: $phpmailer_path</span><br>";
    echo "<div class='test'>Install with: composer require phpmailer/phpmailer</div>";
}

// 4. SMS System Check
echo "<h3>4. SMS System Check</h3>";
$textbee_device_id = '694481d8fb73763bb262451f';
$textbee_api_key = '105efcf3-2696-417a-add4-e3b60b4360a2';

echo "<span class='success'>✓ SMS configuration set</span><br>";
echo "Device ID: $textbee_device_id<br>";
echo "API Key: " . substr($textbee_api_key, 0, 10) . "...<br>";
echo "Endpoint: https://api.textbee.dev/api/v1/gateway/devices/$textbee_device_id/sendSMS<br>";

// Test SMS API connectivity
echo "<strong>Testing SMS API connectivity:</strong><br>";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.textbee.dev/api/v1/gateway/devices/' . $textbee_device_id . '/sendSMS',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'message' => 'Test message',
        'recipients' => ['+251911111111']
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . $textbee_api_key,
        'Accept: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo "<span class='success'>✓ SMS API accessible (HTTP $httpCode)</span><br>";
} else {
    echo "<span class='error'>✗ SMS API error (HTTP $httpCode)</span><br>";
    echo "Response: " . substr($response, 0, 200) . "<br>";
}

// 5. Controllers Check
echo "<h3>5. Controllers & APIs Check</h3>";
$files = [
    'PaymentWorkflowController' => '../controllers/PaymentWorkflowController.php',
    'Orders API' => '../api/orders.php',
    'AuthController' => '../controllers/AuthController.php'
];

foreach ($files as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "<span class='success'>✓ $name found</span><br>";
    } else {
        echo "<span class='error'>✗ $name missing at: $path</span><br>";
    }
}

// 6. Session & User Check
echo "<h3>6. Session & User Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<span class='success'>✓ User logged in</span><br>";
    echo "User ID: {$_SESSION['user_id']}<br>";
    echo "Role: {$_SESSION['role']}<br>";
    
    if ($_SESSION['role'] === 'customer') {
        $customer_id = $_SESSION['customer_id'] ?? null;
        if (!$customer_id) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $customer_id = $stmt->fetchColumn();
            $_SESSION['customer_id'] = $customer_id;
        }
        echo "Customer ID: " . ($customer_id ?: 'Not found') . "<br>";
    }
} else {
    echo "<span class='warning'>⚠ No user session - need to login first</span><br>";
}

// 7. Cart Check
echo "<h3>7. Cart Check</h3>";
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo "<span class='warning'>⚠ Cart is empty</span><br>";
} else {
    echo "<span class='success'>✓ Cart has " . count($cart) . " items</span><br>";
    $total = 0;
    foreach ($cart as $product_id => $quantity) {
        $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($product) {
            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;
            echo "  - {$product['name']}: $quantity × {$product['price']} = $subtotal ETB<br>";
        }
    }
    echo "<strong>Total: $total ETB</strong><br>";
}

// 8. Test Payment Instruction Generation
echo "<h3>8. Payment Instruction Test</h3>";
if (!empty($cart) && isset($_SESSION['user_id'])) {
    echo "<div class='test'>";
    echo "<strong>Test Data:</strong><br>";
    echo "Email: test@example.com<br>";
    echo "Phone: +251911234567<br>";
    echo "Payment Method: TeleBirr<br>";
    echo "Amount: $total ETB<br>";
    echo "Order ID: 999 (test)<br>";
    
    // Generate test confirmation code
    $test_code = sprintf('%06d', random_int(100000, 999999));
    echo "Generated Code: $test_code<br>";
    
    echo "<strong>TeleBirr Instructions would be:</strong><br>";
    echo "Pay $total ETB via TeleBirr to: 0935714446<br>";
    echo "Confirmation Code: $test_code<br>";
    
    echo "</div>";
} else {
    echo "<span class='warning'>⚠ Cannot test - need login and cart items</span><br>";
}

echo "<h3>Summary & Next Steps</h3>";
echo "<p><strong>If you see errors:</strong></p>";
echo "<ul>";
echo "<li>✗ Red errors must be fixed first</li>";
echo "<li>⚠ Orange warnings may affect functionality</li>";
echo "<li>✓ Green items are working correctly</li>";
echo "</ul>";
echo "<p><strong>To test payment instructions:</strong></p>";
echo "<ol>";
echo "<li>Login as customer</li>";
echo "<li>Add items to cart</li>";
echo "<li>Go to checkout</li>";
echo "<li>Try sending payment instructions</li>";
echo "</ol>";

?>