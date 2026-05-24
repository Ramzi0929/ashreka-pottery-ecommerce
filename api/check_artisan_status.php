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
                SELECT a.approval_status, u.rejection_reason 
                FROM artisans a 
                JOIN users u ON a.user_id = u.id 
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
        } else if ($phone) {
            $stmt = $pdo->prepare("
                SELECT a.approval_status, u.rejection_reason 
                FROM artisans a 
                JOIN users u ON a.user_id = u.id 
                WHERE u.phone = ?
            ");
            $stmt->execute([$phone]);
        } else {
            echo json_encode(['status' => 'pending']);
            exit;
        }
        
        $result = $stmt->fetch();
        
        if ($result) {
            if ($result['approval_status'] === 'rejected') {
                echo json_encode([
                    'status' => 'rejected',
                    'reason' => $result['rejection_reason'] ?? 'No specific reason provided'
                ]);
            } else {
                echo json_encode(['status' => $result['approval_status']]);
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