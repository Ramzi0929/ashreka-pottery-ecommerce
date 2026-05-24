<?php
class Order {
    private $conn;
    private $table = 'orders';

    public $id;
    public $customer_id;
    public $artisan_id;
    public $product_id;
    public $type;
    public $custom_details;
    public $quantity;
    public $total_amount;
    public $status;
    public $estimated_delivery;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET customer_id=:customer_id, artisan_id=:artisan_id, product_id=:product_id,
                     type=:type, custom_details=:custom_details, quantity=:quantity,
                     total_amount=:total_amount, status=:status, estimated_delivery=:estimated_delivery";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":artisan_id", $this->artisan_id);
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":custom_details", $this->custom_details);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":estimated_delivery", $this->estimated_delivery);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getOrdersByCustomer($customer_id) {
        $query = "SELECT o.*, p.name as product_name, u.name as artisan_name 
                  FROM " . $this->table . " o 
                  LEFT JOIN products p ON o.product_id = p.id 
                  LEFT JOIN users u ON o.artisan_id = u.id 
                  WHERE o.customer_id = ? 
                  ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $customer_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function getOrdersByArtisan($artisan_id) {
        $query = "SELECT o.*, p.name as product_name, u.name as customer_name 
                  FROM " . $this->table . " o 
                  LEFT JOIN products p ON o.product_id = p.id 
                  LEFT JOIN users u ON o.customer_id = u.id 
                  WHERE o.artisan_id = ? 
                  ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function updateStatus($order_id, $status) {
        $query = "UPDATE " . $this->table . " 
                 SET status=:status, updated_at=NOW() 
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $order_id);
        
        return $stmt->execute();
    }
}
?>