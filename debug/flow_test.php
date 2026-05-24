<!DOCTYPE html>
<html>
<head>
    <title>Checkout Flow Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        button { padding: 10px 20px; margin: 5px; }
        input { padding: 8px; margin: 5px; width: 200px; }
    </style>
</head>
<body>
    <h1>🧪 Checkout Flow Test</h1>
    
    <div class="test-section">
        <h3>Step 1: Create Order</h3>
        <button onclick="createOrder()">Create Test Order</button>
        <div id="orderResult"></div>
    </div>
    
    <div class="test-section">
        <h3>Step 2: Send Payment Instructions</h3>
        <input type="email" id="testEmail" placeholder="Enter email" value="test@example.com">
        <input type="tel" id="testPhone" placeholder="Enter phone" value="+251912345678">
        <select id="paymentMethod">
            <option value="telebirr">TeleBirr</option>
            <option value="bank_transfer">Bank Transfer</option>
        </select>
        <button onclick="sendInstructions()">Send Instructions</button>
        <div id="instructionsResult"></div>
    </div>
    
    <div class="test-section">
        <h3>Step 3: Verify Code</h3>
        <input type="text" id="confirmCode" placeholder="Enter 6-digit code" maxlength="6">
        <button onclick="verifyCode()">Verify Code</button>
        <div id="verifyResult"></div>
    </div>
    
    <div class="test-section">
        <h3>Test Results</h3>
        <div id="testLog"></div>
    </div>

    <script>
        let testOrderId = null;
        
        function log(message, type = 'info') {
            const div = document.getElementById('testLog');
            const p = document.createElement('p');
            p.className = type;
            p.textContent = new Date().toLocaleTimeString() + ': ' + message;
            div.appendChild(p);
        }
        
        async function createOrder() {
            // First set up session
            await fetch('../debug/setup_session.php');
            
            try {
                const response = await fetch('../api/orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=create_order'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    testOrderId = result.order_id;
                    document.getElementById('orderResult').innerHTML = 
                        `<span class="success">✅ Order created: ${testOrderId}</span>`;
                    log('Order created successfully: ' + testOrderId, 'success');
                } else {
                    document.getElementById('orderResult').innerHTML = 
                        `<span class="error">❌ Failed: ${result.message}</span>`;
                    log('Order creation failed: ' + result.message, 'error');
                }
            } catch (error) {
                document.getElementById('orderResult').innerHTML = 
                    `<span class="error">❌ Error: ${error.message}</span>`;
                log('Order creation error: ' + error.message, 'error');
            }
        }
        
        async function sendInstructions() {
            if (!testOrderId) {
                alert('Create an order first');
                return;
            }
            
            const email = document.getElementById('testEmail').value;
            const phone = document.getElementById('testPhone').value;
            const method = document.getElementById('paymentMethod').value;
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_payment_instructions');
                formData.append('order_id', testOrderId);
                formData.append('email', email);
                formData.append('phone', phone);
                formData.append('payment_method', method);
                formData.append('bank_name', method === 'bank_transfer' ? 'Commercial Bank of Ethiopia' : '');
                formData.append('total_amount', 100);
                
                const response = await fetch('../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('instructionsResult').innerHTML = 
                        `<span class="success">✅ Instructions sent: ${result.message}</span>`;
                    log('Instructions sent successfully', 'success');
                } else {
                    document.getElementById('instructionsResult').innerHTML = 
                        `<span class="error">❌ Failed: ${result.message}</span>`;
                    log('Instructions failed: ' + result.message, 'error');
                }
            } catch (error) {
                document.getElementById('instructionsResult').innerHTML = 
                    `<span class="error">❌ Error: ${error.message}</span>`;
                log('Instructions error: ' + error.message, 'error');
            }
        }
        
        async function verifyCode() {
            if (!testOrderId) {
                alert('Create an order and send instructions first');
                return;
            }
            
            const code = document.getElementById('confirmCode').value;
            
            try {
                const formData = new FormData();
                formData.append('action', 'verify_confirm_code');
                formData.append('order_id', testOrderId);
                formData.append('confirm_code', code);
                
                const response = await fetch('../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('verifyResult').innerHTML = 
                        `<span class="success">✅ Code verified: ${result.message}</span>`;
                    log('Code verified successfully', 'success');
                } else {
                    document.getElementById('verifyResult').innerHTML = 
                        `<span class="error">❌ Failed: ${result.message}</span>`;
                    log('Code verification failed: ' + result.message, 'error');
                }
            } catch (error) {
                document.getElementById('verifyResult').innerHTML = 
                    `<span class="error">❌ Error: ${error.message}</span>`;
                log('Code verification error: ' + error.message, 'error');
            }
        }
        
        log('Test page loaded. Click "Create Test Order" to start.');
    </script>
</body>
</html>