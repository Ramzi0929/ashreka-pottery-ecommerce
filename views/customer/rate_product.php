<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header('Location: dashboard.php');
    exit;
}

// Get customer info
$stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

// Handle direct product rating (for loyal customers)
$product_id = $_GET['product_id'] ?? null;
if ($product_id) {
    // Check if customer is loyal
    if (!$customer['is_loyal']) {
        $_SESSION['error'] = 'Only loyal customers can rate products';
        header('Location: catalog.php');
        exit;
    }
    
    // Get product details
    $stmt = $pdo->prepare("
        SELECT p.*, a.id as artisan_id, a.name as artisan_name
        FROM products p 
        JOIN artisans a ON p.artisan_id = a.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product_data = $stmt->fetch();
    
    if (!$product_data) {
        header('Location: catalog.php');
        exit;
    }
} else {
    // Original order-based rating
    $stmt = $pdo->prepare("
        SELECT o.*, oi.product_id, p.name as product_name, p.image_path, a.id as artisan_id, a.name as artisan_name
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        JOIN artisans a ON p.artisan_id = a.id 
        WHERE o.id = ? AND o.customer_id = ? AND o.status = 'completed'
    ");
    $stmt->execute([$order_id, $customer['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: dashboard.php');
        exit;
    }
}

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

// Handle rating submission
if ($_POST && isset($_POST['submit_rating'])) {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    if ($product_id) {
        // Direct product rating
        $stmt = $pdo->prepare("INSERT INTO ratings (customer_id, artisan_id, rating, comment) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$customer['id'], $product_data['artisan_id'], $rating, $comment]);
    } else {
        // Order-based rating
        $stmt = $pdo->prepare("INSERT INTO ratings (order_id, customer_id, artisan_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $success = $stmt->execute([$order_id, $customer['id'], $order['artisan_id'], $rating, $comment]);
    }
    
    if ($success) {
        $_SESSION['success'] = 'Thank you for your rating!';
        header('Location: ' . ($product_id ? 'catalog.php' : 'dashboard.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Product</title>
    <link href="../../assets/css/bootstrap.min.css" rel=\"stylesheet\">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .star-rating { font-size: 2rem; color: #ddd; cursor: pointer; }
        .star-rating .star.active { color: #ffc107; }
        .product-card { background: #fff; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="product-card p-4">
                    <h2 class="text-center mb-4" style="color: #8B4513;">Rate Your Purchase</h2>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <img src="../../<?= $product_id ? $product_data['image_path'] : $order['image_path'] ?>" class="img-fluid rounded" alt="Product">
                        </div>
                        <div class="col-md-8">
                            <h4><?= htmlspecialchars($product_id ? $product_data['name'] : $order['product_name']) ?></h4>
                            <p><strong>Artisan:</strong> <?= htmlspecialchars($product_id ? $product_data['artisan_name'] : $order['artisan_name']) ?></p>
                            <?php if (!$product_id): ?>
                            <p><strong>Order Date:</strong> <?= date('M j, Y', strtotime($order['created_at'])) ?></p>
                            <p><strong>Total:</strong> <?= number_format($order['total_amount']) ?> ETB</p>
                            <?php else: ?>
                            <p><strong>Price:</strong> <?= number_format($product_data['price']) ?> ETB</p>
                            <p class="text-success"><i class="fas fa-crown me-1"></i>Loyal Customer Rating</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-4 text-center">
                            <label class="form-label h5">Rate this product:</label>
                            <div class="star-rating" id="starRating">
                                <span class="star" data-rating="1"><i class="fas fa-star"></i></span>
                                <span class="star" data-rating="2"><i class="fas fa-star"></i></span>
                                <span class="star" data-rating="3"><i class="fas fa-star"></i></span>
                                <span class="star" data-rating="4"><i class="fas fa-star"></i></span>
                                <span class="star" data-rating="5"><i class="fas fa-star"></i></span>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Your Review:</label>
                            <textarea class="form-control" name="comment" rows="4" 
                                      placeholder="Share your experience with this product..."></textarea>
                        </div>

                        <div class="text-center">
                            <a href="dashboard.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="submit_rating" class="btn btn-primary">Submit Rating</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('ratingValue');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingValue.value = rating;
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
</html>