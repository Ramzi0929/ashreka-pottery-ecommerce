<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../". $_SESSION['role'] ."/dashboard.php");
    exit();
}

// Check if pre-registration verification is completed
if (!isset($_GET['verified'])) {
    // Check if user has verified pre-registration data
    echo '<script>
        if (!sessionStorage.getItem("verified_pre_registration")) {
            window.location.href = "pre_register.php";
        }
    </script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../../assets/js/validation.js"></script>
    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            padding: 2rem 0;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .customer-type-card {
            border: 2px solid #ddd;
            border-radius: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .customer-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .customer-type-card.selected {
            border-color: #8B4513;
            background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);
        }
        .key-display {
            background: #000;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 15px;
            border-radius: 10px;
            font-size: 18px;
            text-align: center;
            margin: 10px 0;
        }
        .countdown {
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="register-container d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="register-card p-5">
                        <div class="text-center mb-4">
                            <h2 style="color: #8B4513;">
                                <i class="fas fa-user-plus me-2"></i>User Registration
                            </h2>
                            <p class="text-muted">Choose your registration type</p>
                        </div>

                        <!-- Customer Type Selection -->
                        <div class="row mb-4" id="customerTypeSelection">
                            <div class="col-md-6">
                                <div class="customer-type-card p-4 text-center" onclick="selectCustomerType('normal')">
                                    <i class="fas fa-user fa-3x mb-3" style="color: #6c757d;"></i>
                                    <h5>Normal Customer</h5>
                                    <p class="small text-muted">Browse and purchase catalog items</p>
                                    <ul class="list-unstyled small">
                                        <li>✓ View products</li>
                                        <li>✓ Purchase catalog items</li>
                                        <li>✗ Custom orders</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="customer-type-card p-4 text-center" onclick="selectCustomerType('artisan')">
                                    <i class="fas fa-hammer fa-3x mb-3" style="color: #28a745;"></i>
                                    <h5>Artisan</h5>
                                    <p class="small text-muted">Sell your handcrafted products</p>
                                    <ul class="list-unstyled small">
                                        <li>✓ Upload products</li>
                                        <li>✓ Manage orders</li>
                                        <li>✓ Earn income</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Form -->
                        <form id="registrationForm" style="display: none;">
                            <input type="hidden" name="customer_type" id="customerType">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" id="name" required readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Father's Name</label>
                                    <input type="text" class="form-control" name="father_name" id="father_name" required readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Grandfather's Name</label>
                                    <input type="text" class="form-control" name="grandfather_name" id="grandfather_name" required readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" id="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" id="phone" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required minlength="6">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="terms" required>
                                    <label class="form-check-label">
                                        I agree to the 
                                        <a href="../legal/terms.php" target="_blank" class="text-decoration-none">Terms of Service</a> 
                                        and 
                                        <a href="../legal/privacy.php" target="_blank" class="text-decoration-none">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p>Already have an account? <a href="login.php">Sign In</a></p>
                            <p>New user? <a href="pre_register.php">Start Registration</a></p>
                            <a href="../../index.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedType = null;
        let verifiedData = null;
        
        // Check verified data from pre-registration OR pending login
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            
            if (status === 'pending') {
                // User is trying to login with pending account - show waiting screen
                showWaitingScreen();
                return;
            }
            
            const storedData = sessionStorage.getItem('verified_pre_registration');
            if (!storedData) {
                window.location.href = 'pre_register.php';
                return;
            }
            verifiedData = JSON.parse(storedData);
        });
        
        function autoFillUserData() {
            if (!verifiedData) return;
            
            document.getElementById('name').value = verifiedData.name;
            document.getElementById('name').readOnly = true;
            document.getElementById('father_name').value = verifiedData.father_name;
            document.getElementById('father_name').readOnly = true;
            document.getElementById('grandfather_name').value = verifiedData.grandfather_name;
            document.getElementById('grandfather_name').readOnly = true;
            
            if (verifiedData.contact_type === 'email') {
                document.getElementById('email').value = verifiedData.contact_value;
                document.getElementById('email').readOnly = true;
            } else {
                document.getElementById('phone').value = verifiedData.contact_value;
                document.getElementById('phone').readOnly = true;
            }
        }

        function selectCustomerType(type) {
            selectedType = type;
            
            // Update UI
            document.querySelectorAll('.customer-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Set customer type
            document.getElementById('customerType').value = type;
            
            if (type === 'artisan') {
                // Redirect to artisan registration
                window.location.href = 'register_artisan.php?verified=1';
            } else {
                // Show form and auto-fill data for normal customer
                autoFillUserData();
                document.getElementById('registrationForm').style.display = 'block';
            }
        }

        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form using ValidationSystem
            if (!ValidationSystem.validateForm(this)) {
                return;
            }
            
            // Show waiting screen immediately
            showWaitingScreen();
            
            const formData = new FormData(this);
            formData.append('action', 'register_customer');
            
            fetch('../../controllers/AuthController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Clear pre-registration data
                sessionStorage.removeItem('verified_pre_registration');
                // Keep showing waiting screen - don't redirect
            })
            .catch(error => {
                console.error('Error:', error);
                // Keep showing waiting screen - don't redirect
            });
        });
        
        let messageIndex = 0;
        const hopeMessages = [
            "Your account is being created...",
            "Almost ready! Just a few more seconds...",
            "Setting up your profile...",
            "Finalizing your registration...",
            "Welcome to Ashreka Pottery family!",
            "Your journey with us begins now...",
            "Preparing your dashboard...",
            "Everything is ready for you!"
        ];
        
        function showWaitingScreen() {
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
                            <button onclick="checkCurrentStatus()" style="background: rgba(40, 167, 69, 0.8); border: 2px solid white; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 500; cursor: pointer; transition: all 0.3s; backdrop-filter: blur(10px); margin: 5px;" onmouseover="this.style.background='rgba(40, 167, 69, 1)'" onmouseout="this.style.background='rgba(40, 167, 69, 0.8)'">
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
            startAutoStatusCheck();
        }
        
        function startMessageRotation() {
            setInterval(() => {
                messageIndex = (messageIndex + 1) % hopeMessages.length;
                const messageEl = document.getElementById('statusMessage');
                if (messageEl) {
                    messageEl.textContent = hopeMessages[messageIndex];
                }
            }, 3000);
        }
        
        function checkCurrentStatus() {
            // Get user email and phone from multiple sources
            let userEmail = '';
            let userPhone = '';
            
            // Try to get from form inputs
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            if (emailInput && emailInput.value) userEmail = emailInput.value;
            if (phoneInput && phoneInput.value) userPhone = phoneInput.value;
            
            // Try to get from verified data
            if (verifiedData) {
                if (verifiedData.contact_type === 'email') {
                    userEmail = verifiedData.contact_value;
                } else if (verifiedData.contact_type === 'phone') {
                    userPhone = verifiedData.contact_value;
                }
            }
            
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
            
            fetch('../../api/check_customer_status.php', {
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
                    statusEl.innerHTML = `<strong style="color: #28a745; font-size: 18px;">✅ ACCOUNT APPROVED!</strong><br><br>Your account is ready to use.`;
                    statusEl.style.color = '#28a745';
                    statusEl.style.animation = 'none';
                    statusEl.style.fontSize = '14px';
                    statusEl.style.lineHeight = '1.5';
                    spinner.innerHTML = '<div style="width: 80px; height: 80px; margin: 0 auto; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-check" style="color: white; font-size: 30px;"></i></div>';
                    
                    // Show "Get In" button
                    const getInBtn = document.createElement('button');
                    getInBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Get In';
                    getInBtn.style.cssText = 'background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; color: white; padding: 12px 30px; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin: 10px; font-size: 16px;';
                    getInBtn.onmouseover = () => getInBtn.style.transform = 'translateY(-2px)';
                    getInBtn.onmouseout = () => getInBtn.style.transform = 'translateY(0)';
                    getInBtn.onclick = () => window.location.href = '../customer/dashboard.php?welcome=1';
                    
                    // Insert button after status message
                    statusEl.parentNode.insertBefore(getInBtn, statusEl.nextSibling);
                } else if (data.status === 'rejected') {
                    const reason = data.reason || 'No specific reason provided';
                    statusEl.innerHTML = `<strong style="color: #dc3545; font-size: 18px;">❌ REGISTRATION REJECTED</strong><br><br><strong>Reason:</strong> ${reason}<br><br><strong>Contact Company:</strong><br>📞 Manager: <strong>+251911234567</strong><br>🏢 Company: <strong>+251922334455</strong><br>📧 Email: <strong>support@ashrekapottery.com</strong>`;
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
                    statusEl.textContent = "Please wait a little bit. Your registration is still being processed.";
                    statusEl.style.color = '#ffc107';
                }
            })
            .catch(() => {
                const statusEl = document.getElementById('statusMessage');
                statusEl.textContent = "Unable to check status. Please try again later.";
                statusEl.style.color = '#dc3545';
            });
        }
        
        function startAutoStatusCheck() {
            // Check status every 10 seconds automatically
            setInterval(() => {
                checkCurrentStatus();
            }, 10000);
        }
        
        function tryAgain() {
            window.location.href = 'pre_register.php';
        }
    </script>
    
    <?php include '../../includes/lang_universal.php'; ?>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>