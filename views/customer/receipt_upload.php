<?php
session_start();
require_once '../../config/database_enhanced.php';

// Check if token is provided
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: ../auth/login.php');
    exit;
}

// Decode and validate token
$decoded = base64_decode($token);
$parts = explode('_', $decoded);
if (count($parts) < 3) {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = $parts[0];
$timestamp = $parts[1];
$email = implode('_', array_slice($parts, 2));

// Check if token is not too old (24 hours)
if (time() - $timestamp > 86400) {
    echo "<script>alert('Link expired. Please request new payment instructions.'); window.close();</script>";
    exit;
}

// Get order details
$stmt = $pdo->prepare("SELECT o.*, u.email FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order || $order['email'] !== $email) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment Receipt - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-area { border: 2px dashed #ddd; border-radius: 10px; padding: 30px; text-align: center; transition: all 0.3s; }
        .upload-area:hover { border-color: #8B4513; background: #FFF8DC; }
        .upload-area.dragover { border-color: #8B4513; background: #FFF8DC; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="fas fa-receipt me-2"></i>Upload Payment Receipt</h4>
                        <p class="mb-0">Order #<?= htmlspecialchars($order_id) ?></p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Instructions</h6>
                            <ul class="mb-0">
                                <li>Upload a clear photo of your payment receipt</li>
                                <li>Ensure all details are visible</li>
                                <li>Supported formats: JPG, PNG</li>
                                <li>We'll verify your payment within 24 hours</li>
                            </ul>
                        </div>

                        <form id="receiptForm" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                            
                            <div class="upload-area mb-3" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Upload Receipt Image</h5>
                                <p class="text-muted">Drag & drop your receipt image here or click to browse</p>
                                <input type="file" id="receiptFile" name="receipt_image" accept="image/*" style="display: none;" required>
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('receiptFile').click()">
                                    <i class="fas fa-folder-open me-2"></i>Choose File
                                </button>
                            </div>
                            
                            <div id="imagePreview" class="mb-3" style="display: none;">
                                <div class="preview-container" style="width: 100%; height: 400px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                    <img id="previewImg" src="" alt="Receipt Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div class="text-center mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                        <i class="fas fa-trash me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" id="submitBtn" class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-paper-plane me-2"></i>Submit Receipt
                                </button>
                            </div>
                        </form>
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
                    <h4>Receipt Submitted Successfully!</h4>
                    <p>Your payment receipt has been uploaded and will be reviewed within 24 hours.</p>
                    <button class="btn btn-success" onclick="window.close()">
                        <i class="fas fa-check me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus window when opened from email
        window.focus();
        
        // If opened in mobile, try to open in PC browser
        if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            // Mobile device detected - show instruction to open on PC
            document.body.innerHTML = `
                <div class="container py-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow">
                                <div class="card-header bg-info text-white text-center">
                                    <h4><i class="fas fa-desktop me-2"></i>Open on Computer</h4>
                                </div>
                                <div class="card-body text-center">
                                    <i class="fas fa-laptop fa-4x text-primary mb-3"></i>
                                    <h5>Please open this link on your computer</h5>
                                    <p class="text-muted">For better file upload experience, please copy this link and open it on your PC/laptop browser.</p>
                                    <div class="alert alert-warning">
                                        <strong>Link:</strong><br>
                                        <small>${window.location.href}</small>
                                    </div>
                                    <button class="btn btn-primary" onclick="copyLink()">
                                        <i class="fas fa-copy me-2"></i>Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            return;
        }
        const uploadArea = document.getElementById('uploadArea');
        const receiptFile = document.getElementById('receiptFile');
        const submitBtn = document.getElementById('submitBtn');
        
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
        
        function handleFileSelect() {
            const file = receiptFile.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    receiptFile.value = '';
                    return;
                }
                
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
            submitBtn.disabled = true;
        }
        
        document.getElementById('receiptForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!receiptFile.files.length) {
                alert('Please select a receipt image');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'submit_receipt');
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const modal = new bootstrap.Modal(document.getElementById('successModal'));
                    modal.show();
                } else {
                    alert('Failed to submit receipt: ' + result.message);
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Receipt';
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while uploading receipt.');
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Receipt';
                submitBtn.disabled = false;
            }
        });
        
        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link copied! Open it on your computer.');
            });
        }
    </script>
</body>
</html>