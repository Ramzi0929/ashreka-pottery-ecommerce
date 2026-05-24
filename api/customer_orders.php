<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $stmt->fetchColumn();
    $_SESSION['customer_id'] = $customer_id;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            GROUP_CONCAT(p.name SEPARATOR ', ') as product_names,
            di.delivery_date,
            di.artisan_name,
            di.artisan_phone,
            oi.artisan_id,
            oi.product_id,
            r.rating,
            CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_rating
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN delivery_info di ON o.id = di.order_id
        LEFT JOIN ratings r ON o.id = r.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $orders]);
    
} catch (Exception $e) {
    error_log("Error loading customer orders: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load orders']);
}
?>