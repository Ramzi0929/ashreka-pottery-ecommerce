<?php
session_start();
require_once '../../config/database_enhanced.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

// Get customer info and check loyalty
$stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

if (!$customer || $customer['purchase_count'] < 3) {
    header("Location: catalog.php?error=not_loyal");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Order - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/responsive-nav.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.1)), url('../../assets/images/ethiopian-craft-bg.jpg');
            background-size: cover;
            background-attachment: fixed;
        }
        .custom-order-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border-left: 5px solid #8B4513;
        }
        .ethiopian-pattern {
            background: linear-gradient(45deg, #FFF8DC 25%, transparent 25%);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <img src="../../assets/images/logo.png" alt="Ashreka" height="40" class="me-2">
                Ashreka & Friends
            </a>
            
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <div class="hamburger-menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </div>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-user"></i> Dashboard
                    </a>
                    <a class="nav-link" href="../../controllers/AuthController.php?action=logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="custom-order-card p-4 shadow">
                    <div class="text-center mb-4">
                        <h2 class="text-primary">
                            <i class="fas fa-crown text-warning me-2"></i>
                            <span class="translate">Custom Order</span>
                        </h2>
                        <p class="text-muted translate">Create your personalized Ethiopian handcraft</p>
                        <div>
                            <button onclick="translatePage('am')" class="btn btn-sm btn-outline-primary me-1">አማ</button>
                            <button onclick="translatePage('en')" class="btn btn-sm btn-outline-primary">En</button>
                        </div>
                    </div>

                    <div class="alert alert-success ethiopian-pattern">
                        <h5><i class="fas fa-star text-warning me-2"></i>Loyal Customer Benefits</h5>
                        <p class="mb-0">As a loyal customer with <?= $customer['purchase_count'] ?> purchases, you can now order custom-made products tailored to your specifications!</p>
                    </div>

                    <form id="customOrderForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="place_custom_order">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label translate">Product Category *</label>
                                <select class="form-select" name="category" required onchange="updateCategoryOptions()">
                                    <option value="">Select Category</option>
                                    <option value="pottery">Pottery (የሸክላ ሥራ)</option>
                                    <option value="weaving">Weaving (ሽመና)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label translate">Size *</label>
                                <select class="form-select" name="size" required>
                                    <option value="">Select Size</option>
                                    <option value="small">Small (ትንሽ)</option>
                                    <option value="medium">Medium (መካከለኛ)</option>
                                    <option value="large">Large (ትልቅ)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label translate">Preferred Colors</label>
                                <input type="text" class="form-control" name="color" 
                                       placeholder="e.g., Traditional brown, Red, Natural clay color">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label translate">Quantity *</label>
                                <input type="number" class="form-control" name="quantity" required 
                                       min="1" max="10" value="1">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label translate">Sample/Reference Image (Optional)</label>
                            <input type="file" class="form-control" name="sample_image" 
                                   accept="image/*" data-preview="samplePreview">
                            <small class="text-muted">Upload a reference image of what you want. Max size: 2MB</small>
                            <div class="mt-2">
                                <img id="samplePreview" class="img-thumbnail" style="display: none; max-width: 200px;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label translate">Detailed Description *</label>
                            <textarea class="form-control" name="description" rows="4" required
                                      placeholder="Describe your custom order in detail: specific design, patterns, traditional motifs, intended use, special requirements, etc."></textarea>
                        </div>

                        <!-- Category-specific options -->
                        <div id="potteryOptions" style="display: none;">
                            <h5 class="text-primary">Pottery Specifications</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Pottery Type</label>
                                    <select class="form-select" name="pottery_type">
                                        <option value="">Select Type</option>
                                        <option value="jebena">Jebena (ጀበና)</option>
                                        <option value="mitad">Mitad (ምጣድ)</option>
                                        <option value="shakla_bet">Shakla Bet (ሸክላ ቤት)</option>
                                        <option value="decorative">Decorative Items</option>
                                        <option value="storage">Storage Containers</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Special Features</label>
                                    <input type="text" class="form-control" name="pottery_features" 
                                           placeholder="e.g., Handle design, spout style, decorative patterns">
                                </div>
                            </div>
                        </div>

                        <div id="weavingOptions" style="display: none;">
                            <h5 class="text-primary">Weaving Specifications</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Weaving Type</label>
                                    <select class="form-select" name="weaving_type">
                                        <option value="">Select Type</option>
                                        <option value="habesha_kemis">Habesha Kemis (ሀበሻ ቀሚስ)</option>
                                        <option value="gabi">Gabi (ጋቢ)</option>
                                        <option value="netela">Netela (ነጠላ)</option>
                                        <option value="scarf">Traditional Scarf</option>
                                        <option value="table_runner">Table Runner</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Thread Type Preference</label>
                                    <input type="text" class="form-control" name="thread_type" 
                                           placeholder="e.g., Cotton, Traditional threads, Specific colors">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Custom Order Process</h6>
                            <ol class="mb-0 small">
                                <li>Submit your custom order request</li>
                                <li>Manager reviews and assigns to suitable artisan</li>
                                <li>Artisan provides quote and timeline</li>
                                <li>Pay 50% to start production</li>
                                <li>Track progress with regular updates</li>
                                <li>Pay remaining 50% upon completion</li>
                            </ol>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                <span class="translate">Submit Custom Order Request</span>
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                <span class="translate">Back to Dashboard</span>
                            </a>
                        </div>
                    </form>
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

        function updateCategoryOptions() {
            const category = document.querySelector('select[name="category"]').value;
            const potteryOptions = document.getElementById('potteryOptions');
            const weavingOptions = document.getElementById('weavingOptions');
            
            potteryOptions.style.display = 'none';
            weavingOptions.style.display = 'none';
            
            if (category === 'pottery') {
                potteryOptions.style.display = 'block';
            } else if (category === 'weaving') {
                weavingOptions.style.display = 'block';
            }
        }

        // Image preview
        document.querySelector('input[name="sample_image"]').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('samplePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Form submission
        document.getElementById('customOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            fetch('../../controllers/OrderController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Custom order submitted successfully! You will be notified when a manager reviews it.');
                    window.location.href = 'dashboard.php';
                } else {
                    alert(data.message || 'Failed to submit order');
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Custom Order Request';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your order');
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Custom Order Request';
                submitBtn.disabled = false;
            });
        });
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/responsive-nav.js"></script>
</body>
</html>