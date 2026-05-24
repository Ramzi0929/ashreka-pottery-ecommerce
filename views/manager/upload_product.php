<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle form submission
if ($_POST) {
    try {
        $pdo->beginTransaction();
        
        // Create company artisan entry if not exists
        $stmt = $pdo->prepare("SELECT id FROM artisans WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $company_artisan = $stmt->fetch();
        
        if (!$company_artisan) {
            $stmt = $pdo->prepare("INSERT INTO artisans (user_id, name, skill_type, experience_years, description, approval_status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'Ashreka & Friends Company', 'pottery', 15, 'Official company products', 'approved']);
            $artisan_id = $pdo->lastInsertId();
        } else {
            $artisan_id = $company_artisan['id'];
        }
        
        // Handle file uploads
        $image_path = null;
        $video_path = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../../assets/uploads/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $image_name = 'product_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_path = $upload_dir . $image_name;
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
            $image_path = 'assets/uploads/products/' . $image_name;
        }
        
        if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
            $upload_dir = '../../assets/uploads/videos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $video_name = 'video_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
            $video_path = $upload_dir . $video_name;
            move_uploaded_file($_FILES['video']['tmp_name'], $video_path);
            $video_path = 'assets/uploads/videos/' . $video_name;
        }
        
        // Insert product
        $stmt = $pdo->prepare("INSERT INTO products (artisan_id, name, description, price, category, size, material, quantity, image_path, video_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([
            $artisan_id,
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['category'],
            $_POST['size'],
            $_POST['material'],
            $_POST['quantity'],
            $image_path,
            $video_path
        ]);
        
        $pdo->commit();
        $_SESSION['success'] = 'Company product uploaded successfully!';
        header('Location: dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Upload failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Company Product</title>
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
                    <h1 class="h2">Upload Company Product</h1>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-upload me-2"></i>Company Product Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Product Name *</label>
                                            <input type="text" class="form-control" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Category *</label>
                                            <select class="form-select" name="category" required>
                                                <option value="">Select Category</option>
                                                <option value="pottery">Pottery</option>
                                                <option value="weaving">Weaving</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Size *</label>
                                            <select class="form-select" name="size" required>
                                                <option value="">Select Size</option>
                                                <option value="small">Small</option>
                                                <option value="medium">Medium</option>
                                                <option value="large">Large</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Price (ETB) *</label>
                                            <input type="number" class="form-control" name="price" required min="1" step="0.01">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Quantity *</label>
                                            <input type="number" class="form-control" name="quantity" required min="1">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Material Used</label>
                                        <input type="text" class="form-control" name="material">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="4"></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Product Image *</label>
                                            <input type="file" class="form-control" name="image" accept="image/*" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Process Video (Optional)</label>
                                            <input type="file" class="form-control" name="video" accept="video/*">
                                        </div>
                                    </div>

                                    <div class="alert alert-success">
                                        <i class="fas fa-building me-2"></i>
                                        <strong>Company Product:</strong> This product will be published under "Ashreka & Friends Company" name.
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="dashboard.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Upload Product
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>