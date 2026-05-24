<?php
class Payment {
    private $conn;
    private $table = 'payments';

    public $id;
    public $order_id;
    public $amount;
    public $payment_method;
    public $transaction_id;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createPayment($order_id, $amount) {
        $query = "INSERT INTO " . $this->table . " 
                 SET order_id=:order_id, amount=:amount, 
                     payment_method='manual', status='pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->bindParam(":amount", $amount);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function updatePaymentStatus($payment_id, $status) {
        $query = "UPDATE " . $this->table . " 
                 SET status=:status WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $payment_id);
        
        return $stmt->execute();
    }

    public function getPaymentsByOrder($order_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE order_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $order_id);
        $stmt->execute();
        
        return $stmt;
    }
}
?>