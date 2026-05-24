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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Customer Dashboard</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-card { transition: all 0.3s; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.8em; }
        .rating-stars { color: #ffc107; }
        .rating-input { cursor: pointer; font-size: 1.5em; color: #ddd; }
        .rating-input:hover, .rating-input.active { color: #ffc107; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <?php include '../layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4><i class="fas fa-shopping-bag me-2"></i>My Orders</h4>
                        </div>
                        <div class="card-body">
                            <div id="ordersContainer">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2">Loading your orders...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Info Modal -->
    <div class="modal fade" id="deliveryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delivery Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="deliveryInfo">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="confirmDeliveryBtn" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>I Received My Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Modal -->
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rate Your Experience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="ratingForm">
                        <input type="hidden" id="ratingOrderId">
                        <input type="hidden" id="ratingArtisanId">
                        <input type="hidden" id="ratingProductId">
                        
                        <div class="text-center mb-4">
                            <h6>How was your experience?</h6>
                            <div class="rating-stars mb-3">
                                <i class="fas fa-star rating-input" data-rating="1"></i>
                                <i class="fas fa-star rating-input" data-rating="2"></i>
                                <i class="fas fa-star rating-input" data-rating="3"></i>
                                <i class="fas fa-star rating-input" data-rating="4"></i>
                                <i class="fas fa-star rating-input" data-rating="5"></i>
                            </div>
                            <input type="hidden" id="selectedRating" value="0">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Review (Optional)</label>
                            <textarea id="reviewText" class="form-control" rows="4" 
                                      placeholder="Share your experience with this artisan..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="submitRatingBtn" class="btn btn-primary" disabled>
                        <i class="fas fa-star me-1"></i>Submit Rating
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/auto-logout.js"></script>
    <script>
        let currentOrderId = null;

        // Load customer orders
        async function loadOrders() {
            try {
                const response = await fetch('../../api/customer_orders.php');
                const result = await response.json();
                
                if (result.success) {
                    displayOrders(result.data);
                } else {
                    document.getElementById('ordersContainer').innerHTML = 
                        '<div class="alert alert-info">No orders found</div>';
                }
            } catch (error) {
                console.error('Error loading orders:', error);
                document.getElementById('ordersContainer').innerHTML = 
                    '<div class="alert alert-danger">Error loading orders</div>';
            }
        }

        function displayOrders(orders) {
            const container = document.getElementById('ordersContainer');
            
            if (orders.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No orders found</div>';
                return;
            }

            let html = '<div class="row">';
            
            orders.forEach(order => {
                const statusColor = getStatusColor(order.status);
                const canConfirm = order.status === 'delivery_info_sent' || order.status === 'in_delivery';
                const canRate = order.status === 'completed' && !order.has_rating;
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card order-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Order #${order.id}</h6>
                                <span class="badge ${statusColor} status-badge">${formatStatus(order.status)}</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Products:</strong> ${order.product_names}
                                </div>
                                <div class="mb-2">
                                    <strong>Total:</strong> ${Number(order.total_amount).toLocaleString()} ETB
                                </div>
                                <div class="mb-2">
                                    <strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}
                                </div>
                                ${order.delivery_date ? 
                                    `<div class="mb-2"><strong>Delivery Date:</strong> ${new Date(order.delivery_date).toLocaleDateString()}</div>` : 
                                    ''
                                }
                                ${order.rating ? 
                                    `<div class="mb-2">
                                        <strong>Your Rating:</strong> 
                                        <span class="rating-stars">${'★'.repeat(order.rating)}${'☆'.repeat(5-order.rating)}</span>
                                    </div>` : 
                                    ''
                                }
                            </div>
                            <div class="card-footer">
                                ${canConfirm ? 
                                    `<button class="btn btn-info btn-sm me-2" onclick="viewDeliveryInfo(${order.id})">
                                        <i class="fas fa-info-circle me-1"></i>View Details
                                    </button>` : 
                                    ''
                                }
                                ${canRate ? 
                                    `<button class="btn btn-warning btn-sm" onclick="showRatingModal(${order.id}, ${order.artisan_id}, ${order.product_id})">
                                        <i class="fas fa-star me-1"></i>Rate Experience
                                    </button>` : 
                                    ''
                                }
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        function getStatusColor(status) {
            const colors = {
                'pending_payment': 'bg-warning',
                'payment_submitted': 'bg-info',
                'payment_approved': 'bg-primary',
                'delivery_info_sent': 'bg-success',
                'in_delivery': 'bg-success',
                'completed': 'bg-success',
                'cancelled': 'bg-danger'
            };
            return colors[status] || 'bg-secondary';
        }

        function formatStatus(status) {
            const formats = {
                'pending_payment': 'Pending Payment',
                'payment_submitted': 'Payment Submitted',
                'payment_approved': 'Payment Approved',
                'delivery_info_sent': 'Ready for Delivery',
                'in_delivery': 'In Delivery',
                'completed': 'Completed',
                'cancelled': 'Cancelled'
            };
            return formats[status] || status;
        }

        async function viewDeliveryInfo(orderId) {
            currentOrderId = orderId;
            
            try {
                const response = await fetch(`../../api/delivery_info.php?order_id=${orderId}`);
                const result = await response.json();
                
                if (result.success) {
                    const info = result.data;
                    document.getElementById('deliveryInfo').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Your Information</h6>
                                <div class="mb-2"><strong>Name:</strong> ${info.customer_name}</div>
                                <div class="mb-2"><strong>Phone:</strong> ${info.customer_phone}</div>
                                <div class="mb-2"><strong>Location:</strong> ${info.customer_location}</div>
                                <div class="mb-2"><strong>Delivery Date:</strong> ${new Date(info.delivery_date).toLocaleDateString()}</div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">Artisan Information</h6>
                                <div class="mb-2"><strong>Name:</strong> ${info.artisan_name || 'Contact manager'}</div>
                                <div class="mb-2"><strong>Phone:</strong> ${info.artisan_phone || 'Contact manager'}</div>
                                <div class="mb-2"><strong>Location:</strong> ${info.artisan_location || 'Contact manager'}</div>
                                ${info.notes ? `<div class="mb-2"><strong>Notes:</strong> ${info.notes}</div>` : ''}
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Please contact the artisan to coordinate product delivery. Once you receive your product, click "I Received My Product" below.
                        </div>
                    `;
                    
                    const modal = new bootstrap.Modal(document.getElementById('deliveryModal'));
                    modal.show();
                } else {
                    alert('Failed to load delivery information');
                }
            } catch (error) {
                console.error('Error loading delivery info:', error);
                alert('Error loading delivery information');
            }
        }

        // Confirm delivery
        document.getElementById('confirmDeliveryBtn').addEventListener('click', async function() {
            if (!currentOrderId) return;

            try {
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'confirm_delivery',
                        order_id: currentOrderId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('deliveryModal')).hide();
                    alert('Delivery confirmed! You can now rate your experience.');
                    loadOrders(); // Reload orders
                } else {
                    alert('Failed to confirm delivery: ' + result.message);
                }
            } catch (error) {
                console.error('Error confirming delivery:', error);
                alert('Error confirming delivery');
            }
        });

        // Rating system
        function showRatingModal(orderId, artisanId, productId) {
            document.getElementById('ratingOrderId').value = orderId;
            document.getElementById('ratingArtisanId').value = artisanId;
            document.getElementById('ratingProductId').value = productId;
            
            // Reset rating
            document.getElementById('selectedRating').value = '0';
            document.querySelectorAll('.rating-input').forEach(star => star.classList.remove('active'));
            document.getElementById('reviewText').value = '';
            document.getElementById('submitRatingBtn').disabled = true;
            
            const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
            modal.show();
        }

        // Rating stars interaction
        document.querySelectorAll('.rating-input').forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                document.getElementById('selectedRating').value = rating;
                
                // Update star display
                document.querySelectorAll('.rating-input').forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                document.getElementById('submitRatingBtn').disabled = false;
            });
        });

        // Submit rating
        document.getElementById('submitRatingBtn').addEventListener('click', async function() {
            const rating = document.getElementById('selectedRating').value;
            const review = document.getElementById('reviewText').value.trim();
            
            if (rating === '0') {
                alert('Please select a rating');
                return;
            }

            try {
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'submit_rating',
                        order_id: document.getElementById('ratingOrderId').value,
                        artisan_id: document.getElementById('ratingArtisanId').value,
                        product_id: document.getElementById('ratingProductId').value,
                        rating: rating,
                        review: review
                    })
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
                    alert('Thank you for your rating!');
                    loadOrders(); // Reload orders
                } else {
                    alert('Failed to submit rating: ' + result.message);
                }
            } catch (error) {
                console.error('Error submitting rating:', error);
                alert('Error submitting rating');
            }
        });

        // Load orders on page load
        loadOrders();

        // Auto-refresh every 30 seconds
        setInterval(loadOrders, 30000);
    </script>
</body>
</html>