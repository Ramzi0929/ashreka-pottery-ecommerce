<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle product approval/rejection
if ($_POST) {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE products SET status = 'approved' WHERE id = ?");
        $stmt->execute([$product_id]);
        $_SESSION['success'] = 'Product approved successfully';
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE products SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$product_id]);
        $_SESSION['success'] = 'Product rejected';
    }
    header('Location: products_review.php');
    exit;
}

// Get pending products
$stmt = $pdo->prepare("
    SELECT p.*, a.name as artisan_name, u.phone as artisan_phone 
    FROM products p 
    JOIN artisans a ON p.artisan_id = a.id 
    JOIN users u ON a.user_id = u.id 
    WHERE p.status = 'pending' 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Review</title>
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
                    <h1 class="h2">Product Review</h1>
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
                                            <strong>Material:</strong> <?= $product['material'] ?><br>
                                            <strong>Quantity:</strong> <?= $product['quantity'] ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <strong>Artisan:</strong> <?= htmlspecialchars($product['artisan_name']) ?><br>
                                            <strong>Phone:</strong> <?= $product['artisan_phone'] ?>
                                        </small>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <form method="POST" class="flex-fill">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm w-100">
                                                <i class="fas fa-check me-1"></i>Approve
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="flex-fill">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm w-100" 
                                                    onclick="return confirm('Are you sure you want to reject this product?')">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <button class="btn btn-outline-info btn-sm w-100 mt-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#productModal<?= $product['id'] ?>">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
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
                                                <h6>Product Details</h6>
                                                <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                                                <p><strong>Price:</strong> <?= number_format($product['price']) ?> ETB</p>
                                                <p><strong>Category:</strong> <?= ucfirst($product['category']) ?></p>
                                                <p><strong>Material:</strong> <?= $product['material'] ?></p>
                                                <p><strong>Size:</strong> <?= ucfirst($product['size']) ?></p>
                                                <p><strong>Quantity:</strong> <?= $product['quantity'] ?></p>
                                                
                                                <h6 class="mt-3">Artisan Information</h6>
                                                <p><strong>Name:</strong> <?= htmlspecialchars($product['artisan_name']) ?></p>
                                                <p><strong>Phone:</strong> <?= $product['artisan_phone'] ?></p>
                                                <p><strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($product['created_at'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success">Approve Product</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to reject this product?')">
                                                Reject Product
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h4>No Products to Review</h4>
                        <p class="text-muted">All products have been reviewed or no products have been submitted yet.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>