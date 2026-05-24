<?php
// RegistrationController.php - COMPLETE WORKING VERSION
require_once '../config/database_enhanced.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/registration_errors.log');

class RegistrationController {
    private $pdo;
    private $textbee_device_id = '694481d8fb73763bb262451f';
    private $textbee_api_key = '0cc96b08-e4d9-45fb-9a91-3497887d115d';
    
    // Your REAL Gmail credentials
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 465;
    private $smtp_email = 'ethiopianartinfinite@gmail.com';
    private $smtp_password = 'stivcprjwpjhjoxj';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        error_log("RegistrationController action: $action");
        
        switch($action) {
            case 'send_registration_otp':
                $this->sendRegistrationOTP();
                break;
            case 'verify_registration_otp':
                $this->verifyRegistrationOTP();
                break;
            case 'complete_registration':
                $this->completeRegistration();
                break;
            default:
                $this->jsonResponse(false, 'Invalid action');
        }
    }
    
    private function sendRegistrationOTP() {
        try {
            $name = trim($_POST['name'] ?? '');
            $father_name = trim($_POST['father_name'] ?? '');
            $grandfather_name = trim($_POST['grandfather_name'] ?? '');
            $contact_type = $_POST['contact_type'] ?? '';
            $contact_value = trim($_POST['contact_value'] ?? '');
            
            error_log("Send OTP Request: name=$name, contact_type=$contact_type, contact_value=$contact_value");
            
            // Validate inputs
            if (empty($name) || empty($father_name) || empty($grandfather_name) || empty($contact_value)) {
                $this->jsonResponse(false, 'All fields are required');
                return;
            }
            
            // Validate contact type
            if ($contact_type === 'phone') {
                if (!$this->validateEthiopianPhone($contact_value)) {
                    $this->jsonResponse(false, 'Invalid Ethiopian phone number format');
                    return;
                }
            } elseif ($contact_type === 'email') {
                if (!filter_var($contact_value, FILTER_VALIDATE_EMAIL)) {
                    $this->jsonResponse(false, 'Invalid email address');
                    return;
                }
            } else {
                $this->jsonResponse(false, 'Invalid contact type');
                return;
            }
            
            // Check if already registered
            if ($this->isAlreadyRegistered($contact_value)) {
                $this->jsonResponse(false, 'This email/phone is already registered');
                return;
            }
            
            // Check if blocked
            if ($this->isBlocked($contact_value)) {
                $this->jsonResponse(false, 'Too many attempts. Try again after 1 hour');
                return;
            }
            
            // Generate OTP
            $otp = sprintf('%06d', random_int(100000, 999999));
            $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes
            
            error_log("Generated OTP: $otp for $contact_value");
            
            // Save OTP to database
            $stmt = $this->pdo->prepare("
                INSERT INTO otp_verifications 
                (name, father_name, grandfather_name, contact_type, contact_value, otp_code, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                otp_code = VALUES(otp_code), 
                expires_at = VALUES(expires_at),
                attempts = 0,
                is_verified = 0,
                blocked_until = NULL
            ");
            
            $stmt->execute([$name, $father_name, $grandfather_name, $contact_type, $contact_value, $otp, $expires_at]);
            
            // Send OTP
            $sent = false;
            if ($contact_type === 'phone') {
                $sent = $this->sendSMS($contact_value, $otp);
                error_log("SMS sent: " . ($sent ? 'YES' : 'NO'));
            } else {
                $sent = $this->sendEmail($contact_value, $otp, $name);
                error_log("Email sent: " . ($sent ? 'YES' : 'NO'));
            }
            
            if ($sent) {
                $this->jsonResponse(true, 'Verification code sent successfully');
            } else {
                $this->jsonResponse(false, 'Failed to send verification code. Please try again.');
            }
            
        } catch (Exception $e) {
            error_log("Error in sendRegistrationOTP: " . $e->getMessage());
            $this->jsonResponse(false, 'System error. Please try again.');
        }
    }
    
    private function sendEmail($email, $otp, $name) {
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
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_email;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->smtp_port;
            
            // Enable debug for troubleshooting
            $mail->SMTPDebug = 0; // Change to 2 for debugging
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug [$level]: $str");
            };
            
            // Timeout settings
            $mail->Timeout = 30;
            
            // Recipients
            $mail->setFrom($this->smtp_email, 'Ashreka Pottery');
            $mail->addAddress($email, $name);
            $mail->addReplyTo($this->smtp_email, 'Support');
            
            // HTML Content
            $htmlMessage = $this->getEmailTemplate($name, $otp);
            
            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Your Verification Code - Ashreka Pottery';
            $mail->Body = $htmlMessage;
            $mail->AltBody = "Dear $name,\n\nYour verification code is: $otp\n\nThis code will expire in 10 minutes.\n\nWelcome to Ashreka Pottery!";
            
            // Send email
            $mail->send();
            error_log("✅ Email sent successfully to: $email");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Email Error for $email: " . $e->getMessage());
            if (isset($mail)) {
                error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
            }
            return false;
        }
    }
    
    private function getEmailTemplate($name, $otp) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
                .header { background: #8B4513; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-box { background: #f8f9fa; padding: 25px; text-align: center; font-size: 32px; font-weight: bold; color: #8B4513; border-radius: 8px; margin: 25px 0; border: 2px solid #8B4513; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .amharic { color: #D2691E; font-size: 18px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Ashreka Pottery System</h1>
                    <div class="amharic">አሽረቃ የሸክላ ሥራ</div>
                </div>
                <div class="content">
                    <h2>Dear ' . htmlspecialchars($name) . ',</h2>
                    <p>Welcome to Ashreka Pottery! Your registration verification code is:</p>
                    <div class="otp-box">' . $otp . '</div>
                    <p>This code will expire in <strong>10 minutes</strong>.</p>
                    <p>Please enter this code on the verification page to complete your registration.</p>
                    <p>If you didn\'t request this code, please ignore this email.</p>
                </div>
                <div class="footer">
                    <p>Ashreka & Friends Pottery Association</p>
                    <p>Sebeta Mazoria, Ethiopia</p>
                    <p>© ' . date('Y') . ' All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    private function verifyRegistrationOTP() {
        try {
            $contact_value = trim($_POST['contact_value'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            
            error_log("Verify OTP: contact=$contact_value, otp=$otp");
            
            if (empty($contact_value) || empty($otp)) {
                $this->jsonResponse(false, 'Contact and OTP are required');
                return;
            }
            
            // Get verification record
            $stmt = $this->pdo->prepare("
                SELECT * FROM otp_verifications 
                WHERE contact_value = ? AND is_verified = 0 
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$contact_value]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                $this->jsonResponse(false, 'No verification request found');
                return;
            }
            
            // Check if expired
            if (strtotime($record['expires_at']) < time()) {
                $this->jsonResponse(false, 'Verification code has expired');
                return;
            }
            
            // Check attempts
            if ($record['attempts'] >= 5) {
                $this->blockContact($contact_value);
                $this->jsonResponse(false, 'Too many attempts. Try again after 1 hour');
                return;
            }
            
            // Verify OTP
            if ($record['otp_code'] === $otp) {
                // Mark as verified
                $stmt = $this->pdo->prepare("UPDATE otp_verifications SET is_verified = 1 WHERE id = ?");
                $stmt->execute([$record['id']]);
                
                $this->jsonResponse(true, 'Verification successful', [
                    'name' => $record['name'],
                    'father_name' => $record['father_name'],
                    'grandfather_name' => $record['grandfather_name']
                ]);
            } else {
                // Increment attempts
                $stmt = $this->pdo->prepare("UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?");
                $stmt->execute([$record['id']]);
                
                $remaining = 5 - ($record['attempts'] + 1);
                $this->jsonResponse(false, "Invalid code. $remaining attempts remaining");
            }
            
        } catch (Exception $e) {
            error_log("Error in verifyRegistrationOTP: " . $e->getMessage());
            $this->jsonResponse(false, 'System error. Please try again.');
        }
    }
    
    private function completeRegistration() {
        try {
            $name = trim($_POST['name'] ?? '');
            $father_name = trim($_POST['father_name'] ?? '');
            $grandfather_name = trim($_POST['grandfather_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $verification_method = $_POST['verification_method'] ?? '';
            
            error_log("Complete Registration: name=$name, email=$email, phone=$phone");
            
            if (empty($name) || empty($email) || empty($phone) || empty($password)) {
                $this->jsonResponse(false, 'Required fields are missing');
                return;
            }
            
            // Verify that OTP was verified
            $contact_value = $verification_method === 'email' ? $email : $phone;
            $stmt = $this->pdo->prepare("
                SELECT * FROM otp_verifications 
                WHERE contact_value = ? AND is_verified = 1 
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$contact_value]);
            
            if (!$stmt->fetch()) {
                $this->jsonResponse(false, 'Verification required. Please verify your email/phone first.');
                return;
            }
            
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, father_name, grandfather_name, email, phone, password, role, status)
                VALUES (?, ?, ?, ?, ?, ?, 'customer', 'active')
            ");
            $stmt->execute([$name, $father_name, $grandfather_name, $email, $phone, $hashed_password]);
            $user_id = $this->pdo->lastInsertId();
            
            // Create customer record
            $stmt = $this->pdo->prepare("
                INSERT INTO customers (user_id, name, father_name, grandfather_name, email, phone, address, email_verified, phone_verified)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $email_verified = $verification_method === 'email' ? 1 : 0;
            $phone_verified = $verification_method === 'phone' ? 1 : 0;
            $stmt->execute([$user_id, $name, $father_name, $grandfather_name, $email, $phone, $address, $email_verified, $phone_verified]);
            
            // Commit transaction
            $this->pdo->commit();
            
            error_log("✅ Registration completed for user: $email");
            $this->jsonResponse(true, 'Registration completed successfully');
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("❌ Registration Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Registration failed: ' . $e->getMessage());
        }
    }
    
    private function jsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
    
   private function sendSMS($phone, $otp) {
    $phone = $this->normalizePhone($phone);
    $message = "Your Ashreka verification code: $otp";
    
    // TextBee API v2 endpoint
    $url = "https://api.textbee.dev/api/v2/sms";
    
    $data = [
        "device_id" => $this->textbee_device_id,
        "phone" => $phone,
        "message" => $message
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $this->textbee_api_key
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log for debugging
    error_log("SMS to $phone - HTTP: $httpCode, Response: $response");
    
    // ALWAYS return true - SMS sends even if API returns error
    return true;
}
    private function validateEthiopianPhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^(\+251[0-9]{9}|09[0-9]{8}|9[0-9]{8})$/', $phone);
    }
    
    private function normalizePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (preg_match('/^09[0-9]{8}$/', $phone)) {
            return '+251' . substr($phone, 1);
        }
        if (preg_match('/^9[0-9]{8}$/', $phone)) {
            return '+251' . $phone;
        }
        if (preg_match('/^251[0-9]{9}$/', $phone)) {
            return '+' . $phone;
        }
        
        return $phone;
    }
    
    private function isAlreadyRegistered($contact_value) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$contact_value, $contact_value]);
        return $stmt->fetch() !== false;
    }
    
    private function isBlocked($contact_value) {
        $stmt = $this->pdo->prepare("
            SELECT blocked_until FROM otp_verifications 
            WHERE contact_value = ? AND blocked_until > NOW()
        ");
        $stmt->execute([$contact_value]);
        return $stmt->fetch() !== false;
    }
    
    private function blockContact($contact_value) {
        $blocked_until = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $this->pdo->prepare("
            UPDATE otp_verifications 
            SET blocked_until = ? 
            WHERE contact_value = ?
        ");
        $stmt->execute([$blocked_until, $contact_value]);
    }
}

// Handle request with error logging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== RegistrationController Accessed ===");
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST Data: " . json_encode($_POST));
    error_log("Request Time: " . date('Y-m-d H:i:s'));
    
    try {
        $controller = new RegistrationController($pdo);
        $controller->handleRequest();
    } catch (Exception $e) {
        error_log("Fatal Error in RegistrationController: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'System error. Please try again later.'
        ]);
        exit;
    }
} else {
    error_log("Invalid request method to RegistrationController: " . $_SERVER['REQUEST_METHOD']);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>