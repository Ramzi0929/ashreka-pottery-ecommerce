<?php
session_start();
require_once '../../config/database_enhanced.php';

// Handle form submission
if ($_POST) {
    if (isset($_POST['upload_receipt'])) {
        $confirm_code = $_POST['confirm_code'];
        $email_phone = trim($_POST['email_phone']);
        $password = $_POST['password'];
        
        // Check if input is email or phone
        $field = filter_var($email_phone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        // For phone, try multiple formats
        if ($field === 'phone') {
            $phone = preg_replace('/[^0-9]/', '', $email_phone);
            if (strlen($phone) == 10 && substr($phone, 0, 2) == '09') {
                $formatted_phone = '+251' . substr($phone, 1);
            } elseif (strlen($phone) == 9 && substr($phone, 0, 1) == '9') {
                $formatted_phone = '+251' . $phone;
            } else {
                $formatted_phone = $email_phone;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? OR phone = ?");
            $stmt->execute([$email_phone, $formatted_phone]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email_phone]);
        }
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Find pending order with this confirmation code
            $stmt = $pdo->prepare("
                SELECT o.id, o.total_amount, pc.id as confirm_id 
                FROM orders o 
                JOIN payment_confirmations pc ON o.id = pc.order_id 
                WHERE pc.confirm_code = ? AND o.customer_id = (
                    SELECT id FROM customers WHERE user_id = ?
                ) AND pc.status = 'pending'
            ");
            $stmt->execute([$confirm_code, $user['id']]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Handle file upload
                $receipt_path = null;
                if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === 0) {
                    $upload_dir = '../../uploads/receipts/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'receipt_' . $order['id'] . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_path)) {
                        $receipt_path = 'uploads/receipts/' . $filename;
                    }
                }
                
                // Update payment confirmation - mark as used
                $stmt = $pdo->prepare("
                    UPDATE payment_confirmations 
                    SET receipt_image_path = ?, receipt_link = ?, status = 'used' 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $receipt_path, 
                    $_POST['receipt_link'] ?? null, 
                    $order['confirm_id']
                ]);
                
                $success_message = "Receipt uploaded successfully! Your payment will be reviewed within 24 hours.";
            } else {
                $error_message = "Invalid confirmation code or code already used";
            }
        } else {
            $error_message = "Invalid email/phone or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Upload - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);
        }
        .upload-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="upload-container d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="upload-card p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-upload fa-3x text-primary mb-3"></i>
                            <h2>Quick Upload</h2>
                            <p class="text-muted">Complete your payment confirmation</p>
                        </div>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>

                        <?php if (!isset($success_message)): ?>
                        <!-- 3-Step Quick Upload Form -->
                        <form method="POST" enctype="multipart/form-data" id="quickUploadForm">
                            <input type="hidden" name="upload_receipt" value="1">
                            
                            <!-- Step 1: Confirmation Code -->
                            <div class="step-section" id="step1">
                                <h5 class="text-primary mb-3">Enter Payment Confirmation Code</h5>
                                <div class="mb-3">
                                    <label class="form-label">Confirmation Code</label>
                                    <input type="text" class="form-control" name="confirm_code" id="confirm_code" required 
                                           placeholder="Enter your 6-digit confirmation code" maxlength="6">
                                    <small class="text-muted">Check your email for the confirmation code</small>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="nextStep(2)" id="step1Btn" disabled>
                                    Next: Upload Receipt <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>

                            <!-- Order Details Frame -->
                            <div id="order_details" class="mb-3" style="display: none;">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-shopping-cart me-2"></i>Order Details
                                        </div>
                                        <button type="button" class="btn btn-sm btn-light" onclick="showOrderDetailsModal()">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </button>
                                    </div>
                                    <div class="card-body" id="order_content">
                                        <!-- Order details will be loaded here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Receipt Upload -->
                            <div class="step-section" id="step2" style="display: none;">
                                <h5 class="text-primary mb-3">Upload Your Payment Receipt</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Upload Receipt Image</label>
                                    <input type="file" class="form-control" name="receipt_image" id="receipt_image" accept="image/*">
                                    <small class="text-muted">Upload a clear photo of your payment receipt</small>
                                    <div id="image_preview" class="mt-2" style="display: none;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <small class="text-success"><i class="fas fa-check-circle"></i> Image selected</small>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showImageModal('file')">
                                                <i class="fas fa-expand"></i> View Large
                                            </button>
                                        </div>
                                        <img id="preview_img" src="" alt="Receipt Preview" class="img-thumbnail" 
                                             style="max-width: 150px; max-height: 150px; cursor: pointer;" 
                                             onclick="showImageModal('file')">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Receipt Link (Optional)</label>
                                    <input type="url" class="form-control" name="receipt_link" id="receipt_link"
                                           placeholder="https://example.com/receipt-link">
                                    <small class="text-muted">Or paste a link to your receipt</small>
                                    <div id="link_preview" class="mt-2" style="display: none;">
                                        <div class="card border-info">
                                            <div class="card-body p-2">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-link text-info me-2"></i>
                                                        <small class="text-muted">Receipt Preview</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showImageModal('link')">
                                                        <i class="fas fa-expand"></i> View Large
                                                    </button>
                                                </div>
                                                <div id="link_content" class="mt-2">
                                                    <img id="link_image" src="" alt="Receipt from Link" class="img-thumbnail" 
                                                         style="max-width: 150px; max-height: 150px; cursor: pointer; display: none;" 
                                                         onclick="showImageModal('link')">
                                                    <iframe id="link_iframe" src="" class="w-100" 
                                                            style="height: 200px; border: 1px solid #ddd; border-radius: 4px; display: none;"
                                                            sandbox="allow-same-origin allow-scripts"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="prevStep(1)">
                                        <i class="fas fa-arrow-left me-1"></i> Back
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                                        Next: Authenticate <i class="fas fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Step 3: User Authentication -->
                            <div class="step-section" id="step3" style="display: none;">
                                <h5 class="text-primary mb-3">Verify Your Identity</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email or Phone</label>
                                    <input type="text" class="form-control" name="email_phone" required 
                                           placeholder="Enter your email or phone number">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required 
                                           placeholder="Enter your password">
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                                        <i class="fas fa-arrow-left me-1"></i> Back
                                    </button>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-upload me-2"></i>Submit Receipt
                                    </button>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="#" onclick="showForgotPasswordModal()" class="text-decoration-none">
                                        Forgot Password?
                                    </a>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="../../index.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shopping-cart me-2"></i>Full Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeOrderDetailsModal()"></button>
                </div>
                <div class="modal-body" id="order_details_modal_content">
                    <!-- Full order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Receipt Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeImageModal()"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modal_image" src="" alt="Receipt" class="img-fluid" style="max-height: 70vh; display: none;">
                    <iframe id="modal_iframe" src="" class="w-100" style="height: 80vh; border: none; display: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="step1" class="step-content">
                        <p class="text-muted mb-3">Enter your email or phone number to receive a reset code</p>
                        <div class="mb-3">
                            <label class="form-label">Email or Phone</label>
                            <input type="text" class="form-control" id="resetContact" placeholder="Enter email or phone number">
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="sendResetCode()">Send Reset Code</button>
                    </div>
                    
                    <div id="step2" class="step-content" style="display: none;">
                        <p class="text-muted mb-3">Enter the 6-digit code sent to <span id="contactDisplay"></span></p>
                        <div class="mb-3">
                            <label class="form-label">Reset Code</label>
                            <input type="text" class="form-control" id="resetCode" placeholder="Enter 6-digit code" maxlength="6">
                        </div>
                        <div class="mb-3 text-center">
                            <small class="text-muted">Code expires in: <span id="countdown" class="fw-bold text-danger">1:00</span></small>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="verifyResetCode()">Verify Code</button>
                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="resendCode()">Resend Code</button>
                        <button type="button" class="btn btn-link w-100" onclick="showStep(1)">Back</button>
                    </div>
                    
                    <div id="step3" class="step-content" style="display: none;">
                        <p class="text-muted mb-3">Enter your new password</p>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" placeholder="Enter new password" minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password">
                        </div>
                        <button type="button" class="btn btn-success w-100" onclick="resetPassword()">Reset Password</button>
                        <button type="button" class="btn btn-link w-100" onclick="showStep(2)">Back</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Step navigation functions
        function nextStep(step) {
            document.querySelectorAll('.step-section').forEach(el => el.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';
        }
        
        function prevStep(step) {
            document.querySelectorAll('.step-section').forEach(el => el.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';
        }
        
        // Order details functionality
        document.addEventListener('DOMContentLoaded', function() {
            const confirmCodeInput = document.getElementById('confirm_code');
            if (confirmCodeInput) {
                confirmCodeInput.addEventListener('input', function() {
                    const code = this.value.trim();
                    const orderDetails = document.getElementById('order_details');
                    const orderContent = document.getElementById('order_content');
                    const step1Btn = document.getElementById('step1Btn');
                    
                    if (code.length === 6) {
                        // Real API call
                        fetch('../../api/get_order_details_public.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ confirm_code: code })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                orderContent.innerHTML = `
                                    <div class="row">
                                        <div class="col-12">
                                            <h6 class="mb-3">Items:</h6>
                                            ${data.items.map(item => `
                                                <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                                    <img src="../../${item.image}" alt="${item.name}" 
                                                         class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;"
                                                         onerror="this.src='../../assets/images/default-product.jpg'">
                                                    <div class="flex-grow-1">
                                                        <strong>${item.name}</strong><br>
                                                        <small class="text-muted">Qty: ${item.quantity}</small>
                                                    </div>
                                                    <span class="fw-bold">${item.price} ETB</span>
                                                </div>
                                            `).join('')}
                                            <div class="text-end mt-3 pt-3 border-top">
                                                <h5 class="text-success mb-0">Total: ${data.total} ETB</h5>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                orderDetails.style.display = 'block';
                                step1Btn.disabled = false;
                            } else {
                                orderDetails.style.display = 'none';
                                step1Btn.disabled = true;
                            }
                        })
                        .catch(error => {
                            orderDetails.style.display = 'none';
                            step1Btn.disabled = true;
                        });
                    } else {
                        orderDetails.style.display = 'none';
                        step1Btn.disabled = true;
                    }
                });
            }
            
            // Image preview functionality
            const receiptImageInput = document.getElementById('receipt_image');
            if (receiptImageInput) {
                receiptImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('image_preview');
                    const previewImg = document.getElementById('preview_img');
                    
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }
            
            // Link preview functionality
            const receiptLinkInput = document.getElementById('receipt_link');
            if (receiptLinkInput) {
                receiptLinkInput.addEventListener('input', function(e) {
                    const link = e.target.value.trim();
                    const linkPreview = document.getElementById('link_preview');
                    const linkImage = document.getElementById('link_image');
                    const linkIframe = document.getElementById('link_iframe');
                    
                    if (link) {
                        linkPreview.style.display = 'block';
                        
                        // Check if it's a bank receipt link or image
                        if (link.includes('awashbank.com') || link.includes('cbebirr.com') || link.includes('telebirr.com') || 
                            link.includes('bank') || link.includes('receipt') || link.includes('payment')) {
                            // Show as iframe for bank/payment links
                            linkImage.style.display = 'none';
                            linkIframe.style.display = 'block';
                            linkIframe.src = link;
                        } else if (link.match(/\.(jpg|jpeg|png|gif|webp)$/i) || link.includes('imgur') || link.includes('drive.google')) {
                            // Show as image for direct image links
                            linkIframe.style.display = 'none';
                            linkImage.style.display = 'block';
                            linkImage.src = link;
                        } else {
                            // Try iframe first, fallback to image
                            linkIframe.style.display = 'block';
                            linkImage.style.display = 'none';
                            linkIframe.src = link;
                        }
                    } else {
                        linkPreview.style.display = 'none';
                        linkImage.style.display = 'none';
                        linkIframe.style.display = 'none';
                    }
                });
            }
        });
        
        // Image modal functionality
        function showImageModal(type) {
            const modalImage = document.getElementById('modal_image');
            const modalIframe = document.getElementById('modal_iframe');
            const imageModal = document.getElementById('imageModal');
            
            // Reset modal content
            modalImage.style.display = 'none';
            modalIframe.style.display = 'none';
            
            if (type === 'file') {
                const previewImg = document.getElementById('preview_img');
                if (previewImg && previewImg.src) {
                    modalImage.src = previewImg.src;
                    modalImage.style.display = 'block';
                }
            } else if (type === 'link') {
                const linkImg = document.getElementById('link_image');
                const linkIframe = document.getElementById('link_iframe');
                
                if (linkIframe && linkIframe.style.display !== 'none' && linkIframe.src) {
                    // Show iframe in modal
                    modalIframe.src = linkIframe.src;
                    modalIframe.style.display = 'block';
                } else if (linkImg && linkImg.src) {
                    // Show image in modal
                    modalImage.src = linkImg.src;
                    modalImage.style.display = 'block';
                }
            }
            
            // Try Bootstrap modal, fallback to simple display
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = new bootstrap.Modal(imageModal);
                    modal.show();
                } else {
                    // Fallback: simple display
                    imageModal.style.display = 'block';
                    imageModal.classList.add('show');
                    document.body.classList.add('modal-open');
                }
            } catch (e) {
                // Fallback: simple display
                imageModal.style.display = 'block';
                imageModal.classList.add('show');
                document.body.classList.add('modal-open');
            }
        }
        
        // Make function globally available
        window.showImageModal = showImageModal;
        
        // Close modal function
        window.closeImageModal = function() {
            const imageModal = document.getElementById('imageModal');
            imageModal.style.display = 'none';
            imageModal.classList.remove('show');
            document.body.classList.remove('modal-open');
        };
        
        // Order details modal functions
        window.showOrderDetailsModal = function() {
            const orderDetailsModal = document.getElementById('orderDetailsModal');
            const modalContent = document.getElementById('order_details_modal_content');
            const confirmCode = document.getElementById('confirm_code').value.trim();
            
            if (confirmCode.length === 6) {
                fetch('../../api/get_order_details.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        confirm_code: confirmCode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalContent.innerHTML = `
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="mb-4 text-center">Complete Order Information</h6>
                                    ${data.items.map(item => `
                                        <div class="card mb-3">
                                            <div class="row g-0">
                                                <div class="col-md-4">
                                                    <img src="../../${item.image}" class="img-fluid rounded-start h-100" 
                                                         style="object-fit: cover; min-height: 200px;"
                                                         onerror="this.src='../../assets/images/default-product.jpg'">
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="card-body">
                                                        <h5 class="card-title">${item.name}</h5>
                                                        <div class="row mb-3">
                                                            <div class="col-6">
                                                                <strong>Quantity:</strong><br>
                                                                <span class="badge bg-primary fs-6">${item.quantity} pieces</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Unit Price:</strong><br>
                                                                <span class="text-success fs-5">${(parseFloat(item.price) / parseInt(item.quantity)).toFixed(2)} ETB</span>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <strong>Total Price:</strong><br>
                                                                <span class="text-success fs-4 fw-bold">${item.price} ETB</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h4 class="mb-0">Order Total: ${data.total} ETB</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        try {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const modal = new bootstrap.Modal(orderDetailsModal);
                                modal.show();
                            } else {
                                orderDetailsModal.style.display = 'block';
                                orderDetailsModal.classList.add('show');
                                document.body.classList.add('modal-open');
                            }
                        } catch (e) {
                            orderDetailsModal.style.display = 'block';
                            orderDetailsModal.classList.add('show');
                            document.body.classList.add('modal-open');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                });
            }
        };
        
        window.closeOrderDetailsModal = function() {
            const orderDetailsModal = document.getElementById('orderDetailsModal');
            orderDetailsModal.style.display = 'none';
            orderDetailsModal.classList.remove('show');
            document.body.classList.remove('modal-open');
        };
        
        // Forgot Password Functions
        let currentContact = '';
        let currentCode = '';
        let countdownTimer = null;
        
        function showForgotPasswordModal() {
            const modalElement = document.getElementById('forgotPasswordModal');
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else {
                    modalElement.style.display = 'block';
                    modalElement.classList.add('show');
                    document.body.classList.add('modal-open');
                }
            } catch (e) {
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
                document.body.classList.add('modal-open');
            }
            showStep(1);
        }
        
        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';
            
            if (step !== 2 && countdownTimer) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
        }
        
        function startCountdown() {
            let timeLeft = 60;
            const countdownEl = document.getElementById('countdown');
            
            countdownTimer = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownTimer);
                    countdownEl.textContent = 'Expired';
                    alert('Reset code has expired. Please request a new one.');
                    showStep(1);
                }
                timeLeft--;
            }, 1000);
        }
        
        function sendResetCode() {
            const contact = document.getElementById('resetContact').value.trim();
            if (!contact) {
                alert('Please enter your email or phone number');
                return;
            }
            
            currentContact = contact;
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            btn.disabled = true;
            
            fetch('../../controllers/AuthController.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=forgot_password&contact=${encodeURIComponent(contact)}`
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                if (data.success) {
                    document.getElementById('contactDisplay').textContent = contact;
                    showStep(2);
                    startCountdown();
                } else {
                    alert(data.message);
                }
            })
            .catch(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Error sending reset code');
            });
        }
        
        function resendCode() {
            if (countdownTimer) clearInterval(countdownTimer);
            sendResetCode();
        }
        
        function verifyResetCode() {
            const code = document.getElementById('resetCode').value.trim();
            if (!code || code.length !== 6) {
                alert('Please enter the 6-digit code');
                return;
            }
            
            currentCode = code;
            
            fetch('../../controllers/AuthController.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=verify_reset_code&contact=${encodeURIComponent(currentContact)}&code=${code}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showStep(3);
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('Error verifying code'));
        }
        
        function resetPassword() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (!newPassword || newPassword.length < 6) {
                alert('Password must be at least 6 characters');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }
            
            fetch('../../controllers/AuthController.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=reset_password&contact=${encodeURIComponent(currentContact)}&code=${currentCode}&password=${encodeURIComponent(newPassword)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password reset successfully! Please login with your new password.');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
                    modal.hide();
                    window.location.href = '../auth/login.php';
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('Error resetting password'));
        }
    </script>
</body>
</html>