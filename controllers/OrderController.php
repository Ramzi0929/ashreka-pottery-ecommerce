<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_order':
        createOrder();
        break;
    case 'update_stock':
        updateStock();
        break;
    default:
        exit(json_encode(['success' => false, 'message' => 'Invalid action']));
}

function createOrder() {
    global $pdo;
    
    $customer_id = $_SESSION['customer_id'] ?? null;
    if (!$customer_id) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $customer_id = $stmt->fetchColumn();
    }
    
    $cart = $_SESSION['cart'] ?? [];
    $total_amount = $_POST['total_amount'] ?? 0;
    
    if (empty($cart)) {
        exit(json_encode(['success' => false, 'message' => 'Cart is empty']));
    }
    
    try {
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, type, status, total_amount) VALUES (?, 'catalog', 'pending', ?)");
        $stmt->execute([$customer_id, $total_amount]);
        $order_id = $pdo->lastInsertId();
        
        // Add order items and update stock
        foreach ($cart as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT price, quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product && $product['quantity'] >= $quantity) {
                // Add order item
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);
                
                // Update stock
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order creation failed']);
    }
}

function updateStock() {
    global $pdo;
    
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 0;
    
    if (!$product_id || $quantity <= 0) {
        exit(json_encode(['success' => false, 'message' => 'Invalid parameters']));
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
        $stmt->execute([$quantity, $product_id, $quantity]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Stock update failed']);
    }
}
?>