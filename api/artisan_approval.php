<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $artisan_id = $_POST['artisan_id'] ?? 0;
    
    if (!$artisan_id) {
        echo json_encode(['success' => false, 'message' => 'Artisan ID required']);
        exit;
    }
    
    try {
        if ($action === 'approve') {
            // Approve artisan
            $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'approved' WHERE id = ?");
            $stmt->execute([$artisan_id]);
            
            // Get artisan user ID for notification
            $stmt = $pdo->prepare("SELECT user_id, name FROM artisans WHERE id = ?");
            $stmt->execute([$artisan_id]);
            $artisan = $stmt->fetch();
            
            // Send notification (if notification system exists)
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, user_type, title, message, type) 
                    VALUES (?, 'artisan', 'Registration Approved', 'Congratulations! Your artisan registration has been approved. You can now access your dashboard and start uploading products.', 'general')
                ");
                $stmt->execute([$artisan['user_id']]);
            } catch (Exception $e) {
                // Notification failed but approval succeeded
                error_log("Failed to send approval notification: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Artisan approved successfully']);
            
        } elseif ($action === 'reject') {
            $reason = $_POST['reason'] ?? 'No reason provided';
            
            // Reject artisan
            $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'rejected' WHERE id = ?");
            $stmt->execute([$artisan_id]);
            
            // Get artisan user ID for notification
            $stmt = $pdo->prepare("SELECT user_id, name FROM artisans WHERE id = ?");
            $stmt->execute([$artisan_id]);
            $artisan = $stmt->fetch();
            
            // Send notification
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, user_type, title, message, type) 
                    VALUES (?, 'artisan', 'Registration Rejected', ?, 'general')
                ");
                $stmt->execute([$artisan['user_id'], "Your artisan registration has been rejected. Reason: $reason"]);
            } catch (Exception $e) {
                error_log("Failed to send rejection notification: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Artisan rejected successfully']);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error in artisan approval: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to process request']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>