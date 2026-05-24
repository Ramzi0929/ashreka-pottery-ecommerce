<?php
session_start();
require_once '../../config/database_enhanced.php';

// Handle artisan registration
if ($_POST) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $skill_type = $_POST['skill_type'];
    $experience = $_POST['experience_years'];
    $address = $_POST['address'];
    $description = $_POST['description'];
    
    // Create user account
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'artisan', 'pending')");
    if ($stmt->execute([$name, $email, $phone, $password])) {
        $user_id = $pdo->lastInsertId();
        
        // Create artisan profile
        $stmt = $pdo->prepare("INSERT INTO artisans (user_id, name, skill_type, experience_years, description, address, approval_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$user_id, $name, $skill_type, $experience, $description, $address])) {
            $artisan_id = $pdo->lastInsertId();
            $_SESSION['pending_artisan_id'] = $artisan_id;
        }
    }
}

if (!isset($_SESSION['pending_artisan_id'])) {
    header('Location: register_artisan.php');
    exit;
}

$artisan_id = $_SESSION['pending_artisan_id'];

// Check artisan status
$stmt = $pdo->prepare("SELECT approval_status FROM artisans WHERE id = ?");
$stmt->execute([$artisan_id]);
$artisan = $stmt->fetch();

if (!$artisan) {
    header('Location: register_artisan.php');
    exit;
}

$status = $artisan['approval_status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center">
                        <?php if ($status === 'pending'): ?>
                        <div id="pendingStatus">
                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                            <h4>Registration Submitted!</h4>
                            <p>Please wait for manager approval...</p>
                            
                            <div class="alert alert-info">
                                <h5>Time Remaining: <span id="countdown">60</span> seconds</h5>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         id="progressBar" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <button class="btn btn-primary" onclick="checkStatus()">
                                <i class="fas fa-sync me-2"></i>Check Status
                            </button>
                        </div>
                        
                        <?php elseif ($status === 'approved'): ?>
                        <div id="approvedStatus">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4>Congratulations!</h4>
                            <p>Your artisan registration has been approved.</p>
                            <a href="../auth/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Now
                            </a>
                        </div>
                        
                        <?php elseif ($status === 'rejected'): ?>
                        <div id="rejectedStatus">
                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                            <h4>Registration Rejected</h4>
                            <p>Sorry, you are not allowed to register as an artisan at this time.</p>
                            <a href="../../index.php" class="btn btn-secondary">
                                <i class="fas fa-home me-2"></i>Go Home
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let timeLeft = 60;
        let timer;
        
        <?php if ($status === 'pending'): ?>
        function startCountdown() {
            timer = setInterval(() => {
                timeLeft--;
                document.getElementById('countdown').textContent = timeLeft;
                
                const progressPercent = (timeLeft / 60) * 100;
                document.getElementById('progressBar').style.width = progressPercent + '%';
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    showTimeoutMessage();
                }
            }, 1000);
        }
        
        function showTimeoutMessage() {
            document.getElementById('pendingStatus').innerHTML = `
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h4>Time Expired</h4>
                <p>Manager approval is taking longer than expected.</p>
                <button class="btn btn-warning" onclick="location.reload()">
                    <i class="fas fa-redo me-2"></i>Try Again
                </button>
                <a href="../../index.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-home me-2"></i>Go Home
                </a>
            `;
        }
        
        function checkStatus() {
            fetch('../../api/check_artisan_status.php?id=<?= $artisan_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'approved') {
                        clearInterval(timer);
                        location.reload();
                    } else if (data.status === 'rejected') {
                        clearInterval(timer);
                        location.reload();
                    }
                });
        }
        
        // Auto-check status every 10 seconds
        setInterval(checkStatus, 10000);
        
        // Start countdown
        startCountdown();
        <?php endif; ?>
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>