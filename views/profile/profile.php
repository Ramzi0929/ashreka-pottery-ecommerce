<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle profile updates
if ($_POST) {
    $user_id = $_SESSION['user_id'];
    
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $user_id])) {
            $_SESSION['success'] = 'Profile updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update profile';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $_SESSION['success'] = 'Password changed successfully';
                } else {
                    $_SESSION['error'] = 'Failed to change password';
                }
            } else {
                $_SESSION['error'] = 'New passwords do not match';
            }
        } else {
            $_SESSION['error'] = 'Current password is incorrect';
        }
    }
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $upload_dir = '../../assets/uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $db_path = 'assets/uploads/profiles/' . $new_filename;
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, profile_image) VALUES (?, ?) ON DUPLICATE KEY UPDATE profile_image = ?");
            if ($stmt->execute([$user_id, $db_path, $db_path])) {
                $_SESSION['success'] = 'Profile picture updated successfully';
            }
        } else {
            $_SESSION['error'] = 'Failed to upload profile picture';
        }
    }
    
    header('Location: profile.php');
    exit;
}

// Get user data with profile
$stmt = $pdo->prepare("
    SELECT u.*, p.profile_image, p.bio, p.address 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
                    <h1 class="h2">My Profile</h1>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <!-- Profile Picture Card -->
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <?php if ($user['profile_image']): ?>
                                <img id="currentProfileImage" src="../../<?= $user['profile_image'] ?>" 
                                     class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                                <?php else: ?>
                                <div class="rounded-circle mb-3 bg-secondary d-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                                    <i class="fas fa-user fa-4x text-white"></i>
                                </div>
                                <?php endif; ?>
                                <h5><?= htmlspecialchars($user['name'] ?: $user['email']) ?></h5>
                                <p class="text-muted"><?= ucfirst($user['role']) ?></p>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#imageModal">
                                    <i class="fas fa-camera me-1"></i>Change Picture
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Profile Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-user me-2"></i>Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="<?= htmlspecialchars($user['name'] ?: '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?= htmlspecialchars($user['phone'] ?: '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" 
                                                   value="<?= ucfirst($user['role']) ?>" readonly>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" 
                                                   minlength="6" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   minlength="6" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Profile Picture Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select New Picture</label>
                            <input type="file" class="form-control" name="profile_image" 
                                   accept="image/*" required>
                            <small class="text-muted">Max size: 2MB. Formats: JPG, PNG</small>
                        </div>
                        <div class="text-center">
                            <img id="imagePreview" class="img-thumbnail" 
                                 style="display: none; max-width: 200px; max-height: 200px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Picture
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Image preview and real-time update
        document.querySelector('input[name="profile_image"]').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('imagePreview');
            const currentImage = document.getElementById('currentProfileImage');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    // Update main profile image immediately
                    currentImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Auto-submit form when image is selected
        document.querySelector('input[name="profile_image"]').addEventListener('change', function() {
            if (this.files[0]) {
                setTimeout(() => {
                    this.closest('form').submit();
                }, 500);
            }
        });
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/auto-logout.js"></script>
</body>
</html>