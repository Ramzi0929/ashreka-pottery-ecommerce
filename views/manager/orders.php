<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle order assignment
if ($_POST && isset($_POST['assign_order'])) {
    $order_id = $_POST['order_id'];
    $artisan_id = $_POST['artisan_id'];
    
    $stmt = $pdo->prepare("UPDATE orders SET artisan_id = ?, status = 'in_progress' WHERE id = ?");
    if ($stmt->execute([$artisan_id, $order_id])) {
        $_SESSION['success'] = 'Order assigned successfully';
    } else {
        $_SESSION['error'] = 'Failed to assign order';
    }
    header('Location: orders.php');
    exit;
}

// Get all orders
$stmt = $pdo->prepare("
    SELECT o.*, p.name as product_name, p.image_path,
           u.name as customer_name, u.email as customer_email,
           a.name as artisan_name
    FROM orders o 
    LEFT JOIN products p ON o.product_id = p.id 
    JOIN customers c ON o.customer_id = c.id 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN artisans a ON o.artisan_id = a.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

// Get active artisans for assignment
$stmt = $pdo->prepare("SELECT a.id, u.name FROM artisans a JOIN users u ON a.user_id = u.id WHERE a.approval_status = 'approved'");
$stmt->execute();
$artisans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
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
                    <h1 class="h2">Order Management</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Artisan</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= $order['id'] ?></td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($order['image_path']): ?>
                                                    <img src="../../<?= $order['image_path'] ?>" width="40" height="40" class="rounded me-2">
                                                    <?php endif; ?>
                                                    <div>
                                                        <?= htmlspecialchars($order['product_name'] ?: 'Custom Order') ?>
                                                        <?php if ($order['order_type'] === 'custom'): ?>
                                                        <br><small class="text-muted">Custom specifications</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= number_format($order['total_amount']) ?> ETB</td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $order['status'] === 'completed' ? 'success' : 
                                                    ($order['status'] === 'in_progress' ? 'warning' : 
                                                    ($order['status'] === 'pending' ? 'secondary' : 'danger'))
                                                ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($order['artisan_name']): ?>
                                                    <?= htmlspecialchars($order['artisan_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <?php if ($order['status'] === 'pending' && !$order['artisan_id']): ?>
                                                <button class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#assignModal<?= $order['id'] ?>">
                                                    Assign
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-outline-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detailModal<?= $order['id'] ?>">
                                                    Details
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Assignment Modal -->
                                        <div class="modal fade" id="assignModal<?= $order['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Assign Order #<?= $order['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Select Artisan</label>
                                                                <select name="artisan_id" class="form-select" required>
                                                                    <option value="">Choose artisan...</option>
                                                                    <?php foreach ($artisans as $artisan): ?>
                                                                    <option value="<?= $artisan['id'] ?>"><?= htmlspecialchars($artisan['name']) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="assign_order" class="btn btn-primary">Assign Order</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Detail Modal -->
                                        <div class="modal fade" id="detailModal<?= $order['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Order Details #<?= $order['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Customer Information</h6>
                                                                <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                                                                <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Order Information</h6>
                                                                <p><strong>Amount:</strong> <?= number_format($order['total_amount']) ?> ETB</p>
                                                                <p><strong>Status:</strong> <?= ucfirst(str_replace('_', ' ', $order['status'])) ?></p>
                                                                <p><strong>Date:</strong> <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($order['order_type'] === 'custom' && $order['custom_specifications']): ?>
                                                        <div class="mt-3">
                                                            <h6>Custom Specifications</h6>
                                                            <div class="alert alert-info">
                                                                <?= nl2br(htmlspecialchars($order['custom_specifications'])) ?>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h4>No Orders Found</h4>
                                <p class="text-muted">Orders will appear here once customers start purchasing.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>