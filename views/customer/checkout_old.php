<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $stmt->fetchColumn();
    $_SESSION['customer_id'] = $customer_id;
}

// Get cart items
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: catalog.php');
    exit;
}

$total = 0;
$items = [];
foreach ($cart as $product_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product) {
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        $items[] = ['product' => $product, 'quantity' => $quantity, 'subtotal' => $subtotal];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-method { cursor: pointer; border: 2px solid #ddd; transition: all 0.3s; }
        .payment-method.selected { border-color: #8B4513; background: #FFF8DC; }
        .payment-processing { display: none; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-credit-card me-2"></i>Checkout</h4>
                    </div>
                    <div class="card-body">
                        <!-- Order Summary -->
                        <div class="mb-4">
                            <h5>Order Summary</h5>
                            <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="d-flex align-items-center">
                                    <img src="../../<?= $item['product']['image_path'] ?>" width="50" height="50" class="rounded me-3">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($item['product']['name']) ?></h6>
                                        <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold"><?= number_format($item['subtotal']) ?> ETB</span>
                            </div>
                            <?php endforeach; ?>
                            <div class="d-flex justify-content-between align-items-center pt-3">
                                <h5>Total:</h5>
                                <h4 class="text-primary"><?= number_format($total) ?> ETB</h4>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="mb-4">
                            <h5>Payment Method</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="payment-method p-3 rounded text-center" data-method="telebirr">
                                        <i class="fas fa-mobile-alt fa-2x text-success mb-2"></i>
                                        <h6>TeleBirr</h6>
                                        <small class="text-muted">Mobile Payment</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="payment-method p-3 rounded text-center" data-method="bank">
                                        <i class="fas fa-university fa-2x text-primary mb-2"></i>
                                        <h6>Bank Transfer</h6>
                                        <small class="text-muted">Direct Bank Payment</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <form id="paymentForm">
                            <input type="hidden" id="paymentMethod" name="payment_method" required>
                            <input type="hidden" name="total_amount" value="<?= $total ?>">
                            
                            <div id="telebirrForm" class="payment-form" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" placeholder="+251912345678" required>
                                </div>
                            </div>
                            
                            <div id="bankForm" class="payment-form" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Account Number</label>
                                    <input type="text" class="form-control" placeholder="1234567890" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-lock me-2"></i>Pay <?= number_format($total) ?> ETB
                                </button>
                                <a href="cart.php" class="btn btn-outline-secondary">Back to Cart</a>
                            </div>
                        </form>

                        <!-- Processing Animation -->
                        <div class="payment-processing text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <h5>Processing Payment...</h5>
                            <p class="text-muted">Please wait while we process your payment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h4>Payment Successful!</h4>
                    <p id="successMessage">Your order has been placed successfully.</p>
                    <div id="loyaltyAlert" class="alert alert-warning" style="display: none;">
                        <h6><i class="fas fa-crown me-2"></i>Congratulations!</h6>
                        <p>You are now a Loyal Customer! You can now place custom orders.</p>
                        <p><strong>Your Loyalty Key:</strong> <span id="loyaltyKey" class="badge bg-gold"></span></p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">
                        Go to Dashboard
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                
                const methodType = this.dataset.method;
                document.getElementById('paymentMethod').value = methodType;
                
                document.querySelectorAll('.payment-form').forEach(form => form.style.display = 'none');
                document.getElementById(methodType + 'Form').style.display = 'block';
            });
        });

        // Payment form submission
        document.getElementById('paymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!document.getElementById('paymentMethod').value) {
                alert('Please select a payment method');
                return;
            }

            // Show processing
            document.querySelector('.card-body').style.display = 'none';
            document.querySelector('.payment-processing').style.display = 'block';

            try {
                // Create order first
                const orderResponse = await fetch('../../controllers/OrderController.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=create_order&total_amount=<?= $total ?>'
                });
                
                const orderResult = await orderResponse.json();
                
                if (orderResult.success) {
                    // Process payment
                    const paymentResponse = await fetch('../../controllers/PaymentController.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=process_payment&order_id=${orderResult.order_id}&amount=<?= $total ?>&payment_method=${document.getElementById('paymentMethod').value}`
                    });
                    
                    const paymentResult = await paymentResponse.json();
                    
                    if (paymentResult.success) {
                        // Show success message
                        document.querySelector('.payment-processing').style.display = 'none';
                        new bootstrap.Modal(document.getElementById('successModal')).show();
                    } else {
                        alert('Payment processing failed: ' + paymentResult.message);
                        location.reload();
                    }
                }
            } catch (error) {
                alert('Payment processing error. Please try again.');
                location.reload();
            }
        });
    </script>
</body>
</html>