<?php
session_start();
require_once '../config/database_enhanced.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_notifications':
        getNotifications();
        break;
    case 'mark_read':
        markAsRead();
        break;
    case 'send_notification':
        sendNotification();
        break;
    case 'get_count':
        getUnreadCount();
        break;
    default:
        exit(json_encode(['success' => false, 'message' => 'Invalid action']));
}

function getNotifications() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

function markAsRead() {
    global $pdo;
    
    $notification_id = $_POST['id'] ?? null;
    
    if ($notification_id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $_SESSION['user_id']]);
    } else {
        // Mark all as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    echo json_encode(['success' => true]);
}

function sendNotification() {
    global $pdo;
    
    $user_id = $_POST['user_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';
    $type = $_POST['type'] ?? 'info';
    
    if (!$user_id || !$title || !$message) {
        exit(json_encode(['success' => false, 'message' => 'Missing required fields']));
    }
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $message, $type]);
    
    echo json_encode(['success' => true]);
}

function getUnreadCount() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $count = $stmt->fetchColumn();
    
    echo json_encode(['count' => $count]);
}

// Helper function to send notifications (can be called from other files)
function createNotification($user_id, $title, $message, $type = 'info') {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}
?>