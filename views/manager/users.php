<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle user actions
if ($_POST) {
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $status, $user_id])) {
            $_SESSION['success'] = 'User updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update user';
        }
    }
    
    if (isset($_POST['approve_artisan'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'approved' WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'Artisan approved successfully';
    }
    
    if (isset($_POST['reject_artisan'])) {
        $user_id = $_POST['user_id'];
        $reason = $_POST['rejection_reason'] ?? 'No reason provided';
        $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'rejected', rejection_reason = ? WHERE user_id = ?");
        $stmt->execute([$reason, $user_id]);
        $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'Artisan rejected with reason';
    }
    
    if (isset($_POST['approve_customer'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'Customer approved successfully';
    }
    
    if (isset($_POST['reject_customer'])) {
        $user_id = $_POST['user_id'];
        $reason = $_POST['rejection_reason'] ?? 'No reason provided';
        $stmt = $pdo->prepare("UPDATE users SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $user_id]);
        $_SESSION['success'] = 'Customer rejected with reason';
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // First get user data for undo
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role NOT IN ('admin', 'manager')");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            // Store in session for undo
            $_SESSION['deleted_user'] = $user_data;
            $_SESSION['deleted_user_time'] = time();
            
            // Soft delete - mark as deleted instead of actual delete
            $stmt = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = 'User deleted successfully';
            $_SESSION['show_undo'] = true;
        } else {
            $_SESSION['error'] = 'Cannot delete admin/manager users';
        }
    }
    
    if (isset($_POST['undo_delete'])) {
        if (isset($_SESSION['deleted_user']) && (time() - $_SESSION['deleted_user_time']) < 10) {
            $user_id = $_SESSION['deleted_user']['id'];
            $original_status = $_SESSION['deleted_user']['status'];
            
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$original_status, $user_id]);
            
            unset($_SESSION['deleted_user'], $_SESSION['deleted_user_time'], $_SESSION['show_undo']);
            $_SESSION['success'] = 'User deletion undone successfully';
        } else {
            $_SESSION['error'] = 'Undo time expired (10 seconds)';
        }
    }
    
    if (isset($_POST['permanent_delete'])) {
        if (isset($_SESSION['deleted_user'])) {
            $user_id = $_SESSION['deleted_user']['id'];
            
            // Permanently delete from database
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            unset($_SESSION['deleted_user'], $_SESSION['deleted_user_time'], $_SESSION['show_undo']);
        }
        exit; // Stop execution for AJAX call
    }
    
    header('Location: users.php');
    exit;
}

// Get all users with additional info (exclude soft deleted)
$stmt = $pdo->query("
    SELECT u.*, 
           c.is_loyal, c.purchase_count,
           a.approval_status as artisan_status
    FROM users u 
    LEFT JOIN customers c ON u.id = c.user_id 
    LEFT JOIN artisans a ON u.id = a.user_id 
    WHERE u.status != 'deleted'
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
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
                
                <?php if (isset($_SESSION['show_undo']) && isset($_SESSION['deleted_user'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert" id="undoAlert">
                    <i class="fas fa-trash me-2"></i>
                    User "<?= htmlspecialchars($_SESSION['deleted_user']['email']) ?>" has been deleted.
                    <button type="button" class="btn btn-sm btn-success ms-3" onclick="undoDelete()" id="undoBtn">
                        <i class="fas fa-undo me-1"></i>Undo (<span id="countdown">10</span>s)
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <script>
                    let timeLeft = <?= 10 - (time() - $_SESSION['deleted_user_time']) ?>;
                    const countdownEl = document.getElementById('countdown');
                    const undoBtn = document.getElementById('undoBtn');
                    const undoAlert = document.getElementById('undoAlert');
                    
                    const timer = setInterval(() => {
                        timeLeft--;
                        countdownEl.textContent = timeLeft;
                        
                        if (timeLeft <= 0) {
                            clearInterval(timer);
                            undoBtn.disabled = true;
                            undoBtn.innerHTML = '<i class="fas fa-times me-1"></i>Deleting...';
                            undoBtn.className = 'btn btn-sm btn-danger ms-3';
                            
                            // Permanently delete user
                            fetch('', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'permanent_delete=1'
                            }).then(() => {
                                undoAlert.innerHTML = '<i class="fas fa-check me-2"></i>User permanently deleted.';
                                undoAlert.className = 'alert alert-danger';
                                setTimeout(() => {
                                    location.reload();
                                }, 2000);
                            });
                        }
                    }, 1000);
                </script>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User Management</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['name'] ?: $user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $user['role'] === 'admin' ? 'danger' : 
                                                ($user['role'] === 'manager' ? 'warning' : 
                                                ($user['role'] === 'artisan' ? 'dark' : 'primary'))
                                            ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                            <?php if ($user['role'] === 'customer' && $user['is_loyal']): ?>
                                            <br><span class="badge bg-gold mt-1">Loyal</span>
                                            <?php endif; ?>
                                            <?php if ($user['role'] === 'artisan' && $user['artisan_status']): ?>
                                            <br><span class="badge bg-info mt-1"><?= ucfirst($user['artisan_status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'artisan' && $user['artisan_status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="approveArtisan(<?= $user['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="rejectArtisan(<?= $user['id'] ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php elseif ($user['role'] === 'customer' && $user['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="approveCustomer(<?= $user['id'] ?>)">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="rejectCustomer(<?= $user['id'] ?>)">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!in_array($user['role'], ['admin', 'manager'])): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteUser(<?= $user['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- View Details Modal -->
                                    <div class="modal fade" id="viewModal<?= $user['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">User Details: <?= htmlspecialchars($user['email']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Basic Information</h6>
                                                            <p><strong>ID:</strong> <?= $user['id'] ?></p>
                                                            <p><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?: 'Not provided') ?></p>
                                                            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                                            <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></p>
                                                            <p><strong>Role:</strong> <span class="badge bg-<?= 
                                                                $user['role'] === 'admin' ? 'danger' : 
                                                                ($user['role'] === 'manager' ? 'warning' : 
                                                                ($user['role'] === 'artisan' ? 'dark' : 'primary'))
                                                            ?>"><?= ucfirst($user['role']) ?></span></p>
                                                            <p><strong>Status:</strong> <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($user['status']) ?></span></p>
                                                            <p><strong>Registered:</strong> <?= date('M j, Y g:i A', strtotime($user['created_at'])) ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <?php if ($user['role'] === 'customer'): ?>
                                                            <h6>Customer Information</h6>
                                                            <p><strong>Loyalty Status:</strong> 
                                                                <?php if ($user['is_loyal']): ?>
                                                                <span class="badge bg-gold">Loyal Customer</span>
                                                                <?php else: ?>
                                                                <span class="badge bg-secondary">Regular Customer</span>
                                                                <?php endif; ?>
                                                            </p>
                                                            <p><strong>Purchase Count:</strong> <?= $user['purchase_count'] ?: 0 ?></p>
                                                            <?php elseif ($user['role'] === 'artisan'): ?>
                                                            <h6>Artisan Information</h6>
                                                            <p><strong>Approval Status:</strong> 
                                                                <span class="badge bg-<?= 
                                                                    $user['artisan_status'] === 'approved' ? 'success' : 
                                                                    ($user['artisan_status'] === 'pending' ? 'warning' : 'danger')
                                                                ?>"><?= ucfirst($user['artisan_status'] ?: 'Not set') ?></span>
                                                            </p>
                                                            <?php 
                                                            // Get additional artisan details
                                                            $stmt = $pdo->prepare("SELECT * FROM artisans WHERE user_id = ?");
                                                            $stmt->execute([$user['id']]);
                                                            $artisan = $stmt->fetch();
                                                            if ($artisan): 
                                                            ?>
                                                            <p><strong>Skill Type:</strong> <?= ucfirst($artisan['skill_type']) ?></p>
                                                            <p><strong>Experience:</strong> <?= $artisan['experience_years'] ?> years</p>
                                                            <p><strong>Address:</strong> <?= htmlspecialchars($artisan['address'] ?: 'Not provided') ?></p>
                                                            <?php if ($artisan['description']): ?>
                                                            <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($artisan['description'])) ?></p>
                                                            <?php endif; ?>
                                                            <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="button" class="btn btn-primary" 
                                                            onclick="openEditModal(<?= $user['id'] ?>)">
                                                        <i class="fas fa-edit me-2"></i>Edit User
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $user['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit User: <?= htmlspecialchars($user['email']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" name="name" 
                                                                   value="<?= htmlspecialchars($user['name'] ?: '') ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" class="form-control" name="email" 
                                                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone</label>
                                                            <input type="tel" class="form-control" name="phone" 
                                                                   value="<?= htmlspecialchars($user['phone'] ?: '') ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="pending" <?= $user['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                                <option value="rejected" <?= $user['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Role</label>
                                                            <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly>
                                                            <small class="text-muted">Role cannot be changed</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>

        
        function approveArtisan(userId) {
            if (confirm('Approve this artisan?')) {
                submitForm('approve_artisan', userId);
            }
        }
        
        function rejectArtisan(userId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason && reason.trim()) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="reject_artisan" value="1">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="rejection_reason" value="${reason}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectCustomer(userId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason && reason.trim()) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="reject_customer" value="1">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="rejection_reason" value="${reason}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function approveCustomer(userId) {
            if (confirm('Approve this customer?')) {
                submitForm('approve_customer', userId);
            }
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                submitForm('delete_user', userId);
            }
        }
        
        function undoDelete() {
            if (confirm('Restore the deleted user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="undo_delete" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function submitForm(action, userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="${action}" value="1">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>