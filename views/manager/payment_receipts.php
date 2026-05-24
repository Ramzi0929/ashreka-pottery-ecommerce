<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle receipt approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $receipt_id = $_POST['receipt_id'] ?? 0;
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if ($receipt_id && in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $pdo->beginTransaction();
        try {
            // Update receipt status
            $stmt = $pdo->prepare("
                UPDATE payment_receipts 
                SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $admin_notes, $_SESSION['user_id'], $receipt_id]);
            
            // Get receipt details
            $stmt = $pdo->prepare("
                SELECT pr.*, o.customer_id, p.amount 
                FROM payment_receipts pr 
                JOIN orders o ON pr.order_id = o.id 
                JOIN payments p ON pr.payment_id = p.id 
                WHERE pr.id = ?
            ");
            $stmt->execute([$receipt_id]);
            $receipt = $stmt->fetch();
            
            if ($receipt && $status === 'approved') {
                // Update payment status
                $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
                $stmt->execute([$receipt['payment_id']]);
                
                // Update order payment status
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET paid_amount = ?, payment_status = 'full', payment_receipt_status = 'approved' 
                    WHERE id = ?
                ");
                $stmt->execute([$receipt['amount'], $receipt['order_id']]);
                
                // Check loyalty status
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM orders 
                    WHERE customer_id = ? AND payment_status = 'full'
                ");
                $stmt->execute([$receipt['customer_id']]);
                $completed_orders = $stmt->fetchColumn();
                
                if ($completed_orders >= 3) {
                    $stmt = $pdo->prepare("
                        UPDATE customers 
                        SET is_loyal = 1, purchase_count = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$completed_orders, $receipt['customer_id']]);
                }
            } elseif ($receipt && $status === 'rejected') {
                // Update order receipt status
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_receipt_status = 'rejected' 
                    WHERE id = ?
                ");
                $stmt->execute([$receipt['order_id']]);
            }
            
            $pdo->commit();
            $success_message = "Receipt " . ($status === 'approved' ? 'approved' : 'rejected') . " successfully!";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error processing receipt: " . $e->getMessage();
        }
    }
}

// Get pending receipts
$stmt = $pdo->prepare("
    SELECT 
        pr.*,
        o.id as order_id,
        o.total_amount,
        o.created_at as order_date,
        c.name as customer_name,
        u.phone as customer_phone,
        p.payment_method,
        p.selected_bank
    FROM payment_receipts pr
    JOIN orders o ON pr.order_id = o.id
    JOIN customers c ON pr.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN payments p ON pr.payment_id = p.id
    WHERE pr.status = 'pending'
    ORDER BY pr.uploaded_at DESC
");
$stmt->execute();
$pending_receipts = $stmt->fetchAll();

// Get recent approved/rejected receipts
$stmt = $pdo->prepare("
    SELECT 
        pr.*,
        o.id as order_id,
        o.total_amount,
        c.name as customer_name,
        p.payment_method,
        p.selected_bank,
        reviewer.name as reviewer_name
    FROM payment_receipts pr
    JOIN orders o ON pr.order_id = o.id
    JOIN customers c ON pr.customer_id = c.id
    JOIN payments p ON pr.payment_id = p.id
    LEFT JOIN users reviewer ON pr.reviewed_by = reviewer.id
    WHERE pr.status IN ('approved', 'rejected')
    ORDER BY pr.reviewed_at DESC
    LIMIT 20
");
$stmt->execute();
$recent_receipts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt Management - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .receipt-card { border-left: 4px solid #ffc107; }
        .receipt-card.approved { border-left-color: #28a745; }
        .receipt-card.rejected { border-left-color: #dc3545; }
        .receipt-image { max-width: 200px; max-height: 150px; object-fit: cover; }
        .status-badge.pending { background-color: #ffc107; }
        .status-badge.approved { background-color: #28a745; }
        .status-badge.rejected { background-color: #dc3545; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-receipt me-2"></i>Payment Receipt Management</h2>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </div>

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Pending Receipts -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Pending Receipt Reviews 
                            <span class="badge bg-dark"><?= count($pending_receipts) ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_receipts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>No Pending Receipts</h5>
                            <p class="text-muted">All receipts have been reviewed!</p>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($pending_receipts as $receipt): ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card receipt-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Order #<?= $receipt['order_id'] ?></h6>
                                        <span class="badge status-badge pending">Pending</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong>Customer:</strong> <?= htmlspecialchars($receipt['customer_name']) ?><br>
                                            <strong>Phone:</strong> <?= htmlspecialchars($receipt['customer_phone']) ?><br>
                                            <strong>Amount:</strong> <?= number_format($receipt['total_amount']) ?> ETB<br>
                                            <strong>Payment Method:</strong> 
                                            <?php if ($receipt['payment_method'] === 'telebirr'): ?>
                                                <span class="badge bg-success">TeleBirr</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?= htmlspecialchars($receipt['selected_bank']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($receipt['receipt_image']): ?>
                                        <div class="mb-3">
                                            <strong>Receipt Image:</strong><br>
                                            <img src="../../<?= $receipt['receipt_image'] ?>" 
                                                 class="receipt-image img-thumbnail" 
                                                 onclick="showImageModal('../../<?= $receipt['receipt_image'] ?>')">
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($receipt['receipt_link']): ?>
                                        <div class="mb-3">
                                            <strong>Receipt Link:</strong><br>
                                            <a href="<?= htmlspecialchars($receipt['receipt_link']) ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i>View Link
                                            </a>
                                        </div>
                                        <?php endif; ?>

                                        <small class="text-muted">
                                            Uploaded: <?= date('M j, Y g:i A', strtotime($receipt['uploaded_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="card-footer">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="receipt_id" value="<?= $receipt['id'] ?>">
                                            <div class="mb-2">
                                                <textarea name="admin_notes" class="form-control form-control-sm" 
                                                         placeholder="Add notes (optional)" rows="2"></textarea>
                                            </div>
                                            <div class="d-grid gap-2 d-md-flex">
                                                <button type="submit" name="action" value="approve" 
                                                        class="btn btn-success btn-sm flex-fill">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" 
                                                        class="btn btn-danger btn-sm flex-fill">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Reviews -->
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent Reviews
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_receipts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Recent Reviews</h5>
                            <p class="text-muted">Recent receipt reviews will appear here.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Reviewed By</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_receipts as $receipt): ?>
                                    <tr>
                                        <td>#<?= $receipt['order_id'] ?></td>
                                        <td><?= htmlspecialchars($receipt['customer_name']) ?></td>
                                        <td><?= number_format($receipt['total_amount']) ?> ETB</td>
                                        <td>
                                            <?php if ($receipt['payment_method'] === 'telebirr'): ?>
                                                <span class="badge bg-success">TeleBirr</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?= htmlspecialchars($receipt['selected_bank']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge status-badge <?= $receipt['status'] ?>">
                                                <?= ucfirst($receipt['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($receipt['reviewer_name'] ?? 'System') ?></td>
                                        <td><?= date('M j, Y', strtotime($receipt['reviewed_at'])) ?></td>
                                        <td>
                                            <?php if ($receipt['receipt_image']): ?>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="showImageModal('../../<?= $receipt['receipt_image'] ?>')">
                                                <i class="fas fa-image"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($receipt['receipt_link']): ?>
                                            <a href="<?= htmlspecialchars($receipt['receipt_link']) ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receipt Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }

        // Auto-refresh every 30 seconds for new receipts
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>