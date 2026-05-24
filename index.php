<?php
session_start();
require_once 'config/database_enhanced.php';
require_once 'includes/functions.php';

// Get featured products with ratings
$stmt = $pdo->prepare("
    SELECT p.*, a.name as artisan_name, a.profile_image as artisan_image, u.phone as artisan_phone,
           AVG(r.rating) as avg_rating, COUNT(r.rating) as rating_count
    FROM products p 
    JOIN artisans a ON p.artisan_id = a.id 
    JOIN users u ON a.user_id = u.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN ratings r ON oi.order_id = r.order_id
    WHERE p.status = 'approved' AND p.quantity > 0 
    GROUP BY p.id
    ORDER BY p.created_at DESC LIMIT 12
");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Get heritage content for preview
$stmt = $pdo->prepare("SELECT * FROM heritage_archive ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$heritage_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ashreka & Friends Pottery Association</title>
    
    <!-- Bootstrap CSS (Offline) -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap CSS (Online Fallback) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/slideshow.css" rel="stylesheet">
    <link href="assets/css/responsive-nav.css" rel="stylesheet">
    <link href="assets/css/google-translate.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/lang_universal.php'; ?>
    <script src="assets/js/translate-messages.js"></script>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#home">
                <img src="assets/images/ashru.jpeg" alt="Ashreka" height="70" width="70" class="me-3 rounded-circle" style="object-fit: cover;">
                <span class="translate">Ashreka & Her Friends Pottery Association </span>
            </a>
            
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <div class="hamburger-menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </div>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link translate" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link translate" href="#catalog">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link translate" href="#heritage">Heritage Archive</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link translate" href="#about">About us</a>
                    </li>
                </ul>
                
                <div class="navbar-nav ms-auto align-items-lg-center">
                    <a class="nav-link" href="views/customer/quick_upload.php">
                        <i class="fas fa-receipt"></i> <span class="translate">Complete Payment</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="views/<?= $_SESSION['role'] ?>/dashboard.php">
                            <i class="fas fa-user"></i> <span class="translate">Dashboard</span>
                        </a>
                        <a class="nav-link" href="controllers/AuthController.php?action=logout">
                            <i class="fas fa-sign-out-alt"></i> <span class="translate">Logout</span>
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="views/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> <span class="translate">Login</span>
                        </a>
                        <a class="nav-link" href="views/auth/pre_register.php">
                            <i class="fas fa-user-plus"></i> <span class="translate">Register</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Enhanced Hero Section with Slideshow -->
    <section id="home" class="hero-section">
        <!-- Slideshow will be dynamically created by JavaScript -->
    </section>



    <!-- Search Bar -->
    <section class="search-section py-4 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="pottery">Pottery</option>
                            <option value="weaving">Weaving</option>
                        </select>
                        <button class="btn btn-primary" onclick="searchProducts()">
                            <i class="fas fa-search"></i> <span class="translate">Search</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="catalog" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 translate">Featured Products</h2>
            <div class="row" id="productsContainer">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4 product-card" data-category="<?= $product['category'] ?>">
                    <div class="card h-100 shadow-sm">
                        <div class="position-relative">
                            <img src="<?= $product['image_path'] ?>" class="card-img-top" alt="<?= $product['name'] ?>" style="height: 200px; object-fit: cover;">
                            <?php if ($product['video_path']): ?>
                            <button class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2" onclick="playVideo('<?= $product['video_path'] ?>')">
                                <i class="fas fa-play"></i>
                            </button>
                            <?php endif; ?>
                            <span class="badge <?= $product['quantity'] > 0 ? 'bg-success' : 'bg-danger' ?> position-absolute bottom-0 start-0 m-2 stock-count" data-product-id="<?= $product['id'] ?>">
                                <?= $product['quantity'] > 0 ? 'Stock: ' . $product['quantity'] : 'Out of Stock' ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text text-muted small"><?= substr($product['description'], 0, 100) ?>...</p>
                            
                            <div class="mb-2">
                                <div class="rating-stars" data-product-id="<?= $product['id'] ?>">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star rating-star" data-rating="<?= $i ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($product['avg_rating']): ?>
                                <small class="text-muted"><?= round($product['avg_rating'], 1) ?> (<?= $product['rating_count'] ?> reviews)</small>
                                <?php else: ?>
                                <small class="text-muted">Rate this product</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-primary"><?= number_format($product['price']) ?> <span class="translate">ETB</span></span>
                                <span class="badge bg-secondary"><?= ucfirst($product['category']) ?></span>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?= $product['artisan_image'] ?: 'assets/images/default-avatar.png' ?>" 
                                     class="rounded-circle me-2" width="30" height="30" alt="Artisan">
                                <small class="text-muted">
                                    <span class="translate">By</span> <?= $product['artisan_name'] ?>
                                    <a href="tel:<?= $product['artisan_phone'] ?>" class="ms-2">
                                        <i class="fas fa-phone text-primary"></i>
                                    </a>
                                </small>
                            </div>
                            <button class="btn btn-primary w-100" onclick="buyProduct(<?= $product['id'] ?>)">
                                <i class="fas fa-shopping-cart"></i> <span class="translate">Buy Now</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Heritage Archive Preview -->
    <section id="heritage" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5 translate">Ethiopian Heritage Archive</h2>
            <div class="row">
                <?php foreach ($heritage_items as $item): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if ($item['content_type'] == 'image'): ?>
                            <img src="<?= $item['file_path'] ?>" class="card-img-top" alt="<?= $item['title'] ?>" style="height: 200px; object-fit: cover;">
                        <?php elseif ($item['content_type'] == 'video_link'): ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-dark text-white" style="height: 200px;">
                                <a href="<?= $item['video_url'] ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-play"></i> <span class="translate">Watch Video</span>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($item['title']) ?></h5>
                            <p class="card-text"><?= substr($item['description'], 0, 150) ?>...</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="views/customer/heritage.php" class="btn btn-outline-primary">
                    <span class="translate">View All Heritage Content</span>
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container text-center">
            <h2 class="mb-4 translate">About Ashreka & Her Friends Pottery Association</h2>
            <p class="lead mb-4 translate">Discover our story, mission, and the artisans behind authentic Ethiopian crafts</p>
            <a href="views/about/about.php" class="btn btn-ethiopian btn-lg translate">Learn More About Us</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="translate">Ashreka & Her Friends Pottery Association</h5>
                    <p class="translate">Preserving Ethiopian heritage through traditional craftsmanship</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-telegram"></i></a>
                    </div>
                    <p class="mt-2">&copy; 2024 <span class="translate">All rights reserved</span></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title translate">Product Making Process</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <video id="productVideo" class="w-100" controls>
                        <source src="" type="video/mp4">
                    </video>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    


    <!-- Custom JS -->
    <script src="assets/js/slideshow.js"></script>
    <script src="assets/js/responsive-nav.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        function translatePage(lang) {
            if (lang === 'am') {
                var selectField = document.querySelector("select.goog-te-combo");
                if (selectField) {
                    selectField.value = 'am';
                    selectField.dispatchEvent(new Event('change'));
                }
            } else {
                var selectField = document.querySelector("select.goog-te-combo");
                if (selectField) {
                    selectField.value = 'en';
                    selectField.dispatchEvent(new Event('change'));
                }
            }
        }

        function playVideo(videoPath) {
            document.getElementById('productVideo').src = videoPath;
            new bootstrap.Modal(document.getElementById('videoModal')).show();
        }

        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const products = document.querySelectorAll('.product-card');

            products.forEach(product => {
                const title = product.querySelector('.card-title').textContent.toLowerCase();
                const productCategory = product.dataset.category;
                
                const matchesSearch = title.includes(searchTerm);
                const matchesCategory = !category || productCategory === category;
                
                product.style.display = (matchesSearch && matchesCategory) ? 'block' : 'none';
            });
        }

        function buyProduct(productId) {
            <?php if (isset($_SESSION['user_id'])): ?>
                window.location.href = `controllers/OrderController.php?action=buy&product_id=${productId}`;
            <?php else: ?>
                showLoginModal();
            <?php endif; ?>
        }
    </script>
</body>
</html>