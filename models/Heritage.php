<?php
class Heritage {
    private $conn;
    private $table = 'heritage_content';

    public $id;
    public $title;
    public $description;
    public $content_type;
    public $file_path;
    public $uploaded_by;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET title=:title, description=:description, content_type=:content_type,
                     uploaded_by=:uploaded_by, status='active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":content_type", $this->content_type);
        $stmt->bindParam(":uploaded_by", $this->uploaded_by);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getAllHeritage() {
        $query = "SELECT h.*, u.name as uploaded_by_name 
                  FROM " . $this->table . " h 
                  JOIN users u ON h.uploaded_by = u.id 
                  WHERE h.status = 'active' 
                  ORDER BY h.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function getHeritageById($id) {
        $query = "SELECT h.*, u.name as uploaded_by_name 
                  FROM " . $this->table . " h 
                  JOIN users u ON h.uploaded_by = u.id 
                  WHERE h.id = ? AND h.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateFilepath($id, $file_path) {
        $query = "UPDATE " . $this->table . " SET file_path = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$file_path, $id]);
    }
}
?>