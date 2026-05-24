<?php
class NotificationHandler {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function sendNotification($user_id, $title, $message, $type = 'info') {
        $notification = new Notification($this->db);
        $notification->user_id = $user_id;
        $notification->title = $title;
        $notification->message = $message;
        $notification->type = $type;
        return $notification->create();
    }

    public function sendBulkNotification($user_ids, $title, $message, $type = 'info') {
        foreach ($user_ids as $user_id) {
            $this->sendNotification($user_id, $title, $message, $type);
        }
        return true;
    }

    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
?>