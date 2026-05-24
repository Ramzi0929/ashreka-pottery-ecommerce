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
    <title>Login - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../../assets/js/validation.js"></script>
    <style>
        .login-container {
            min-height: 100vh;
            background: url('../../assets/images/logo.png') center/cover no-repeat,
                        linear-gradient(135deg, #009639 0%, #FFCD00 50%, #DA020E 100%);
            backdrop-filter: blur(10px);
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
            filter: blur(5px);
            z-index: -1;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
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
                            <h2 class="translate">Welcome Back</h2>
                            <p class="text-muted translate">Sign in to your account</p>
                        </div>

                        <?php if (isset($_GET['timeout'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-clock me-2"></i>Your session expired due to inactivity. Please login again to continue.
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <?= $_GET['error'] ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($_GET['success']) ?>
                            </div>
                        <?php endif; ?>

                        <form action="../../controllers/AuthController.php" method="POST" id="loginForm">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-3">
                                <label class="form-label translate">Email or Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="email_phone" id="email_phone" required 
                                           placeholder="Enter email or phone number">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label translate">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required 
                                           placeholder="Enter password">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end align-items-center mb-4">
                                <a href="#" onclick="showForgotPasswordModal()" class="text-decoration-none translate">Forgot Password?</a>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <span class="translate">Sign In</span>
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="pre_register.php">Register</a></p>
                            <a href="../../index.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>
                                <span class="translate">Back to Home</span>
                            </a>
                        </div>
                    </div>
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
                    <!-- Step 1: Enter Contact -->
                    <div id="step1" class="step-content">
                        <p class="text-muted mb-3">Enter your email or phone number to receive a reset code</p>
                        <div class="mb-3">
                            <label class="form-label">Email or Phone</label>
                            <input type="text" class="form-control" id="resetContact" placeholder="Enter email or phone number">
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="sendResetCode()">Send Reset Code</button>
                    </div>
                    
                    <!-- Step 2: Enter OTP -->
                    <div id="step2" class="step-content" style="display: none;">
                        <p class="text-muted mb-3">Enter the 6-digit code sent to <span id="contactDisplay"></span></p>
                        <div class="mb-3">
                            <label class="form-label">Reset Code</label>
                            <input type="text" class="form-control" id="resetCode" placeholder="Enter 6-digit code" maxlength="6">
                        </div>
                        <div class="mb-3 text-center">
                            <small class="text-muted">Code expires in: <span id="countdown" class="fw-bold text-danger">2:00</span></small>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="verifyResetCode()">Verify Code</button>
                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="resendCode()">Resend Code</button>
                        <button type="button" class="btn btn-link w-100" onclick="showStep(1)">Back</button>
                    </div>
                    
                    <!-- Step 3: New Password -->
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

    <?php include '../../includes/lang_universal.php'; ?>
    <script type="text/javascript">
        // Validate email or phone on login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const emailPhoneInput = document.getElementById('email_phone');
            const value = emailPhoneInput.value.trim();
            
            // Check if it's email or phone and validate accordingly
            const isEmail = value.includes('@');
            
            if (isEmail) {
                if (!ValidationSystem.validateEmail(value)) {
                    e.preventDefault();
                    ValidationSystem.showError(emailPhoneInput, 'Use: name@example.com');
                    return;
                }
            } else {
                if (!ValidationSystem.validateEthiopianPhone(value)) {
                    e.preventDefault();
                    ValidationSystem.showError(emailPhoneInput, '10 digit Ethiopian number start by (+251, 09, 9)');
                    return;
                }
                // Format phone number
                emailPhoneInput.value = ValidationSystem.formatEthiopianPhone(value);
            }
            
            ValidationSystem.clearError(emailPhoneInput);
        });
        
        // Handle session resumption after timeout
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('timeout') === '1') {
                // Store current form data for after login
                const form = document.getElementById('loginForm');
                form.addEventListener('submit', function() {
                    // After successful login, redirect to last page
                    setTimeout(() => {
                        const lastPage = localStorage.getItem('lastPage');
                        if (lastPage && lastPage !== window.location.href) {
                            window.location.href = lastPage;
                            localStorage.removeItem('lastPage');
                        }
                    }, 100);
                });
            }
        });
        
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
        
        // Forgot Password Functions
        let currentContact = '';
        let currentCode = '';
        let countdownTimer = null;
        
        function showForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'block';
            const modal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
            modal.show();
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
            let timeLeft = 60; // 60 seconds
            const countdownEl = document.getElementById('countdown');
            
            countdownTimer = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownTimer);
                    countdownEl.textContent = 'Expired';
                    countdownEl.className = 'fw-bold text-danger';
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
            
            // Show loading
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
            if (countdownTimer) {
                clearInterval(countdownTimer);
            }
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
                    alert('Password reset successfully! You can now login with your new password.');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
                    modal.hide();
                    // Clear form
                    document.getElementById('resetContact').value = '';
                    document.getElementById('resetCode').value = '';
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmPassword').value = '';
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('Error resetting password'));
        }
    </script>
    
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>