<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artisan') {
    header('Location: ../auth/login.php');
    exit;
}

$artisan_id = $_SESSION['artisan_id'] ?? null;
if (!$artisan_id) {
    $stmt = $pdo->prepare("SELECT id FROM artisans WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $artisan_id = $stmt->fetchColumn();
    $_SESSION['artisan_id'] = $artisan_id;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Product Orders - Artisan Dashboard</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-card { transition: all 0.3s; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.8em; }
        .rating-stars { color: #ffc107; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <?php include '../layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h4><i class="fas fa-box me-2"></i>Product Orders</h4>
                        </div>
                        <div class="card-body">
                            <div id="ordersContainer">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-success" role="status"></div>
                                    <p class="mt-2">Loading your product orders...</p>
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
                    <h5 class="modal-title">Customer Contact Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="deliveryInfo">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="confirmDeliveryBtn" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>I Delivered the Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentOrderId = null;

        // Load artisan orders
        async function loadOrders() {
            try {
                const response = await fetch('../../api/artisan_orders.php');
                const result = await response.json();
                
                if (result.success) {
                    displayOrders(result.data);
                } else {
                    document.getElementById('ordersContainer').innerHTML = 
                        '<div class="alert alert-info">No orders found for your products</div>';
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
                container.innerHTML = '<div class="alert alert-info">No orders found for your products</div>';
                return;
            }

            let html = '<div class="row">';
            
            orders.forEach(order => {
                const statusColor = getStatusColor(order.status);
                const canConfirm = order.status === 'delivery_info_sent' || order.status === 'in_delivery';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card order-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Order #${order.id}</h6>
                                <span class="badge ${statusColor} status-badge">${formatStatus(order.status)}</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Product:</strong> ${order.product_name}
                                </div>
                                <div class="mb-2">
                                    <strong>Customer:</strong> ${order.customer_name}
                                </div>
                                <div class="mb-2">
                                    <strong>Quantity:</strong> ${order.quantity}
                                </div>
                                <div class="mb-2">
                                    <strong>Amount:</strong> ${Number(order.subtotal).toLocaleString()} ETB
                                </div>
                                <div class="mb-2">
                                    <strong>Order Date:</strong> ${new Date(order.created_at).toLocaleDateString()}
                                </div>
                                ${order.delivery_date ? 
                                    `<div class="mb-2"><strong>Delivery Date:</strong> ${new Date(order.delivery_date).toLocaleDateString()}</div>` : 
                                    ''
                                }
                                ${order.rating ? 
                                    `<div class="mb-2">
                                        <strong>Customer Rating:</strong> 
                                        <span class="rating-stars">${'★'.repeat(order.rating)}${'☆'.repeat(5-order.rating)}</span>
                                    </div>` : 
                                    ''
                                }
                                ${order.review ? 
                                    `<div class="mb-2">
                                        <strong>Review:</strong> 
                                        <small class="text-muted">"${order.review}"</small>
                                    </div>` : 
                                    ''
                                }
                            </div>
                            <div class="card-footer">
                                ${canConfirm ? 
                                    `<button class="btn btn-info btn-sm w-100" onclick="viewDeliveryInfo(${order.id})">
                                        <i class="fas fa-info-circle me-1"></i>View Customer Contact
                                    </button>` : 
                                    `<div class="text-muted text-center">
                                        <small>${getStatusMessage(order.status)}</small>
                                    </div>`
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

        function getStatusMessage(status) {
            const messages = {
                'pending_payment': 'Waiting for customer payment',
                'payment_submitted': 'Payment being verified',
                'payment_approved': 'Waiting for delivery coordination',
                'completed': 'Order completed successfully'
            };
            return messages[status] || '';
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
                                <h6 class="text-primary mb-3">Customer Contact Information</h6>
                                <div class="mb-2"><strong>Name:</strong> ${info.customer_name}</div>
                                <div class="mb-2"><strong>Phone:</strong> 
                                    <a href="tel:${info.customer_phone}" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i>${info.customer_phone}
                                    </a>
                                </div>
                                <div class="mb-2"><strong>Location:</strong> ${info.customer_location}</div>
                                <div class="mb-2"><strong>Delivery Date:</strong> ${new Date(info.delivery_date).toLocaleDateString()}</div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">Your Information</h6>
                                <div class="mb-2"><strong>Name:</strong> ${info.artisan_name || 'Not provided'}</div>
                                <div class="mb-2"><strong>Phone:</strong> ${info.artisan_phone || 'Not provided'}</div>
                                <div class="mb-2"><strong>Location:</strong> ${info.artisan_location || 'Not provided'}</div>
                                ${info.notes ? `<div class="mb-2"><strong>Notes:</strong> ${info.notes}</div>` : ''}
                            </div>
                        </div>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Please contact the customer to coordinate product delivery. Once you have delivered the product, click "I Delivered the Product" below.
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
                    alert('Delivery confirmed! Waiting for customer confirmation.');
                    loadOrders(); // Reload orders
                } else {
                    alert('Failed to confirm delivery: ' + result.message);
                }
            } catch (error) {
                console.error('Error confirming delivery:', error);
                alert('Error confirming delivery');
            }
        });

        // Load orders on page load
        loadOrders();

        // Auto-refresh every 30 seconds
        setInterval(loadOrders, 30000);
    </script>
</body>
</html>