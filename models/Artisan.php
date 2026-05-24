<?php
class Artisan {
    private $conn;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $phone;
    public $address;
    public $profile_image;
    public $specialization;
    public $experience;
    public $bio;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getArtisanProfile($artisan_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? AND role = 'artisan'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllArtisans() {
        $query = "SELECT id, name, email, phone, address, profile_image, bio 
                  FROM " . $this->table . " 
                  WHERE role = 'artisan' AND status = 'active' 
                  ORDER BY name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function getArtisanStats($artisan_id) {
        $stats = [];
        
        // Total products
        $query = "SELECT COUNT(*) as total FROM products WHERE artisan_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$artisan_id]);
        $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Approved products
        $query = "SELECT COUNT(*) as total FROM products WHERE artisan_id = ? AND status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$artisan_id]);
        $stats['approved_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Completed orders
        $query = "SELECT COUNT(*) as total FROM orders WHERE artisan_id = ? AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$artisan_id]);
        $stats['completed_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }

    public function updateArtisanProfile($artisan_id, $data) {
        $query = "UPDATE " . $this->table . " 
                 SET name = ?, phone = ?, address = ?, bio = ?, profile_image = ?, updated_at = NOW() 
                 WHERE id = ? AND role = 'artisan'";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'], $data['phone'], $data['address'], $data['bio'], $data['profile_image'], $artisan_id
        ]);
    }
}
?>