<div class="dashboard-sidebar">
    <div class="p-3 text-white">
        <img src="<?= $artisan['profile_image'] ?: '../../assets/images/default-avatar.png' ?>" 
             class="rounded-circle mb-2" width="50" height="50" alt="Profile">
        <h6><?= htmlspecialchars($artisan['name']) ?></h6>
        <small><?= ucfirst($artisan['skill_type']) ?> Artisan</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>" href="products.php">
            <i class="fas fa-box me-2"></i>My Products
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'upload_product.php' ? 'active' : '' ?>" href="upload_product.php">
            <i class="fas fa-plus me-2"></i>Upload Product
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>" href="orders.php">
            <i class="fas fa-shopping-cart me-2"></i>My Orders
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ratings.php' ? 'active' : '' ?>" href="ratings.php">
            <i class="fas fa-star me-2"></i>My Ratings
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="profile.php">
            <i class="fas fa-user me-2"></i>Profile
        </a>
        <a class="nav-link" href="../../index.php">
            <i class="fas fa-home me-2"></i>Home
        </a>
        <a class="nav-link" href="../../controllers/AuthController.php?action=logout">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </nav>
</div>