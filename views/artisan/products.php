<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artisan') {
    header('Location: ../auth/login.php');
    exit;
}

// Get artisan ID
$stmt = $pdo->prepare("SELECT id FROM artisans WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$artisan = $stmt->fetch();

// Get all products for this artisan
$stmt = $pdo->prepare("SELECT * FROM products WHERE artisan_id = ? ORDER BY created_at DESC");
$stmt->execute([$artisan['id']]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products</title>
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
                    <h1 class="h2">My Products</h1>
                    <a href="upload_product.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Upload New Product
                    </a>
                </div>

                <?php if (!empty($products)): ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card">
                                <img src="../../<?= $product['image_path'] ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text"><?= substr(htmlspecialchars($product['description']), 0, 100) ?>...</p>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <strong>Price:</strong> <?= number_format($product['price']) ?> ETB<br>
                                            <strong>Category:</strong> <?= ucfirst($product['category']) ?><br>
                                            <strong>Quantity:</strong> <?= $product['quantity'] ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-<?= 
                                            $product['status'] === 'approved' ? 'success' : 
                                            ($product['status'] === 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                        <small class="text-muted d-block">
                                            Created: <?= date('M j, Y', strtotime($product['created_at'])) ?>
                                        </small>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#productModal<?= $product['id'] ?>">
                                            <i class="fas fa-eye me-1"></i>View
                                        </button>
                                        
                                        <?php if ($product['status'] !== 'approved'): ?>
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="editProduct(<?= $product['id'] ?>)">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteProduct(<?= $product['id'] ?>)">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Detail Modal -->
                        <div class="modal fade" id="productModal<?= $product['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?= htmlspecialchars($product['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <img src="../../<?= $product['image_path'] ?>" class="img-fluid rounded mb-3">
                                                <?php if ($product['video_path']): ?>
                                                <video class="w-100" controls>
                                                    <source src="../../<?= $product['video_path'] ?>" type="video/mp4">
                                                </video>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                                                <p><strong>Price:</strong> <?= number_format($product['price']) ?> ETB</p>
                                                <p><strong>Category:</strong> <?= ucfirst($product['category']) ?></p>
                                                <p><strong>Material:</strong> <?= $product['material'] ?></p>
                                                <p><strong>Size:</strong> <?= ucfirst($product['size']) ?></p>
                                                <p><strong>Quantity:</strong> <?= $product['quantity'] ?></p>
                                                <p><strong>Status:</strong> 
                                                    <span class="badge bg-<?= 
                                                        $product['status'] === 'approved' ? 'success' : 
                                                        ($product['status'] === 'pending' ? 'warning' : 'danger') 
                                                    ?>">
                                                        <?= ucfirst($product['status']) ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h4>No Products Yet</h4>
                        <p class="text-muted">You haven't uploaded any products yet. Start showcasing your crafts!</p>
                        <a href="upload_product.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Upload Your First Product
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        function editProduct(productId) {
            window.location.href = `upload_product.php?edit=${productId}`;
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                fetch('../../controllers/ProductController.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to delete product');
                    }
                });
            }
        }
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>