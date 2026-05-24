<?php
class ArtisanController {
    private $db;
    private $product;
    private $order;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->product = new Product($this->db);
        $this->order = new Order($this->db);
    }

    public function dashboard() {
        $artisan_id = $_SESSION['user_id'];
        
        // Get artisan's products
        $products = $this->product->getProductsByArtisan($artisan_id);
        
        // Get assigned orders
        $orders = $this->order->getOrdersByArtisan($artisan_id);
        
        require_once 'views/artisan/dashboard.php';
    }

    public function upload_product() {
        if ($_POST) {
            $this->product->artisan_id = $_SESSION['user_id'];
            $this->product->name = $_POST['name'];
            $this->product->description = $_POST['description'];
            $this->product->materials = $_POST['materials'];
            $this->product->colors = $_POST['colors'];
            $this->product->size = $_POST['size'];
            $this->product->weight = $_POST['weight'];
            $this->product->category = $_POST['category'];
            $this->product->price = $_POST['price'];
            $this->product->quantity = $_POST['quantity'];
            $this->product->status = PRODUCT_DRAFT;
            
            if (isset($_POST['submit_for_review'])) {
                $this->product->status = PRODUCT_PENDING;
            }
            
            $product_id = $this->product->create();
            
            if ($product_id) {
                // Handle file uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $this->uploadProductImages($product_id);
                }
                
                if (!empty($_FILES['video']['name'])) {
                    $this->uploadProductVideo($product_id);
                }
                
                if ($this->product->status == PRODUCT_PENDING) {
                    // Notify manager
                    $this->notifyManagerProductSubmission($product_id);
                    $_SESSION['success'] = "Product submitted for review successfully!";
                } else {
                    $_SESSION['success'] = "Product saved as draft successfully!";
                }
                
                header('Location: index.php?page=artisan&action=products');
                exit();
            } else {
                $error = "Failed to create product";
            }
        }
        
        require_once 'views/artisan/upload_product.php';
    }

    public function products() {
        $artisan_id = $_SESSION['user_id'];
        $products = $this->product->getProductsByArtisan($artisan_id);
        require_once 'views/artisan/products.php';
    }

    public function orders() {
        $artisan_id = $_SESSION['user_id'];
        $orders = $this->order->getOrdersByArtisan($artisan_id);
        require_once 'views/artisan/orders.php';
    }

    private function uploadProductImages($product_id) {
        // Implementation for multiple image upload
        $uploadHandler = new UploadHandler();
        return $uploadHandler->uploadProductImages($_FILES['images'], $product_id);
    }

    private function uploadProductVideo($product_id) {
        // Implementation for video upload
        $uploadHandler = new UploadHandler();
        return $uploadHandler->uploadProductVideo($_FILES['video'], $product_id);
    }

    private function notifyManagerProductSubmission($product_id) {
        $notification = new Notification($this->db);
        $notification->user_id = $this->getManagerId();
        $notification->title = "New Product Submission";
        $notification->message = "A new product has been submitted for review";
        $notification->type = "product_submission";
        $notification->create();
    }

    private function getManagerId() {
        // Get manager user ID
        $query = "SELECT id FROM users WHERE role = 'manager' LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id'];
    }
}
?>