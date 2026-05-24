<?php
class Product {
    private $conn;
    private $table = 'products';

    public $id;
    public $artisan_id;
    public $name;
    public $description;
    public $materials;
    public $colors;
    public $size;
    public $weight;
    public $category;
    public $price;
    public $quantity;
    public $status;
    public $rejection_reason;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET artisan_id=:artisan_id, name=:name, description=:description, 
                     materials=:materials, colors=:colors, size=:size, weight=:weight,
                     category=:category, price=:price, quantity=:quantity, status=:status";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":artisan_id", $this->artisan_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":materials", $this->materials);
        $stmt->bindParam(":colors", $this->colors);
        $stmt->bindParam(":size", $this->size);
        $stmt->bindParam(":weight", $this->weight);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":status", $this->status);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getProductsByArtisan($artisan_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE artisan_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function getApprovedProducts() {
        $query = "SELECT p.*, u.name as artisan_name, u.profile_image as artisan_image 
                  FROM " . $this->table . " p 
                  JOIN users u ON p.artisan_id = u.id 
                  WHERE p.status = 'approved' AND p.quantity > 0 
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function updateStatus($product_id, $status, $rejection_reason = null) {
        $query = "UPDATE " . $this->table . " 
                 SET status=:status, rejection_reason=:rejection_reason, updated_at=NOW() 
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":rejection_reason", $rejection_reason);
        $stmt->bindParam(":id", $product_id);
        
        return $stmt->execute();
    }

    public function getProductById($id) {
        $query = "SELECT p.*, u.name as artisan_name, u.profile_image as artisan_image 
                  FROM " . $this->table . " p 
                  JOIN users u ON p.artisan_id = u.id 
                  WHERE p.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>