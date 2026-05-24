<?php
session_start();
require_once '../config/database_enhanced.php';
require_once '../includes/functions.php';

class ProductController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function uploadProduct($data, $files) {
        try {
            // Get artisan ID
            $stmt = $this->pdo->prepare("SELECT id FROM artisans WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $artisan = $stmt->fetch();
            
            if (!$artisan) {
                return ['success' => false, 'message' => 'Artisan not found'];
            }
            
            $this->pdo->beginTransaction();
            
            // Handle image upload
            $imagePath = null;
            if (isset($files['image']) && $files['image']['error'] === 0) {
                $imagePath = $this->uploadFile($files['image'], 'products', ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
                if (!$imagePath) {
                    throw new Exception('Failed to upload image');
                }
            }
            
            // Handle video upload
            $videoPath = null;
            if (isset($files['video']) && $files['video']['error'] === 0) {
                $videoPath = $this->uploadFile($files['video'], 'products', ['mp4', 'webm', 'avi'], 10 * 1024 * 1024);
                if (!$videoPath) {
                    throw new Exception('Failed to upload video');
                }
            }
            
            // Insert product
            $stmt = $this->pdo->prepare("
                INSERT INTO products (artisan_id, name, description, category, material, size, price, quantity, image_path, video_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $artisan['id'],
                $data['name'],
                $data['description'],
                $data['category'],
                $data['material'],
                $data['size'],
                $data['price'],
                $data['quantity'],
                $imagePath,
                $videoPath
            ]);
            
            $productId = $this->pdo->lastInsertId();
            
            // Notify manager
            $this->notifyManager('New Product Submission', "Artisan has uploaded a new product: {$data['name']}");
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Product uploaded successfully', 'product_id' => $productId];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    public function updateProduct($productId, $data, $files = []) {
        try {
            // Verify ownership
            $stmt = $this->pdo->prepare("
                SELECT p.*, a.user_id 
                FROM products p 
                JOIN artisans a ON p.artisan_id = a.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product || $product['user_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'Product not found or access denied'];
            }
            
            $this->pdo->beginTransaction();
            
            // Handle new image upload
            $imagePath = $product['image_path'];
            if (isset($files['image']) && $files['image']['error'] === 0) {
                $newImagePath = $this->uploadFile($files['image'], 'products', ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
                if ($newImagePath) {
                    // Delete old image
                    if ($imagePath && file_exists('../' . $imagePath)) {
                        unlink('../' . $imagePath);
                    }
                    $imagePath = $newImagePath;
                }
            }
            
            // Handle new video upload
            $videoPath = $product['video_path'];
            if (isset($files['video']) && $files['video']['error'] === 0) {
                $newVideoPath = $this->uploadFile($files['video'], 'products', ['mp4', 'webm', 'avi'], 10 * 1024 * 1024);
                if ($newVideoPath) {
                    // Delete old video
                    if ($videoPath && file_exists('../' . $videoPath)) {
                        unlink('../' . $videoPath);
                    }
                    $videoPath = $newVideoPath;
                }
            }
            
            // Update product
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET name = ?, description = ?, category = ?, material = ?, size = ?, price = ?, quantity = ?, 
                    image_path = ?, video_path = ?, status = 'pending'
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['category'],
                $data['material'],
                $data['size'],
                $data['price'],
                $data['quantity'],
                $imagePath,
                $videoPath,
                $productId
            ]);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Product updated successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }
    
    public function deleteProduct($productId) {
        try {
            // Verify ownership
            $stmt = $this->pdo->prepare("
                SELECT p.*, a.user_id 
                FROM products p 
                JOIN artisans a ON p.artisan_id = a.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product || $product['user_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'Product not found or access denied'];
            }
            
            $this->pdo->beginTransaction();
            
            // Delete files
            if ($product['image_path'] && file_exists('../' . $product['image_path'])) {
                unlink('../' . $product['image_path']);
            }
            if ($product['video_path'] && file_exists('../' . $product['video_path'])) {
                unlink('../' . $product['video_path']);
            }
            
            // Delete product
            $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Product deleted successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()];
        }
    }
    
    public function updateStock($productId, $quantity) {
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
            $stmt->execute([$quantity, $productId, $quantity]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Insufficient stock'];
            }
            
            return ['success' => true, 'message' => 'Stock updated'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Stock update failed: ' . $e->getMessage()];
        }
    }
    
    private function uploadFile($file, $folder, $allowedTypes, $maxSize) {
        $uploadDir = "../assets/uploads/$folder/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds limit');
        }
        
        // Validate file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return "assets/uploads/$folder/$filename";
        }
        
        return false;
    }
    
    private function notifyManager($title, $message) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'manager' LIMIT 1");
        $stmt->execute();
        $manager = $stmt->fetch();
        
        if ($manager) {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'product')");
            $stmt->execute([$manager['id'], $title, $message]);
        }
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $controller = new ProductController($pdo);
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload_product':
            if ($_SESSION['role'] !== 'artisan') {
                echo json_encode(['success' => false, 'message' => 'Only artisans can upload products']);
                break;
            }
            $result = $controller->uploadProduct($_POST, $_FILES);
            break;
            
        case 'update_product':
            if ($_SESSION['role'] !== 'artisan') {
                echo json_encode(['success' => false, 'message' => 'Only artisans can update products']);
                break;
            }
            $result = $controller->updateProduct($_POST['product_id'], $_POST, $_FILES);
            break;
            
        case 'delete_product':
            if ($_SESSION['role'] !== 'artisan') {
                echo json_encode(['success' => false, 'message' => 'Only artisans can delete products']);
                break;
            }
            $result = $controller->deleteProduct($_POST['product_id']);
            break;
            
        case 'update_stock':
            $result = $controller->updateStock($_POST['product_id'], $_POST['quantity']);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>