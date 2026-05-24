<?php
require_once '../config/database_enhanced.php';

if (!isset($_GET['id'])) {
    exit('Product ID required');
}

$stmt = $pdo->prepare("
    SELECT p.*, a.name as artisan_name, a.bio, a.profile_image, u.phone as artisan_phone
    FROM products p 
    JOIN artisans a ON p.artisan_id = a.id 
    JOIN users u ON a.user_id = u.id
    WHERE p.id = ? AND p.status = 'approved'
");
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch();

if (!$product) {
    exit('Product not found');
}
?>

<div class="row">
    <div class="col-md-6">
        <img src="../<?= $product['image_path'] ?>" class="img-fluid rounded" alt="<?= $product['name'] ?>">
        <?php if ($product['video_path']): ?>
        <div class="mt-3">
            <video class="w-100" controls>
                <source src="../<?= $product['video_path'] ?>" type="video/mp4">
            </video>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
        
        <div class="mb-3">
            <strong>Price:</strong> <?= number_format($product['price']) ?> ETB<br>
            <strong>Category:</strong> <?= ucfirst($product['category']) ?><br>
            <strong>Material:</strong> <?= $product['material'] ?><br>
            <strong>Size:</strong> <?= ucfirst($product['size']) ?><br>
            <strong>Stock:</strong> <?= $product['quantity'] ?> available
        </div>
        
        <div class="border-top pt-3">
            <h5>About the Artisan</h5>
            <div class="d-flex align-items-center mb-2">
                <img src="../<?= $product['profile_image'] ?: 'assets/images/default-avatar.png' ?>" 
                     class="rounded-circle me-3" width="50" height="50">
                <div>
                    <strong><?= $product['artisan_name'] ?></strong><br>
                    <a href="tel:<?= $product['artisan_phone'] ?>" class="text-primary">
                        <i class="fas fa-phone me-1"></i><?= $product['artisan_phone'] ?>
                    </a>
                </div>
            </div>
            <?php if ($product['bio']): ?>
            <p class="small text-muted"><?= htmlspecialchars($product['bio']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>