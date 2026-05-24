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
    <title>Login - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #009639 0%, #FFCD00 50%, #DA020E 100%);
            position: relative;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: -1;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .verification-option {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .verification-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .verification-option.selected {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .otp-input {
            width: 45px;
            height: 45px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 0 3px;
        }
    </style>
</head>
<body>
    <div class="login-container d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="login-card p-5">
                        <div class="text-center mb-4">
                            <img src="../../assets/images/logo.png" alt="Logo" height="60" class="mb-3">
                            <h2>Welcome Back</h2>
                            <p class="text-muted">Sign in to your account</p>
                        </div>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($_GET['error']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($_GET['success']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <div id="loginSection">
                            <form action="../../controllers/AuthController.php" method="POST" id="loginForm">
                                <input type="hidden" name="action" value="login">
                                
                                <div class="mb-3">
                                    <label class="form-label">Email or Phone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="email_phone" required 
                                               placeholder="Enter email or phone number">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" required 
                                               placeholder="Enter password">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <button type="button" class="btn btn-link p-0" onclick="showForgotPassword()">
                                        Forgot Password?
                                    </button>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </form>

                            <div class="text-center">
                                <p>Don't have an account? <a href="pre_register.php">Register</a></p>
                                <a href="../../index.php" class="text-decoration-none">
                                    <i class="fas fa-home me-1"></i>Back to Home
                                </a>
                            </div>
                        </div>

                        <!-- Forgot Password Section -->
                        <div id="forgotPasswordSection" style="display: none;">
                            <div class="text-center mb-4">
                                <h4>Reset Password</h4>
                                <p class="text-muted">Choose verification method</p>
                            </div>

                            <form id="forgotPasswordForm">
                                <div class="mb-3">
                                    <label class="form-label">Email or Phone</label>
                                    <input type="text" class="form-control" name="contact_value" required 
                                           placeholder="Enter your email or phone">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Verification Method</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="verification-option text-center" onclick="selectResetMethod('phone')">
                                                <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                                <small>SMS</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="verification-option text-center" onclick="selectResetMethod('email')">
                                                <i class="fas fa-envelope fa-2x text-success mb-2"></i>
                                                <small>Email</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-warning w-100 py-2 mb-3">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reset Code
                                </button>

                                <div class="text-center">
                                    <button type="button" class="btn btn-link" onclick="showLogin()">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Login
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Reset Verification Section -->
                        <div id="resetVerificationSection" style="display: none;">
                            <div class="text-center mb-4">
                                <h4>Enter Reset Code</h4>
                                <p class="text-muted">Code sent to: <span id="resetSentTo"></span></p>
                            </div>

                            <form id="resetVerificationForm">
                                <div class="d-flex justify-content-center mb-4">
                                    <input type="text" class="otp-input" maxlength="1" data-index="0">
                                    <input type="text" class="otp-input" maxlength="1" data-index="1">
                                    <input type="text" class="otp-input" maxlength="1" data-index="2">
                                    <input type="text" class="otp-input" maxlength="1" data-index="3">
                                    <input type="text" class="otp-input" maxlength="1" data-index="4">
                                    <input type="text" class="otp-input" maxlength="1" data-index="5">
                                </div>

                                <button type="submit" class="btn btn-success w-100 py-2 mb-3">
                                    <i class="fas fa-check me-2"></i>Verify Code
                                </button>

                                <div class="text-center">
                                    <button type="button" class="btn btn-link" onclick="showForgotPassword()">
                                        <i class="fas fa-arrow-left me-1"></i>Back
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- New Password Section -->
                        <div id="newPasswordSection" style="display: none;">
                            <div class="text-center mb-4">
                                <h4>Set New Password</h4>
                            </div>

                            <form id="newPasswordForm">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" required minlength="6">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-success w-100 py-2">
                                    <i class="fas fa-save me-2"></i>Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedResetMethod = null;
        let resetContactValue = '';

        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        function showLogin() {
            document.getElementById('loginSection').style.display = 'block';
            document.getElementById('forgotPasswordSection').style.display = 'none';
            document.getElementById('resetVerificationSection').style.display = 'none';
            document.getElementById('newPasswordSection').style.display = 'none';
        }

        function showForgotPassword() {
            document.getElementById('loginSection').style.display = 'none';
            document.getElementById('forgotPasswordSection').style.display = 'block';
            document.getElementById('resetVerificationSection').style.display = 'none';
            document.getElementById('newPasswordSection').style.display = 'none';
        }

        function selectResetMethod(method) {
            selectedResetMethod = method;
            document.querySelectorAll('.verification-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        // Handle forgot password form
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!selectedResetMethod) {
                alert('Please select a verification method');
                return;
            }

            const formData = new FormData(this);
            resetContactValue = formData.get('contact_value');
            
            const resetData = new FormData();
            resetData.append('action', 'send_reset_otp');
            resetData.append('contact_value', resetContactValue);
            resetData.append('contact_type', selectedResetMethod);

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;

            fetch('../../controllers/PasswordResetController.php', {
                method: 'POST',
                body: resetData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('forgotPasswordSection').style.display = 'none';
                    document.getElementById('resetVerificationSection').style.display = 'block';
                    document.getElementById('resetSentTo').textContent = resetContactValue;
                    document.querySelector('.otp-input').focus();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('Error sending reset code. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Reset Code';
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

        // Handle reset verification
        document.getElementById('resetVerificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const otpInputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                alert('Please enter the complete 6-digit code');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'verify_reset_otp');
            formData.append('contact_value', resetContactValue);
            formData.append('otp', otp);

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
            submitBtn.disabled = true;

            fetch('../../controllers/PasswordResetController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('resetVerificationSection').style.display = 'none';
                    document.getElementById('newPasswordSection').style.display = 'block';
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

        // Handle new password form
        document.getElementById('newPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                alert('Passwords do not match');
                return;
            }

            const resetData = new FormData();
            resetData.append('action', 'update_password');
            resetData.append('contact_value', resetContactValue);
            resetData.append('new_password', formData.get('new_password'));

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            submitBtn.disabled = true;

            fetch('../../controllers/PasswordResetController.php', {
                method: 'POST',
                body: resetData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password updated successfully!');
                    showLogin();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('Error updating password. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Password';
                submitBtn.disabled = false;
            });
        });
    </script>
    
    <?php include '../../includes/lang_universal.php'; ?>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>