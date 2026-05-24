<?php
session_start();
require_once '../config/database_enhanced.php';

if (!isset($_GET['id'])) {
    exit('Order ID required');
}

$stmt = $pdo->prepare("
    SELECT o.*, p.name as product_name, p.image_path, p.description,
           a.name as artisan_name, a.profile_image as artisan_image, u.phone as artisan_phone,
           op.progress_percentage, op.status as progress_status, op.notes as progress_notes,
           op.updated_at as progress_updated
    FROM orders o 
    LEFT JOIN products p ON o.product_id = p.id 
    LEFT JOIN artisans a ON o.artisan_id = a.id
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN order_progress op ON o.id = op.order_id
    WHERE o.id = ?
");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

if (!$order) {
    exit('Order not found');
}

// Get progress history
$stmt = $pdo->prepare("SELECT * FROM order_progress WHERE order_id = ? ORDER BY updated_at DESC");
$stmt->execute([$_GET['id']]);
$progressHistory = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-6">
        <?php if ($order['image_path']): ?>
        <img src="../<?= $order['image_path'] ?>" class="img-fluid rounded mb-3" alt="Product">
        <?php endif; ?>
        
        <h4><?= $order['product_name'] ?: 'Custom Order' ?></h4>
        <?php if ($order['description']): ?>
        <p class="text-muted"><?= htmlspecialchars($order['description']) ?></p>
        <?php endif; ?>
        
        <?php if ($order['order_type'] === 'custom'): ?>
        <div class="alert alert-info">
            <h6>Custom Specifications:</h6>
            <p class="mb-0"><?= nl2br(htmlspecialchars($order['custom_specifications'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Order Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Order ID:</strong></td>
                        <td>#<?= $order['id'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge bg-<?= 
                                $order['status'] === 'completed' ? 'success' : 
                                ($order['status'] === 'in_progress' ? 'warning' : 'secondary') 
                            ?>">
                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Amount:</strong></td>
                        <td><?= number_format($order['total_amount']) ?> ETB</td>
                    </tr>
                    <tr>
                        <td><strong>Order Date:</strong></td>
                        <td><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></td>
                    </tr>
                    <?php if ($order['expected_delivery']): ?>
                    <tr>
                        <td><strong>Expected Delivery:</strong></td>
                        <td><?= date('M j, Y', strtotime($order['expected_delivery'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <?php if ($order['artisan_name']): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h5>Artisan Information</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <img src="../<?= $order['artisan_image'] ?: 'assets/images/default-avatar.png' ?>" 
                         class="rounded-circle me-3" width="60" height="60">
                    <div>
                        <h6><?= $order['artisan_name'] ?></h6>
                        <a href="tel:<?= $order['artisan_phone'] ?>" class="text-primary">
                            <i class="fas fa-phone me-1"></i><?= $order['artisan_phone'] ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($progressHistory)): ?>
<div class="mt-4">
    <h5>Progress Timeline</h5>
    <div class="timeline">
        <?php foreach ($progressHistory as $progress): ?>
        <div class="timeline-item">
            <div class="timeline-marker bg-primary"></div>
            <div class="timeline-content">
                <h6 class="mb-1"><?= $progress['progress_percentage'] ?>% Complete</h6>
                <p class="text-muted mb-1"><?= htmlspecialchars($progress['notes']) ?></p>
                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($progress['updated_at'])) ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-item {
    position: relative;
    margin-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
}
.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}
</style>