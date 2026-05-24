<?php
session_start();
require_once '../config/database_enhanced.php';
require_once '../includes/functions.php';

class AuthController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function login($email_phone, $password, $remember = false) {
        try {
            // Check if input is email or phone
            $field = filter_var($email_phone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
            
            // First check if user exists (any status)
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE $field = ?");
            $stmt->execute([$email_phone]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check user status
                if ($user['status'] === 'pending') {
                    $_SESSION['pending_user_id'] = $user['id'];
                    $userType = $user['role'] === 'artisan' ? 'artisan' : 'customer';
                    return ['success' => false, 'message' => 'Your registration is pending manager approval. Please wait for approval.'];
                }
                
                if ($user['status'] === 'rejected') {
                    $reason = $user['rejection_reason'] ?? 'No specific reason provided';
                    return ['success' => false, 'message' => "Your registration has been rejected. Reason: $reason. Please contact support: <a href='tel:0983795429' style='color: #007bff; text-decoration: none;'><i class='fas fa-phone'></i> 0983795429</a>"];
                }
                
                if ($user['status'] === 'active') {
                    // For artisans, check approval status
                    if ($user['role'] === 'artisan') {
                        $stmt = $this->pdo->prepare("SELECT approval_status FROM artisans WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        $artisan = $stmt->fetch();
                        
                        if (!$artisan || $artisan['approval_status'] === 'pending') {
                            return ['success' => false, 'message' => 'Your artisan registration is pending manager approval. Please wait for approval.'];
                        }
                        
                        if ($artisan['approval_status'] === 'rejected') {
                            return ['success' => false, 'message' => 'Your artisan registration has been rejected. Please contact support: <a href="tel:0983795429" style="color: #007bff; text-decoration: none;"><i class="fas fa-phone"></i> 0983795429</a>'];
                        }
                    }
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Set remember me cookie
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                    }
                    
                    // Check for session resumption after timeout
                    $redirectUrl = null;
                    if (isset($_COOKIE['session_backup'])) {
                        $sessionData = json_decode($_COOKIE['session_backup'], true);
                        if ($sessionData && $sessionData['user_id'] == $user['id']) {
                            // Restore additional session data
                            if (isset($sessionData['customer_id'])) {
                                $_SESSION['customer_id'] = $sessionData['customer_id'];
                            }
                            // Get last page from localStorage (will be handled by JavaScript)
                        }
                        setcookie('session_backup', '', time() - 3600, '/'); // Clear backup
                    }
                    
                    return ['success' => true, 'role' => $user['role']];
                }
            }
            
            return ['success' => false, 'message' => 'Invalid email/phone or password'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    public function registerCustomer($data) {
        try {
            // Check if email/phone already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$data['email'], $data['phone']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email or phone already registered'];
            }
            
            $this->pdo->beginTransaction();
            
            // Create user account - customers need manager approval
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, father_name, grandfather_name, email, phone, password, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'customer', 'pending')
            ");
            $stmt->execute([$data['name'], $data['father_name'], $data['grandfather_name'], $data['email'], $data['phone'], $hashedPassword]);
            $userId = $this->pdo->lastInsertId();
            
            // Create customer profile - pending approval
            $stmt = $this->pdo->prepare("
                INSERT INTO customers (user_id, name, father_name, grandfather_name, address, email_verified) 
                VALUES (?, ?, ?, ?, ?, FALSE)
            ");
            $stmt->execute([$userId, $data['name'], $data['father_name'], $data['grandfather_name'], $data['address']]);
            
            // Notify manager about new customer registration
            $this->notifyManager('New customer registration', "New customer {$data['name']} has registered and needs approval.");
            
            $this->pdo->commit();
            
            $_SESSION['pending_user_id'] = $userId;
            
            return ['success' => true, 'message' => 'Registration successful. Please wait for manager approval.'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function registerArtisan($data) {
        try {
            // Check if email/phone already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$data['email'], $data['phone']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email or phone already registered'];
            }
            
            $this->pdo->beginTransaction();
            
            // Create user account
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, father_name, grandfather_name, email, phone, password, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'artisan', 'pending')
            ");
            $stmt->execute([$data['name'], $data['father_name'], $data['grandfather_name'], $data['email'], $data['phone'], $hashedPassword]);
            $userId = $this->pdo->lastInsertId();
            
            // Handle profile image upload
            $profileImage = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                $profileImage = $this->uploadProfileImage($_FILES['profile_image'], $userId);
            }
            
            // Create artisan profile
            $stmt = $this->pdo->prepare("
                INSERT INTO artisans (user_id, name, father_name, grandfather_name, skill_type, experience_years, description, profile_image, address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId, 
                $data['name'], 
                $data['father_name'], 
                $data['grandfather_name'], 
                $data['skill_type'], 
                $data['experience_years'], 
                $data['description'], 
                $profileImage,
                $data['address']
            ]);
            
            // Notify manager
            $this->notifyManager('New artisan registration', "New artisan {$data['name']} has registered and needs approval.");
            
            $this->pdo->commit();
            
            $_SESSION['pending_user_id'] = $userId;
            
            return ['success' => true, 'message' => 'Registration submitted successfully. Please wait for manager approval.'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function verifyEmail($code) {
        try {
            if (!isset($_SESSION['pending_user_id'])) {
                return ['success' => false, 'message' => 'No pending verification'];
            }
            
            $userId = $_SESSION['pending_user_id'];
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM customers 
                WHERE user_id = ? AND verification_code = ? AND verification_expires > NOW()
            ");
            $stmt->execute([$userId, $code]);
            $customer = $stmt->fetch();
            
            if ($customer) {
                $this->pdo->beginTransaction();
                
                // Update user status
                $stmt = $this->pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$userId]);
                
                // Update customer verification
                $stmt = $this->pdo->prepare("
                    UPDATE customers 
                    SET email_verified = TRUE, verification_code = NULL, verification_expires = NULL 
                    WHERE user_id = ?
                ");
                $stmt->execute([$userId]);
                
                $this->pdo->commit();
                
                unset($_SESSION['pending_user_id']);
                
                return ['success' => true, 'message' => 'Email verified successfully'];
            }
            
            return ['success' => false, 'message' => 'Invalid or expired verification code'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()];
        }
    }

    
    public function forgotPassword($contact) {
        try {
            // Ensure password_resets table exists
            $this->createPasswordResetsTable();
            
            // Clean and format contact
            $contact = trim($contact);
            
            // Check if input is email or phone
            $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);
            
            if ($isEmail) {
                // Search by email and check approval status
                $stmt = $this->pdo->prepare("
                    SELECT u.id, u.name, u.email, u.status, u.role,
                           CASE 
                               WHEN u.role = 'artisan' THEN a.approval_status
                               WHEN u.role = 'customer' THEN 'approved'
                               ELSE u.status
                           END as approval_status
                    FROM users u 
                    LEFT JOIN artisans a ON u.id = a.user_id AND u.role = 'artisan'
                    WHERE u.email = ?
                ");
                $stmt->execute([$contact]);
            } else {
                // Format phone number and search with approval status check
                $formattedPhone = $this->formatPhoneForSearch($contact);
                $stmt = $this->pdo->prepare("
                    SELECT u.id, u.name, u.email, u.phone, u.status, u.role,
                           CASE 
                               WHEN u.role = 'artisan' THEN a.approval_status
                               WHEN u.role = 'customer' THEN 'approved'
                               ELSE u.status
                           END as approval_status
                    FROM users u 
                    LEFT JOIN artisans a ON u.id = a.user_id AND u.role = 'artisan'
                    WHERE u.phone = ? OR u.phone = ? OR u.phone = ?
                ");
                $stmt->execute([$contact, $formattedPhone, '+251' . ltrim($formattedPhone, '+251')]);
            }
            
            $user = $stmt->fetch();
            
            if ($user) {
                // Check if user is approved
                if ($user['status'] !== 'active') {
                    return ['success' => false, 'message' => 'Password reset is only available for approved users. Please wait for manager approval.'];
                }
                
                $resetCode = sprintf('%06d', mt_rand(100000, 999999));
                $expiresAt = date('Y-m-d H:i:s', time() + 60); // 60 seconds
                
                // Store reset code in database with contact type
                $contactType = $isEmail ? 'email' : 'phone';
                $normalizedContact = $isEmail ? strtolower(trim($contact)) : $this->formatPhoneForSearch($contact);
                $stmt = $this->pdo->prepare("
                    INSERT INTO password_resets (user_id, contact, contact_type, reset_code, expires_at) 
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    contact = VALUES(contact),
                    contact_type = VALUES(contact_type),
                    reset_code = VALUES(reset_code), 
                    expires_at = VALUES(expires_at)
                ");
                $stmt->execute([$user['id'], $normalizedContact, $contactType, $resetCode, $expiresAt]);
                
                // Send reset code only to chosen method
                if ($isEmail) {
                    $this->sendPasswordResetEmail($user['email'], $resetCode, $user['name']);
                } else {
                    $this->sendPasswordResetSMS($normalizedContact, $resetCode);
                }
                
                return ['success' => true, 'message' => 'Reset code sent successfully'];
            }
            
            return ['success' => false, 'message' => 'Email/phone not found'];
            
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send reset code'];
        }
    }
    
    private function createPasswordResetsTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    contact VARCHAR(100) NOT NULL,
                    contact_type ENUM('email', 'phone') DEFAULT 'email',
                    reset_code VARCHAR(6) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_reset (user_id)
                )
            ";
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            error_log("Error creating password_resets table: " . $e->getMessage());
        }
    }
    
    private function formatPhoneForSearch($phone) {
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle different Ethiopian phone formats
        if (strlen($phone) == 10 && substr($phone, 0, 2) == '09') {
            return '+251' . substr($phone, 1); // 0911234567 -> +2519111234567
        } elseif (strlen($phone) == 9 && substr($phone, 0, 1) == '9') {
            return '+251' . $phone; // 911234567 -> +251911234567
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '251') {
            return '+' . $phone; // 251911234567 -> +251911234567
        } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '2519') {
            return '+' . $phone; // +251911234567
        }
        
        return '+251' . ltrim($phone, '0'); // Default format
    }
    
    public function verifyResetCode($contact, $code) {
        try {
            $code = trim($code);
            
            // Simply check if code exists and is not expired
            $stmt = $this->pdo->prepare("
                SELECT user_id, expires_at FROM password_resets 
                WHERE reset_code = ?
            ");
            $stmt->execute([$code]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Check expiration using PHP time
                if (strtotime($result['expires_at']) > time()) {
                    return ['success' => true, 'user_id' => $result['user_id']];
                } else {
                    return ['success' => false, 'message' => 'Reset code has expired'];
                }
            }
            
            return ['success' => false, 'message' => 'Invalid reset code'];
            
        } catch (Exception $e) {
            error_log("Verify reset code error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed'];
        }
    }
    
    public function resetPassword($contact, $code, $newPassword) {
        try {
            // Verify code first
            $verifyResult = $this->verifyResetCode($contact, $code);
            if (!$verifyResult['success']) {
                return $verifyResult;
            }
            
            $userId = $verifyResult['user_id'];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->pdo->beginTransaction();
            
            // Update password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Delete reset code
            $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Password reset successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Reset password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }
    
    private function uploadProfileImage($file, $userId) {
        $uploadDir = '../assets/uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'assets/uploads/profiles/' . $filename;
        }
        
        return null;
    }
    
    private function sendVerificationEmail($email, $code) {
        // Simple email sending (in production, use proper email service)
        $subject = "Verify Your Email - Ashreka Pottery";
        $message = "Your verification code is: $code\n\nThis code will expire in 1 hour.";
        $headers = "From: noreply@ashrekapottery.com";
        
        mail($email, $subject, $message, $headers);
    }
    
    private function sendPasswordResetEmail($email, $code, $name) {
        try {
            // Load PHPMailer
            $autoload_path = __DIR__ . '/../vendor/autoload.php';
            if (!file_exists($autoload_path)) {
                error_log("PHPMailer not found at: $autoload_path");
                return false;
            }
            
            require_once $autoload_path;
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ethiopianartinfinite@gmail.com';
            $mail->Password = 'stivcprjwpjhjoxj';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            // Recipients
            $mail->setFrom('ethiopianartinfinite@gmail.com', 'Ashreka Pottery');
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code - Ashreka Pottery';
            $mail->Body = "
                <h2>Password Reset Request</h2>
                <p>Dear $name,</p>
                <p>Your password reset code is: <strong style='font-size: 24px; color: #8B4513;'>$code</strong></p>
                <p>This code will expire in 2 minutes.</p>
                <p>If you didn't request this, please ignore this email.</p>
            ";
            
            $mail->send();
            error_log("Password reset email sent to: $email");
            return true;
            
        } catch (Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendPasswordResetSMS($phone, $code) {
        try {
            $message = "Your Ashreka Pottery password reset code is: $code. This code expires in 2 minutes.";
            
            $textbee_device_id = '694481d8fb73763bb262451f';
            $textbee_api_key = '105efcf3-2696-417a-add4-e3b60b4360a2';
            
            $data = [
                'message' => $message,
                'recipients' => [$phone]
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.textbee.dev/api/v1/gateway/devices/' . $textbee_device_id . '/sendSMS',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $textbee_api_key,
                    'Accept: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            error_log("Password reset SMS sent to: $phone");
            return ($httpCode >= 200 && $httpCode < 300);
            
        } catch (Exception $e) {
            error_log("Password reset SMS error: " . $e->getMessage());
            return true; // Return true to not block the process
        }
    }
    
    private function notifyManager($title, $message) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'manager' LIMIT 1");
        $stmt->execute();
        $manager = $stmt->fetch();
        
        if ($manager) {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, ?, ?, 'registration')
            ");
            $stmt->execute([$manager['id'], $title, $message]);
        }
    }
    
    public function logout() {
        $isTimeout = isset($_GET['timeout']) && $_GET['timeout'] == '1';
        
        if ($isTimeout) {
            // Store session data for resumption including last page from localStorage
            $sessionData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'role' => $_SESSION['role'] ?? null,
                'email' => $_SESSION['email'] ?? null,
                'customer_id' => $_SESSION['customer_id'] ?? null,
                'last_page' => $_SERVER['HTTP_REFERER'] ?? null
            ];
            setcookie('session_backup', json_encode($sessionData), time() + 3600, '/');
        }
        
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
        
        if ($isTimeout) {
            header("Location: ../views/auth/login.php?timeout=1");
        } else {
            header("Location: ../index.php");
        }
        exit();
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController($pdo);
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'login':
            $result = $auth->login($_POST['email_phone'], $_POST['password'], isset($_POST['remember']));
            if ($result['success']) {
                // Check if this is a timeout login and redirect to last page
                if (isset($_GET['timeout']) && isset($_COOKIE['session_backup'])) {
                    $sessionData = json_decode($_COOKIE['session_backup'], true);
                    if ($sessionData && isset($sessionData['last_page'])) {
                        header("Location: " . $sessionData['last_page']);
                        exit;
                    }
                }
                header("Location: ../views/{$result['role']}/dashboard.php");
            } else {
                if (isset($result['redirect'])) {
                    header("Location: {$result['redirect']}");
                } else {
                    header("Location: ../views/auth/login.php?error=" . urlencode($result['message']));
                }
            }
            break;
            
        case 'register_customer':
            $result = $auth->registerCustomer($_POST);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'register_artisan':
            $result = $auth->registerArtisan($_POST);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'verify_email':
            $result = $auth->verifyEmail($_POST['code']);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'forgot_password':
            $result = $auth->forgotPassword($_POST['contact']);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'verify_reset_code':
            $result = $auth->verifyResetCode($_POST['contact'], $_POST['code']);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'reset_password':
            $result = $auth->resetPassword($_POST['contact'], $_POST['code'], $_POST['password']);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'logout':
            $auth->logout();
            break;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $auth = new AuthController($pdo);
    
    if ($_GET['action'] === 'logout') {
        $auth->logout();
    } else if ($_GET['action'] === 'check_session') {
        header('Content-Type: application/json');
        echo json_encode([
            'logged_in' => isset($_SESSION['user_id']),
            'role' => $_SESSION['role'] ?? null
        ]);
    }
}
?>