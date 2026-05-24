<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle approval actions
if ($_POST) {
    if (isset($_POST['approve_artisan'])) {
        $artisan_id = $_POST['artisan_id'];
        $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'approved' WHERE id = ?");
        $stmt->execute([$artisan_id]);
        $stmt = $pdo->prepare("UPDATE users u JOIN artisans a ON u.id = a.user_id SET u.status = 'active' WHERE a.id = ?");
        $stmt->execute([$artisan_id]);
        $_SESSION['success'] = 'Artisan approved successfully';
    }
    
    if (isset($_POST['reject_artisan'])) {
        $artisan_id = $_POST['artisan_id'];
        $reason = $_POST['rejection_reason'] ?? 'No reason provided';
        $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $artisan_id]);
        $stmt = $pdo->prepare("UPDATE users u JOIN artisans a ON u.id = a.user_id SET u.status = 'rejected' WHERE a.id = ?");
        $stmt->execute([$artisan_id]);
        $_SESSION['success'] = 'Artisan rejected with reason';
    }
    
    if (isset($_POST['approve_customer'])) {
        $customer_id = $_POST['customer_id'];
        $stmt = $pdo->prepare("UPDATE customers SET is_loyal = 1 WHERE id = ?");
        $stmt->execute([$customer_id]);
        $stmt = $pdo->prepare("UPDATE users u JOIN customers c ON u.id = c.user_id SET u.status = 'active' WHERE c.id = ?");
        $stmt->execute([$customer_id]);
        $_SESSION['success'] = 'Customer approved as loyal';
    }
    
    header('Location: pending_approvals.php');
    exit;
}

// Get pending artisans
$stmt = $pdo->prepare("
    SELECT a.*, u.name, u.email, u.phone, u.created_at 
    FROM artisans a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.approval_status = 'pending' 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$pending_artisans = $stmt->fetchAll();

// Get pending loyal customers (customers with 3+ orders or 1000+ ETB spent)
$stmt = $pdo->prepare("
    SELECT c.*, u.name, u.email, u.phone, u.created_at,
           COUNT(o.id) as order_count, 
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM customers c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN orders o ON c.id = o.customer_id AND o.status = 'completed'
    WHERE c.is_loyal = 0 AND u.status = 'pending'
    GROUP BY c.id 
    HAVING order_count >= 3 OR total_spent >= 1000
    ORDER BY u.created_at DESC
");
$stmt->execute();
$pending_loyal = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals</title>
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
                    <h1 class="h2">Pending Approvals</h1>
                </div>

                <!-- Pending Artisans -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user-tie me-2"></i>Pending Artisan Registrations (<?= count($pending_artisans) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pending_artisans)): ?>
                        <div class="row">
                            <?php foreach ($pending_artisans as $artisan): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6><?= htmlspecialchars($artisan['name']) ?></h6>
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($artisan['email']) ?><br>
                                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($artisan['phone']) ?><br>
                                            <i class="fas fa-clock me-1"></i>Applied: <?= date('M j, Y g:i A', strtotime($artisan['created_at'])) ?>
                                        </p>
                                        
                                        <div class="mb-2">
                                            <span class="badge bg-info"><?= ucfirst($artisan['skill_type']) ?></span>
                                            <span class="badge bg-secondary"><?= $artisan['experience_years'] ?> years exp</span>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#reviewModal<?= $artisan['id'] ?>">
                                                <i class="fas fa-search me-1"></i>Review
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Artisan Review Modal -->
                            <div class="modal fade" id="reviewModal<?= $artisan['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Review Artisan Application</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <!-- Profile Photo -->
                                                <div class="col-md-3 text-center mb-4">
                                                    <?php if ($artisan['profile_image']): ?>
                                                        <img src="../../uploads/profiles/<?= $artisan['profile_image'] ?>" 
                                                             class="img-fluid rounded-circle mb-3" 
                                                             style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #007bff;">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mb-3" 
                                                             style="width: 150px; height: 150px; margin: 0 auto;">
                                                            <i class="fas fa-user fa-4x text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <h5 class="text-primary"><?= htmlspecialchars($artisan['name']) ?></h5>
                                                    <span class="badge bg-info fs-6"><?= ucfirst($artisan['skill_type']) ?> Artisan</span>
                                                </div>
                                                
                                                <!-- Personal Information -->
                                                <div class="col-md-9">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="card h-100">
                                                                <div class="card-header bg-light">
                                                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p><strong>Full Name:</strong><br><?= htmlspecialchars($artisan['name']) ?></p>
                                                                    <p><strong>Email:</strong><br><?= htmlspecialchars($artisan['email']) ?></p>
                                                                    <p><strong>Phone:</strong><br><?= htmlspecialchars($artisan['phone']) ?></p>
                                                                    <p><strong>Address:</strong><br><?= htmlspecialchars($artisan['address'] ?: 'Not provided') ?></p>
                                                                    <p><strong>Applied:</strong><br><?= date('M j, Y g:i A', strtotime($artisan['created_at'])) ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card h-100">
                                                                <div class="card-header bg-light">
                                                                    <h6 class="mb-0"><i class="fas fa-hammer me-2"></i>Craft Expertise</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p><strong>Specialization:</strong><br>
                                                                        <span class="badge bg-primary"><?= ucfirst($artisan['skill_type']) ?></span>
                                                                    </p>
                                                                    <p><strong>Experience:</strong><br>
                                                                        <span class="badge bg-success"><?= $artisan['experience_years'] ?> years</span>
                                                                    </p>
                                                                    <p><strong>Status:</strong><br>
                                                                        <span class="badge bg-warning">Pending Review</span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Craft Description -->
                                            <?php if ($artisan['description']): ?>
                                            <div class="card mt-4">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0"><i class="fas fa-scroll me-2"></i>About Their Craft</h6>
                                                </div>
                                                <div class="card-body">
                                                    <p class="mb-0"><?= nl2br(htmlspecialchars($artisan['description'])) ?></p>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Portfolio Images -->
                                            <?php 
                                            $portfolio_stmt = $pdo->prepare("SELECT image_path FROM artisan_portfolio WHERE artisan_id = ?");
                                            $portfolio_stmt->execute([$artisan['id']]);
                                            $portfolio_images = $portfolio_stmt->fetchAll();
                                            ?>
                                            <?php if (!empty($portfolio_images)): ?>
                                            <div class="card mt-4">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0"><i class="fas fa-images me-2"></i>Portfolio (<?= count($portfolio_images) ?> images)</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <?php foreach ($portfolio_images as $image): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <img src="../../uploads/portfolio/<?= $image['image_path'] ?>" 
                                                                 class="img-fluid rounded shadow" 
                                                                 style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;"
                                                                 onclick="showImageModal('../../uploads/portfolio/<?= $image['image_path'] ?>')">
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-2"></i>Close
                                            </button>
                                            <button type="button" class="btn btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#rejectModal<?= $artisan['id'] ?>">
                                                <i class="fas fa-times me-2"></i>Reject Application
                                            </button>
                                            <button type="button" class="btn btn-success" onclick="approveArtisan(<?= $artisan['id'] ?>)">
                                                <i class="fas fa-check me-2"></i>Approve Artisan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rejection Modal -->
                            <div class="modal fade" id="rejectModal<?= $artisan['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Reject Application</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <p>Are you sure you want to reject <strong><?= htmlspecialchars($artisan['name']) ?></strong>'s application?</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Reason for rejection:</label>
                                                    <textarea class="form-control" name="rejection_reason" rows="3" 
                                                              placeholder="Please provide a reason for rejection..." required></textarea>
                                                </div>
                                                <input type="hidden" name="artisan_id" value="<?= $artisan['id'] ?>">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="reject_artisan" class="btn btn-danger">
                                                    <i class="fas fa-times me-2"></i>Reject Application
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                            <h5>No Pending Artisan Applications</h5>
                            <p class="text-muted">All artisan applications have been reviewed.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Loyal Customers -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-crown me-2"></i>Pending Loyal Customer Approvals (<?= count($pending_loyal) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pending_loyal)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                        <th>Applied</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_loyal as $customer): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($customer['name']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($customer['email']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($customer['phone']) ?></small>
                                        </td>
                                        <td><span class="badge bg-primary"><?= $customer['order_count'] ?> orders</span></td>
                                        <td><span class="badge bg-success"><?= number_format($customer['total_spent']) ?> ETB</span></td>
                                        <td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-gold" onclick="approveCustomer(<?= $customer['id'] ?>)">
                                                <i class="fas fa-crown me-1"></i>Make Loyal
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-crown fa-3x text-muted mb-3"></i>
                            <h5>No Pending Loyal Customer Applications</h5>
                            <p class="text-muted">No customers currently qualify for loyal status approval.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Portfolio Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="img-fluid" style="max-height: 500px;">
                </div>
            </div>
        </div>
    </div>

    <script>
        function approveArtisan(artisanId) {
            if (confirm('Approve this artisan? They will be able to login and start uploading products.')) {
                submitForm('approve_artisan', 'artisan_id', artisanId);
            }
        }
        
        function approveCustomer(customerId) {
            if (confirm('Approve this customer as loyal? They will unlock custom order features.')) {
                submitForm('approve_customer', 'customer_id', customerId);
            }
        }
        
        function submitForm(action, idField, id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="${action}" value="1">
                <input type="hidden" name="${idField}" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>