<?php
session_start();
require_once '../../config/database_enhanced.php';

$orderId = $_GET['order_id'] ?? '';
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-left: 5px solid #28a745;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            animation: bounce 1s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="success-card p-5 text-center">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <h2 class="text-success mb-3">Payment Successful!</h2>
                    <h3 class="h5 text-primary mb-3">ክፍያ በተሳካ ሁኔታ ተከናውኗል!</h3>
                    
                    <p class="lead mb-4">
                        Thank you for your purchase! Your payment has been processed successfully.
                    </p>
                    
                    <?php if ($orderId): ?>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Order Details</h6>
                        <p class="mb-0">Order ID: <strong>#<?= $orderId ?></strong></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-success">
                        <h6><i class="fas fa-gift me-2"></i>What's Next?</h6>
                        <ul class="list-unstyled mb-0 text-start">
                            <li><i class="fas fa-check text-success me-2"></i>You'll receive an email confirmation</li>
                            <li><i class="fas fa-check text-success me-2"></i>Track your order in your dashboard</li>
                            <li><i class="fas fa-check text-success me-2"></i>Artisan will start working on your order</li>
                            <li><i class="fas fa-check text-success me-2"></i>Get updates on production progress</li>
                        </ul>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Go to Dashboard
                        </a>
                        <a href="orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart me-2"></i>
                            View My Orders
                        </a>
                        <a href="../../index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>
                            Continue Shopping
                        </a>
                    </div>
                    
                    <div class="mt-4 pt-4 border-top">
                        <p class="text-muted small">
                            <i class="fas fa-heart text-danger me-1"></i>
                            Thank you for supporting Ethiopian artisans!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto redirect after 10 seconds
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 10000);
        
        // Show countdown
        let countdown = 10;
        const timer = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(timer);
            }
        }, 1000);
    </script>
</body>
</html>