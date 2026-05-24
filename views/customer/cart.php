<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check for direct purchase
$direct_product_id = $_GET['direct'] ?? null;
if ($direct_product_id) {
    // Create a temporary cart with only this product for direct purchase
    $_SESSION['cart'] = [$direct_product_id => 1];
}

// Handle cart actions
if ($_POST) {
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'] ?? 1;
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $_SESSION['success'] = 'Product added to cart';
    }
    
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
        }
        $_SESSION['success'] = 'Cart updated';
    }
    
    if (isset($_POST['remove_item'])) {
        unset($_SESSION['cart'][$_POST['product_id']]);
        $_SESSION['success'] = 'Item removed from cart';
    }
    
    header('Location: cart.php');
    exit;
}

// Get cart items
$cart_items = [];
$total_amount = 0;
$total_items = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND status = 'approved'");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
        
        $total_amount += $subtotal;
        $total_items += $quantity;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/responsive-nav.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">Ashreka & Friends</a>
            
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <div class="hamburger-menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </div>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="../../index.php">Home</a>
                    <a class="nav-link" href="catalog.php">Catalog</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                        <a class="nav-link" href="../../controllers/AuthController.php?action=logout">Logout</a>
                    <?php else: ?>
                        <a class="nav-link" href="../auth/login.php">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h2>
            <a href="../../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
        
        <?php include '../layouts/alerts.php'; ?>
        
        <?php if (!empty($cart_items)): ?>
        <form method="POST">
            <input type="hidden" name="product_id" value="">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../../<?= $item['product']['image_path'] ?>" width="60" height="60" class="rounded me-3">
                                            <div>
                                                <h6><?= htmlspecialchars($item['product']['name']) ?></h6>
                                                <small class="text-muted"><?= ucfirst($item['product']['category']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= number_format($item['product']['price']) ?> ETB</td>
                                    <td>
                                        <input type="number" class="form-control" style="width: 80px;" 
                                               name="quantities[<?= $item['product']['id'] ?>]" 
                                               value="<?= $item['quantity'] ?>" min="1" max="<?= $item['product']['quantity'] ?>">
                                    </td>
                                    <td><?= number_format($item['subtotal']) ?> ETB</td>
                                    <td>
                                        <button type="submit" name="remove_item" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="this.form.querySelector('input[name=product_id]').value='<?= $item['product']['id'] ?>'">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" name="update_cart" class="btn btn-outline-primary">
                                <i class="fas fa-sync me-2"></i>Update Cart
                            </button>
                            <a href="catalog.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4>Total: <?= number_format($total_amount) ?> ETB</h4>
                            <p class="text-muted"><?= $total_items ?> item(s)</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="card mt-3">
            <div class="card-body">
                <h5>Checkout</h5>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="checkout.php" class="btn btn-success btn-lg">
                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout (<?= number_format($total_amount) ?> ETB)
                </a>
                <?php else: ?>
                <p class="text-muted">Please login to proceed with checkout</p>
                <a href="../auth/login.php" class="btn btn-primary">Login to Checkout</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
            <h4>Your cart is empty</h4>
            <p class="text-muted">Add some products to your cart to get started</p>
            <a href="catalog.php" class="btn btn-primary btn-lg">Browse Products</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/responsive-nav.js"></script>
    <script src="../../assets/js/auto-logout.js"></script>
</body>
</html>