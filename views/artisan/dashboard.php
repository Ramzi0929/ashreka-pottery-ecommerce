<?php
session_start();
require_once '../../config/database_enhanced.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artisan') {
    header("Location: ../auth/login.php");
    exit();
}

// Get artisan info
$stmt = $pdo->prepare("SELECT * FROM artisans WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$artisan = $stmt->fetch();

if (!$artisan || $artisan['approval_status'] !== 'approved') {
    echo "<div class='alert alert-warning m-3'>Your account is pending approval or has been rejected.</div>";
    exit();
}

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE artisan_id = ?");
$stmt->execute([$artisan['id']]);
$total_products = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE artisan_id = ? AND status = 'approved'");
$stmt->execute([$artisan['id']]);
$approved_products = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE artisan_id = ? AND status = 'pending'");
$stmt->execute([$artisan['id']]);
$pending_products = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE artisan_id = ? AND status IN ('approved', 'in_progress')");
$stmt->execute([$artisan['id']]);
$assigned_orders = $stmt->fetchColumn();

// Get rating statistics
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM ratings WHERE artisan_id = ?");
$stmt->execute([$artisan['id']]);
$rating_data = $stmt->fetch();
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$total_ratings = $rating_data['total_ratings'];

$stats = [
    'total_products' => $total_products,
    'approved_products' => $approved_products,
    'pending_products' => $pending_products,
    'assigned_orders' => $assigned_orders,
    'avg_rating' => $avg_rating,
    'total_ratings' => $total_ratings
];

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, c.name as customer_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.artisan_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$artisan['id']]);
$recent_orders = $stmt->fetchAll();

// Get recent products
$stmt = $pdo->prepare("SELECT * FROM products WHERE artisan_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$artisan['id']]);
$recent_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Dashboard - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php 
    // Auto-include Google Translate
    $paths = ['views/layouts/google_translate.php', '../layouts/google_translate.php', '../../views/layouts/google_translate.php'];
    foreach($paths as $path) { if(file_exists($path)) { include $path; break; } }
    ?>
    
    <!-- Welcome Message Modal -->
    <?php if (isset($_GET['welcome'])): ?>
    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Welcome to Ashreka Pottery!</h5>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-hammer fa-3x text-success mb-3"></i>
                    <h4>Congratulations, Artisan!</h4>
                    <p>Your artisan application has been approved! Welcome to our community of skilled craftspeople.</p>
                    <p class="text-muted">You can now upload products, manage orders, and showcase your traditional Ethiopian crafts to the world.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Start Creating</button>
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
    <?php include '../layouts/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../layouts/sidebar.php'; ?>
            
            <main class="col-md-9 col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Welcome, <?= htmlspecialchars($artisan['name']) ?>!</h1>
                </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $stats['total_products'] ?></h4>
                                            <p class="mb-0">Total Products</p>
                                        </div>
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $stats['approved_products'] ?></h4>
                                            <p class="mb-0">Approved Products</p>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $stats['pending_products'] ?></h4>
                                            <p class="mb-0">Pending Review</p>
                                        </div>
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $stats['assigned_orders'] ?></h4>
                                            <p class="mb-0">Active Orders</p>
                                        </div>
                                        <i class="fas fa-tasks fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-star me-2"></i>My Ratings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3">
                                                    <h2 class="mb-0 text-warning"><?= $stats['avg_rating'] ?></h2>
                                                    <div class="text-warning">
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star<?= $i <= $stats['avg_rating'] ? '' : '-o' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="mb-0">Average Rating</p>
                                                    <small class="text-muted"><?= $stats['total_ratings'] ?> reviews</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <a href="ratings.php" class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-2"></i>View All Ratings
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="upload_product.php" class="btn btn-primary w-100">
                                                <i class="fas fa-plus me-2"></i>Upload New Product
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="products.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-box me-2"></i>Manage Products
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="orders.php" class="btn btn-outline-info w-100">
                                                <i class="fas fa-shopping-cart me-2"></i>View Orders
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="profile.php" class="btn btn-outline-secondary w-100">
                                                <i class="fas fa-user me-2"></i>Edit Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row">
                        <!-- Recent Orders -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-shopping-cart me-2"></i>Recent Orders</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_orders)): ?>
                                        <p class="text-muted">No orders assigned yet</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                            <div>
                                                <strong>Order #<?= $order['id'] ?></strong>
                                                <br><small class="text-muted"><?= $order['customer_name'] ?></small>
                                                <br><span class="badge bg-<?= $order['status'] === 'approved' ? 'warning' : 'info' ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </div>
                                            <div>
                                                <button class="btn btn-sm btn-primary" onclick="updateOrderStatus(<?= $order['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <a href="orders.php" class="btn btn-primary btn-sm w-100">View All Orders</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Products -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-box me-2"></i>Recent Products</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_products)): ?>
                                        <p class="text-muted">No products uploaded yet</p>
                                        <a href="upload_product.php" class="btn btn-primary">Upload Your First Product</a>
                                    <?php else: ?>
                                        <?php foreach ($recent_products as $product): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                            <div>
                                                <strong><?= htmlspecialchars($product['name']) ?></strong>
                                                <br><small class="text-muted"><?= number_format($product['price']) ?> ETB</small>
                                                <br><span class="badge bg-<?= $product['status'] === 'approved' ? 'success' : ($product['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                    <?= ucfirst($product['status']) ?>
                                                </span>
                                            </div>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?= $product['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <a href="products.php" class="btn btn-primary btn-sm w-100">View All Products</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../../includes/lang_universal.php'; ?>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>