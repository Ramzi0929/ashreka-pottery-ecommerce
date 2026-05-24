<?php
header('Content-Type: application/json');
require_once '../config/database_enhanced.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['email']) || isset($_POST['phone']))) {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    try {
        // Check by email or phone
        if ($email) {
            $stmt = $pdo->prepare("
                SELECT u.status, c.email_verified, u.rejection_reason 
                FROM users u 
                LEFT JOIN customers c ON u.id = c.user_id 
                WHERE u.email = ? AND u.role = 'customer'
            ");
            $stmt->execute([$email]);
        } else if ($phone) {
            $stmt = $pdo->prepare("
                SELECT u.status, c.email_verified, u.rejection_reason 
                FROM users u 
                LEFT JOIN customers c ON u.id = c.user_id 
                WHERE u.phone = ? AND u.role = 'customer'
            ");
            $stmt->execute([$phone]);
        } else {
            echo json_encode(['status' => 'pending']);
            exit;
        }
        
        $result = $stmt->fetch();
        
        if ($result) {
            if ($result['status'] === 'active') {
                echo json_encode(['status' => 'approved']);
            } else if ($result['status'] === 'rejected') {
                echo json_encode([
                    'status' => 'rejected',
                    'reason' => $result['rejection_reason'] ?? 'No specific reason provided'
                ]);
            } else {
                echo json_encode(['status' => 'pending']);
            }
        } else {
            echo json_encode(['status' => 'pending']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'pending']);
    }
} else {
    echo json_encode(['status' => 'pending']);
}
?>