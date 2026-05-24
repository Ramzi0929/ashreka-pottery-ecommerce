<!DOCTYPE html>
<html>
<head>
    <title>Checkout Flow Simulator</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-panel { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 10px 0; }
        .log-output { background: #1a1a1a; color: #00ff00; font-family: monospace; padding: 15px; border-radius: 5px; height: 400px; overflow-y: auto; }
        .status-success { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .test-button { margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Checkout Flow Simulator</h1>
        <p>This tool simulates real user interactions with the checkout system to identify issues.</p>
        
        <div class="test-panel">
            <h3>Test Controls</h3>
            <button onclick="runFullFlow()" class="btn btn-primary test-button">🚀 Full Checkout Flow</button>
            <button onclick="testEmailValidation()" class="btn btn-info test-button">📧 Email Tests</button>
            <button onclick="testPhoneValidation()" class="btn btn-info test-button">📱 Phone Tests</button>
            <button onclick="testPaymentMethods()" class="btn btn-warning test-button">💳 Payment Methods</button>
            <button onclick="testErrorScenarios()" class="btn btn-danger test-button">💥 Error Scenarios</button>
            <button onclick="clearLog()" class="btn btn-secondary test-button">🗑️ Clear Log</button>
        </div>
        
        <div class="test-panel">
            <h3>Test Results</h3>
            <div id="testResults" class="log-output">
                <div>Ready to run tests...</div>
            </div>
        </div>
        
        <div class="test-panel">
            <h3>Live Checkout Page</h3>
            <iframe id="checkoutFrame" src="../views/customer/checkout.php" width="100%" height="600" style="border: 1px solid #ddd;"></iframe>
        </div>
    </div>

    <script>
        let testLog = [];
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const entry = `[${timestamp}] ${message}`;
            testLog.push({ timestamp, message, type });
            
            const output = document.getElementById('testResults');
            const div = document.createElement('div');
            div.className = `status-${type}`;
            div.textContent = entry;
            output.appendChild(div);
            output.scrollTop = output.scrollHeight;
        }
        
        function clearLog() {
            testLog = [];
            document.getElementById('testResults').innerHTML = '<div>Log cleared...</div>';
        }
        
        async function apiCall(url, data) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(data)
                });
                
                const text = await response.text();
                let result;
                
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    log(`API returned non-JSON: ${text.substring(0, 100)}...`, 'error');
                    return { success: false, error: 'Invalid JSON response', raw: text };
                }
                
                return result;
            } catch (error) {
                log(`API call failed: ${error.message}`, 'error');
                return { success: false, error: error.message };
            }
        }
        
        async function testEmailValidation() {
            log('🧪 Testing email validation...', 'info');
            
            const testEmails = [
                { email: 'valid@example.com', expected: true },
                { email: 'test@domain.co.uk', expected: true },
                { email: 'user+tag@gmail.com', expected: true },
                { email: 'invalid-email', expected: false },
                { email: 'test@', expected: false },
                { email: '@domain.com', expected: false },
                { email: 'test..test@domain.com', expected: false },
                { email: '', expected: false }
            ];
            
            for (const test of testEmails) {
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(test.email);
                if (isValid === test.expected) {
                    log(`✅ Email "${test.email}" validation correct`, 'success');
                } else {
                    log(`❌ Email "${test.email}" validation failed (expected ${test.expected}, got ${isValid})`, 'error');
                }
            }
        }
        
        async function testPhoneValidation() {
            log('🧪 Testing phone validation...', 'info');
            
            const testPhones = [
                { phone: '+251912345678', expected: true },
                { phone: '0912345678', expected: true },
                { phone: '912345678', expected: true },
                { phone: '+251 91 234 5678', expected: true },
                { phone: '+1234567890', expected: false },
                { phone: '123456', expected: false },
                { phone: '+25191234567', expected: false },
                { phone: '', expected: true } // Optional field
            ];
            
            for (const test of testPhones) {
                const cleaned = test.phone.replace(/[^0-9+]/g, '');
                const isValid = test.phone === '' || /^(\+2519[0-9]{8}|09[0-9]{8}|9[0-9]{8})$/.test(cleaned);
                
                if (isValid === test.expected) {
                    log(`✅ Phone "${test.phone}" validation correct`, 'success');
                } else {
                    log(`❌ Phone "${test.phone}" validation failed (expected ${test.expected}, got ${isValid})`, 'error');
                }
            }
        }
        
        async function testPaymentMethods() {
            log('🧪 Testing payment method flow...', 'info');
            
            // Test TeleBirr flow
            log('Testing TeleBirr payment...', 'info');
            const telebirrResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                action: 'send_payment_instructions',
                order_id: 99999,
                email: 'test@example.com',
                phone: '+251912345678',
                payment_method: 'telebirr',
                total_amount: 100
            });
            
            if (telebirrResult.success) {
                log('✅ TeleBirr payment instructions sent', 'success');
            } else {
                log(`❌ TeleBirr payment failed: ${telebirrResult.message || 'Unknown error'}`, 'error');
            }
            
            // Test Bank Transfer flow
            const banks = ['Commercial Bank of Ethiopia', 'Birhan Bank', 'Awash Bank'];
            for (const bank of banks) {
                log(`Testing ${bank} transfer...`, 'info');
                const bankResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                    action: 'send_payment_instructions',
                    order_id: 99999,
                    email: 'test@example.com',
                    phone: '+251912345678',
                    payment_method: 'bank_transfer',
                    bank_name: bank,
                    total_amount: 100
                });
                
                if (bankResult.success) {
                    log(`✅ ${bank} instructions sent`, 'success');
                } else {
                    log(`❌ ${bank} failed: ${bankResult.message || 'Unknown error'}`, 'error');
                }
            }
        }
        
        async function testErrorScenarios() {
            log('🧪 Testing error scenarios...', 'info');
            
            // Test with missing email
            log('Testing missing email...', 'info');
            const noEmailResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                action: 'send_payment_instructions',
                order_id: 99999,
                phone: '+251912345678',
                payment_method: 'telebirr',
                total_amount: 100
            });
            
            if (!noEmailResult.success) {
                log('✅ Missing email properly rejected', 'success');
            } else {
                log('❌ Missing email was accepted (should be rejected)', 'error');
            }
            
            // Test with invalid order ID
            log('Testing invalid order ID...', 'info');
            const invalidOrderResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                action: 'send_payment_instructions',
                order_id: -1,
                email: 'test@example.com',
                payment_method: 'telebirr',
                total_amount: 100
            });
            
            if (!invalidOrderResult.success) {
                log('✅ Invalid order ID properly rejected', 'success');
            } else {
                log('❌ Invalid order ID was accepted (should be rejected)', 'error');
            }
            
            // Test with zero amount
            log('Testing zero amount...', 'info');
            const zeroAmountResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                action: 'send_payment_instructions',
                order_id: 99999,
                email: 'test@example.com',
                payment_method: 'telebirr',
                total_amount: 0
            });
            
            if (!zeroAmountResult.success) {
                log('✅ Zero amount properly rejected', 'success');
            } else {
                log('❌ Zero amount was accepted (should be rejected)', 'error');
            }
            
            // Test SQL injection attempts
            log('Testing SQL injection protection...', 'info');
            const sqlInjectionResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                action: 'send_payment_instructions',
                order_id: "1'; DROP TABLE orders; --",
                email: 'test@example.com',
                payment_method: 'telebirr',
                total_amount: 100
            });
            
            if (!sqlInjectionResult.success) {
                log('✅ SQL injection attempt properly blocked', 'success');
            } else {
                log('❌ SQL injection attempt was processed (SECURITY RISK!)', 'error');
            }
        }
        
        async function runFullFlow() {
            log('🚀 Starting full checkout flow simulation...', 'info');
            
            // Step 1: Create order
            log('Step 1: Creating test order...', 'info');
            const orderResult = await apiCall('../api/orders.php', {
                action: 'create_order'
            });
            
            if (!orderResult.success) {
                log(`❌ Order creation failed: ${orderResult.message}`, 'error');
                return;
            }
            
            const orderId = orderResult.order_id;
            log(`✅ Order created with ID: ${orderId}`, 'success');
            
            // Step 2: Send payment instructions
            log('Step 2: Sending payment instructions...', 'info');
            const paymentResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                action: 'send_payment_instructions',
                order_id: orderId,
                email: 'test@example.com',
                phone: '+251912345678',
                payment_method: 'telebirr',
                total_amount: 100
            });
            
            if (!paymentResult.success) {
                log(`❌ Payment instructions failed: ${paymentResult.message}`, 'error');
                return;
            }
            
            log('✅ Payment instructions sent successfully', 'success');
            
            // Step 3: Generate and verify confirmation code
            log('Step 3: Testing confirmation code...', 'info');
            const confirmCode = String(Math.floor(Math.random() * 900000) + 100000);
            
            // Insert test confirmation code
            const insertResult = await apiCall('../debug/insert_test_code.php', {
                order_id: orderId,
                confirm_code: confirmCode,
                email: 'test@example.com'
            });
            
            if (insertResult.success) {
                log(`✅ Test confirmation code created: ${confirmCode}`, 'success');
                
                // Verify the code
                const verifyResult = await apiCall('../controllers/PaymentWorkflowController.php', {
                    action: 'verify_confirm_code',
                    order_id: orderId,
                    confirm_code: confirmCode
                });
                
                if (verifyResult.success) {
                    log('✅ Confirmation code verification successful', 'success');
                } else {
                    log(`❌ Confirmation code verification failed: ${verifyResult.message}`, 'error');
                }
            } else {
                log('❌ Failed to create test confirmation code', 'error');
            }
            
            log('🏁 Full flow simulation completed', 'info');
        }
        
        // Auto-refresh iframe every 30 seconds to catch any changes
        setInterval(() => {
            const frame = document.getElementById('checkoutFrame');
            if (frame.src) {
                frame.src = frame.src;
            }
        }, 30000);
        
        // Initial log
        log('Checkout Flow Simulator initialized', 'success');
    </script>
</body>
</html>