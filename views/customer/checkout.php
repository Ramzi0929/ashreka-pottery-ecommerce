<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $stmt->fetchColumn();
    $_SESSION['customer_id'] = $customer_id;
}

// Check for direct purchase
$direct_product_id = $_GET['direct'] ?? null;
if ($direct_product_id) {
    // Create a temporary cart with only this product for direct purchase
    $_SESSION['cart'] = [$direct_product_id => 1];
}

// Get cart items
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    // Redirect to catalog to add items
    header('Location: catalog.php?error=' . urlencode('Your cart is empty. Please add items to continue.'));
    exit;
}

$total = 0;
$items = [];
foreach ($cart as $product_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product) {
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        $items[] = ['product' => $product, 'quantity' => $quantity, 'subtotal' => $subtotal];
    }
}

// Prevent zero amount checkout
if ($total <= 0) {
    header('Location: catalog.php?error=' . urlencode('Invalid cart total. Please add valid items.'));
    exit;
}

// Get available banks - hardcoded to avoid duplicates
$banks = ['Commercial Bank of Ethiopia', 'Birhan Bank', 'Awash Bank'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Ashreka Pottery</title>
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
        .bank-logo { width: 40px; height: 40px; border-radius: 50%; background: #f8f9fa; display: flex; align-items: center; justify-content: center; }
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
                        <h4><i class="fas fa-credit-card me-2"></i>Secure Checkout</h4>
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
                                    <i class="fas fa-envelope"></i> Email
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
                            <h5><i class="fas fa-list me-2"></i>Order Summary</h5>
                            <div class="row">
                                <div class="col-md-8">
                                    <?php foreach ($items as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                        <div class="d-flex align-items-center">
                                            <img src="../../<?= $item['product']['image_path'] ?>" width="120" height="120" class="rounded me-3" style="object-fit: cover;">
                                            <div>
                                                <h4 class="mb-2"><?= htmlspecialchars($item['product']['name']) ?></h4>
                                                <h6 class="mb-0 text-muted">Qty: <?= $item['quantity'] ?> × <?= number_format($item['product']['price']) ?> ETB</h6>
                                            </div>
                                        </div>
                                        <span class="fw-bold h4"><?= number_format($item['subtotal']) ?> ETB</span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-muted">Total Amount</h5>
                                            <h2 class="text-primary fw-bold"><?= number_format($total) ?> ETB</h2>
                                    <?php if ($total <= 0): ?>
                                        <div class="alert alert-warning mt-2">
                                            <small>Invalid total amount</small>
                                        </div>
                                    <?php endif; ?>
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
                                        <img src="../../assets/images/tb.jpeg" alt="TeleBirr" class="mb-3" style="width: 120px; height: 80px; object-fit: cover; border-radius: 10px;">
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

                        <!-- Step 2: Bank Selection (for Bank Transfer) -->
                        <div id="bankSelectionStep" class="payment-step">
                            <h5><i class="fas fa-university me-2"></i>Select Your Bank</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="bank-option p-3 text-center" data-bank="Commercial Bank of Ethiopia">
                                        <img src="../../assets/images/cbe.jpeg" alt="CBE" class="mb-2" style="width: 100px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <h6 class="mb-0">Commercial Bank of Ethiopia</h6>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="bank-option p-3 text-center" data-bank="Birhan Bank">
                                        <img src="../../assets/images/bb.jpeg" alt="Birhan Bank" class="mb-2" style="width: 100px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <h6 class="mb-0">Birhan Bank</h6>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="bank-option p-3 text-center" data-bank="Awash Bank">
                                        <img src="../../assets/images/ab.jpeg" alt="Awash Bank" class="mb-2" style="width: 100px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <h6 class="mb-0">Awash Bank</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Contact Information -->
                        <div id="phoneNumberStep" class="payment-step">
                            <h5><i class="fas fa-envelope me-2"></i>Contact Information</h5>
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Choose Contact Method *</label>
                                        <select id="contactMethod" class="form-control form-control-lg">
                                            <option value="email">Email Address</option>
                                            <option value="phone">Phone Number (SMS)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3" id="emailSection">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" id="emailAddress" class="form-control form-control-lg" placeholder="your@email.com" required>
                                        <div class="form-text">We'll send payment instructions to your email</div>
                                    </div>
                                    
                                    <div class="mb-3" id="phoneSection" style="display: none;">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="tel" id="phoneNumber" class="form-control form-control-lg" placeholder="+2519XXXXXXXX or 09XXXXXXXX" required>
                                        <div class="form-text">We'll send SMS with payment instructions</div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button id="sendInstructionsBtn" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Send Payment Instructions
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
                                <p class="lead">Check your email and SMS for payment details</p>
                                <div id="smsMessage" class="alert alert-info"></div>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-info-circle me-2"></i>Next Steps:</h6>
                                    <ol class="text-start mb-0">
                                        <li>Complete your payment using the instructions sent</li>
                                        <li>Find the 6-digit confirmation code in your email</li>
                                        <li>Enter the code below to upload your receipt</li>
                                    </ol>
                                </div>
                                
                                <div class="row justify-content-center mt-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Enter Confirmation Code</label>
                                        <input type="text" id="confirmCode" class="form-control form-control-lg text-center" 
                                               placeholder="000000" maxlength="6" style="font-size: 24px; letter-spacing: 5px;">
                                        <div class="form-text">6-digit code from your email (remains valid until used)</div>
                                        <button id="verifyCodeBtn" class="btn btn-primary btn-lg w-100 mt-3">
                                            <i class="fas fa-key me-2"></i>Verify Code
                                        </button>
                                    </div>
                                </div>
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
                                    <div id="imagePreview" class="mt-3" style="display: none;">
                                        <div class="preview-container" style="width: 100%; height: 400px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                            <img id="previewImg" src="" alt="Receipt Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                        </div>
                                        <div class="text-center mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                                <i class="fas fa-trash me-1"></i>Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div id="linkPreview" class="mt-3" style="display: none;">
                                        <div class="preview-container" style="width: 100%; height: 400px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden;">
                                            <iframe id="previewFrame" src="" class="w-100 h-100" style="border: none;"></iframe>
                                        </div>
                                        <div class="text-center mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLink()">
                                                <i class="fas fa-trash me-1"></i>Remove Link
                                            </button>
                                        </div>
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

                        <!-- Navigation Buttons -->
                        <div class="d-flex justify-content-between mt-4">
                            <button id="backBtn" class="btn btn-outline-secondary" onclick="goBack()" style="display: none;">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <a href="cart.php" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-cart me-2"></i>Back to Cart
                            </a>
                            <button id="nextBtn" class="btn btn-primary" onclick="goNext()" style="display: none;">
                                Next <i class="fas fa-arrow-right ms-2"></i>
                            </button>
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
                    <p>Your order has been created and is pending payment verification.</p>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>What's Next?</h6>
                        <ul class="text-start mb-0">
                            <li>Your receipt will be reviewed by our team</li>
                            <li>You'll receive confirmation within 24 hours</li>
                            <li>Track your order status in your dashboard</li>
                        </ul>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.location.href='orders.php'">
                            <i class="fas fa-list me-2"></i>View Orders
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/auto-logout.js"></script>
    <script>
        let currentStep = 1;
        let selectedPaymentMethod = '';
        let selectedBank = '';
        let orderId = null;
        let paymentId = null;
        let currentOTP = '';
        let userEmail = '';
        
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
        
        // Contact method toggle
        document.getElementById('contactMethod').addEventListener('change', function() {
            const method = this.value;
            const emailSection = document.getElementById('emailSection');
            const phoneSection = document.getElementById('phoneSection');
            
            if (method === 'email') {
                emailSection.style.display = 'block';
                phoneSection.style.display = 'none';
                document.getElementById('emailAddress').required = true;
                document.getElementById('phoneNumber').required = false;
            } else {
                emailSection.style.display = 'none';
                phoneSection.style.display = 'block';
                document.getElementById('emailAddress').required = false;
                document.getElementById('phoneNumber').required = true;
            }
        });
        
        // Send Instructions button
        document.getElementById('sendInstructionsBtn').addEventListener('click', async function() {
            const contactMethod = document.getElementById('contactMethod').value;
            let emailAddress = '';
            let phoneNumber = '';
            
            if (contactMethod === 'email') {
                emailAddress = document.getElementById('emailAddress').value.trim();
                if (!emailAddress || !validateEmail(emailAddress)) {
                    alert('Please enter a valid email address');
                    return;
                }
            } else {
                phoneNumber = document.getElementById('phoneNumber').value.trim();
                if (!phoneNumber || !validateEthiopianPhone(phoneNumber)) {
                    alert('Please enter a valid Ethiopian phone number (+2519XXXXXXXX or 09XXXXXXXX)');
                    return;
                }
            }
            
            showProcessing('Creating order and sending payment instructions...');
            
            try {
                // Create order first
                const orderResponse = await fetch('../../api/orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=create_order'
                });
                
                const orderResult = await orderResponse.json();
                console.log('Order result:', orderResult);
                
                if (orderResult.success) {
                    orderId = orderResult.order_id;
                    
                    // Send payment instructions
                    const formData = new FormData();
                    formData.append('action', 'send_payment_instructions');
                    formData.append('order_id', orderId);
                    formData.append('payment_method', selectedPaymentMethod);
                    formData.append('bank_name', selectedBank);
                    formData.append('total_amount', <?= $total ?>);
                    
                    if (contactMethod === 'email') {
                        formData.append('email', emailAddress);
                    } else {
                        formData.append('phone', phoneNumber);
                        formData.append('email', 'noemail@temp.com'); // Temp email for phone-only
                    }
                    
                    const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showEmailSent(true);
                    } else {
                        console.error('Payment instructions failed:', result);
                        alert('Failed to send payment instructions: ' + result.message);
                        showStep(3);
                    }
                } else {
                    console.error('Order creation failed:', orderResult);
                    alert('Failed to create order: ' + orderResult.message);
                    showStep(3);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error details: ' + error.message);
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
            const link = this.value.trim();
            if (link && isValidUrl(link)) {
                document.getElementById('previewFrame').src = link;
                document.getElementById('linkPreview').style.display = 'block';
                uploadArea.style.display = 'none';
                document.getElementById('imagePreview').style.display = 'none';
                submitBtn.disabled = false;
            } else if (!link) {
                removeLink();
            }
        });
        
        function handleFileSelect() {
            const file = receiptFile.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    receiptFile.value = '';
                    return;
                }
                
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);
                
                submitBtn.disabled = false;
            }
        }
        
        function removeImage() {
            receiptFile.value = '';
            document.getElementById('imagePreview').style.display = 'none';
            uploadArea.style.display = 'block';
            uploadArea.innerHTML = `
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                <h5>Upload Receipt Image</h5>
                <p class="text-muted">Drag & drop your receipt image here or click to browse</p>
                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('receiptFile').click()">
                    <i class="fas fa-folder-open me-2"></i>Choose File
                </button>
            `;
            
            const receiptLink = document.getElementById('receiptLink').value.trim();
            submitBtn.disabled = !receiptLink;
        }
        
        function removeLink() {
            document.getElementById('receiptLink').value = '';
            document.getElementById('linkPreview').style.display = 'none';
            uploadArea.style.display = 'block';
            
            submitBtn.disabled = !receiptFile.files.length;
        }
        
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
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
                formData.append('action', 'submit_receipt');
                formData.append('order_id', orderId);
                formData.append('payment_method', selectedPaymentMethod);
                formData.append('bank_name', selectedBank);
                formData.append('email', document.getElementById('emailAddress').value);
                
                if (receiptFile.files.length) {
                    formData.append('receipt_image', receiptFile.files[0]);
                }
                
                if (receiptLink) {
                    formData.append('receipt_link', receiptLink);
                }
                
                                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                
                                const result = await response.json();
                                
                                if (result.success) {
                                    showSuccess();
                                    // Clear cart
                                    await fetch('../../api/cart.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: 'action=clear_cart'
                                    });
                                } else {
                                    alert('Failed to submit order: ' + result.message);
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
            
            // Show/hide navigation buttons
            const backBtn = document.getElementById('backBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            if (step > 1 && step < 4) {
                backBtn.style.display = 'block';
            } else {
                backBtn.style.display = 'none';
            }
            
            if (step === 1) {
                nextBtn.style.display = 'none';
            } else if (step === 2) {
                nextBtn.style.display = 'block';
            } else {
                nextBtn.style.display = 'none';
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
        
        function goBack() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        }
        
        function goNext() {
            if (currentStep === 2) {
                showStep(3);
            }
        }
        
        function showProcessing(message) {
            document.querySelectorAll('.payment-step').forEach(s => s.classList.remove('active'));
            document.getElementById('processingStep').classList.add('active');
            document.getElementById('processingMessage').textContent = message;
        }
        
        function showEmailSent(success) {
            let message = '';
            
            if (success) {
                if (selectedPaymentMethod === 'telebirr') {
                    message = 'Payment instructions sent to your email and SMS. Pay to TeleBirr: 0935714446';
                } else {
                    message = `Payment instructions sent to your email and SMS for ${selectedBank} transfer`;
                }
            } else {
                message = 'Payment instructions sent successfully.';
            }
            
            document.getElementById('smsMessage').innerHTML = message;
            showStep(4);
            
            // Do NOT auto-advance to receipt upload - user must click email button
        }
        
        function showSuccess() {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        }
        
        function validateEmail(email) {
            console.log('Validating email:', email);
            const result = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            console.log('Validation result:', result);
            return result;
        }
        
        function validateEthiopianPhone(phone) {
            const cleaned = phone.replace(/[^0-9+]/g, '');
            return /^(\+2519[0-9]{8}|09[0-9]{8}|9[0-9]{8})$/.test(cleaned);
        }
        
        // Verify confirmation code
        document.getElementById('verifyCodeBtn').addEventListener('click', async function() {
            const confirmCode = document.getElementById('confirmCode').value.trim();
            
            if (!confirmCode || confirmCode.length !== 6) {
                alert('Please enter the 6-digit confirmation code');
                return;
            }
            
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
            btn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'verify_confirm_code');
                formData.append('order_id', orderId);
                formData.append('confirm_code', confirmCode);
                
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response was:', responseText);
                    alert('Server returned invalid response: ' + responseText.substring(0, 200));
                    showStep(3);
                    return;
                }
                
                if (result.success) {
                    showStep(5); // Show receipt upload step
                } else {
                    alert(result.message || 'Invalid confirmation code');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>

    <script>
        // Simple error detection
        window.addEventListener('error', function(e) {
            console.error('JS Error:', e.message, 'at', e.filename + ':' + e.lineno);
        });
        
        // Monitor failed network requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).then(response => {
                if (!response.ok) {
                    console.error('Network Error:', response.status, 'for', args[0]);
                }
                return response;
            }).catch(error => {
                console.error('Fetch Failed:', error.message, 'for', args[0]);
                throw error;
            });
        };
    </script>
</body>
</html>