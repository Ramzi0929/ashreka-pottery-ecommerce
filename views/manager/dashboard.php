<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Get statistics
$stats = [];

// Total artisans
$stmt = $pdo->query("SELECT COUNT(*) FROM artisans");
$stats['total_artisans'] = $stmt->fetchColumn();

// Active artisans
$stmt = $pdo->query("SELECT COUNT(*) FROM artisans WHERE approval_status = 'approved'");
$stats['active_artisans'] = $stmt->fetchColumn();

// Total products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// Pending products
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending'");
$stats['pending_products'] = $stmt->fetchColumn();

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->prepare("
    SELECT o.*, p.name as product_name, u.name as customer_name 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id 
    JOIN customers c ON o.customer_id = c.id 
    JOIN users u ON c.user_id = u.id 
    ORDER BY o.created_at DESC LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../layouts/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php include '../layouts/alerts.php'; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manager Dashboard</h1>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Artisans</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_artisans'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Artisans</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['active_artisans'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Products</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_products'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Products</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['pending_products'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Artisan Management -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Artisan Management</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get recent artisans
                        $stmt = $pdo->prepare("
                            SELECT a.*, u.name, u.email, u.phone, u.created_at 
                            FROM artisans a 
                            JOIN users u ON a.user_id = u.id 
                            ORDER BY u.created_at DESC LIMIT 5
                        ");
                        $stmt->execute();
                        $artisans = $stmt->fetchAll();
                        ?>
                        
                        <?php if (!empty($artisans)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($artisans as $artisan): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($artisan['name']) ?></td>
                                        <td><?= htmlspecialchars($artisan['email']) ?></td>
                                        <td><?= htmlspecialchars($artisan['phone']) ?></td>
                                        <td><?= ucfirst($artisan['skill_type']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $artisan['approval_status'] === 'approved' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($artisan['approval_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($artisan['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="showArtisanModal(<?= $artisan['id'] ?>)">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Artisan Details Modal -->
                                    <div class="modal fade" id="artisanModal<?= $artisan['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Artisan Details: <?= htmlspecialchars($artisan['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-4 text-center">
                                                            <img src="../../<?= $artisan['profile_image'] ?: 'assets/images/default-avatar.png' ?>" 
                                                                 class="rounded-circle mb-3" width="150" height="150">
                                                            <h5><?= htmlspecialchars($artisan['name']) ?></h5>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <p><strong>Email:</strong> <?= htmlspecialchars($artisan['email']) ?></p>
                                                            <p><strong>Phone:</strong> <?= htmlspecialchars($artisan['phone']) ?></p>
                                                            <p><strong>Skill Type:</strong> <?= ucfirst($artisan['skill_type']) ?></p>
                                                            <p><strong>Experience:</strong> <?= $artisan['experience_years'] ?> years</p>
                                                            <p><strong>Status:</strong> 
                                                                <span class="badge bg-<?= $artisan['approval_status'] === 'approved' ? 'success' : 'warning' ?>">
                                                                    <?= ucfirst($artisan['approval_status']) ?>
                                                                </span>
                                                            </p>
                                                            <p><strong>Address:</strong> <?= htmlspecialchars($artisan['address'] ?: 'Not provided') ?></p>
                                                            <p><strong>Registered:</strong> <?= date('M j, Y g:i A', strtotime($artisan['created_at'])) ?></p>
                                                            <?php if ($artisan['description']): ?>
                                                            <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($artisan['description'])) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center">
                            <a href="artisans.php" class="btn btn-primary">View All Artisans</a>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No Artisans Yet</h5>
                            <p class="text-muted">Artisans will appear here once they register.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?= $order['id'] ?></td>
                                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($order['product_name'] ?: 'Custom Order') ?></td>
                                            <td><?= number_format($order['total_amount']) ?> ETB</td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $order['status'] === 'completed' ? 'success' : 
                                                    ($order['status'] === 'in_progress' ? 'warning' : 'secondary') 
                                                ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="orders.php" class="btn btn-primary">View All Orders</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5>No Orders Yet</h5>
                                <p class="text-muted">Orders will appear here once customers start purchasing.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function showArtisanModal(artisanId) {
            var modal = new bootstrap.Modal(document.getElementById('artisanModal' + artisanId));
            modal.show();
        }
    </script>
</body>
</html>