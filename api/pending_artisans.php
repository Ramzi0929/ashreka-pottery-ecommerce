<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.email, u.created_at
        FROM artisans a
        JOIN users u ON a.user_id = u.id
        WHERE a.approval_status = 'pending'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $artisans = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $artisans]);
    
} catch (Exception $e) {
    error_log("Error loading pending artisans: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load pending artisans']);
}
?>