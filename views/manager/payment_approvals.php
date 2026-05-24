<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approvals - Manager Dashboard</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .receipt-card { transition: all 0.3s; }
        .receipt-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .receipt-image { max-width: 100%; height: 200px; object-fit: cover; border-radius: 8px; }
        .status-badge { font-size: 0.8em; }
        .delivery-form { background: #f8f9fa; border-radius: 10px; }
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
                            <h4><i class="fas fa-receipt me-2"></i>Payment Receipt Approvals</h4>
                        </div>
                        <div class="card-body">
                            <div id="receiptsContainer">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2">Loading payment receipts...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Review Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Payment Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Receipt Image</h6>
                            <img id="receiptImage" class="receipt-image w-100 mb-3" alt="Receipt">
                            <div id="receiptLink" class="mb-3" style="display: none;">
                                <h6>Receipt Link</h6>
                                <a id="receiptLinkUrl" href="#" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>View Receipt
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Details</h6>
                            <div id="orderDetails"></div>
                            
                            <div class="mt-3">
                                <h6>Payment Information</h6>
                                <div id="paymentDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="rejectBtn" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Reject
                    </button>
                    <button type="button" id="approveBtn" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Approve Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Info Modal -->
    <div class="modal fade" id="deliveryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Delivery Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="deliveryForm" class="delivery-form p-4">
                        <input type="hidden" id="deliveryOrderId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Customer Information</h6>
                                <div class="mb-3">
                                    <label class="form-label">Customer Name *</label>
                                    <input type="text" id="customerName" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Customer Phone *</label>
                                    <input type="tel" id="customerPhone" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Customer Location *</label>
                                    <textarea id="customerLocation" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Delivery Date *</label>
                                    <input type="date" id="deliveryDate" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">Artisan Information</h6>
                                <div class="mb-3">
                                    <label class="form-label">Artisan Name</label>
                                    <input type="text" id="artisanName" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Artisan Phone</label>
                                    <input type="tel" id="artisanPhone" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Artisan Location</label>
                                    <textarea id="artisanLocation" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea id="deliveryNotes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="sendDeliveryBtn" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Delivery Information
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason *</label>
                        <textarea id="rejectionReason" class="form-control" rows="4" required 
                                  placeholder="Please provide a clear reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmRejectBtn" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentReceiptId = null;
        let currentOrderId = null;

        // Load pending receipts
        async function loadPendingReceipts() {
            try {
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_pending_receipts'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayReceipts(result.data);
                } else {
                    document.getElementById('receiptsContainer').innerHTML = 
                        '<div class="alert alert-warning">No pending receipts found</div>';
                }
            } catch (error) {
                console.error('Error loading receipts:', error);
                document.getElementById('receiptsContainer').innerHTML = 
                    '<div class="alert alert-danger">Error loading receipts</div>';
            }
        }

        function displayReceipts(receipts) {
            const container = document.getElementById('receiptsContainer');
            
            if (receipts.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No pending payment receipts</div>';
                return;
            }

            let html = '<div class="row">';
            
            receipts.forEach(receipt => {
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card receipt-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Order #${receipt.order_id}</h6>
                                <span class="badge bg-warning status-badge">Pending</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Customer:</strong> ${receipt.customer_name}
                                </div>
                                <div class="mb-2">
                                    <strong>Product:</strong> ${receipt.product_name}
                                </div>
                                <div class="mb-2">
                                    <strong>Amount:</strong> ${Number(receipt.total_amount).toLocaleString()} ETB
                                </div>
                                <div class="mb-2">
                                    <strong>Payment Method:</strong> 
                                    <span class="badge bg-info">${receipt.payment_method}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Submitted:</strong> ${new Date(receipt.created_at).toLocaleDateString()}
                                </div>
                                ${receipt.receipt_image_path ? 
                                    `<img src="../../${receipt.receipt_image_path}" class="receipt-image mb-2" alt="Receipt">` : 
                                    '<div class="text-muted mb-2">Receipt link provided</div>'
                                }
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary btn-sm w-100" 
                                        onclick="reviewReceipt(${receipt.id}, ${receipt.order_id}, '${receipt.receipt_image_path}', '${receipt.receipt_link}', '${receipt.customer_name}', '${receipt.product_name}', ${receipt.total_amount}, '${receipt.payment_method}', '${receipt.bank_name || ''}', '${receipt.phone_number}')">
                                    <i class="fas fa-eye me-1"></i>Review Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        function reviewReceipt(receiptId, orderId, imagePath, receiptLink, customerName, productName, totalAmount, paymentMethod, bankName, phoneNumber) {
            currentReceiptId = receiptId;
            currentOrderId = orderId;

            // Set receipt image or link
            if (imagePath && imagePath !== 'null') {
                document.getElementById('receiptImage').src = '../../' + imagePath;
                document.getElementById('receiptImage').style.display = 'block';
                document.getElementById('receiptLink').style.display = 'none';
            } else if (receiptLink && receiptLink !== 'null') {
                document.getElementById('receiptLinkUrl').href = receiptLink;
                document.getElementById('receiptImage').style.display = 'none';
                document.getElementById('receiptLink').style.display = 'block';
            }

            // Set order details
            document.getElementById('orderDetails').innerHTML = `
                <div class="mb-2"><strong>Order ID:</strong> #${orderId}</div>
                <div class="mb-2"><strong>Customer:</strong> ${customerName}</div>
                <div class="mb-2"><strong>Product:</strong> ${productName}</div>
                <div class="mb-2"><strong>Total Amount:</strong> ${Number(totalAmount).toLocaleString()} ETB</div>
            `;

            // Set payment details
            document.getElementById('paymentDetails').innerHTML = `
                <div class="mb-2"><strong>Payment Method:</strong> ${paymentMethod}</div>
                ${bankName ? `<div class="mb-2"><strong>Bank:</strong> ${bankName}</div>` : ''}
                <div class="mb-2"><strong>Phone Number:</strong> ${phoneNumber}</div>
            `;

            const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
            modal.show();
        }

        // Approve payment
        document.getElementById('approveBtn').addEventListener('click', async function() {
            if (!currentReceiptId || !currentOrderId) return;

            try {
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'approve_payment',
                        receipt_id: currentReceiptId,
                        order_id: currentOrderId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('receiptModal')).hide();
                    showDeliveryForm();
                } else {
                    alert('Failed to approve payment: ' + result.message);
                }
            } catch (error) {
                console.error('Error approving payment:', error);
                alert('Error approving payment');
            }
        });

        // Show delivery form
        function showDeliveryForm() {
            document.getElementById('deliveryOrderId').value = currentOrderId;
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deliveryDate').min = today;
            document.getElementById('deliveryDate').value = today;

            const modal = new bootstrap.Modal(document.getElementById('deliveryModal'));
            modal.show();
        }

        // Send delivery information
        document.getElementById('sendDeliveryBtn').addEventListener('click', async function() {
            const form = document.getElementById('deliveryForm');
            const formData = new FormData();
            
            formData.append('action', 'send_delivery_info');
            formData.append('order_id', document.getElementById('deliveryOrderId').value);
            formData.append('customer_name', document.getElementById('customerName').value);
            formData.append('customer_phone', document.getElementById('customerPhone').value);
            formData.append('customer_location', document.getElementById('customerLocation').value);
            formData.append('delivery_date', document.getElementById('deliveryDate').value);
            formData.append('artisan_name', document.getElementById('artisanName').value);
            formData.append('artisan_phone', document.getElementById('artisanPhone').value);
            formData.append('artisan_location', document.getElementById('artisanLocation').value);
            formData.append('notes', document.getElementById('deliveryNotes').value);

            try {
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('deliveryModal')).hide();
                    alert('Delivery information sent successfully!');
                    loadPendingReceipts(); // Reload receipts
                    form.reset();
                } else {
                    alert('Failed to send delivery information: ' + result.message);
                }
            } catch (error) {
                console.error('Error sending delivery info:', error);
                alert('Error sending delivery information');
            }
        });

        // Reject payment
        document.getElementById('rejectBtn').addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('receiptModal')).hide();
            const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
            modal.show();
        });

        document.getElementById('confirmRejectBtn').addEventListener('click', async function() {
            const reason = document.getElementById('rejectionReason').value.trim();
            
            if (!reason) {
                alert('Please provide a rejection reason');
                return;
            }

            try {
                const response = await fetch('../../controllers/PaymentWorkflowController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'reject_payment',
                        receipt_id: currentReceiptId,
                        order_id: currentOrderId,
                        rejection_reason: reason
                    })
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('rejectionModal')).hide();
                    alert('Payment rejected successfully');
                    loadPendingReceipts(); // Reload receipts
                    document.getElementById('rejectionReason').value = '';
                } else {
                    alert('Failed to reject payment: ' + result.message);
                }
            } catch (error) {
                console.error('Error rejecting payment:', error);
                alert('Error rejecting payment');
            }
        });

        // Load receipts on page load
        loadPendingReceipts();

        // Auto-refresh every 30 seconds
        setInterval(loadPendingReceipts, 30000);
    </script>
</body>
</html>