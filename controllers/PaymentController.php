<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'process_payment':
        processPayment();
        break;
    case 'verify_payment':
        verifyPayment();
        break;
    default:
        exit(json_encode(['success' => false, 'message' => 'Invalid action']));
}

function processPayment() {
    global $pdo;
    
    $order_id = $_POST['order_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $payment_method = $_POST['payment_method'] ?? 'telebirr';
    
    if (!$order_id || !$amount) {
        exit(json_encode(['success' => false, 'message' => 'Missing required fields']));
    }
    
    try {
        // Get order details
        $stmt = $pdo->prepare("SELECT o.*, c.first_name, c.last_name, c.email, c.phone FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            exit(json_encode(['success' => false, 'message' => 'Order not found']));
        }
        
        // Store transaction reference
        $tx_ref = 'ORDER_' . $order_id . '_' . time();
        $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$order_id, $amount, $payment_method, $tx_ref]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment initiated. Please complete payment via ' . $payment_method
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Payment processing error']);
    }
}

function verifyPayment() {
    global $pdo;
    
    $tx_ref = $_GET['tx_ref'] ?? $_POST['tx_ref'] ?? null;
    
    if (!$tx_ref) {
        exit(json_encode(['success' => false, 'message' => 'Transaction reference missing']));
    }
    
    try {
        // Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE transaction_id = ?");
        $stmt->execute([$tx_ref]);
        
        // Update order payment status
        $stmt = $pdo->prepare("SELECT order_id, amount FROM payments WHERE transaction_id = ?");
        $stmt->execute([$tx_ref]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $stmt = $pdo->prepare("UPDATE orders SET paid_amount = paid_amount + ?, payment_status = 'full' WHERE id = ?");
            $stmt->execute([$payment['amount'], $payment['order_id']]);
            
            // Check and update purchase count
            $stmt = $pdo->prepare("SELECT customer_id FROM orders WHERE id = ?");
            $stmt->execute([$payment['order_id']]);
            $customer_id = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ? AND payment_status = 'full'");
            $stmt->execute([$customer_id]);
            $completed_orders = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE customers SET purchase_count = ? WHERE id = ?");
            $stmt->execute([$completed_orders, $customer_id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Payment verification error']);
    }
}
?>