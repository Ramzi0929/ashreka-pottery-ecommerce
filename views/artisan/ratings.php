<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artisan') {
    header("Location: ../auth/login.php");
    exit();
}

// Get artisan info
$stmt = $pdo->prepare("SELECT * FROM artisans WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$artisan = $stmt->fetch();

if (!$artisan || $artisan['approval_status'] !== 'approved') {
    echo "<div class='alert alert-warning m-3'>Your account is pending approval or has been rejected.</div>";
    exit();
}

// Get all ratings with customer and order details
$stmt = $pdo->prepare("
    SELECT r.*, c.name as customer_name, o.id as order_id, o.type as order_type, o.created_at as order_date
    FROM ratings r
    JOIN customers c ON r.customer_id = c.id
    JOIN orders o ON r.order_id = o.id
    WHERE r.artisan_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$artisan['id']]);
$ratings = $stmt->fetchAll();

// Get rating statistics
$stmt = $pdo->prepare("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_ratings,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM ratings WHERE artisan_id = ?
");
$stmt->execute([$artisan['id']]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <?php include '../layouts/artisan_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="dashboard-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-star me-2"></i>My Ratings & Reviews</h2>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <?php if ($stats['total_ratings'] > 0): ?>
                    <!-- Rating Overview -->
                    <div class="row mb-4">
                        <div class="col-lg-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h1 class="display-4 text-warning mb-0"><?= round($stats['avg_rating'], 1) ?></h1>
                                    <div class="text-warning mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $stats['avg_rating'] ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-muted"><?= $stats['total_ratings'] ?> total reviews</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Rating Breakdown</h6>
                                    <?php 
                                    $ratings_breakdown = [
                                        5 => $stats['five_star'],
                                        4 => $stats['four_star'], 
                                        3 => $stats['three_star'],
                                        2 => $stats['two_star'],
                                        1 => $stats['one_star']
                                    ];
                                    foreach($ratings_breakdown as $star => $count): 
                                        $percentage = $stats['total_ratings'] > 0 ? ($count / $stats['total_ratings']) * 100 : 0;
                                    ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2"><?= $star ?> <i class="fas fa-star text-warning"></i></span>
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar bg-warning" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <span class="text-muted"><?= $count ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Reviews -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Customer Reviews</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach($ratings as $rating): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($rating['customer_name']) ?></h6>
                                        <div class="text-warning mb-1">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= $rating['rating'] ? '' : '-o' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted">
                                            Order #<?= $rating['order_id'] ?> • <?= ucfirst($rating['order_type']) ?> Order • 
                                            <?= date('M j, Y', strtotime($rating['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php if($rating['comment']): ?>
                                <p class="mb-0"><?= htmlspecialchars($rating['comment']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- No Ratings Yet -->
                    <div class="text-center py-5">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <h4>No Ratings Yet</h4>
                        <p class="text-muted">Complete some orders to start receiving customer ratings!</p>
                        <a href="orders.php" class="btn btn-primary">View My Orders</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>