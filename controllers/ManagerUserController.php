<?php
session_start();
require_once '../config/database_enhanced.php';

class ManagerUserController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all users (except admin)
     */
    public function getAllUsers($search = '', $role = '') {
        try {
            $where = "role != 'admin'";
            $params = [];
            
            if ($search) {
                $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $searchParam = "%$search%";
                $params = [$searchParam, $searchParam, $searchParam];
            }
            
            if ($role) {
                $where .= " AND role = ?";
                $params[] = $role;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT u.*, 
                       CASE 
                           WHEN u.role = 'customer' THEN c.name
                           WHEN u.role = 'artisan' THEN a.name
                           ELSE u.name
                       END as display_name
                FROM users u
                LEFT JOIN customers c ON u.id = c.user_id
                LEFT JOIN artisans a ON u.id = a.user_id
                WHERE $where
                ORDER BY u.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, 
                       CASE 
                           WHEN u.role = 'customer' THEN c.name
                           WHEN u.role = 'artisan' THEN a.name
                           ELSE u.name
                       END as display_name
                FROM users u
                LEFT JOIN customers c ON u.id = c.user_id
                LEFT JOIN artisans a ON u.id = a.user_id
                WHERE u.id = ? AND u.role != 'admin'
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        try {
            // Check if email/phone exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$data['email'], $data['phone'] ?? '']);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email or phone already exists'];
            }
            
            $this->pdo->beginTransaction();
            
            // Create user
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, phone, password, role, name, status) 
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $data['email'],
                $data['phone'] ?? null,
                $hashedPassword,
                $data['role'],
                $data['name']
            ]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Create role-specific record
            if ($data['role'] === 'customer') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO customers (user_id, name, email_verified) 
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([$userId, $data['name']]);
            } elseif ($data['role'] === 'artisan') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO artisans (user_id, name, skill_type, approval_status) 
                    VALUES (?, ?, ?, 'approved')
                ");
                $stmt->execute([$userId, $data['name'], $data['skill_type'] ?? 'pottery']);
            }
            
            $this->logActivity('user_create', "Created {$data['role']} user: {$data['email']}", $userId);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $userId];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($userId, $data) {
        try {
            // Check if user exists and is not admin
            $user = $this->getUserById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $this->pdo->beginTransaction();
            
            // Update user
            $updateFields = [];
            $params = [];
            
            if (isset($data['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $data['name'];
            }
            
            if (isset($data['email'])) {
                // Check if email already exists for another user
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
                $updateFields[] = "email = ?";
                $params[] = $data['email'];
            }
            
            if (isset($data['phone'])) {
                // Check if phone already exists for another user
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
                $stmt->execute([$data['phone'], $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Phone already exists');
                }
                $updateFields[] = "phone = ?";
                $params[] = $data['phone'];
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (!empty($updateFields)) {
                $params[] = $userId;
                $stmt = $this->pdo->prepare("
                    UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?
                ");
                $stmt->execute($params);
            }
            
            // Update role-specific record
            if ($user['role'] === 'customer' && isset($data['name'])) {
                $stmt = $this->pdo->prepare("UPDATE customers SET name = ? WHERE user_id = ?");
                $stmt->execute([$data['name'], $userId]);
            } elseif ($user['role'] === 'artisan' && isset($data['name'])) {
                $stmt = $this->pdo->prepare("UPDATE artisans SET name = ? WHERE user_id = ?");
                $stmt->execute([$data['name'], $userId]);
            }
            
            $this->logActivity('user_update', "Updated user: {$user['email']}", $userId);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'User updated successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete user (soft delete by setting status)
     */
    public function deleteUser($userId) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Don't allow deleting active users with orders
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM orders o
                JOIN customers c ON o.customer_id = c.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            $orderCount = $stmt->fetchColumn();
            
            if ($orderCount > 0) {
                // Soft delete - set status to rejected
                $stmt = $this->pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$userId]);
            } else {
                // Hard delete if no orders
                $this->pdo->beginTransaction();
                $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $this->pdo->commit();
            }
            
            $this->logActivity('user_delete', "Deleted user: {$user['email']}", $userId);
            
            return ['success' => true, 'message' => 'User deleted successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Log activity
     */
    private function logActivity($type, $description, $referenceId = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO system_activities (type, description, user_id, reference_id, reference_table) 
            VALUES (?, ?, ?, ?, 'users')
        ");
        $stmt->execute([$type, $description, $_SESSION['user_id'], $referenceId]);
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $controller = new ManagerUserController($pdo);
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $result = $controller->createUser($_POST);
            echo json_encode($result);
            break;
            
        case 'update':
            $userId = $_POST['user_id'] ?? 0;
            unset($_POST['action'], $_POST['user_id']);
            $result = $controller->updateUser($userId, $_POST);
            echo json_encode($result);
            break;
            
        case 'delete':
            $userId = $_POST['user_id'] ?? 0;
            $result = $controller->deleteUser($userId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>

