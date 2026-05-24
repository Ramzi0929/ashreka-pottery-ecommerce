<?php
// Real-time checkout monitor - captures all errors and debug info
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
require_once '../config/database_enhanced.php';

// Force test session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'customer';
$_SESSION['customer_id'] = 1;
$_SESSION['cart'] = [1 => 1]; // Test cart

class CheckoutMonitor {
    private $pdo;
    private $logFile;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->logFile = 'checkout_debug.log';
        file_put_contents($this->logFile, "=== CHECKOUT MONITOR STARTED ===\n", FILE_APPEND);
    }
    
    public function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message\n", FILE_APPEND);
        echo "<div class='log-entry'>[$timestamp] $message</div>";
        flush();
    }
    
    public function testEmailSending() {
        $this->log("🧪 TESTING EMAIL SENDING");
        
        $testData = [
            'order_id' => 99999,
            'email' => 'test@example.com',
            'phone' => '+251912345678',
            'payment_method' => 'telebirr',
            'total_amount' => 100
        ];
        
        // Capture all output and errors
        ob_start();
        $errorOutput = '';
        
        try {
            // Test PaymentWorkflowController directly
            $_POST = array_merge(['action' => 'send_payment_instructions'], $testData);
            
            $this->log("POST data: " . json_encode($_POST));
            
            // Include and capture output
            include '../controllers/PaymentWorkflowController.php';
            $output = ob_get_contents();
            
            $this->log("Controller output: " . $output);
            
            // Try to parse JSON response
            $result = json_decode($output, true);
            if ($result) {
                $this->log("Parsed result: " . json_encode($result));
                if (isset($result['success']) && $result['success']) {
                    $this->log("✅ EMAIL TEST PASSED");
                } else {
                    $this->log("❌ EMAIL TEST FAILED: " . ($result['message'] ?? 'Unknown error'));
                }
            } else {
                $this->log("❌ INVALID JSON RESPONSE");
            }
            
        } catch (Exception $e) {
            $this->log("❌ EXCEPTION: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
        } finally {
            ob_end_clean();
        }
    }
    
    public function testOrderCreation() {
        $this->log("🧪 TESTING ORDER CREATION");
        
        try {
            $_POST = ['action' => 'create_order'];
            
            ob_start();
            include '../api/orders.php';
            $output = ob_get_contents();
            ob_end_clean();
            
            $this->log("Orders API output: " . $output);
            
            $result = json_decode($output, true);
            if ($result && isset($result['success'])) {
                if ($result['success']) {
                    $this->log("✅ ORDER CREATION PASSED - Order ID: " . $result['order_id']);
                    return $result['order_id'];
                } else {
                    $this->log("❌ ORDER CREATION FAILED: " . $result['message']);
                }
            } else {
                $this->log("❌ INVALID ORDER API RESPONSE");
            }
        } catch (Exception $e) {
            $this->log("❌ ORDER CREATION EXCEPTION: " . $e->getMessage());
        }
        
        return null;
    }
    
    public function testConfirmationCode($orderId) {
        $this->log("🧪 TESTING CONFIRMATION CODE");
        
        if (!$orderId) {
            $this->log("❌ NO ORDER ID PROVIDED");
            return null;
        }
        
        try {
            // Generate test confirmation code
            $confirmCode = sprintf('%06d', mt_rand(0, 999999));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_confirmations (order_id, confirm_code, email, phone, status, created_at)
                VALUES (?, ?, 'test@example.com', '+251912345678', 'pending', NOW())
            ");
            $stmt->execute([$orderId, $confirmCode]);
            
            $this->log("✅ CONFIRMATION CODE CREATED: $confirmCode");
            
            // Test verification
            $_POST = [
                'action' => 'verify_confirm_code',
                'order_id' => $orderId,
                'confirm_code' => $confirmCode
            ];
            
            ob_start();
            include '../controllers/PaymentWorkflowController.php';
            $output = ob_get_contents();
            ob_end_clean();
            
            $this->log("Verify code output: " . $output);
            
            $result = json_decode($output, true);
            if ($result && isset($result['success']) && $result['success']) {
                $this->log("✅ CODE VERIFICATION PASSED");
                return $confirmCode;
            } else {
                $this->log("❌ CODE VERIFICATION FAILED");
            }
            
        } catch (Exception $e) {
            $this->log("❌ CONFIRMATION CODE EXCEPTION: " . $e->getMessage());
        }
        
        return null;
    }
    
    public function testReceiptUpload($orderId) {
        $this->log("🧪 TESTING RECEIPT UPLOAD");
        
        if (!$orderId) {
            $this->log("❌ NO ORDER ID PROVIDED");
            return;
        }
        
        try {
            // Create test image file
            $testImage = imagecreate(100, 100);
            $white = imagecolorallocate($testImage, 255, 255, 255);
            $black = imagecolorallocate($testImage, 0, 0, 0);
            imagestring($testImage, 5, 10, 40, 'TEST', $black);
            
            $tempFile = tempnam(sys_get_temp_dir(), 'receipt_test');
            imagejpeg($testImage, $tempFile);
            imagedestroy($testImage);
            
            // Simulate file upload
            $_FILES['receipt_image'] = [
                'name' => 'test_receipt.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tempFile,
                'error' => 0,
                'size' => filesize($tempFile)
            ];
            
            $_POST = [
                'action' => 'submit_receipt',
                'order_id' => $orderId,
                'payment_method' => 'telebirr',
                'email' => 'test@example.com'
            ];
            
            ob_start();
            include '../controllers/PaymentWorkflowController.php';
            $output = ob_get_contents();
            ob_end_clean();
            
            $this->log("Receipt upload output: " . $output);
            
            $result = json_decode($output, true);
            if ($result && isset($result['success']) && $result['success']) {
                $this->log("✅ RECEIPT UPLOAD PASSED");
            } else {
                $this->log("❌ RECEIPT UPLOAD FAILED");
            }
            
            // Cleanup
            unlink($tempFile);
            
        } catch (Exception $e) {
            $this->log("❌ RECEIPT UPLOAD EXCEPTION: " . $e->getMessage());
        }
    }
    
    public function checkDatabaseState() {
        $this->log("🧪 CHECKING DATABASE STATE");
        
        try {
            // Check recent orders
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $recentOrders = $stmt->fetchColumn();
            $this->log("Recent orders (last hour): $recentOrders");
            
            // Check payment confirmations
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM payment_confirmations WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $recentConfirms = $stmt->fetchColumn();
            $this->log("Recent confirmations (last hour): $recentConfirms");
            
            // Check for errors in recent data
            $stmt = $this->pdo->query("SELECT * FROM payment_confirmations ORDER BY created_at DESC LIMIT 5");
            $recent = $stmt->fetchAll();
            foreach ($recent as $row) {
                $this->log("Recent confirmation: ID={$row['id']}, Code={$row['confirm_code']}, Status={$row['status']}");
            }
            
        } catch (Exception $e) {
            $this->log("❌ DATABASE CHECK EXCEPTION: " . $e->getMessage());
        }
    }
    
    public function checkServerConfiguration() {
        $this->log("🧪 CHECKING SERVER CONFIGURATION");
        
        // PHP configuration
        $this->log("PHP Version: " . phpversion());
        $this->log("Upload max filesize: " . ini_get('upload_max_filesize'));
        $this->log("Post max size: " . ini_get('post_max_size'));
        $this->log("Memory limit: " . ini_get('memory_limit'));
        $this->log("Max execution time: " . ini_get('max_execution_time'));
        
        // Extensions
        $extensions = ['curl', 'gd', 'pdo_mysql', 'openssl'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->log("✅ Extension $ext loaded");
            } else {
                $this->log("❌ Extension $ext NOT loaded");
            }
        }
        
        // File permissions
        $dirs = ['../uploads/', '../uploads/receipts/'];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                if (is_writable($dir)) {
                    $this->log("✅ Directory $dir is writable");
                } else {
                    $this->log("❌ Directory $dir is NOT writable");
                }
            } else {
                $this->log("⚠️ Directory $dir does not exist");
            }
        }
    }
    
    public function runFullTest() {
        $this->log("🚀 STARTING FULL CHECKOUT TEST");
        
        $this->checkServerConfiguration();
        $this->checkDatabaseState();
        
        $orderId = $this->testOrderCreation();
        $this->testEmailSending();
        
        if ($orderId) {
            $confirmCode = $this->testConfirmationCode($orderId);
            if ($confirmCode) {
                $this->testReceiptUpload($orderId);
            }
        }
        
        $this->log("🏁 FULL TEST COMPLETED");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout Monitor - Live Debug</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #1a1a1a; color: #00ff00; font-family: 'Courier New', monospace; }
        .log-entry { 
            margin: 2px 0; 
            padding: 5px; 
            border-left: 3px solid #00ff00; 
            background: rgba(0,255,0,0.1);
        }
        .container { max-width: 1200px; }
        h1 { color: #ff0000; text-align: center; }
        .btn { margin: 5px; }
        #logOutput { 
            height: 500px; 
            overflow-y: auto; 
            background: #000; 
            padding: 10px; 
            border: 1px solid #00ff00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 CHECKOUT MONITOR - LIVE DEBUG 🔥</h1>
        
        <div class="row mb-3">
            <div class="col">
                <button onclick="runTest('full')" class="btn btn-danger">🚀 Full Test</button>
                <button onclick="runTest('email')" class="btn btn-warning">📧 Email Test</button>
                <button onclick="runTest('order')" class="btn btn-info">🛒 Order Test</button>
                <button onclick="runTest('config')" class="btn btn-secondary">⚙️ Config Check</button>
                <button onclick="clearLog()" class="btn btn-dark">🗑️ Clear Log</button>
            </div>
        </div>
        
        <div id="logOutput">
            <div class="log-entry">[READY] Checkout Monitor initialized. Click a test button to start.</div>
        </div>
    </div>

    <script>
        function runTest(type) {
            const output = document.getElementById('logOutput');
            output.innerHTML += '<div class="log-entry">[STARTING] Running ' + type + ' test...</div>';
            
            fetch('?test=' + type)
                .then(response => response.text())
                .then(data => {
                    output.innerHTML += data;
                    output.scrollTop = output.scrollHeight;
                })
                .catch(error => {
                    output.innerHTML += '<div class="log-entry">[ERROR] ' + error + '</div>';
                });
        }
        
        function clearLog() {
            document.getElementById('logOutput').innerHTML = '<div class="log-entry">[CLEARED] Log cleared.</div>';
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            if (document.getElementById('logOutput').children.length > 100) {
                clearLog();
            }
        }, 30000);
    </script>

    <?php
    if (isset($_GET['test'])) {
        $monitor = new CheckoutMonitor($pdo);
        
        switch ($_GET['test']) {
            case 'full':
                $monitor->runFullTest();
                break;
            case 'email':
                $monitor->testEmailSending();
                break;
            case 'order':
                $monitor->testOrderCreation();
                break;
            case 'config':
                $monitor->checkServerConfiguration();
                break;
        }
    }
    ?>
</body>
</html>