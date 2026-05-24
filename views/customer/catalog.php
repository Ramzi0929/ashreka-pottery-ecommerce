<?php
session_start();
require_once '../../config/database_enhanced.php';
require_once '../../includes/functions.php';

// Get all approved products with ratings
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
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE status = 'approved'")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/slideshow.css" rel="stylesheet">
    <link href="../../assets/css/responsive-nav.css" rel="stylesheet">
    <style>
        .rating-star {
            color: rgba(255, 193, 7, 0.2) !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            margin-right: 3px !important;
            font-size: 16px !important;
        }
        .rating-star:hover {
            color: #ffc107 !important;
            transform: scale(1.3) !important;
            text-shadow: 0 0 10px #ffc107 !important;
        }
        .rating-stars {
            display: inline-block;
            padding: 5px;
        }
        .rating-stars:hover .rating-star {
            color: rgba(255, 193, 7, 0.6) !important;
        }
        .video-frame {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 140px;
            height: 100px;
            background: linear-gradient(135deg, #8B4513, #D2691E);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            border: 2px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .video-frame:hover {
            transform: scale(1.15);
            box-shadow: 0 8px 25px rgba(139,69,19,0.5);
        }
        .video-frame:active {
            transform: scale(1.3);
            transition: transform 0.1s ease;
        }
        .video-expanded {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            z-index: 1050;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            background: #000;
        }
        .video-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1049;
        }
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            z-index: 2;
            background: rgba(0,0,0,0.3);
        }
        .video-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            display: none;
        }
        .video-frame:hover .video-preview {
            display: block;
        }
        .video-frame:hover .video-overlay {
            background: rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../../includes/lang_universal.php'; ?>
    <script src="../../assets/js/translate-messages.js"></script>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <img src="../../assets/images/logo.png" alt="Ashreka" height="40" class="me-2">
                Ashreka & Friends
            </a>
            
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <div class="hamburger-menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </div>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a class="nav-link position-relative" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle" id="cartCount" style="display: none;">0</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-user"></i> Dashboard
                        </a>
                        <a class="nav-link" href="../../controllers/AuthController.php?action=logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Slideshow -->
        <?php include '../layouts/page_slideshow.php'; ?>
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Product Catalog</h2>
            <div>
                <!-- Translation handled by universal component -->
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category ?>"><?= ucfirst($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" onclick="searchProducts()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="sortFilter" onchange="sortProducts()">
                    <option value="newest">Newest First</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="name">Name A-Z</option>
                </select>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row" id="productsContainer">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h5>No Products Available</h5>
                        <p class="text-muted">Check back later for new products from our artisans</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4 product-card" 
                     data-category="<?= $product['category'] ?>"
                     data-price="<?= $product['price'] ?>"
                     data-name="<?= strtolower($product['name']) ?>"
                     data-created="<?= $product['created_at'] ?>">
                    <div class="card h-100 shadow-sm">
                        <div class="position-relative video-container">
                            <img src="../../<?= $product['image_path'] ?>" class="card-img-top" 
                                 alt="<?= $product['name'] ?>" style="height: 250px; object-fit: cover;">
                            
                            <?php if ($product['video_path']): ?>
                            <div class="video-frame" 
                                 onmouseover="showVideoPreview(this, '../../<?= $product['video_path'] ?>')" 
                                 onmouseout="hideVideoPreview(this)"
                                 onclick="showExpandedVideo('../../<?= $product['video_path'] ?>')">
                                <video class="video-preview" muted loop></video>
                                <div class="video-overlay">
                                    <i class="fas fa-play-circle fa-2x"></i>
                                    <small>Making Process</small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <span class="badge bg-success position-absolute bottom-0 start-0 m-2">
                                Stock: <?= $product['quantity'] ?>
                            </span>
                            
                            <span class="badge bg-secondary position-absolute top-0 start-0 m-2">
                                <?= ucfirst($product['category']) ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text text-muted small">
                                <?= substr($product['description'], 0, 100) ?>...
                            </p>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-tag me-1"></i>Material: <?= $product['material'] ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-ruler me-1"></i>Size: <?= ucfirst($product['size']) ?>
                                </small>
                            </div>
                            
                            <div class="mb-2">
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
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h4 text-primary mb-0">
                                    <?= number_format($product['price']) ?> ETB
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex align-items-center mb-2">
                                <img src="../../<?= $product['artisan_image'] ?: 'assets/images/default-avatar.png' ?>" 
                                     class="rounded-circle me-2" width="30" height="30" alt="Artisan">
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">By <?= $product['artisan_name'] ?></small>
                                    <a href="tel:<?= $product['artisan_phone'] ?>" class="text-primary small">
                                        <i class="fas fa-phone me-1"></i><?= $product['artisan_phone'] ?>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" onclick="buyProduct(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                                <a href="cart.php?direct=<?= $product['id'] ?>" class="btn btn-success btn-sm mt-1">
                                    <i class="fas fa-bolt me-1"></i>Buy Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- No Results Message -->
        <div id="noResults" class="text-center py-5" style="display: none;">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5>No Products Found</h5>
            <p class="text-muted">Try adjusting your search criteria</p>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="productModalBody">
                    <!-- Product details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Making Process</h5>
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

    <script>
        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const products = document.querySelectorAll('.product-card');
            let visibleCount = 0;

            products.forEach(product => {
                const title = product.querySelector('.card-title').textContent.toLowerCase();
                const description = product.querySelector('.card-text').textContent.toLowerCase();
                const productCategory = product.dataset.category;
                
                const matchesSearch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);
                const matchesCategory = !category || productCategory === category;
                
                if (matchesSearch && matchesCategory) {
                    product.style.display = 'block';
                    visibleCount++;
                } else {
                    product.style.display = 'none';
                }
            });

            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
        }

        function sortProducts() {
            const sortBy = document.getElementById('sortFilter').value;
            const container = document.getElementById('productsContainer');
            const products = Array.from(container.querySelectorAll('.product-card'));

            products.sort((a, b) => {
                switch (sortBy) {
                    case 'price_low':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price_high':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'name':
                        return a.dataset.name.localeCompare(b.dataset.name);
                    case 'newest':
                    default:
                        return new Date(b.dataset.created) - new Date(a.dataset.created);
                }
            });

            products.forEach(product => container.appendChild(product));
        }

        function showVideoPreview(element, videoPath) {
            const video = element.querySelector('.video-preview');
            if (video && videoPath) {
                video.src = videoPath;
                video.currentTime = 0;
                video.play().catch(e => console.log('Video preview failed'));
            }
        }
        
        function hideVideoPreview(element) {
            const video = element.querySelector('.video-preview');
            if (video) {
                video.pause();
                video.src = '';
            }
        }
        
        function showExpandedVideo(videoPath) {
            const backdrop = document.createElement('div');
            backdrop.className = 'video-backdrop';
            
            const expandedVideo = document.createElement('video');
            expandedVideo.className = 'video-expanded';
            expandedVideo.src = videoPath;
            expandedVideo.controls = true;
            expandedVideo.autoplay = true;
            
            backdrop.onclick = () => {
                document.body.removeChild(backdrop);
                document.body.removeChild(expandedVideo);
            };
            
            document.body.appendChild(backdrop);
            document.body.appendChild(expandedVideo);
        }
        
        function playFullVideo(videoPath) {
            document.getElementById('productVideo').src = videoPath;
            new bootstrap.Modal(document.getElementById('videoModal')).show();
        }

        function buyProduct(productId) {
            fetch('cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `add_to_cart=1&product_id=${productId}&quantity=1`
            })
            .then(response => response.text())
            .then(() => {
                updateCartCount();
                showAddedToCartMessage();
            });
        }
        
        function updateCartCount() {
            fetch('../../api/cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (data.count > 0) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = 'block';
                    } else {
                        cartBadge.style.display = 'none';
                    }
                });
        }
        
        function showAddedToCartMessage() {
            const toast = document.createElement('div');
            toast.className = 'position-fixed top-0 end-0 m-3 alert alert-success';
            toast.style.zIndex = '9999';
            toast.innerHTML = '<i class="fas fa-check me-2"></i>Added to cart!';
            document.body.appendChild(toast);
            setTimeout(() => document.body.removeChild(toast), 3000);
        }
        
        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', updateCartCount);

        function viewProductDetails(productId) {
            // Load product details in modal
            fetch(`../../api/product_details.php?id=${productId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('productModalBody').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('productModal')).show();
                });
        }

        function rateProduct(productId) {
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                // Check if customer is loyal
                fetch('../../api/check_loyalty.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.is_loyal) {
                            window.location.href = `rate_product.php?product_id=${productId}`;
                        } else {
                            alert(t('loyalty_required') + '\n\n' + t('loyalty_info'));
                        }
                    });
            <?php else: ?>
                alert('Please login as a customer to rate products');
                window.location.href = '../auth/login.php';
            <?php endif; ?>
        }
        
        // Rating star interactions
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.rating-stars').forEach(ratingContainer => {
                const stars = ratingContainer.querySelectorAll('.rating-star');
                
                stars.forEach((star, index) => {
                    star.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Star clicked!');
                        
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                            fetch('../../api/check_loyalty.php')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.is_loyal) {
                                        const productId = ratingContainer.dataset.productId;
                                        window.location.href = `rate_product.php?product_id=${productId}`;
                                    } else {
                                        alert(t('loyalty_required') + '\n\n' + t('loyalty_info'));
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error checking loyalty status');
                                });
                        <?php else: ?>
                            showLoginModal();
                        <?php endif; ?>
                    });
                    
                    // Hover effects
                    star.addEventListener('mouseenter', function() {
                        for (let i = 0; i <= index; i++) {
                            stars[i].style.color = '#ffc107';
                            stars[i].style.transform = 'scale(1.2)';
                        }
                    });
                    
                    ratingContainer.addEventListener('mouseleave', function() {
                        stars.forEach(s => {
                            s.style.color = '';
                            s.style.transform = '';
                        });
                    });
                });
            });
        });
        
        // Initialize search on input
        document.getElementById('searchInput').addEventListener('input', searchProducts);
        document.getElementById('categoryFilter').addEventListener('change', searchProducts);
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/responsive-nav.js"></script>
    <script src="../../assets/js/auto-logout.js"></script>
</body>
</html>