<?php
class CustomerController {
    private $db;
    private $product;
    private $order;
    private $customer;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->product = new Product($this->db);
        $this->order = new Order($this->db);
        $this->customer = new Customer($this->db);
    }

    public function dashboard() {
        // Get customer stats using the Customer model
        $stats = $this->customer->getCustomerOrderStats($_SESSION['user_id']);
        
        // Pass database connection and stats to the view
        $db = $this->db;
        
        require_once 'views/customer/dashboard.php';
    }

    public function catalog() {
        $products = $this->product->getApprovedProducts();
        $db = $this->db; // Pass db connection to view
        
        require_once 'views/customer/catalog.php';
    }

    public function custom_order() {
        if ($_POST) {
            $order = new Order($this->db);
            $order->customer_id = $_SESSION['user_id'];
            $order->type = 'custom';
            $order->custom_details = json_encode([
                'product_type' => $_POST['product_type'],
                'materials' => $_POST['materials'],
                'colors' => $_POST['colors'],
                'dimensions' => $_POST['dimensions'],
                'notes' => $_POST['notes']
            ]);
            $order->quantity = $_POST['quantity'];
            $order->total_amount = $_POST['estimated_price'];
            $order->status = 'pending';
            
            if ($order->create()) {
                $this->notifyManagerCustomOrder($order->id);
                $_SESSION['success'] = "Custom order submitted successfully!";
                header('Location: index.php?page=customer&action=orders');
                exit();
            } else {
                $error = "Failed to submit custom order";
            }
        }
        
        $db = $this->db;
        require_once 'views/customer/custom_order.php';
    }

    public function orders() {
        $customer_id = $_SESSION['user_id'];
        $orders = $this->order->getOrdersByCustomer($customer_id);
        $db = $this->db;
        
        require_once 'views/customer/orders.php';
    }

    private function notifyManagerCustomOrder($order_id) {
        $notification = new Notification($this->db);
        $notification->user_id = $this->getManagerId();
        $notification->title = "New Custom Order";
        $notification->message = "A new custom order has been submitted";
        $notification->type = "custom_order";
        $notification->create();
    }

    private function getManagerId() {
        $query = "SELECT id FROM users WHERE role = 'manager' LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id'];
    }
}
?>