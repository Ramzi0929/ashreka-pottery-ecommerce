<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../". $_SESSION['role'] ."/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                                <i class="fas fa-user-plus me-2"></i>Customer Registration
                            </h2>
                            <p class="text-muted">Choose your registration type</p>
                        </div>

                        <!-- Customer Type Selection -->
                        <div class="row mb-4" id="customerTypeSelection">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <div class="customer-type-card p-4 text-center" onclick="selectCustomerType('loyal')">
                                    <i class="fas fa-crown fa-3x mb-3" style="color: #ffc107;"></i>
                                    <h5>Loyal Customer</h5>
                                    <p class="small text-muted">Requires special key</p>
                                    <ul class="list-unstyled small">
                                        <li>✓ All normal features</li>
                                        <li>✓ Custom orders</li>
                                        <li>✓ Priority support</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Key Request Section -->
                        <div id="keyRequestSection" style="display: none;">
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-key me-2"></i>Loyal Customer Key Required</h5>
                                <p>To register as a loyal customer, you need a special key. This key is earned by purchasing at least 3 products from our catalog.</p>
                                <button class="btn btn-primary" onclick="requestKey()">
                                    <i class="fas fa-gift me-2"></i>Request Loyalty Key
                                </button>
                            </div>
                        </div>

                        <!-- Key Display Section -->
                        <div id="keyDisplaySection" style="display: none;">
                            <div class="alert alert-success">
                                <h5><i class="fas fa-key me-2"></i>Your Loyalty Key</h5>
                                <p>Copy this key within <span class="countdown" id="keyCountdown">60</span> seconds:</p>
                                <div class="key-display" id="loyaltyKey"></div>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-outline-primary" onclick="copyKey()">
                                        <i class="fas fa-copy me-2"></i>Copy Key
                                    </button>
                                    <small class="text-muted align-self-center">Key expires in <span id="keyTimer">60</span>s</small>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Form -->
                        <form id="registrationForm" style="display: none;">
                            <input type="hidden" name="customer_type" id="customerType">
                            <input type="hidden" name="loyalty_key" id="loyaltyKeyInput">

                            <!-- Loyalty Key Input (for loyal customers) -->
                            <div id="keyInputSection" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Enter Your Loyalty Key</label>
                                    <input type="text" class="form-control" id="keyInput" placeholder="Enter the key you received">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required minlength="6">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
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
        let keyTimer = null;
        let currentKey = null;

        function selectCustomerType(type) {
            selectedType = type;
            
            // Update UI
            document.querySelectorAll('.customer-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Hide all sections first
            document.getElementById('keyRequestSection').style.display = 'none';
            document.getElementById('keyDisplaySection').style.display = 'none';
            document.getElementById('registrationForm').style.display = 'none';
            document.getElementById('keyInputSection').style.display = 'none';
            
            // Set customer type
            document.getElementById('customerType').value = type;
            
            if (type === 'artisan') {
                // Redirect to artisan registration
                window.location.href = 'register_artisan.php';
            } else if (type === 'loyal') {
                // Show key request
                document.getElementById('keyRequestSection').style.display = 'block';
            } else {
                // Normal customer - show form directly
                document.getElementById('registrationForm').style.display = 'block';
            }
        }

        function requestKey() {
            // Generate a random key
            currentKey = generateLoyaltyKey();
            
            // Show key display
            document.getElementById('keyRequestSection').style.display = 'none';
            document.getElementById('keyDisplaySection').style.display = 'block';
            document.getElementById('loyaltyKey').textContent = currentKey;
            
            // Start countdown
            startKeyCountdown();
        }

        function generateLoyaltyKey() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let key = '';
            for (let i = 0; i < 12; i++) {
                if (i > 0 && i % 4 === 0) key += '-';
                key += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return key;
        }

        function startKeyCountdown() {
            let timeLeft = 60;
            
            keyTimer = setInterval(() => {
                timeLeft--;
                document.getElementById('keyTimer').textContent = timeLeft;
                document.getElementById('keyCountdown').textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(keyTimer);
                    // Hide key and show input form
                    document.getElementById('keyDisplaySection').style.display = 'none';
                    document.getElementById('registrationForm').style.display = 'block';
                    document.getElementById('keyInputSection').style.display = 'block';
                    currentKey = null;
                }
            }, 1000);
        }

        function copyKey() {
            navigator.clipboard.writeText(currentKey).then(() => {
                alert('Key copied to clipboard!');
                // Show registration form
                clearInterval(keyTimer);
                document.getElementById('keyDisplaySection').style.display = 'none';
                document.getElementById('registrationForm').style.display = 'block';
                document.getElementById('keyInputSection').style.display = 'block';
                document.getElementById('keyInput').value = currentKey;
                document.getElementById('loyaltyKeyInput').value = currentKey;
            });
        }

        // Handle form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate loyalty key if needed
            if (selectedType === 'loyal') {
                const keyInput = document.getElementById('keyInput').value;
                if (!keyInput) {
                    alert('Please enter your loyalty key');
                    return;
                }
                document.getElementById('loyaltyKeyInput').value = keyInput;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'register_customer');
            
            fetch('../../controllers/AuthController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration successful!');
                    window.location.href = 'login.php';
                } else {
                    alert(data.message || 'Registration failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Registration submitted successfully!');
                window.location.href = 'login.php';
            });
        });
    </script>
    
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>