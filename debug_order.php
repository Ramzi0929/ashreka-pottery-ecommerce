<?php
session_start();
require_once 'config/database_enhanced.php';

echo "<h2>Debug Order Creation</h2>";

// Check what customers exist
echo "<h3>Available Customers:</h3>";
$stmt = $pdo->prepare("SELECT id, name FROM customers LIMIT 5");
$stmt->execute();
$customers = $stmt->fetchAll();

if (empty($customers)) {
    echo "<p style='color: red;'>No customers found! Creating a test customer...</p>";
    
    // Create a test customer
    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
    $stmt->execute(['Test Customer', 'test@example.com', '0912345678']);
    $customer_id = $pdo->lastInsertId();
    echo "<p style='color: green;'>Created test customer with ID: $customer_id</p>";
} else {
    foreach ($customers as $customer) {
        echo "<p>ID: {$customer['id']}, Name: {$customer['name']}</p>";
    }
    $customer_id = $customers[0]['id'];
    echo "<p>Using customer ID: $customer_id</p>";
}

// Check what products exist
echo "<h3>Available Products:</h3>";
$stmt = $pdo->prepare("SELECT id, name, price FROM products LIMIT 10");
$stmt->execute();
$products = $stmt->fetchAll();

if (empty($products)) {
    echo "<p style='color: red;'>No products found in database!</p>";
} else {
    foreach ($products as $product) {
        echo "<p>ID: {$product['id']}, Name: {$product['name']}, Price: {$product['price']}</p>";
    }
    
    // Use the first available product
    $first_product_id = $products[0]['id'];
    
    // Set up test session with valid product and customer
    $_SESSION['user_id'] = 9;
    $_SESSION['role'] = 'customer';
    $_SESSION['customer_id'] = $customer_id;
    $_SESSION['cart'] = [$first_product_id => 1];
    
    echo "<h3>Testing Order Creation with Product ID: $first_product_id, Customer ID: $customer_id</h3>";
    
    try {
        $session_customer_id = $_SESSION['customer_id'];
        $cart = $_SESSION['cart'];
        
        echo "<p>Customer ID: $session_customer_id</p>";
        echo "<p>Cart: " . json_encode($cart) . "</p>";
        
        $total = 0;
        $items = [];
        
        // Check products
        foreach ($cart as $product_id => $quantity) {
            echo "<p>Checking product ID: $product_id</p>";
            
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                echo "<p>Product found: " . $product['name'] . "</p>";
                echo "<p>Price: " . $product['price'] . "</p>";
                
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                
                $items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $subtotal
                ];
            } else {
                echo "<p style='color: red;'>Product not found!</p>";
            }
        }
        
        echo "<p>Total: $total</p>";
        echo "<p>Items: " . json_encode($items) . "</p>";
        
        if (!empty($items)) {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, status) VALUES (?, ?, 'pending_payment')");
            $stmt->execute([$session_customer_id, $total]);
            $order_id = $pdo->lastInsertId();
            
            echo "<p>Order created with ID: $order_id</p>";
            
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
                echo "<p>Order item created for product " . $item['product_id'] . "</p>";
            }
            
            $pdo->commit();
            echo "<p style='color: green;'>✓ Order created successfully!</p>";
            echo "<p><a href='views/customer/checkout.php'>Try checkout now</a></p>";
            
        } else {
            echo "<p style='color: red;'>No valid items found!</p>";
        }
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        echo "<p style='color: red;'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    }
}
?>