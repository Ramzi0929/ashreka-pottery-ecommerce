<?php
class NotificationController {
    private $db;
    private $notification;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->notification = new Notification($this->db);
    }

    public function get() {
        header('Content-Type: application/json');
        
        $user_id = $_SESSION['user_id'];
        $notifications = $this->notification->getUserNotifications($user_id);
        
        $result = [];
        while ($notification = $notifications->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'is_read' => (bool)$notification['is_read'],
                'created_at' => formatDate($notification['created_at'])
            ];
        }
        
        echo json_encode(['success' => true, 'notifications' => $result]);
    }

    public function markRead() {
        header('Content-Type: application/json');
        
        $notification_id = $_POST['notification_id'] ?? 0;
        
        if ($this->notification->markAsRead($notification_id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
        }
    }

    public function count() {
        header('Content-Type: application/json');
        
        $user_id = $_SESSION['user_id'];
        $unread_count = $this->getUnreadCount($user_id);
        
        echo json_encode(['success' => true, 'unread_count' => $unread_count]);
    }

    private function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
?>