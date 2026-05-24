<?php
session_start();
require_once '../config/database_enhanced.php';
require_once '../includes/functions.php';

class ManagerController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function approveArtisan($artisanId) {
        try {
            $this->pdo->beginTransaction();
            
            // Update artisan status
            $stmt = $this->pdo->prepare("UPDATE artisans SET approval_status = 'approved' WHERE id = ?");
            $stmt->execute([$artisanId]);
            
            // Update user status
            $stmt = $this->pdo->prepare("UPDATE users u JOIN artisans a ON u.id = a.user_id SET u.status = 'active' WHERE a.id = ?");
            $stmt->execute([$artisanId]);
            
            // Get artisan info for notification
            $stmt = $this->pdo->prepare("SELECT a.name, u.id as user_id FROM artisans a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
            $stmt->execute([$artisanId]);
            $artisan = $stmt->fetch();
            
            // Send notification
            $this->sendNotification($artisan['user_id'], 'Registration Approved', 'Your artisan registration has been approved. You can now start uploading products.');
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Artisan approved successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to approve artisan: ' . $e->getMessage()];
        }
    }
    
    public function rejectArtisan($artisanId, $reason) {
        try {
            $this->pdo->beginTransaction();
            
            // Update artisan status
            $stmt = $this->pdo->prepare("UPDATE artisans SET approval_status = 'rejected' WHERE id = ?");
            $stmt->execute([$artisanId]);
            
            // Get artisan info for notification
            $stmt = $this->pdo->prepare("SELECT a.name, u.id as user_id FROM artisans a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
            $stmt->execute([$artisanId]);
            $artisan = $stmt->fetch();
            
            // Send notification
            $this->sendNotification($artisan['user_id'], 'Registration Rejected', "Your artisan registration has been rejected. Reason: $reason");
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Artisan rejected'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to reject artisan: ' . $e->getMessage()];
        }
    }
    
    public function approveProduct($productId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET status = 'approved' WHERE id = ?");
            $stmt->execute([$productId]);
            
            // Get product and artisan info
            $stmt = $this->pdo->prepare("SELECT p.name, a.user_id FROM products p JOIN artisans a ON p.artisan_id = a.id WHERE p.id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            // Send notification
            $this->sendNotification($product['user_id'], 'Product Approved', "Your product '{$product['name']}' has been approved and is now visible to customers.");
            
            return ['success' => true, 'message' => 'Product approved successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to approve product: ' . $e->getMessage()];
        }
    }
    
    public function rejectProduct($productId, $reason) {
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET status = 'rejected', rejection_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $productId]);
            
            // Get product and artisan info
            $stmt = $this->pdo->prepare("SELECT p.name, a.user_id FROM products p JOIN artisans a ON p.artisan_id = a.id WHERE p.id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            // Send notification
            $this->sendNotification($product['user_id'], 'Product Rejected', "Your product '{$product['name']}' has been rejected. Reason: $reason");
            
            return ['success' => true, 'message' => 'Product rejected'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to reject product: ' . $e->getMessage()];
        }
    }
    
    public function assignOrder($orderId, $artisanId) {
        try {
            $this->pdo->beginTransaction();
            
            // Update order
            $stmt = $this->pdo->prepare("UPDATE orders SET artisan_id = ?, status = 'approved' WHERE id = ?");
            $stmt->execute([$artisanId, $orderId]);
            
            // Calculate due date (simple: 14 days from now)
            $dueDate = date('Y-m-d', strtotime('+14 days'));
            $stmt = $this->pdo->prepare("UPDATE orders SET due_date = ? WHERE id = ?");
            $stmt->execute([$dueDate, $orderId]);
            
            // Get order and customer info
            $stmt = $this->pdo->prepare("SELECT o.*, c.user_id as customer_user_id FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            // Get artisan info
            $stmt = $this->pdo->prepare("SELECT user_id FROM artisans WHERE id = ?");
            $stmt->execute([$artisanId]);
            $artisan = $stmt->fetch();
            
            // Notify customer
            $this->sendNotification($order['customer_user_id'], 'Order Assigned', "Your order #$orderId has been assigned to an artisan. Due date: $dueDate");
            
            // Notify artisan
            $this->sendNotification($artisan['user_id'], 'New Order Assignment', "You have been assigned order #$orderId. Due date: $dueDate");
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Order assigned successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to assign order: ' . $e->getMessage()];
        }
    }
    
    public function generateReport($type, $startDate = null, $endDate = null) {
        try {
            $whereClause = '';
            $params = [];
            
            if ($startDate && $endDate) {
                $whereClause = "WHERE DATE(created_at) BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            }
            
            switch ($type) {
                case 'sales':
                    $stmt = $this->pdo->prepare("
                        SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue 
                        FROM orders $whereClause 
                        GROUP BY DATE(created_at) 
                        ORDER BY date DESC
                    ");
                    break;
                    
                case 'artisan_performance':
                    $stmt = $this->pdo->prepare("
                        SELECT a.name, COUNT(o.id) as orders_completed, AVG(r.rating) as avg_rating
                        FROM artisans a 
                        LEFT JOIN orders o ON a.id = o.artisan_id AND o.status = 'delivered'
                        LEFT JOIN ratings r ON o.id = r.order_id
                        GROUP BY a.id, a.name
                        ORDER BY orders_completed DESC
                    ");
                    break;
                    
                case 'products':
                    $stmt = $this->pdo->prepare("
                        SELECT p.name, p.category, p.price, p.quantity, a.name as artisan_name
                        FROM products p 
                        JOIN artisans a ON p.artisan_id = a.id 
                        WHERE p.status = 'approved' $whereClause
                        ORDER BY p.created_at DESC
                    ");
                    break;
                    
                default:
                    return ['success' => false, 'message' => 'Invalid report type'];
            }
            
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            return ['success' => true, 'data' => $data];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to generate report: ' . $e->getMessage()];
        }
    }
    
    private function sendNotification($userId, $title, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $title, $message]);
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $manager = new ManagerController($pdo);
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'approve_artisan':
            $result = $manager->approveArtisan($_POST['artisan_id']);
            break;
            
        case 'reject_artisan':
            $result = $manager->rejectArtisan($_POST['artisan_id'], $_POST['reason']);
            break;
            
        case 'approve_product':
            $result = $manager->approveProduct($_POST['product_id']);
            break;
            
        case 'reject_product':
            $result = $manager->rejectProduct($_POST['product_id'], $_POST['reason']);
            break;
            
        case 'assign_order':
            $result = $manager->assignOrder($_POST['order_id'], $_POST['artisan_id']);
            break;
            
        case 'generate_report':
            $result = $manager->generateReport($_POST['type'], $_POST['start_date'] ?? null, $_POST['end_date'] ?? null);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>