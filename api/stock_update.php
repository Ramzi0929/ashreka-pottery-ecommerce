<?php
session_start();
require_once '../config/database_enhanced.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_stock':
            if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            $productId = $_POST['product_id'] ?? 0;
            $newQuantity = $_POST['quantity'] ?? 0;
            
            try {
                $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $productId]);
                
                echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to update stock']);
            }
            break;
            
        case 'get_stock':
            $productId = $_POST['product_id'] ?? 0;
            
            try {
                $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $quantity = $stmt->fetchColumn();
                
                echo json_encode(['success' => true, 'quantity' => $quantity]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to get stock']);
            }
            break;
            
        case 'decrease_stock':
            // Called automatically when order is paid
            $productId = $_POST['product_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            try {
                $pdo->beginTransaction();
                
                // Check current stock
                $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $currentStock = $stmt->fetchColumn();
                
                if ($currentStock < $quantity) {
                    throw new Exception('Insufficient stock');
                }
                
                // Update stock
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$quantity, $productId]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Stock decreased successfully']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all products with stock info
    try {
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.quantity, p.price, p.category, a.name as artisan_name
            FROM products p 
            JOIN artisans a ON p.artisan_id = a.id 
            WHERE p.status = 'approved'
            ORDER BY p.name
        ");
        $products = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'products' => $products]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch products']);
    }
}
?>