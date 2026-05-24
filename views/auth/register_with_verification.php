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
    <title>Register - Ashreka Pottery</title>
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
        .step-indicator {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .verification-option {
            border: 2px solid #ddd;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .verification-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .verification-option.selected {
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
    </style>
</head>
<body>
    <div class="register-container d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="register-card p-5">
                        <div class="text-center mb-4">
                            <img src="../../assets/images/logo.png" alt="Logo" height="60" class="mb-3">
                            <h2 style="color: #8B4513;">Create Account</h2>
                            <p class="text-muted">Join Ashreka Pottery Community</p>
                        </div>

                        <!-- Step 1: Basic Info + Verification Method -->
                        <div id="step1" class="step-content">
                            <div class="d-flex align-items-center mb-4">
                                <div class="step-indicator me-3">1</div>
                                <h5 class="mb-0">Account Information</h5>
                            </div>
                            
                            <form id="registrationForm">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Name *</label>
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
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" name="phone" placeholder="+251911000000" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Choose Verification Method</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="verification-option" onclick="selectVerificationMethod('phone')">
                                                <div class="text-center">
                                                    <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                                    <h6>SMS Verification</h6>
                                                    <small class="text-muted">Verify via phone number</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="verification-option" onclick="selectVerificationMethod('email')">
                                                <div class="text-center">
                                                    <i class="fas fa-envelope fa-2x text-success mb-2"></i>
                                                    <h6>Email Verification</h6>
                                                    <small class="text-muted">Verify via email address</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="fas fa-paper-plane me-2"></i>Send Verification Code
                                </button>
                            </form>
                        </div>

                        <!-- Step 2: OTP Verification -->
                        <div id="step2" class="step-content" style="display: none;">
                            <div class="d-flex align-items-center mb-4">
                                <div class="step-indicator me-3">2</div>
                                <h5 class="mb-0">Verify Your Identity</h5>
                            </div>
                            
                            <div class="text-center mb-4">
                                <p>We sent a 6-digit code to:</p>
                                <strong id="sentTo"></strong>
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
                                        <i class="fas fa-arrow-left me-1"></i>Back to Registration
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Step 3: Set Password -->
                        <div id="step3" class="step-content" style="display: none;">
                            <div class="d-flex align-items-center mb-4">
                                <div class="step-indicator me-3">3</div>
                                <h5 class="mb-0">Set Your Password</h5>
                            </div>
                            
                            <form id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" required minlength="6">
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="2"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="terms" required>
                                        <label class="form-check-label">
                                            I agree to the Terms of Service and Privacy Policy
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100 py-2">
                                    <i class="fas fa-user-plus me-2"></i>Complete Registration
                                </button>
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
        let selectedVerificationMethod = null;
        let countdownTimer = null;
        let registrationData = {};

        function selectVerificationMethod(method) {
            selectedVerificationMethod = method;
            
            document.querySelectorAll('.verification-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        // Handle registration form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!selectedVerificationMethod) {
                alert('Please select a verification method');
                return;
            }
            
            const formData = new FormData(this);
            registrationData = {
                name: formData.get('name'),
                father_name: formData.get('father_name'),
                grandfather_name: formData.get('grandfather_name'),
                email: formData.get('email'),
                phone: formData.get('phone')
            };
            
            const verificationData = new FormData();
            verificationData.append('action', 'send_registration_otp');
            verificationData.append('name', registrationData.name);
            verificationData.append('father_name', registrationData.father_name);
            verificationData.append('grandfather_name', registrationData.grandfather_name);
            verificationData.append('contact_type', selectedVerificationMethod);
            verificationData.append('contact_value', selectedVerificationMethod === 'email' ? registrationData.email : registrationData.phone);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;
            
            fetch('../controllers/RegistrationController.php', {
                method: 'POST',
                body: verificationData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'block';
                    document.getElementById('sentTo').textContent = selectedVerificationMethod === 'email' ? registrationData.email : registrationData.phone;
                    startCountdown();
                    document.querySelector('.otp-input').focus();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('Error sending verification code. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Verification Code';
                submitBtn.disabled = false;
            });
        });

        // Handle OTP input
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

        // Handle OTP verification
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const otpInputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                alert('Please enter the complete 6-digit code');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'verify_registration_otp');
            formData.append('contact_value', selectedVerificationMethod === 'email' ? registrationData.email : registrationData.phone);
            formData.append('otp', otp);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
            submitBtn.disabled = true;
            
            fetch('../controllers/RegistrationController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    clearInterval(countdownTimer);
                    document.getElementById('step2').style.display = 'none';
                    document.getElementById('step3').style.display = 'block';
                } else {
                    alert(data.message);
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
                }
            })
            .catch(error => {
                alert('Error verifying code. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Verify Code';
                submitBtn.disabled = false;
            });
        });

        // Handle password form
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            if (formData.get('password') !== formData.get('confirm_password')) {
                alert('Passwords do not match');
                return;
            }
            
            const finalData = new FormData();
            finalData.append('action', 'complete_registration');
            finalData.append('name', registrationData.name);
            finalData.append('father_name', registrationData.father_name);
            finalData.append('grandfather_name', registrationData.grandfather_name);
            finalData.append('email', registrationData.email);
            finalData.append('phone', registrationData.phone);
            finalData.append('password', formData.get('password'));
            finalData.append('address', formData.get('address'));
            finalData.append('verification_method', selectedVerificationMethod);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
            submitBtn.disabled = true;
            
            fetch('../controllers/RegistrationController.php', {
                method: 'POST',
                body: finalData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'login.php?success=Registration completed successfully';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('Error completing registration. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Complete Registration';
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
    
    <?php include '../../includes/lang_universal.php'; ?>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>