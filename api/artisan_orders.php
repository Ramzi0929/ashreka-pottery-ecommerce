<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artisan') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$artisan_id = $_SESSION['artisan_id'] ?? null;
if (!$artisan_id) {
    $stmt = $pdo->prepare("SELECT id FROM artisans WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $artisan_id = $stmt->fetchColumn();
    $_SESSION['artisan_id'] = $artisan_id;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            oi.quantity,
            oi.subtotal,
            p.name as product_name,
            c.name as customer_name,
            di.delivery_date,
            r.rating,
            r.review
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN customers c ON o.customer_id = c.id
        LEFT JOIN delivery_info di ON o.id = di.order_id
        LEFT JOIN ratings r ON o.id = r.order_id AND r.artisan_id = ?
        WHERE oi.artisan_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$artisan_id, $artisan_id]);
    $orders = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $orders]);
    
} catch (Exception $e) {
    error_log("Error loading artisan orders: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load orders']);
}
?>