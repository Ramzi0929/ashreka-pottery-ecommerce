<?php
class Customer {
    private $conn;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $phone;
    public $address;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getCustomerProfile($customer_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? AND role = 'customer'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $customer_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCustomerProfile($customer_id, $data) {
        $query = "UPDATE " . $this->table . " 
                 SET name = ?, phone = ?, address = ?, updated_at = NOW() 
                 WHERE id = ? AND role = 'customer'";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'], $data['phone'], $data['address'], $customer_id
        ]);
    }

    public function getCustomerOrderStats($customer_id) {
        $stats = [];
        
        // Check if database connection is valid
        if (!$this->conn) {
            error_log("Database connection is null in Customer model");
            return ['total_orders' => 0, 'completed_orders' => 0, 'pending_orders' => 0];
        }
        
        try {
            // Total orders
            $query = "SELECT COUNT(*) as total FROM orders WHERE customer_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$customer_id]);
            $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Completed orders
            $query = "SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND status = 'completed'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$customer_id]);
            $stats['completed_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Pending orders
            $query = "SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND status IN ('pending', 'in_progress')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$customer_id]);
            $stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Database error in getCustomerOrderStats: " . $e->getMessage());
            // Return default values on error
            $stats = ['total_orders' => 0, 'completed_orders' => 0, 'pending_orders' => 0];
        }
        
        return $stats;
    }
}
?>