<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Get customer info
$stmt = $pdo->prepare("SELECT c.*, u.name, u.email FROM customers c JOIN users u ON c.user_id = u.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

// Get order count
$stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE customer_id = ? AND status = 'completed'");
$stmt->execute([$customer['id']]);
$orderCount = $stmt->fetch()['order_count'];

// Get recent orders
$stmt = $pdo->prepare("SELECT o.*, p.name as product_name, p.image_path FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_id = p.id WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute([$customer['id']]);
$recentOrders = $stmt->fetchAll();

// Get notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>
    
    <!-- Welcome Message Modal -->
    <?php if (isset($_GET['welcome'])): ?>
    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Welcome to Ashreka Pottery!</h5>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                    <h4>Congratulations!</h4>
                    <p>Your account has been successfully activated. Welcome to the Ashreka Pottery family!</p>
                    <p class="text-muted">You can now browse our products, place orders, and enjoy our services.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Start Shopping</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
            welcomeModal.show();
        });
    </script>
    <?php endif; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../layouts/sidebar.php'; ?>
            
            <main class="col-md-9 col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">የደንበኛ ዳሽቦርድ</h1>

                </div>
                
                <div class="row">
                    <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>እንኳን ደህና መጣህ፣ <?= htmlspecialchars($customer['name']) ?>!</h2>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= $orderCount ?></h4>
                                        <p class="mb-0">ጥቅላላ ትዕዛዞች</p>
                                    </div>
                                    <i class="fas fa-shopping-bag fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= count($notifications) ?></h4>
                                        <p class="mb-0">አዲስ ማሳወቂያዎች</p>
                                    </div>
                                    <i class="fas fa-bell fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>የቅርብ ጊዜ ትዕዛዞች</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentOrders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h6>እስካሁን ምንም ትዕዛዞች የሉም</h6>
                            <p class="text-muted">ትዕዛዞችዎን እዚህ ለማየት ግቢይት ይጀምሩ</p>
                            <a href="catalog.php" class="btn btn-primary">ምርቶችን ይፈታተሩ</a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Product</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($order['image_path']): ?>
                                                <img src="../../<?= $order['image_path'] ?>" width="40" height="40" class="rounded me-2">
                                                <?php endif; ?>
                                                <?= $order['product_name'] ?: 'Custom Order' ?>
                                            </div>
                                        </td>
                                        <td><?= number_format($order['total_amount']) ?> ETB</td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $order['status'] === 'completed' ? 'success' : 
                                                ($order['status'] === 'in_progress' ? 'warning' : 'secondary') 
                                            ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                            <?php if ($order['status'] === 'completed' && !$order['is_rated']): ?>
                                            <br><a href="rate_product.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-warning mt-1">
                                                <i class="fas fa-star"></i> Rate
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center">
                            <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                    </div>

                    <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt me-2"></i>ፍጣን እርምጃዎች</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="catalog.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>ምርቶችን ይፈታተሩ
                            </a>
                            <a href="orders.php" class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i>የእኔ ትዕዛዞችን ይኩ
                            </a>
                            <a href="heritage.php" class="btn btn-outline-secondary">
                                <i class="fas fa-book me-2"></i>የባህል ቅርስ ምዝገብ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Auto Logout Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Auto Logout Settings</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Session Timeout:</label>
                        <select class="form-select" id="timeoutSetting" onchange="updateTimeout()">
                            <option value="off">Off</option>
                            <option value="5">5 seconds</option>
                            <option value="60">1 minute</option>
                            <option value="1800">30 minutes</option>
                        </select>
                        <small class="text-muted">Auto logout after inactivity</small>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="notification-icon me-3">
                                <i class="fas fa-bell text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Recent Updates</h6>
                                <small class="text-muted"><?= count($notifications) ?> new messages</small>
                            </div>
                            <?php if (!empty($notifications)): ?>
                            <button class="btn btn-sm btn-link text-decoration-none p-0" onclick="markAllAsRead()">
                                <small>Clear all</small>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($notifications)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                            <p class="text-muted mb-0">All caught up!</p>
                        </div>
                        <?php else: ?>
                        <div class="notification-list">
                            <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                            <div class="notification-item d-flex align-items-start mb-2 p-2 rounded bg-light">
                                <div class="flex-grow-1">
                                    <p class="mb-1 small"><?= htmlspecialchars($notification['message']) ?></p>
                                    <small class="text-muted"><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></small>
                                </div>
                                <button class="btn btn-sm btn-link p-0 ms-2" onclick="markAsRead(<?= $notification['id'] ?>)">
                                    <i class="fas fa-times text-muted"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../../includes/lang_universal.php'; ?>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/notifications.js"></script>
    <script src="../../assets/js/auto-logout.js"></script>
    <script>
        // Load saved timeout setting on page load
        document.addEventListener('DOMContentLoaded', function() {
            const timeoutDuration = localStorage.getItem('sessionTimeout') || 'off';
            document.getElementById('timeoutSetting').value = timeoutDuration;
        });
    </script>
</body>
</html>