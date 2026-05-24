<?php
// PreRegistrationController.php - COMPLETE WORKING VERSION
ob_start();
require_once __DIR__ . '/../config/database_enhanced.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/pre_registration_errors.log');

class PreRegistrationController {
    private $pdo;
    
    // SMS Configuration
    private $textbee_device_id = '694481d8fb73763bb262451f';
    private $textbee_api_key = '105efcf3-2696-417a-add4-e3b60b4360a2';
    
    // Email Configuration - YOUR REAL CREDENTIALS
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_email = 'ethiopianartinfinite@gmail.com';
    private $smtp_password = 'stivcprjwpjhjoxj';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        error_log("PreRegistrationController action: $action");
        
        switch($action) {
            case 'send_pre_registration_otp':
                $this->sendPreRegistrationOTP();
                break;
            case 'verify_pre_registration_otp':
                $this->verifyPreRegistrationOTP();
                break;
            default:
                $this->jsonResponse(false, 'Invalid action');
        }
    }
    
    private function jsonResponse($success, $message, $data = []) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success, 
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
    
    private function sendPreRegistrationOTP() {
        try {
            $name = trim($_POST['name'] ?? '');
            $father_name = trim($_POST['father_name'] ?? '');
            $grandfather_name = trim($_POST['grandfather_name'] ?? '');
            $contact_type = $_POST['contact_type'] ?? '';
            $contact_value = trim($_POST['contact_value'] ?? '');
            
            error_log("Pre-registration OTP request: $name, $contact_type: $contact_value");
            
            // Validate inputs
            if (empty($name) || empty($father_name) || empty($grandfather_name) || empty($contact_value)) {
                $this->jsonResponse(false, 'All fields are required');
                return;
            }
            
            // Validate contact type
            if ($contact_type === 'phone') {
                if (!$this->validateEthiopianPhone($contact_value)) {
                    $this->jsonResponse(false, 'Please enter a valid Ethiopian phone number');
                    return;
                }
            } elseif ($contact_type === 'email') {
                if (!filter_var($contact_value, FILTER_VALIDATE_EMAIL)) {
                    $this->jsonResponse(false, 'Please enter a valid email address');
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
                $this->jsonResponse(false, 'Too many attempts. Please try again after 60 minutes');
                return;
            }
            
            // Generate OTP
            $otp = sprintf('%06d', random_int(100000, 999999));
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes
            
            error_log("Generated OTP: $otp for $contact_value");
            
            // Save to database
            $stmt = $this->pdo->prepare("
                INSERT INTO pre_registration_otps 
                (name, father_name, grandfather_name, contact_type, contact_value, otp_hash, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                otp_hash = VALUES(otp_hash), 
                expires_at = VALUES(expires_at),
                attempts = 0,
                is_verified = 0,
                blocked_until = NULL
            ");
            
            $stmt->execute([$name, $father_name, $grandfather_name, $contact_type, $contact_value, $otp_hash, $expires_at]);
            
            // Send OTP
            $sent = false;
            $error_message = '';
            
            if ($contact_type === 'phone') {
                $normalized_phone = $this->normalizePhone($contact_value);
                error_log("Attempting SMS to normalized phone: $normalized_phone");
                
                $sent = $this->sendSMS($normalized_phone, $otp);
                
                if (!$sent) {
                    error_log("SMS failed for $normalized_phone");
                    $error_message = 'SMS service temporarily unavailable. Please try email instead.';
                } else {
                    error_log("SMS sent successfully to $normalized_phone");
                }
            } else {
                $sent = $this->sendEmail($contact_value, $otp, $name);
                
                if (!$sent) {
                    error_log("Email failed for $contact_value");
                    $error_message = 'Failed to send email. Please check your email address.';
                } else {
                    error_log("Email sent successfully to $contact_value");
                }
            }
            
            if ($sent) {
                $this->jsonResponse(true, 'Verification code sent successfully');
            } else {
                // For SMS failures, suggest email as alternative
                if ($contact_type === 'phone') {
                    $this->jsonResponse(false, 'SMS temporarily unavailable. Please use email verification.');
                } else {
                    $this->jsonResponse(false, 'Failed to send email. Please try again.');
                }
            }
            
        } catch (Exception $e) {
            error_log("Error in sendPreRegistrationOTP: " . $e->getMessage());
            $this->jsonResponse(false, 'System error. Please try again');
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
            $mail->Port = 465;
            
            // Debug (set to 0 after it works)
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug [$level]: $str");
            };
            
            // Timeout
            $mail->Timeout = 30;
            
            // Recipients
            $mail->setFrom($this->smtp_email, 'Ashreka Pottery System');
            $mail->addAddress($email, $name);
            
            // HTML Content
            $htmlMessage = $this->getEmailTemplate($name, $otp);
            
            $mail->isHTML(true);
            $mail->Subject = 'Pre-Registration Verification - Ashreka Pottery';
            $mail->Body = $htmlMessage;
            $mail->AltBody = "Dear $name,\n\nYour pre-registration verification code is: $otp\n\nThis code will expire in 10 minutes.\n\nThank you!\nAshreka Pottery System";
            
            // Send email
            $mail->send();
            error_log("✅ Pre-registration email sent to: $email");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Pre-registration email failed for $email: " . $e->getMessage());
            if (isset($mail)) {
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
            return false;
        }
    }
    
    private function getEmailTemplate($name, $otp) {
        $logoUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/ashreka-pottery-system/assets/images/ashru.jpeg';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; padding: 30px; text-align: center; }
                .logo { width: 80px; height: 80px; border-radius: 50%; margin-bottom: 15px; object-fit: cover; }
                .content { padding: 30px; }
                .otp-box { background: #f8f9fa; padding: 25px; text-align: center; font-size: 28px; font-weight: bold; color: #8B4513; border-radius: 8px; margin: 25px 0; border: 2px dashed #8B4513; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .amharic { font-size: 18px; color: #D2691E; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='$logoUrl' alt='Ashreka Logo' class='logo'>
                    <h1>Ashreka & Friends Pottery Association</h1>
                    <div class='amharic'>አሽረቃ እና ጓደኞቿ የሸክላ ሥራ ማሕበር</div>
                </div>
                <div class='content'>
                    <h2 style='color: #8B4513;'>Dear $name,</h2>
                    <p>Thank you for starting your registration with Ashreka & Friends Pottery Association!</p>
                    <p>Your pre-registration verification code is:</p>
                    <div class='otp-box'>$otp</div>
                    <p><strong>This code will expire in 10 minutes.</strong></p>
                    <p>Please enter this code to continue with your registration.</p>
                    <p>If you didn't request this code, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>Ashreka & Friends Pottery Association</p>
                    <p>Sebeta Mazoria, Ethiopia</p>
                    <p>© " . date('Y') . " All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function verifyPreRegistrationOTP() {
        try {
            $contact_value = trim($_POST['contact_value'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            
            error_log("Verify pre-registration OTP: contact=$contact_value");
            
            if (empty($contact_value) || empty($otp)) {
                $this->jsonResponse(false, 'Contact and OTP are required');
                return;
            }
            
            // Get verification record
            $stmt = $this->pdo->prepare("
                SELECT * FROM pre_registration_otps 
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
                $this->jsonResponse(false, 'Too many attempts. Please try again after 60 minutes');
                return;
            }
            
            // Verify OTP
            if (password_verify($otp, $record['otp_hash'])) {
                // Mark as verified
                $stmt = $this->pdo->prepare("UPDATE pre_registration_otps SET is_verified = 1 WHERE id = ?");
                $stmt->execute([$record['id']]);
                
                $this->jsonResponse(true, 'Verification successful', [
                    'name' => $record['name'],
                    'father_name' => $record['father_name'],
                    'grandfather_name' => $record['grandfather_name'],
                    'contact_type' => $record['contact_type'],
                    'contact_value' => $record['contact_value']
                ]);
            } else {
                // Increment attempts
                $stmt = $this->pdo->prepare("UPDATE pre_registration_otps SET attempts = attempts + 1 WHERE id = ?");
                $stmt->execute([$record['id']]);
                
                $remaining = 5 - ($record['attempts'] + 1);
                if ($remaining <= 0) {
                    $this->blockContact($contact_value);
                    $this->jsonResponse(false, 'Too many attempts. Please try again after 60 minutes');
                } else {
                    $this->jsonResponse(false, "Invalid code. $remaining attempts remaining");
                }
            }
        } catch (Exception $e) {
            error_log("Error in verifyPreRegistrationOTP: " . $e->getMessage());
            $this->jsonResponse(false, 'System error. Please try again');
        }
    }
    
    private function validateEthiopianPhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        // Accept: +251912345678, 0912345678, 912345678
        return preg_match('/^(\+2519[0-9]{8}|09[0-9]{8}|9[0-9]{8})$/', $phone);
    }
    
    private function normalizePhone($phone) {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Convert 0912345678 to +251912345678
        if (preg_match('/^09[0-9]{8}$/', $phone)) {
            return '+251' . substr($phone, 1);
        }
        
        // Convert 912345678 to +251912345678
        if (preg_match('/^9[0-9]{8}$/', $phone)) {
            return '+251' . $phone;
        }
        
        // Already in +251912345678 format
        if (preg_match('/^\+2519[0-9]{8}$/', $phone)) {
            return $phone;
        }
        
        error_log("Invalid phone format: $phone");
        return $phone;
    }
    
    private function sendSMS($phone, $otp) {
        try {
            $phone = $this->normalizePhone($phone);
            
            // Bilingual message - English and Amharic
            $english_message = "Your Ashreka Pottery verification code is: $otp. Valid for 10 minutes. Do not share this code.";
            $amharic_message = "የአስረቃ የተክላ ስራ የመረጋገጫ ኮድዎን: $otp ። ለ 10 ደቂቃ ይጠናል ። ይህንን ኮድ አያቓራኩ።";
            $message = $english_message . "\n" . $amharic_message;
            
            error_log("Sending bilingual SMS to: $phone with message: $message");
            
            $data = [
                'message' => $message,
                'recipients' => [$phone]
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.textbee.dev/api/v1/gateway/devices/' . $this->textbee_device_id . '/sendSMS',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $this->textbee_api_key,
                    'Accept: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Ashreka-Pottery-System/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            curl_close($ch);
            
            error_log("SMS API Response - HTTP: $httpCode, Response: $response");
            
            if ($curlError) {
                error_log("SMS cURL Error: $curlError");
                return false;
            }
            
            // Check for specific API errors that should return false
            if ($httpCode == 429 || $httpCode == 400) {
                $responseData = json_decode($response, true);
                if ($responseData && (
                    (isset($responseData['message']) && strpos($responseData['message'], 'Daily SMS limit') !== false) ||
                    (isset($responseData['error']) && strpos($responseData['error'], 'Device does not exist') !== false) ||
                    (isset($responseData['success']) && $responseData['success'] === false)
                )) {
                    error_log("❌ SMS API Error: $response");
                    return false;
                }
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("✅ Bilingual SMS sent successfully to $phone");
                return true;
            } else {
                error_log("❌ SMS failed - HTTP $httpCode: $response");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SMS Exception: " . $e->getMessage());
            return false;
        }
    }
    
    private function isAlreadyRegistered($contact_value) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$contact_value, $contact_value]);
        return $stmt->fetch() !== false;
    }
    
    private function isBlocked($contact_value) {
        $stmt = $this->pdo->prepare("
            SELECT blocked_until FROM pre_registration_otps 
            WHERE contact_value = ? AND blocked_until > NOW()
        ");
        $stmt->execute([$contact_value]);
        return $stmt->fetch() !== false;
    }
    
    private function blockContact($contact_value) {
        $blocked_until = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $this->pdo->prepare("
            UPDATE pre_registration_otps 
            SET blocked_until = ? 
            WHERE contact_value = ?
        ");
        $stmt->execute([$blocked_until, $contact_value]);
    }
}

// Handle request with logging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== PreRegistrationController Accessed ===");
    error_log("POST Data: " . json_encode($_POST));
    
    try {
        $controller = new PreRegistrationController($pdo);
        $controller->handleRequest();
    } catch (Exception $e) {
        error_log("Fatal Error in PreRegistrationController: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'System error. Please try again.']);
        exit;
    }
} else {
    error_log("Invalid GET request to PreRegistrationController");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>