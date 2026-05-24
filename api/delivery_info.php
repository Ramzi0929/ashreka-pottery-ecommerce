<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Verify user has access to this order
    if ($_SESSION['role'] === 'customer') {
        $customer_id = $_SESSION['customer_id'] ?? null;
        if (!$customer_id) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $customer_id = $stmt->fetchColumn();
        }
        
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ?");
        $stmt->execute([$order_id, $customer_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
    } elseif ($_SESSION['role'] === 'artisan') {
        $artisan_id = $_SESSION['artisan_id'] ?? null;
        if (!$artisan_id) {
            $stmt = $pdo->prepare("SELECT id FROM artisans WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $artisan_id = $stmt->fetchColumn();
        }
        
        $stmt = $pdo->prepare("
            SELECT o.id FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.id = ? AND oi.artisan_id = ?
        ");
        $stmt->execute([$order_id, $artisan_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
    }
    
    // Get delivery information
    $stmt = $pdo->prepare("SELECT * FROM delivery_info WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $delivery_info = $stmt->fetch();
    
    if ($delivery_info) {
        echo json_encode(['success' => true, 'data' => $delivery_info]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delivery information not found']);
    }
    
} catch (Exception $e) {
    error_log("Error loading delivery info: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load delivery information']);
}
?>