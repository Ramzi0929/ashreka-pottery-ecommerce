<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../". $_SESSION['role'] ."/dashboard.php");
    exit();
}

// Check if user is verified
if (!isset($_GET['verified'])) {
    header("Location: verify.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Registration - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../../assets/js/validation.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 50%, #CD853F 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            background: rgba(255, 248, 220, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(139, 69, 19, 0.2);
        }
        .craft-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.3);
        }
        .form-control, .form-select {
            border: 2px solid #D2691E;
            border-radius: 15px;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #8B4513;
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            border: none;
            border-radius: 25px;
            padding: 15px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.4);
        }
        .craft-card {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #D2691E;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .craft-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(139, 69, 19, 0.2);
        }
        .ethiopian-text {
            font-family: 'Noto Sans Ethiopic', serif;
            color: #8B4513;
            font-weight: bold;
        }
        .step-indicator {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Language Toggle -->
    <div class="position-fixed top-0 end-0 m-3" style="z-index: 1000;">
        <button onclick="translatePage('am')" class="btn btn-sm btn-outline-light me-1">አማ</button>
        <button onclick="translatePage('en')" class="btn btn-sm btn-outline-light">En</button>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="register-container p-5">
                    <div class="text-center mb-5">
                        <div class="craft-icon">
                            <i class="fas fa-hammer text-white fa-2x"></i>
                        </div>
                        <h1 class="ethiopian-text mb-2">የእጅ ባለሞያዎች ማህበር</h1>
                        <h2 class="text-primary mb-3">Join Our Artisan Community</h2>
                        <p class="text-muted">Share your traditional Ethiopian crafts with the world</p>
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="craft-card text-center">
                                    <i class="fas fa-vase fa-2x text-primary mb-2"></i>
                                    <h6>Pottery Masters</h6>
                                    <small class="text-muted">የሸክላ ሥራ ባለሞያዎች</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="craft-card text-center">
                                    <i class="fas fa-cut fa-2x text-success mb-2"></i>
                                    <h6>Weaving Artists</h6>
                                    <small class="text-muted">የሽመና ባለሞያዎች</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="craft-card text-center">
                                    <i class="fas fa-crown fa-2x text-warning mb-2"></i>
                                    <h6>Master Craftsmen</h6>
                                    <small class="text-muted">ሁለቱንም የሚሰሩ</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="../../controllers/AuthController.php" method="POST" enctype="multipart/form-data" id="artisanForm">
                        <input type="hidden" name="action" value="register_artisan">
                        
                        <!-- Step 1: Personal Information -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-indicator me-3">1</div>
                                <h5 class="mb-0 text-primary">Personal Information</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-user me-2"></i>Name *</label>
                                    <input type="text" class="form-control form-control-sm" name="name" id="name" required readonly
                                           placeholder="Name" maxlength="50">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-user me-2"></i>Father's Name *</label>
                                    <input type="text" class="form-control form-control-sm" name="father_name" id="father_name" required readonly
                                           placeholder="Father's name" maxlength="50">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-user me-2"></i>Grandfather's Name *</label>
                                    <input type="text" class="form-control form-control-sm" name="grandfather_name" id="grandfather_name" required readonly
                                           placeholder="Grandfather's name" maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-envelope me-2"></i>Email *</label>
                                    <input type="email" class="form-control form-control-sm" name="email" id="email" required 
                                           placeholder="Email address" maxlength="100">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-phone me-2"></i>Phone *</label>
                                    <input type="tel" class="form-control form-control-sm" name="phone" id="phone" required 
                                           placeholder="+251911000000" maxlength="15">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-lock me-2"></i>Password *</label>
                                    <input type="password" class="form-control form-control-sm" name="password" required 
                                           placeholder="Min 6 characters" minlength="6" maxlength="50">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Craft Expertise -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-indicator me-3">2</div>
                                <h5 class="mb-0 text-primary">Craft Expertise</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-hammer me-2"></i>Craft *</label>
                                    <select class="form-select form-select-sm" name="skill_type" required>
                                        <option value="">Select craft</option>
                                        <option value="pottery">🏺 Pottery</option>
                                        <option value="weaving">🧵 Weaving</option>
                                        <option value="both">👑 Both</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-calendar me-2"></i>Experience *</label>
                                    <input type="number" class="form-control form-control-sm" name="experience_years" required 
                                           min="1" max="50" placeholder="Years">
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Additional Details -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-indicator me-3">3</div>
                                <h5 class="mb-0 text-primary">Additional Details</h5>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Address</label>
                                <textarea class="form-control form-control-sm" name="address" rows="2" 
                                          placeholder="City, Kebele, Woreda" maxlength="200"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-scroll me-2"></i>About Your Craft</label>
                                <textarea class="form-control form-control-sm" name="description" rows="3" 
                                          placeholder="Brief description of your craft experience and techniques" maxlength="500"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-camera me-2"></i>Photo</label>
                                <input type="file" class="form-control form-control-sm" name="profile_image" 
                                       accept="image/*" data-preview="imagePreview">
                                <small class="text-muted">Max 2MB, JPG/PNG</small>
                                <div class="mt-3 text-center">
                                    <img id="imagePreview" class="img-thumbnail rounded-circle" 
                                         style="display: none; max-width: 120px; max-height: 120px; object-fit: cover;">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-images me-2"></i>Portfolio Photos *</label>
                                <input type="file" class="form-control form-control-sm" name="portfolio_images[]" 
                                       accept="image/*" multiple required>
                                <small class="text-muted">Upload 2 photos of your work. Max 2MB each, JPG/PNG</small>
                                <div class="mt-3" id="portfolioPreview" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                            </div>
                        </div>

                        <!-- Registration Process Info -->
                        <div class="alert" style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%); border: 2px solid #D2691E; border-radius: 15px;">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-info-circle text-primary fa-lg me-2"></i>
                                <h6 class="mb-0 text-primary">Registration Process</h6>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0 small">
                                        <li>✅ Application review by manager</li>
                                        <li>📧 Email notification of status</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="mb-0 small">
                                        <li>🏺 Upload products after approval</li>
                                        <li>🔍 Product approval before going live</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="terms" id="terms" required>
                                <label class="form-check-label translate" for="terms">
                                    I agree to the 
                                    <a href="../legal/terms.php" target="_blank" class="text-decoration-none">Terms of Service</a>, 
                                    <a href="../legal/privacy.php" target="_blank" class="text-decoration-none">Privacy Policy</a>, 
                                    and to preserve traditional Ethiopian craftsmanship
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            <i class="fas fa-user-plus me-2"></i>
                            <span class="translate">Submit Application</span>
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-2 translate">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-primary me-2 translate">Sign In</a>
                        <a href="register.php" class="btn btn-outline-success translate">Register as Customer</a>
                    </div>

                    <div class="text-center mt-3">
                        <a href="../../index.php" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>
                            <span class="translate">Back to Home</span>
                        </a>
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
        
        // Auto-fill verified data OR show waiting screen for pending login
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            
            if (status === 'pending') {
                // User is trying to login with pending account - show waiting screen
                userEmail = 'pending@user.com'; // Placeholder
                showApprovalWaiting();
                return;
            }
            
            const verifiedData = sessionStorage.getItem('verified_pre_registration');
            if (verifiedData) {
                const data = JSON.parse(verifiedData);
                document.getElementById('name').value = data.name;
                document.getElementById('father_name').value = data.father_name;
                document.getElementById('grandfather_name').value = data.grandfather_name;
                
                if (data.contact_type === 'email') {
                    document.getElementById('email').value = data.contact_value;
                    document.getElementById('email').readOnly = true;
                } else {
                    document.getElementById('phone').value = data.contact_value;
                    document.getElementById('phone').readOnly = true;
                }
            }
        });
        
        function translatePage(lang) {
            var selectField = document.querySelector("select.goog-te-combo");
            if (selectField) {
                selectField.value = lang;
                selectField.dispatchEvent(new Event('change'));
            }
        }

        // Image preview
        document.querySelector('input[name="profile_image"]').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Portfolio images preview
        document.querySelector('input[name="portfolio_images[]"]').addEventListener('change', function() {
            const files = this.files;
            const preview = document.getElementById('portfolioPreview');
            preview.innerHTML = '';
            
            if (files.length !== 2) {
                alert('Please select exactly 2 portfolio images');
                this.value = '';
                return;
            }
            
            Array.from(files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.cssText = 'width: 80px; height: 80px; object-fit: cover;';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
        
        // Handle form submission
        document.getElementById('artisanForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form using ValidationSystem
            if (!ValidationSystem.validateForm(this)) {
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Show spinner
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting Application...';
            submitBtn.disabled = true;
            
            // Submit form
            fetch('../../controllers/AuthController.php', {
                method: 'POST',
                body: formData
            })
            .then(() => {
                // Show approval waiting screen
                showApprovalWaiting();
            })
            .catch(() => {
                showApprovalWaiting();
            });
        });
        
        let userEmail = '';
        
        let messageIndex = 0;
        const hopeMessages = [
            "Your application is being reviewed...",
            "Manager is checking your portfolio...",
            "Almost approved! Just a few more minutes...",
            "Your craft skills are impressive...",
            "Setting up your artisan profile...",
            "Welcome to our artisan community!",
            "Your journey as an artisan begins...",
            "Everything looks great!"
        ];
        
        function showApprovalWaiting() {
            userEmail = document.querySelector('input[name="email"]').value;
            
            document.body.innerHTML = `
                <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(rgba(139, 69, 19, 0.8), rgba(210, 105, 30, 0.8)), url('../../assets/images/people.png') center/cover no-repeat;">
                    <div class="text-center">
                        <!-- Logo -->
                        <div class="mb-4">
                            <img src="../../assets/images/ashru.jpeg" alt="Ashreka Pottery" style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                        </div>
                        
                        <!-- Spinning disc -->
                        <div class="mb-4" id="spinner">
                            <div style="width: 80px; height: 80px; margin: 0 auto; border: 6px solid rgba(255,255,255,0.3); border-top: 6px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        </div>
                        
                        <!-- Status message -->
                        <div class="mb-4">
                            <p id="statusMessage" style="color: white; font-size: 16px; font-weight: 500; margin: 0; animation: fadeInOut 2s ease-in-out infinite; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">${hopeMessages[0]}</p>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="mb-3">
                            <button onclick="window.location.href='../../index.php'" style="background: rgba(255,255,255,0.2); border: 2px solid white; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 500; cursor: pointer; transition: all 0.3s; backdrop-filter: blur(10px); margin: 5px;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                <i class="fas fa-home me-2"></i>Go to Home
                            </button>
                            <button onclick="window.location.href='../customer/heritage.php'" style="background: rgba(255,255,255,0.2); border: 2px solid white; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 500; cursor: pointer; transition: all 0.3s; backdrop-filter: blur(10px); margin: 5px;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                <i class="fas fa-history me-2"></i>See Heritage
                            </button>
                        </div>
                        <div>
                            <button onclick="checkArtisanStatus()" style="background: rgba(40, 167, 69, 0.8); border: 2px solid white; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 500; cursor: pointer; transition: all 0.3s; backdrop-filter: blur(10px); margin: 5px;" onmouseover="this.style.background='rgba(40, 167, 69, 1)'" onmouseout="this.style.background='rgba(40, 167, 69, 0.8)'">
                                <i class="fas fa-check-circle me-2"></i>Check Current Status
                            </button>
                            <button id="tryAgainBtn" onclick="tryAgain()" style="background: rgba(220, 53, 69, 0.8); border: 2px solid white; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 500; cursor: pointer; transition: all 0.3s; backdrop-filter: blur(10px); margin: 5px; display: none;" onmouseover="this.style.background='rgba(220, 53, 69, 1)'" onmouseout="this.style.background='rgba(220, 53, 69, 0.8)'">
                                <i class="fas fa-redo me-2"></i>Try Again
                            </button>
                        </div>
                    </div>
                    
                    <style>
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        @keyframes fadeInOut {
                            0%, 100% { opacity: 1; }
                            50% { opacity: 0.7; }
                        }
                    </style>
                </div>
            `;
            
            startMessageRotation();
            startCountdownLoop();
        }
        
        function startMessageRotation() {
            setInterval(() => {
                messageIndex = (messageIndex + 1) % hopeMessages.length;
                const messageEl = document.getElementById('statusMessage');
                if (messageEl) {
                    messageEl.textContent = hopeMessages[messageIndex];
                }
            }, 4000);
        }
        
        function checkArtisanStatus() {
            // Get user email and phone from multiple sources
            let userEmail = '';
            let userPhone = '';
            
            // Try to get from form inputs
            const emailInput = document.querySelector('input[name="email"]');
            const phoneInput = document.querySelector('input[name="phone"]');
            if (emailInput && emailInput.value) userEmail = emailInput.value;
            if (phoneInput && phoneInput.value) userPhone = phoneInput.value;
            
            // Try to get from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (!userEmail) userEmail = urlParams.get('email') || '';
            if (!userPhone) userPhone = urlParams.get('phone') || '';
            
            // Prepare request body
            let requestBody = '';
            if (userEmail) {
                requestBody = `email=${encodeURIComponent(userEmail)}`;
            } else if (userPhone) {
                requestBody = `phone=${encodeURIComponent(userPhone)}`;
            } else {
                // Ask user for email or phone
                const contact = prompt('Please enter your email or phone number to check status:');
                if (!contact) {
                    document.getElementById('statusMessage').textContent = "Contact information required to check status.";
                    return;
                }
                // Check if it's email or phone
                if (contact.includes('@')) {
                    requestBody = `email=${encodeURIComponent(contact)}`;
                } else {
                    requestBody = `phone=${encodeURIComponent(contact)}`;
                }
            }
            
            fetch('../../api/check_artisan_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: requestBody
            })
            .then(response => response.json())
            .then(data => {
                const statusEl = document.getElementById('statusMessage');
                const spinner = document.getElementById('spinner');
                const tryAgainBtn = document.getElementById('tryAgainBtn');
                
                if (data.status === 'approved') {
                    statusEl.innerHTML = `<strong style="color: #28a745; font-size: 18px;">✅ APPLICATION APPROVED!</strong><br><br>Congratulations! You're now an approved artisan.`;
                    statusEl.style.color = '#28a745';
                    statusEl.style.animation = 'none';
                    statusEl.style.fontSize = '14px';
                    statusEl.style.lineHeight = '1.5';
                    spinner.innerHTML = '<div style="width: 80px; height: 80px; margin: 0 auto; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-check" style="color: white; font-size: 30px;"></i></div>';
                    
                    // Show "Get In" button
                    const getInBtn = document.createElement('button');
                    getInBtn.innerHTML = '<i class="fas fa-hammer me-2"></i>Get In';
                    getInBtn.style.cssText = 'background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; color: white; padding: 12px 30px; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin: 10px; font-size: 16px;';
                    getInBtn.onmouseover = () => getInBtn.style.transform = 'translateY(-2px)';
                    getInBtn.onmouseout = () => getInBtn.style.transform = 'translateY(0)';
                    getInBtn.onclick = () => window.location.href = '../artisan/dashboard.php?welcome=1';
                    
                    // Insert button after status message
                    statusEl.parentNode.insertBefore(getInBtn, statusEl.nextSibling);
                } else if (data.status === 'rejected') {
                    const reason = data.reason || 'No specific reason provided';
                    statusEl.innerHTML = `<strong style="color: #dc3545; font-size: 18px;">❌ APPLICATION REJECTED</strong><br><br><strong>Reason:</strong> ${reason}<br><br><strong>Contact Manager:</strong><br>📞 Manager: <strong>+251911234567</strong><br>🏢 Company: <strong>+251922334455</strong><br>📧 Email: <strong>manager@ashrekapottery.com</strong>`;
                    statusEl.style.color = '#dc3545';
                    statusEl.style.animation = 'none';
                    statusEl.style.fontSize = '14px';
                    statusEl.style.lineHeight = '1.5';
                    spinner.innerHTML = '<div style="width: 80px; height: 80px; margin: 0 auto; background: #dc3545; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times" style="color: white; font-size: 30px;"></i></div>';
                    
                    // Show "Register Again" button
                    const registerAgainBtn = document.createElement('button');
                    registerAgainBtn.innerHTML = '<i class="fas fa-redo me-2"></i>Register Again';
                    registerAgainBtn.style.cssText = 'background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border: none; color: white; padding: 12px 30px; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin: 10px; font-size: 16px;';
                    registerAgainBtn.onmouseover = () => registerAgainBtn.style.transform = 'translateY(-2px)';
                    registerAgainBtn.onmouseout = () => registerAgainBtn.style.transform = 'translateY(0)';
                    registerAgainBtn.onclick = () => window.location.href = 'pre_register.php';
                    
                    // Insert button after status message
                    statusEl.parentNode.insertBefore(registerAgainBtn, statusEl.nextSibling);
                } else {
                    statusEl.textContent = "Please wait a little bit. Your application is still being reviewed.";
                    statusEl.style.color = '#ffc107';
                }
            })
            .catch(() => {
                const statusEl = document.getElementById('statusMessage');
                statusEl.textContent = "Unable to check status. Please try again later.";
                statusEl.style.color = '#dc3545';
            });
        }
        
        function tryAgain() {
            window.location.href = 'register_artisan.php';
        }
        
        function startCountdownLoop() {
            // Remove countdown, just check status every 5 seconds
            setTimeout(() => {
                checkApprovalStatus();
            }, 5000);
        }
        
        function checkApprovalStatus() {
            fetch('../../api/check_artisan_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `email=${encodeURIComponent(userEmail)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'approved') {
                    showLoginForm();
                } else if (data.status === 'rejected') {
                    showRejectionMessage();
                } else {
                    // Loop again with new countdown
                    startCountdownLoop();
                }
            })
            .catch(() => {
                // Loop again on error
                startCountdownLoop();
            });
        }
        
        function showLoginForm() {
            document.body.innerHTML = `
                <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);">
                    <div class="p-5" style="background: rgba(255,248,220,0.95); border-radius: 25px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); max-width: 400px; width: 100%;">
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h3 class="text-success">Approved!</h3>
                            <p class="text-muted">Your artisan account has been approved by the manager</p>
                        </div>
                        <form id="loginForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" id="loginEmail" value="${userEmail}" required style="border: 2px solid #D2691E; border-radius: 15px; padding: 12px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Password</label>
                                <input type="password" class="form-control" id="loginPassword" required style="border: 2px solid #D2691E; border-radius: 15px; padding: 12px;">
                            </div>
                            <button type="submit" class="btn w-100 py-2" style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); border: none; border-radius: 25px; color: white; font-weight: 600;">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
                            </button>
                        </form>
                    </div>
                </div>
            `;
            
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = document.getElementById('loginEmail').value;
                const password = document.getElementById('loginPassword').value;
                
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('email', email);
                formData.append('password', password);
                
                fetch('../../controllers/AuthController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(() => {
                    window.location.href = '../artisan/dashboard.php';
                })
                .catch(() => {
                    window.location.href = '../artisan/dashboard.php';
                });
            });
        }
        
        function showRejectionMessage() {
            document.body.innerHTML = `
                <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);">
                    <div class="text-center p-5" style="background: rgba(255,248,220,0.95); border-radius: 25px; box-shadow: 0 25px 60px rgba(0,0,0,0.3);">
                        <div class="mb-4">
                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                        </div>
                        <h3 class="text-danger mb-3">Application Rejected</h3>
                        <p class="text-muted mb-4">Your application was not approved by the manager</p>
                        <a href="register_artisan.php" class="btn btn-primary">Try Again</a>
                    </div>
                </div>
            `;
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>