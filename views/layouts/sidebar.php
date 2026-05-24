<div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
    <div class="position-sticky pt-3">
        <?php
        // Get current user profile image
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("
                SELECT u.name, 
                       COALESCE(a.profile_image, c.profile_image) as profile_image 
                FROM users u 
                LEFT JOIN artisans a ON u.id = a.user_id 
                LEFT JOIN customers c ON u.id = c.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $current_user = $stmt->fetch();
        }
        ?>
        <!-- User Profile Section -->
        <div class="text-center mb-3 p-3 border-bottom border-secondary">
            <?php if ($current_user['profile_image']): ?>
            <img src="../../<?= $current_user['profile_image'] ?>" 
                 class="rounded-circle mb-2" width="60" height="60" style="object-fit: cover;">
            <?php else: ?>
            <div class="rounded-circle mb-2 bg-secondary d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                <i class="fas fa-user text-white"></i>
            </div>
            <?php endif; ?>
            <div class="text-white small"><?= htmlspecialchars($current_user['name'] ?: 'User') ?></div>
            <div class="text-muted small"><?= ucfirst($_SESSION['role']) ?></div>
        </div>
        
        <ul class="nav flex-column">
            <?php
            $role = $_SESSION['role'] ?? '';
            $currentPage = basename($_SERVER['PHP_SELF']);
            
            if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'system.php' ? 'active' : '' ?>" href="system.php">
                        <i class="fas fa-cogs me-2"></i>System Monitor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'heritage.php' ? 'active' : '' ?>" href="heritage.php">
                        <i class="fas fa-book me-2"></i>Heritage Archive
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../profile/profile.php">
                        <i class="fas fa-user-circle me-2"></i>My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </li>
            <?php elseif ($role === 'manager'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'artisans.php' ? 'active' : '' ?>" href="artisans.php">
                        <i class="fas fa-users me-2"></i>Artisans
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>" href="orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'products_review.php' ? 'active' : '' ?>" href="products_review.php">
                        <i class="fas fa-box me-2"></i>Product Review
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="users.php">
                        <i class="fas fa-user-cog me-2"></i>User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'artisan_approvals.php' ? 'active' : '' ?>" href="artisan_approvals.php">
                        <i class="fas fa-user-check me-2"></i>Artisan Approvals
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'payment_approvals.php' ? 'active' : '' ?>" href="payment_approvals.php">
                        <i class="fas fa-receipt me-2"></i>Payment Approvals
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'wallet.php' ? 'active' : '' ?>" href="wallet.php">
                        <i class="fas fa-wallet me-2"></i>Wallet
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'upload_product.php' ? 'active' : '' ?>" href="upload_product.php">
                        <i class="fas fa-plus me-2"></i>Upload Product
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user-circle me-2"></i>My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </li>
            <?php elseif ($role === 'artisan'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>" href="products.php">
                        <i class="fas fa-box me-2"></i>My Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>" href="orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>My Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'upload_product.php' ? 'active' : '' ?>" href="upload_product.php">
                        <i class="fas fa-plus me-2"></i>Upload Product
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user-circle me-2"></i>My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </li>
            <?php elseif ($role === 'customer'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'catalog.php' ? 'active' : '' ?>" href="catalog.php">
                        <i class="fas fa-store me-2"></i>Shop
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>" href="orders.php">
                        <i class="fas fa-shopping-bag me-2"></i>My Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'cart.php' ? 'active' : '' ?>" href="cart.php">
                        <i class="fas fa-shopping-cart me-2"></i>Cart
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user-circle me-2"></i>My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>