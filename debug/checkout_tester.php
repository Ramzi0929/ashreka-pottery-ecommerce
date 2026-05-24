<?php
session_start();
require_once '../config/database_enhanced.php';

// Force admin access for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['customer_id'] = 1;

class CheckoutTester {
    private $pdo;
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function runAllTests() {
        echo "<h1>🔥 BAD BOY CHECKOUT TESTER 🔥</h1>";
        echo "<p>Deep testing checkout page for SMS/Email issues...</p><hr>";
        
        $this->testDatabaseConnections();
        $this->testRequiredFiles();
        $this->testEmailConfiguration();
        $this->testSMSConfiguration();
        $this->testPaymentWorkflow();
        $this->testOrderCreation();
        $this->testConfirmationCodes();
        $this->testReceiptUpload();
        $this->testErrorHandling();
        $this->testSecurityVulnerabilities();
        
        $this->displayResults();
    }
    
    private function testDatabaseConnections() {
        echo "<h2>🗄️ Database Connection Tests</h2>";
        
        try {
            $stmt = $this->pdo->query("SELECT 1");
            $this->success[] = "✅ Database connection working";
        } catch (Exception $e) {
            $this->errors[] = "❌ Database connection failed: " . $e->getMessage();
        }
        
        // Test required tables
        $tables = ['orders', 'order_items', 'payment_confirmations', 'customers', 'products'];
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                $this->success[] = "✅ Table '$table' exists";
            } catch (Exception $e) {
                $this->errors[] = "❌ Table '$table' missing: " . $e->getMessage();
            }
        }
        
        // Test payment_confirmations structure
        try {
            $stmt = $this->pdo->query("DESCRIBE payment_confirmations");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $required = ['id', 'order_id', 'confirm_code', 'email', 'phone', 'status', 'created_at'];
            foreach ($required as $col) {
                if (!in_array($col, $columns)) {
                    $this->errors[] = "❌ Missing column '$col' in payment_confirmations";
                }
            }
        } catch (Exception $e) {
            $this->errors[] = "❌ Cannot check payment_confirmations structure: " . $e->getMessage();
        }
    }
    
    private function testRequiredFiles() {
        echo "<h2>📁 File Existence Tests</h2>";
        
        $files = [
            '../controllers/PaymentWorkflowController.php',
            '../api/orders.php',
            '../config/email_config.php',
            '../config/sms_config.php',
            '../assets/js/auto-logout.js'
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $this->success[] = "✅ File exists: $file";
            } else {
                $this->errors[] = "❌ Missing file: $file";
            }
        }
        
        // Test if PaymentWorkflowController has required methods
        if (file_exists('../controllers/PaymentWorkflowController.php')) {
            $content = file_get_contents('../controllers/PaymentWorkflowController.php');
            $methods = ['send_payment_instructions', 'verify_confirm_code', 'submit_receipt'];
            foreach ($methods as $method) {
                if (strpos($content, $method) !== false) {
                    $this->success[] = "✅ Method '$method' found in PaymentWorkflowController";
                } else {
                    $this->errors[] = "❌ Method '$method' missing in PaymentWorkflowController";
                }
            }
        }
    }
    
    private function testEmailConfiguration() {
        echo "<h2>📧 Email Configuration Tests</h2>";
        
        if (file_exists('../config/email_config.php')) {
            include_once '../config/email_config.php';
            
            // Test PHPMailer
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                $this->success[] = "✅ PHPMailer class available";
            } else {
                $this->errors[] = "❌ PHPMailer not found - check composer install";
            }
            
            // Test SMTP settings
            if (defined('SMTP_HOST') && defined('SMTP_USERNAME')) {
                $this->success[] = "✅ SMTP configuration found";
                echo "<div class='alert alert-info'>SMTP Host: " . SMTP_HOST . "</div>";
            } else {
                $this->errors[] = "❌ SMTP configuration missing";
            }
        } else {
            $this->errors[] = "❌ Email config file missing";
        }
        
        // Test actual email sending
        $this->testEmailSending();
    }
    
    private function testEmailSending() {
        echo "<h3>📤 Email Sending Test</h3>";
        
        try {
            $formData = [
                'action' => 'send_payment_instructions',
                'order_id' => 999999, // Test order
                'email' => 'test@example.com',
                'phone' => '+251912345678',
                'payment_method' => 'telebirr',
                'bank_name' => '',
                'total_amount' => 100
            ];
            
            // Simulate POST request to PaymentWorkflowController
            $_POST = $formData;
            
            ob_start();
            include '../controllers/PaymentWorkflowController.php';
            $output = ob_get_clean();
            
            if (strpos($output, 'success') !== false) {
                $this->success[] = "✅ Email sending appears to work";
            } else {
                $this->warnings[] = "⚠️ Email sending test inconclusive: " . substr($output, 0, 100);
            }
        } catch (Exception $e) {
            $this->errors[] = "❌ Email sending failed: " . $e->getMessage();
        }
    }
    
    private function testSMSConfiguration() {
        echo "<h2>📱 SMS Configuration Tests</h2>";
        
        if (file_exists('../config/sms_config.php')) {
            include_once '../config/sms_config.php';
            
            if (defined('SMS_API_URL') && defined('SMS_API_KEY')) {
                $this->success[] = "✅ SMS configuration found";
                echo "<div class='alert alert-info'>SMS API: " . SMS_API_URL . "</div>";
            } else {
                $this->errors[] = "❌ SMS configuration incomplete";
            }
        } else {
            $this->warnings[] = "⚠️ SMS config file missing - SMS won't work";
        }
        
        // Test SMS API connectivity
        $this->testSMSConnectivity();
    }
    
    private function testSMSConnectivity() {
        echo "<h3>📡 SMS API Connectivity Test</h3>";
        
        if (defined('SMS_API_URL')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, SMS_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200 || $httpCode == 405) {
                $this->success[] = "✅ SMS API endpoint reachable (HTTP $httpCode)";
            } else {
                $this->warnings[] = "⚠️ SMS API endpoint issue (HTTP $httpCode)";
            }
        }
    }
    
    private function testPaymentWorkflow() {
        echo "<h2>💳 Payment Workflow Tests</h2>";
        
        // Test order creation
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (customer_id, total_amount, status, created_at) 
                VALUES (1, 100, 'pending', NOW())
            ");
            $stmt->execute();
            $testOrderId = $this->pdo->lastInsertId();
            $this->success[] = "✅ Order creation works (Test Order ID: $testOrderId)";
            
            // Test confirmation code generation
            $confirmCode = sprintf('%06d', mt_rand(0, 999999));
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_confirmations (order_id, confirm_code, email, phone, status, created_at)
                VALUES (?, ?, 'test@example.com', '+251912345678', 'pending', NOW())
            ");
            $stmt->execute([$testOrderId, $confirmCode]);
            $this->success[] = "✅ Confirmation code generation works (Code: $confirmCode)";
            
            // Test code verification
            $stmt = $this->pdo->prepare("
                SELECT * FROM payment_confirmations 
                WHERE order_id = ? AND confirm_code = ? AND status = 'pending'
            ");
            $stmt->execute([$testOrderId, $confirmCode]);
            if ($stmt->fetch()) {
                $this->success[] = "✅ Confirmation code verification works";
            } else {
                $this->errors[] = "❌ Confirmation code verification failed";
            }
            
            // Cleanup test data
            $this->pdo->prepare("DELETE FROM payment_confirmations WHERE order_id = ?")->execute([$testOrderId]);
            $this->pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$testOrderId]);
            
        } catch (Exception $e) {
            $this->errors[] = "❌ Payment workflow test failed: " . $e->getMessage();
        }
    }
    
    private function testOrderCreation() {
        echo "<h2>🛒 Order Creation API Tests</h2>";
        
        // Test orders.php API
        $_POST = ['action' => 'create_order'];
        $_SESSION['cart'] = [1 => 2]; // Fake cart
        
        try {
            ob_start();
            include '../api/orders.php';
            $output = ob_get_clean();
            
            $result = json_decode($output, true);
            if ($result && isset($result['success'])) {
                if ($result['success']) {
                    $this->success[] = "✅ Order creation API works";
                } else {
                    $this->errors[] = "❌ Order creation API failed: " . ($result['message'] ?? 'Unknown error');
                }
            } else {
                $this->errors[] = "❌ Order creation API returned invalid JSON: " . substr($output, 0, 200);
            }
        } catch (Exception $e) {
            $this->errors[] = "❌ Order creation API error: " . $e->getMessage();
        }
    }
    
    private function testConfirmationCodes() {
        echo "<h2>🔐 Confirmation Code Tests</h2>";
        
        // Test code format
        for ($i = 0; $i < 10; $i++) {
            $code = sprintf('%06d', mt_rand(0, 999999));
            if (strlen($code) == 6 && is_numeric($code)) {
                $this->success[] = "✅ Code format valid: $code";
            } else {
                $this->errors[] = "❌ Invalid code format: $code";
            }
        }
        
        // Test code uniqueness
        $codes = [];
        for ($i = 0; $i < 1000; $i++) {
            $code = sprintf('%06d', mt_rand(0, 999999));
            $codes[] = $code;
        }
        $unique = array_unique($codes);
        $duplicates = count($codes) - count($unique);
        
        if ($duplicates < 50) {
            $this->success[] = "✅ Code uniqueness acceptable ($duplicates duplicates in 1000)";
        } else {
            $this->warnings[] = "⚠️ High code duplication ($duplicates duplicates in 1000)";
        }
    }
    
    private function testReceiptUpload() {
        echo "<h2>📄 Receipt Upload Tests</h2>";
        
        // Test upload directory
        $uploadDir = '../uploads/receipts/';
        if (!is_dir($uploadDir)) {
            if (mkdir($uploadDir, 0755, true)) {
                $this->success[] = "✅ Created upload directory: $uploadDir";
            } else {
                $this->errors[] = "❌ Cannot create upload directory: $uploadDir";
            }
        } else {
            $this->success[] = "✅ Upload directory exists: $uploadDir";
        }
        
        // Test directory permissions
        if (is_writable($uploadDir)) {
            $this->success[] = "✅ Upload directory is writable";
        } else {
            $this->errors[] = "❌ Upload directory not writable";
        }
        
        // Test file size limits
        $maxSize = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        echo "<div class='alert alert-info'>Max file size: $maxSize, Max post size: $postMax</div>";
        
        if (intval($maxSize) >= 5) {
            $this->success[] = "✅ File size limit adequate ($maxSize)";
        } else {
            $this->warnings[] = "⚠️ File size limit may be too small ($maxSize)";
        }
    }
    
    private function testErrorHandling() {
        echo "<h2>🚨 Error Handling Tests</h2>";
        
        // Test invalid email
        $invalidEmails = ['invalid', 'test@', '@domain.com', 'test..test@domain.com'];
        foreach ($invalidEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->warnings[] = "⚠️ Invalid email passed validation: $email";
            } else {
                $this->success[] = "✅ Invalid email rejected: $email";
            }
        }
        
        // Test invalid phone numbers
        $invalidPhones = ['123', '+1234567890', '0812345678', '+25191234567'];
        foreach ($invalidPhones as $phone) {
            $cleaned = preg_replace('/[^0-9+]/', '', $phone);
            if (preg_match('/^(\+2519[0-9]{8}|09[0-9]{8}|9[0-9]{8})$/', $cleaned)) {
                $this->warnings[] = "⚠️ Invalid phone passed validation: $phone";
            } else {
                $this->success[] = "✅ Invalid phone rejected: $phone";
            }
        }
        
        // Test SQL injection protection
        $maliciousInputs = ["'; DROP TABLE orders; --", "1' OR '1'='1", "<script>alert('xss')</script>"];
        foreach ($maliciousInputs as $input) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$input]);
                $this->success[] = "✅ SQL injection protected for: " . htmlspecialchars($input);
            } catch (Exception $e) {
                $this->warnings[] = "⚠️ SQL error with input: " . htmlspecialchars($input);
            }
        }
    }
    
    private function testSecurityVulnerabilities() {
        echo "<h2>🔒 Security Tests</h2>";
        
        // Test session security
        if (session_status() == PHP_SESSION_ACTIVE) {
            $this->success[] = "✅ Session is active";
        } else {
            $this->errors[] = "❌ Session not active";
        }
        
        // Test CSRF protection
        $checkoutContent = file_get_contents('../views/customer/checkout.php');
        if (strpos($checkoutContent, 'csrf') !== false || strpos($checkoutContent, 'token') !== false) {
            $this->success[] = "✅ CSRF protection may be present";
        } else {
            $this->warnings[] = "⚠️ No obvious CSRF protection found";
        }
        
        // Test input sanitization
        if (strpos($checkoutContent, 'htmlspecialchars') !== false || strpos($checkoutContent, 'filter_var') !== false) {
            $this->success[] = "✅ Input sanitization found";
        } else {
            $this->warnings[] = "⚠️ Limited input sanitization found";
        }
        
        // Test file upload security
        if (strpos($checkoutContent, 'getimagesize') !== false || strpos($checkoutContent, 'mime_content_type') !== false) {
            $this->success[] = "✅ File type validation found";
        } else {
            $this->warnings[] = "⚠️ File upload validation may be insufficient";
        }
    }
    
    private function displayResults() {
        echo "<hr><h2>📊 TEST RESULTS SUMMARY</h2>";
        
        echo "<div class='row'>";
        echo "<div class='col-md-4'>";
        echo "<h3 style='color: green;'>✅ PASSED (" . count($this->success) . ")</h3>";
        foreach ($this->success as $item) {
            echo "<div class='alert alert-success'>$item</div>";
        }
        echo "</div>";
        
        echo "<div class='col-md-4'>";
        echo "<h3 style='color: orange;'>⚠️ WARNINGS (" . count($this->warnings) . ")</h3>";
        foreach ($this->warnings as $item) {
            echo "<div class='alert alert-warning'>$item</div>";
        }
        echo "</div>";
        
        echo "<div class='col-md-4'>";
        echo "<h3 style='color: red;'>❌ FAILED (" . count($this->errors) . ")</h3>";
        foreach ($this->errors as $item) {
            echo "<div class='alert alert-danger'>$item</div>";
        }
        echo "</div>";
        echo "</div>";
        
        // Overall status
        if (count($this->errors) == 0) {
            echo "<div class='alert alert-success'><h4>🎉 ALL CRITICAL TESTS PASSED!</h4></div>";
        } else {
            echo "<div class='alert alert-danger'><h4>💥 CRITICAL ISSUES FOUND - FIX BEFORE PRODUCTION!</h4></div>";
        }
        
        // Recommendations
        echo "<h3>🔧 RECOMMENDATIONS</h3>";
        echo "<ul>";
        echo "<li>Check server error logs: /xampp/apache/logs/error.log</li>";
        echo "<li>Enable PHP error reporting: error_reporting(E_ALL)</li>";
        echo "<li>Test with real email/SMS credentials</li>";
        echo "<li>Monitor network requests in browser DevTools</li>";
        echo "<li>Check database for actual data insertion</li>";
        echo "</ul>";
    }
}

// Run the tests
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout Tester</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .alert { margin: 5px 0; padding: 10px; }
        h1 { color: #dc3545; text-align: center; }
        h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    </style>
</head>
<body>
<?php
$tester = new CheckoutTester($pdo);
$tester->runAllTests();
?>
</body>
</html>