<?php
require_once './config/database_enhanced.php';

echo "<h3>Checking products table structure</h3>";

try {
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "<h4>Products table columns:</h4>";
    echo "<pre>" . print_r($columns, true) . "</pre>";
    
    // Also check order_items table
    $stmt = $pdo->query("DESCRIBE order_items");
    $columns = $stmt->fetchAll();
    
    echo "<h4>Order_items table columns:</h4>";
    echo "<pre>" . print_r($columns, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>