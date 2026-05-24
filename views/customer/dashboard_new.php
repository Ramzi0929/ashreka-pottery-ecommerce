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
$isLoyal = $orderCount >= 3;

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); }
        .sidebar .nav-link { color: rgba(255,255,255,0.9); padding: 12px 20px; margin: 2px 10px; border-radius: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .stats-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: none; }
        .stats-number { font-size: 2rem; font-weight: bold; color: #8B4513; }
    </style>
</head>
<body class="bg-light">
    <?php include '../layouts/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3 text-white border-bottom border-light border-opacity-25">
                        <h6 class="mb-0">Customer Portal</h6>
                        <small class="opacity-75"><?= htmlspecialchars($customer['name']) ?></small>
                    </div>
                    <nav class="nav flex-column py-3">
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        <a class="nav-link" href="catalog.php"><i class="fas fa-store me-2"></i>Shop</a>
                        <a class="nav-link" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a>
                        <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-2"></i>Cart</a>
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a>
                        <a class="nav-link" href="../../index.php"><i class="fas fa-home me-2"></i>Home</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Customer Dashboard</h2>
                            <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($customer['name']) ?>!</p>
                        </div>
                        <?php if ($isLoyal): ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                            <i class="fas fa-crown me-1"></i>Loyal Customer
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-shopping-bag fa-2x text-primary mb-2"></i>
                                <div class="stats-number"><?= $orderCount ?></div>
                                <div class="text-muted">Total Orders</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-crown fa-2x text-warning mb-2"></i>
                                <div class="stats-number"><?= max(0, 3 - $orderCount) ?></div>
                                <div class="text-muted">Orders to Loyalty</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-bell fa-2x text-info mb-2"></i>
                                <div class="stats-number"><?= count($notifications) ?></div>
                                <div class="text-muted">New Notifications</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Recent Orders -->
                        <div class="col-lg-8 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 py-3">
                                    <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Recent Orders</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recentOrders)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <h6>No Orders Yet</h6>
                                        <p class="text-muted mb-3">Start shopping to see your orders here</p>
                                        <a href="catalog.php" class="btn btn-primary">Browse Products</a>
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
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
                                                    <td><?= $order['product_name'] ?: 'Custom Order' ?></td>
                                                    <td><?= number_format($order['total_amount']) ?> ETB</td>
                                                    <td><span class="badge bg-success"><?= ucfirst($order['status']) ?></span></td>
                                                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
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

                        <!-- Quick Actions & Notifications -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white border-0 py-3">
                                    <h5 class="mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="catalog.php" class="btn btn-primary">
                                            <i class="fas fa-shopping-bag me-2"></i>Browse Products
                                        </a>
                                        <a href="orders.php" class="btn btn-outline-primary">
                                            <i class="fas fa-list me-2"></i>View My Orders
                                        </a>
                                        <?php if ($isLoyal): ?>
                                        <a href="custom_order.php" class="btn btn-warning">
                                            <i class="fas fa-crown me-2"></i>Place Custom Order
                                        </a>
                                        <?php endif; ?>
                                        <a href="heritage.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-book me-2"></i>Heritage Archive
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Notifications -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-bell me-2 text-info"></i>Updates</h5>
                                    <?php if (!empty($notifications)): ?>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">Clear all</button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($notifications)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                        <p class="text-muted mb-0">All caught up!</p>
                                    </div>
                                    <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                    <div class="d-flex align-items-start mb-2 p-2 rounded bg-light">
                                        <div class="flex-grow-1">
                                            <p class="mb-1 small"><?= htmlspecialchars($notification['message']) ?></p>
                                            <small class="text-muted"><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></small>
                                        </div>
                                        <button class="btn btn-sm btn-link p-0 ms-2" onclick="markAsRead(<?= $notification['id'] ?>)">
                                            <i class="fas fa-times text-muted"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/lang_universal.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAsRead(notificationId) {
            fetch('../../api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=mark_read&id=${notificationId}`
            }).then(() => location.reload());
        }
        
        function markAllAsRead() {
            fetch('../../api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            }).then(() => location.reload());
        }
    </script>
</body>
</html>