<?php
session_start();
require_once '../../config/database_enhanced.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artisan') {
    header("Location: ../auth/login.php");
    exit();
}

// Get artisan info
$stmt = $pdo->prepare("SELECT * FROM artisans WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$artisan = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Product - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="dashboard-sidebar">
                    <div class="p-3 text-white">
                        <img src="<?= $artisan['profile_image'] ?: '../../assets/images/default-avatar.png' ?>" 
                             class="rounded-circle mb-2" width="50" height="50" alt="Profile">
                        <h6><?= htmlspecialchars($artisan['name']) ?></h6>
                        <small><?= ucfirst($artisan['skill_type']) ?> Artisan</small>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i>My Products
                        </a>
                        <a class="nav-link active" href="upload_product.php">
                            <i class="fas fa-plus me-2"></i>Upload Product
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i>My Orders
                        </a>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                        <a class="nav-link" href="../../index.php">
                            <i class="fas fa-home me-2"></i>Home
                        </a>
                        <a class="nav-link" href="../../controllers/AuthController.php?action=logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="dashboard-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Upload New Product</h2>
                        <div>
                            <button onclick="translatePage('am')" class="btn btn-sm btn-outline-primary me-1">አማ</button>
                            <button onclick="translatePage('en')" class="btn btn-sm btn-outline-primary">En</button>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-upload me-2"></i>Product Information</h5>
                                </div>
                                <div class="card-body">
                                    <form id="productForm" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="upload_product">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Product Name *</label>
                                                <input type="text" class="form-control" name="name" required 
                                                       placeholder="Enter product name">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Category *</label>
                                                <select class="form-select" name="category" required>
                                                    <option value="">Select Category</option>
                                                    <option value="pottery">Pottery</option>
                                                    <option value="weaving">Weaving</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Size *</label>
                                                <select class="form-select" name="size" required>
                                                    <option value="">Select Size</option>
                                                    <option value="small">Small</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="large">Large</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Price (ETB) *</label>
                                                <input type="number" class="form-control" name="price" required 
                                                       min="1" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Quantity *</label>
                                                <input type="number" class="form-control" name="quantity" required 
                                                       min="1" placeholder="1">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Material Used</label>
                                            <input type="text" class="form-control" name="material" 
                                                   placeholder="e.g., Clay, Cotton, Wool">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="4" 
                                                      placeholder="Describe your product..."></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Product Image *</label>
                                                <input type="file" class="form-control" name="image" 
                                                       accept="image/*" required data-preview="imagePreview">
                                                <small class="text-muted">Max size: 5MB. Formats: JPG, PNG, WEBP</small>
                                                <div class="mt-2">
                                                    <img id="imagePreview" class="img-thumbnail" style="display: none; max-width: 200px;">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Making Process Video (Optional)</label>
                                                <input type="file" class="form-control" name="video" 
                                                       accept="video/*" data-preview="videoPreview">
                                                <small class="text-muted">Max size: 10MB. Formats: MP4, WEBM</small>
                                                <div class="mt-2">
                                                    <video id="videoPreview" class="img-thumbnail" style="display: none; max-width: 200px;" controls>
                                                        <source src="" type="video/mp4">
                                                    </video>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Note:</strong> Your product will be reviewed by the manager before being published to the catalog.
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <a href="products.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Products
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i>Upload Product
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="google_translate_element" style="display: none;"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,am',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
        
        function translatePage(lang) {
            var selectField = document.querySelector("select.goog-te-combo");
            if (selectField) {
                selectField.value = lang;
                selectField.dispatchEvent(new Event('change'));
            }
        }

        // Handle form submission
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            submitBtn.disabled = true;
            
            fetch('../../controllers/ProductController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product uploaded successfully! It will be reviewed by the manager.');
                    window.location.href = 'products.php';
                } else {
                    alert(data.message || 'Upload failed');
                    submitBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Product';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during upload');
                submitBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Product';
                submitBtn.disabled = false;
            });
        });

        // File preview handlers
        function handleFileUpload(input, preview, maxSize = 10 * 1024 * 1024) {
            const file = input.files[0];
            if (!file) return;

            if (file.size > maxSize) {
                alert(`File size must be less than ${maxSize / (1024 * 1024)}MB`);
                input.value = '';
                return;
            }

            if (file.type.startsWith('image/') && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }

            if (file.type.startsWith('video/') && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        // Initialize file upload handlers
        document.querySelectorAll('input[type="file"]').forEach(input => {
            const previewId = input.dataset.preview;
            const preview = previewId ? document.getElementById(previewId) : null;
            
            input.addEventListener('change', function() {
                const maxSize = this.name === 'image' ? 5 * 1024 * 1024 : 10 * 1024 * 1024;
                handleFileUpload(this, preview, maxSize);
            });
        });
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>