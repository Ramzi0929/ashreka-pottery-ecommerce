<?php
session_start();
require_once '../../config/database_enhanced.php';

// Test setup - bypass authentication
$_SESSION['user_id'] = 9; // Use existing customer
$_SESSION['role'] = 'customer';
$_SESSION['customer_id'] = 7; // Use existing customer ID

// Create test cart if empty
if (empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [1 => 1]; // Product ID 1, Quantity 1
}

$customer_id = $_SESSION['customer_id'];
$cart = $_SESSION['cart'];
$total = 200; // Test amount

// Get available banks
$stmt = $pdo->prepare("SELECT bank_name FROM bank_sms_codes WHERE is_active = 1 ORDER BY bank_name");
$stmt->execute();
$banks = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Test items
$items = [
    [
        'product' => ['name' => 'Test Jebena', 'image_path' => 'assets/uploads/products/product_1766181223_6945c96772311.jpg', 'price' => 200],
        'quantity' => 1,
        'subtotal' => 200
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Checkout - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-method { cursor: pointer; border: 2px solid #ddd; transition: all 0.3s; border-radius: 10px; }
        .payment-method.selected { border-color: #8B4513; background: #FFF8DC; box-shadow: 0 4px 8px rgba(139, 69, 19, 0.2); }
        .payment-method:hover { border-color: #D2691E; }
        .bank-option { cursor: pointer; border: 2px solid #e9ecef; transition: all 0.3s; border-radius: 8px; }
        .bank-option.selected { border-color: #8B4513; background: #FFF8DC; }
        .bank-option:hover { border-color: #D2691E; }
        .payment-step { display: none; }
        .payment-step.active { display: block; }
        .step-indicator { background: #e9ecef; color: #6c757d; }
        .step-indicator.active { background: #8B4513; color: white; }
        .step-indicator.completed { background: #28a745; color: white; }
        .upload-area { border: 2px dashed #ddd; border-radius: 10px; padding: 30px; text-align: center; transition: all 0.3s; }
        .upload-area:hover { border-color: #8B4513; background: #FFF8DC; }
        .upload-area.dragover { border-color: #8B4513; background: #FFF8DC; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-credit-card me-2"></i>Test Checkout - Payment Flow</h4>
                        <!-- Step Indicator -->
                        <div class="row mt-3">
                            <div class="col-3">
                                <div class="step-indicator active rounded-pill py-2 px-3 text-center">
                                    <i class="fas fa-shopping-cart"></i> Order
                                </div>
                            </div>
                            <div class="col-3">
                                <div id="step2" class="step-indicator rounded-pill py-2 px-3 text-center">
                                    <i class="fas fa-credit-card"></i> Payment
                                </div>
                            </div>
                            <div class="col-3">
                                <div id="step3" class="step-indicator rounded-pill py-2 px-3 text-center">
                                    <i class="fas fa-sms"></i> SMS
                                </div>
                            </div>
                            <div class="col-3">
                                <div id="step4" class="step-indicator rounded-pill py-2 px-3 text-center">
                                    <i class="fas fa-receipt"></i> Receipt
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Order Summary -->
                        <div class="mb-4">
                            <h5><i class="fas fa-list me-2"></i>Test Order Summary</h5>
                            <div class="row">
                                <div class="col-md-8">
                                    <?php foreach ($items as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($item['product']['name']) ?></h6>
                                                <small class="text-muted">Qty: <?= $item['quantity'] ?> × <?= number_format($item['product']['price']) ?> ETB</small>
                                            </div>
                                        </div>
                                        <span class="fw-bold"><?= number_format($item['subtotal']) ?> ETB</span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-muted">Total Amount</h5>
                                            <h2 class="text-primary fw-bold"><?= number_format($total) ?> ETB</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 1: Payment Method Selection -->
                        <div id="paymentMethodStep" class="payment-step active">
                            <h5><i class="fas fa-credit-card me-2"></i>Choose Payment Method</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="payment-method p-4 text-center" data-method="telebirr">
                                        <i class="fas fa-mobile-alt fa-3x text-success mb-3"></i>
                                        <h5>TeleBirr</h5>
                                        <p class="text-muted mb-0">Mobile Payment</p>
                                        <small class="text-success">Fast & Secure</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="payment-method p-4 text-center" data-method="bank_transfer">
                                        <i class="fas fa-university fa-3x text-primary mb-3"></i>
                                        <h5>Bank Transfer</h5>
                                        <p class="text-muted mb-0">Direct Bank Payment</p>
                                        <small class="text-primary">Traditional & Reliable</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Bank Selection -->
                        <div id="bankSelectionStep" class="payment-step">
                            <h5><i class="fas fa-university me-2"></i>Select Your Bank</h5>
                            <div class="row">
                                <?php foreach ($banks as $bank): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="bank-option p-3 text-center" data-bank="<?= htmlspecialchars($bank) ?>">
                                        <div class="mb-2">
                                            <i class="fas fa-university fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="mb-0"><?= htmlspecialchars($bank) ?></h6>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 3: Phone Number Input -->
                        <div id="phoneNumberStep" class="payment-step">
                            <h5><i class="fas fa-phone me-2"></i>Enter Your Phone Number</h5>
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" id="phoneNumber" class="form-control form-control-lg" placeholder="+251912345678 or 0912345678" required>
                                        <div class="form-text">We'll send payment instructions to this number</div>
                                    </div>
                                    <div class="d-grid">
                                        <button id="sendSMSBtn" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sms me-2"></i>Send Payment Instructions
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: SMS Sent Confirmation -->
                        <div id="smsSentStep" class="payment-step">
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                <h4>Payment Instructions Sent!</h4>
                                <p class="lead">Check your phone for payment details</p>
                                <div id="smsMessage" class="alert alert-info"></div>
                            </div>
                        </div>

                        <!-- Step 5: Receipt Upload -->
                        <div id="receiptUploadStep" class="payment-step">
                            <h5><i class="fas fa-receipt me-2"></i>Upload Payment Receipt</h5>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="upload-area" id="uploadArea">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h5>Upload Receipt Image</h5>
                                        <p class="text-muted">Drag & drop your receipt image here or click to browse</p>
                                        <input type="file" id="receiptFile" accept="image/*" style="display: none;">
                                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('receiptFile').click()">
                                            <i class="fas fa-folder-open me-2"></i>Choose File
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label">Or paste receipt link (optional)</label>
                                        <input type="url" id="receiptLink" class="form-control" placeholder="https://example.com/receipt-link">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6><i class="fas fa-info-circle me-2"></i>Instructions</h6>
                                            <ul class="small mb-0">
                                                <li>Take a clear photo of your receipt</li>
                                                <li>Ensure all details are visible</li>
                                                <li>Supported formats: JPG, PNG</li>
                                                <li>Max file size: 5MB</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button id="submitReceiptBtn" class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-paper-plane me-2"></i>Submit Receipt
                                </button>
                            </div>
                        </div>

                        <!-- Processing Animation -->
                        <div id="processingStep" class="payment-step text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <h5 id="processingMessage">Processing...</h5>
                            <p class="text-muted">Please wait while we process your request</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h4>Order Submitted Successfully!</h4>
                    <p>Your test order has been created and is pending payment verification.</p>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>What's Next?</h6>
                        <ul class="text-start mb-0">
                            <li>Your receipt will be reviewed by our team</li>
                            <li>You'll receive confirmation within 24 hours</li>
                            <li>Track your order status in your dashboard</li>
                        </ul>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="window.location.href='../../test_payment_flow.php'">
                            <i class="fas fa-test-tube me-2"></i>Back to Test Page
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.location.href='../manager/payment_receipts.php'">
                            <i class="fas fa-receipt me-2"></i>Manager Dashboard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        let selectedPaymentMethod = '';
        let selectedBank = '';
        let orderId = null;
        let paymentId = null;
        
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                
                selectedPaymentMethod = this.dataset.method;
                
                if (selectedPaymentMethod === 'telebirr') {
                    showStep(3); // Skip bank selection for Telebirr
                } else {
                    showStep(2); // Show bank selection for bank transfer
                }
            });
        });
        
        // Bank selection
        document.querySelectorAll('.bank-option').forEach(bank => {
            bank.addEventListener('click', function() {
                document.querySelectorAll('.bank-option').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                
                selectedBank = this.dataset.bank;
                showStep(3); // Show phone number step
            });
        });
        
        // Send SMS button
        document.getElementById('sendSMSBtn').addEventListener('click', async function() {
            const phoneNumber = document.getElementById('phoneNumber').value.trim();
            
            if (!phoneNumber) {
                alert('Please enter your phone number');
                return;
            }
            
            if (!validateEthiopianPhone(phoneNumber)) {
                alert('Please enter a valid Ethiopian phone number');
                return;
            }
            
            showProcessing('Creating order and sending SMS...');
            
            try {
                // First create the order
                const orderResponse = await fetch('../../controllers/EnhancedPaymentController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'process_payment',
                        payment_method: selectedPaymentMethod,
                        total_amount: <?= $total ?>,
                        phone_number: phoneNumber,
                        selected_bank: selectedBank
                    })
                });
                
                const orderResult = await orderResponse.json();
                
                if (orderResult.success) {
                    orderId = orderResult.order_id;
                    paymentId = orderResult.payment_id;
                    
                    // Send SMS
                    const smsAction = selectedPaymentMethod === 'telebirr' ? 'send_telebirr_sms' : 'send_bank_sms';
                    const smsData = {
                        action: smsAction,
                        phone_number: phoneNumber,
                        order_id: orderId
                    };
                    
                    if (selectedPaymentMethod === 'bank_transfer') {
                        smsData.bank_name = selectedBank;
                    }
                    
                    const smsResponse = await fetch('../../controllers/EnhancedPaymentController.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams(smsData)
                    });
                    
                    const smsResult = await smsResponse.json();
                    
                    if (smsResult.success) {
                        showSMSSent();
                    } else {
                        alert('Failed to send SMS: ' + smsResult.message);
                        showStep(3);
                    }
                } else {
                    alert('Failed to create order: ' + orderResult.message);
                    showStep(3);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                showStep(3);
            }
        });
        
        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const receiptFile = document.getElementById('receiptFile');
        const submitBtn = document.getElementById('submitReceiptBtn');
        
        uploadArea.addEventListener('click', () => receiptFile.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                receiptFile.files = files;
                handleFileSelect();
            }
        });
        
        receiptFile.addEventListener('change', handleFileSelect);
        
        document.getElementById('receiptLink').addEventListener('input', function() {
            submitBtn.disabled = !this.value.trim() && !receiptFile.files.length;
        });
        
        function handleFileSelect() {
            const file = receiptFile.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    receiptFile.value = '';
                    return;
                }
                
                uploadArea.innerHTML = `
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h6>File Selected: ${file.name}</h6>
                    <p class="text-muted">Size: ${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                `;
                
                submitBtn.disabled = false;
            }
        }
        
        // Submit receipt
        document.getElementById('submitReceiptBtn').addEventListener('click', async function() {
            const receiptLink = document.getElementById('receiptLink').value.trim();
            
            if (!receiptFile.files.length && !receiptLink) {
                alert('Please upload a receipt image or provide a receipt link');
                return;
            }
            
            showProcessing('Uploading receipt...');
            
            try {
                const formData = new FormData();
                formData.append('action', 'upload_receipt');
                formData.append('order_id', orderId);
                formData.append('payment_id', paymentId);
                
                if (receiptFile.files.length) {
                    formData.append('receipt_image', receiptFile.files[0]);
                }
                
                if (receiptLink) {
                    formData.append('receipt_link', receiptLink);
                }
                
                const response = await fetch('../../controllers/EnhancedPaymentController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess();
                } else {
                    alert('Failed to upload receipt: ' + result.message);
                    showStep(5);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while uploading receipt.');
                showStep(5);
            }
        });
        
        function showStep(step) {
            currentStep = step;
            
            // Hide all steps
            document.querySelectorAll('.payment-step').forEach(s => s.classList.remove('active'));
            
            // Update step indicators
            for (let i = 1; i <= 4; i++) {
                const indicator = document.getElementById(`step${i}`);
                if (indicator) {
                    indicator.classList.remove('active', 'completed');
                    if (i < step) {
                        indicator.classList.add('completed');
                    } else if (i === step) {
                        indicator.classList.add('active');
                    }
                }
            }
            
            // Show current step
            switch (step) {
                case 1:
                    document.getElementById('paymentMethodStep').classList.add('active');
                    break;
                case 2:
                    document.getElementById('bankSelectionStep').classList.add('active');
                    break;
                case 3:
                    document.getElementById('phoneNumberStep').classList.add('active');
                    break;
                case 4:
                    document.getElementById('smsSentStep').classList.add('active');
                    break;
                case 5:
                    document.getElementById('receiptUploadStep').classList.add('active');
                    break;
            }
        }
        
        function showProcessing(message) {
            document.querySelectorAll('.payment-step').forEach(s => s.classList.remove('active'));
            document.getElementById('processingStep').classList.add('active');
            document.getElementById('processingMessage').textContent = message;
        }
        
        function showSMSSent() {
            let message = '';
            if (selectedPaymentMethod === 'telebirr') {
                message = 'Telebirr payment instructions sent to your phone. Please pay to: 0935714446';
            } else {
                message = `Bank payment code sent to your phone for ${selectedBank}. Use this code to complete your payment.`;
            }
            
            document.getElementById('smsMessage').textContent = message;
            showStep(4);
            
            // Auto-advance to receipt upload after 3 seconds
            setTimeout(() => {
                showStep(5);
            }, 3000);
        }
        
        function showSuccess() {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        }
        
        function validateEthiopianPhone(phone) {
            const cleaned = phone.replace(/[^0-9+]/g, '');
            return /^(\+2519[0-9]{8}|09[0-9]{8}|9[0-9]{8})$/.test(cleaned);
        }
    </script>
</body>
</html>