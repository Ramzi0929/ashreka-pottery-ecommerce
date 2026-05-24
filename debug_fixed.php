<?php
require_once './config/database_enhanced.php';

$confirm_code = '826047';

echo "<h3>Debug: Checking confirmation code $confirm_code</h3>";

try {
    // Check if code exists
    $stmt = $pdo->prepare("SELECT * FROM payment_confirmations WHERE confirm_code = ?");
    $stmt->execute([$confirm_code]);
    $confirmation = $stmt->fetch();
    
    if ($confirmation) {
        echo "<p><strong>✓ Confirmation code found!</strong></p>";
        echo "<pre>" . print_r($confirmation, true) . "</pre>";
        
        // Check if used
        if ($confirmation['status'] === 'used') {
            echo "<p><strong>❌ Code has been USED</strong></p>";
        } else {
            echo "<p><strong>✓ Code is still AVAILABLE</strong></p>";
            
            // Check order details
            $stmt = $pdo->prepare("
                SELECT o.id, o.total_amount, o.customer_id 
                FROM orders o 
                WHERE o.id = ?
            ");
            $stmt->execute([$confirmation['order_id']]);
            $order = $stmt->fetch();
            
            if ($order) {
                echo "<p><strong>✓ Order found!</strong></p>";
                echo "<pre>" . print_r($order, true) . "</pre>";
                
                // Check order items
                $stmt = $pdo->prepare("
                    SELECT oi.quantity, oi.price, p.name, p.image_path 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order['id']]);
                $items = $stmt->fetchAll();
                
                echo "<p><strong>Order Items:</strong></p>";
                echo "<pre>" . print_r($items, true) . "</pre>";
                
            } else {
                echo "<p><strong>❌ Order not found!</strong></p>";
            }
        }
        
    } else {
        echo "<p><strong>❌ Confirmation code NOT found in database</strong></p>";
        
        // Show all codes for debugging
        $stmt = $pdo->prepare("SELECT confirm_code, order_id, status FROM payment_confirmations ORDER BY id DESC LIMIT 5");
        $stmt->execute();
        $recent_codes = $stmt->fetchAll();
        
        echo "<p><strong>Recent confirmation codes:</strong></p>";
        echo "<pre>" . print_r($recent_codes, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Database error:</strong> " . $e->getMessage() . "</p>";
}
?>