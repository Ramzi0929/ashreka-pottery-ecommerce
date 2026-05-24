<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../" . $_SESSION['role'] . "/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Registration Verification - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../../assets/js/validation.js"></script>
    <script src="../../assets/js/lang.js"></script>
    <style>
        .verify-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            padding: 2rem 0;
        }
        .verify-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .contact-option {
            border: 2px solid #ddd;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .contact-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .contact-option.selected {
            border-color: #8B4513;
            background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);
        }
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 10px;
            margin: 0 5px;
        }
        .countdown {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }
        .lang-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Language Toggle -->
    <div class="lang-toggle">
        <button id="langToggle" class="btn btn-outline-light btn-sm">
            <i class="fas fa-globe me-1"></i>
            <span id="langText">አማ</span>
        </button>
    </div>

    <div class="verify-container d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="verify-card p-5">
                        <div class="text-center mb-4">
                            <img src="../../assets/images/ashru.jpeg" alt="Logo" height="60" class="mb-3">
                            <h2 style="color: #8B4513;">Verify Your Identity</h2>
                            <p class="text-muted">Complete verification to continue registration</p>
                        </div>

                        <!-- Step 1: Personal Details -->
                        <div id="step1" class="step-content">
                            <form id="preRegistrationForm">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Father's Name *</label>
                                        <input type="text" class="form-control" name="father_name" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Grandfather's Name *</label>
                                        <input type="text" class="form-control" name="grandfather_name" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Choose Verification Method</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="contact-option" onclick="selectContactType('phone')">
                                                <div class="text-center">
                                                    <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                                    <h6>Phone Number</h6>
                                                    <small class="text-muted">Verify via SMS</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="contact-option" onclick="selectContactType('email')">
                                                <div class="text-center">
                                                    <i class="fas fa-envelope fa-2x text-success mb-2"></i>
                                                    <h6>Email Address</h6>
                                                    <small class="text-muted">Verify via Email</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="contactInput" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label" id="contactLabel">Contact</label>
                                        <input type="text" class="form-control" id="contactValue" required>
                                        <small class="text-muted" id="contactHint"></small>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="fas fa-paper-plane me-2"></i>Send Verification Code
                                </button>
                            </form>
                        </div>

                        <!-- Step 2: OTP Verification -->
                        <div id="step2" class="step-content" style="display: none;">
                            <div class="text-center mb-4">
                                <h5>Enter Verification Code</h5>
                                <p>Code sent to: <strong id="sentTo"></strong></p>
                            </div>
                            
                            <form id="otpForm">
                                <div class="d-flex justify-content-center mb-4">
                                    <input type="text" class="otp-input" maxlength="1" data-index="0">
                                    <input type="text" class="otp-input" maxlength="1" data-index="1">
                                    <input type="text" class="otp-input" maxlength="1" data-index="2">
                                    <input type="text" class="otp-input" maxlength="1" data-index="3">
                                    <input type="text" class="otp-input" maxlength="1" data-index="4">
                                    <input type="text" class="otp-input" maxlength="1" data-index="5">
                                </div>
                                
                                <div class="text-center mb-4">
                                    <div class="countdown" id="countdown">60</div>
                                    <small class="text-muted">seconds remaining</small>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100 py-2">
                                    <i class="fas fa-check me-2"></i>Verify Code
                                </button>
                                
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-link" onclick="goBack()">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Details
                                    </button>
                                </div>
                            </form>
                        </div>

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
        let selectedContactType = null;
        let countdownTimer = null;
        let verificationData = {};

        function selectContactType(type) {
            selectedContactType = type;
            
            document.querySelectorAll('.contact-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            const contactInput = document.getElementById('contactInput');
            const contactLabel = document.getElementById('contactLabel');
            const contactValue = document.getElementById('contactValue');
            const contactHint = document.getElementById('contactHint');
            
            contactInput.style.display = 'block';
            
            if (type === 'phone') {
                contactLabel.textContent = 'Phone Number *';
                contactValue.placeholder = '+2519XXXXXXXX or 09XXXXXXXX';
                contactValue.type = 'tel';
                contactHint.textContent = 'Ethiopian format: +2519XXXXXXXX or 09XXXXXXXX';
            } else {
                contactLabel.textContent = 'Email Address *';
                contactValue.placeholder = 'your@email.com';
                contactValue.type = 'email';
                contactHint.textContent = 'Enter your valid email address';
            }
        }



        document.getElementById('preRegistrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!selectedContactType) {
                alert('Please select a verification method');
                return;
            }
            
            const formData = new FormData(this);
            const contactValue = document.getElementById('contactValue').value.trim();
            
            // Validate contact value based on type
            if (selectedContactType === 'phone') {
                if (!ValidationSystem.validateEthiopianPhone(contactValue)) {
                    ValidationSystem.showError(document.getElementById('contactValue'), '10 digit Ethiopian number start by (+251, 09, 9)');
                    return;
                }
                // Format phone number
                document.getElementById('contactValue').value = ValidationSystem.formatEthiopianPhone(contactValue);
            } else {
                if (!ValidationSystem.validateEmail(contactValue)) {
                    ValidationSystem.showError(document.getElementById('contactValue'), 'Use: name@example.com');
                    return;
                }
            }
            
            formData.append('action', 'send_pre_registration_otp');
            formData.append('contact_type', selectedContactType);
            formData.append('contact_value', contactValue);
            
            verificationData = {
                name: formData.get('name'),
                father_name: formData.get('father_name'),
                grandfather_name: formData.get('grandfather_name'),
                contact_type: selectedContactType,
                contact_value: contactValue
            };
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;
            
            fetch('../../controllers/PreRegistrationController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        document.getElementById('step1').style.display = 'none';
                        document.getElementById('step2').style.display = 'block';
                        document.getElementById('sentTo').textContent = contactValue;
                        startCountdown();
                        document.querySelector('.otp-input').focus();
                    } else {
                        alert(data.message || 'Failed to send verification code');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response text:', text);
                    alert('System error. Please try again.');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Network error. Please check your connection and try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        document.querySelectorAll('.otp-input').forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1) {
                    const nextInput = document.querySelector(`[data-index="${index + 1}"]`);
                    if (nextInput) nextInput.focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '') {
                    const prevInput = document.querySelector(`[data-index="${index - 1}"]`);
                    if (prevInput) prevInput.focus();
                }
            });
        });

        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const otpInputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                alert('Please enter the complete 6-digit code');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'verify_pre_registration_otp');
            formData.append('contact_value', verificationData.contact_value);
            formData.append('otp', otp);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
            submitBtn.disabled = true;
            
            fetch('../../controllers/PreRegistrationController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        sessionStorage.setItem('verified_pre_registration', JSON.stringify(verificationData));
                        alert('Verification successful! Redirecting to registration...');
                        window.location.href = 'register.php';
                    } else {
                        alert(data.message || 'Verification failed');
                        otpInputs.forEach(input => input.value = '');
                        otpInputs[0].focus();
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response text:', text);
                    alert('System error. Please try again.');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Network error. Please check your connection and try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        function startCountdown() {
            let timeLeft = 60;
            const countdownEl = document.getElementById('countdown');
            
            countdownTimer = setInterval(() => {
                timeLeft--;
                countdownEl.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownTimer);
                    alert('Verification code expired. Please request a new code.');
                    goBack();
                }
            }, 1000);
        }

        function goBack() {
            if (countdownTimer) {
                clearInterval(countdownTimer);
            }
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
            document.querySelectorAll('.otp-input').forEach(input => input.value = '');
        }
    </script>
    
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>