<?php
class Notification {
    private $conn;
    private $table = 'notifications';

    public $id;
    public $user_id;
    public $title;
    public $message;
    public $type;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET user_id=:user_id, title=:title, message=:message, type=:type";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":type", $this->type);
        
        return $stmt->execute();
    }

    public function getUserNotifications($user_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function markAsRead($notification_id) {
        $query = "UPDATE " . $this->table . " SET is_read = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $notification_id);
        return $stmt->execute();
    }
}
?>