<?php
header('Content-Type: application/json');
require_once '../config/database_enhanced.php';

$input = json_decode(file_get_contents('php://input'), true);
$confirm_code = $input['confirm_code'] ?? '';
$user_id = $input['user_id'] ?? 0;

if (strlen($confirm_code) !== 6 || !$user_id) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    // Get order details with confirmation code
    $stmt = $pdo->prepare("
        SELECT o.id, o.total_amount, pc.id as confirm_id 
        FROM orders o 
        JOIN payment_confirmations pc ON o.id = pc.order_id 
        WHERE pc.confirm_code = ? AND o.customer_id = (
            SELECT id FROM customers WHERE user_id = ?
        ) AND pc.expires_at > NOW()
    ");
    $stmt->execute([$confirm_code, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    // Get order items with product images
    $stmt = $pdo->prepare("
        SELECT oi.quantity, oi.price, p.name, p.image_url 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'total' => number_format($order['total_amount'], 2),
        'items' => array_map(function($item) {
            return [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => number_format($item['price'] * $item['quantity'], 2),
                'image' => $item['image_url'] ?: 'assets/images/default-product.jpg'
            ];
        }, $items)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
?>