<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_order') {
        try {
            $customer_id = $_SESSION['customer_id'] ?? null;
            if (!$customer_id) {
                if (!isset($_SESSION['user_id'])) {
                    echo json_encode(['success' => false, 'message' => 'User not logged in']);
                    exit;
                }
                $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $customer_id = $stmt->fetchColumn();
                
                if (!$customer_id) {
                    // Create customer record if it doesn't exist
                    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, email) VALUES (?, ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $user['username'], $user['email']]);
                        $customer_id = $pdo->lastInsertId();
                        $_SESSION['customer_id'] = $customer_id;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        exit;
                    }
                } else {
                    $_SESSION['customer_id'] = $customer_id;
                }
            }
            
            $cart = $_SESSION['cart'] ?? [];
            if (empty($cart)) {
                echo json_encode(['success' => false, 'message' => 'Cart is empty']);
                exit;
            }
            
            $total = 0;
            $items = [];
            
            // Calculate total and prepare items
            foreach ($cart as $product_id => $quantity) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product) {
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                    
                    $items[] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'price' => $product['price'],
                        'subtotal' => $subtotal
                    ];
                }
            }
            
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, status) VALUES (?, ?, 'pending_payment')");
            $stmt->execute([$customer_id, $total]);
            $order_id = $pdo->lastInsertId();
            
            // Create order items
            foreach ($items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $item['price'], 
                    $item['subtotal']
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Order created successfully',
                'order_id' => $order_id
            ]);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error creating order: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>