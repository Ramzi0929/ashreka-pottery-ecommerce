<?php
// PaymentWorkflowController.php - Complete Payment System
ini_set('display_errors', 0);
error_reporting(0);
ob_start(); // Start output buffering to catch any unwanted output
session_start();
require_once __DIR__ . '/../config/database_enhanced.php';

class PaymentWorkflowController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        
        switch($action) {
            case 'send_payment_instructions':
                $this->sendPaymentInstructions();
                break;
            case 'submit_receipt':
                $this->submitReceipt();
                break;
            case 'verify_confirm_code':
                $this->verifyConfirmCode();
                break;
            default:
                $this->jsonResponse(false, 'Invalid action');
        }
    }
    
    private function sendPaymentInstructions() {
        try {
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $payment_method = $_POST['payment_method'] ?? '';
            $bank_name = $_POST['bank_name'] ?? '';
            $total_amount = $_POST['total_amount'] ?? 0;
            $order_id = $_POST['order_id'] ?? 0;
            
            // Check if we have either email or phone
            if (empty($email) && empty($phone)) {
                $this->jsonResponse(false, 'Email or phone number required');
                return;
            }
            
            // If email provided, validate it
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(false, 'Invalid email address');
                return;
            }
            
            // If phone provided, validate it
            if (!empty($phone) && !$this->validateEthiopianPhone($phone)) {
                $this->jsonResponse(false, 'Invalid phone number');
                return;
            }
            
            // Get payment details
            $payment_details = $this->getPaymentDetails($payment_method, $bank_name);
            
            // Generate confirmation code and store in database
            $confirm_code = sprintf('%06d', random_int(100000, 999999));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_confirmations (order_id, email, phone, confirm_code) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                confirm_code = VALUES(confirm_code),
                phone = VALUES(phone)
            ");
            $stmt->execute([$order_id, $email ?: 'noemail@temp.com', $phone, $confirm_code]);
            
            $email_sent = false;
            $sms_sent = false;
            
            // Send email if provided
            if (!empty($email)) {
                $subject = "Payment Instructions - Order #$order_id";
                $message = $this->getPaymentInstructionsTemplate($payment_method, $bank_name, $payment_details, $total_amount, $order_id, $confirm_code);
                $email_sent = $this->sendEmailWithPHPMailer($email, $subject, $message);
            }
            
            // Send SMS if provided
            if (!empty($phone)) {
                $sms_message = $this->getSMSMessage($payment_method, $bank_name, $payment_details, $total_amount, $order_id, $confirm_code);
                $sms_sent = $this->sendSMS($this->normalizePhone($phone), $sms_message);
            }
            
            // Determine response
            if ($email_sent || $sms_sent) {
                $response_message = 'Payment instructions sent';
                if ($email_sent && $sms_sent) {
                    $response_message .= ' to email and SMS';
                } elseif ($email_sent) {
                    $response_message .= ' to email';
                } elseif ($sms_sent) {
                    $response_message .= ' via SMS';
                }
                $this->jsonResponse(true, $response_message);
            } else {
                $this->jsonResponse(false, 'Failed to send payment instructions');
            }
            
        } catch (Exception $e) {
            error_log("Error in sendPaymentInstructions: " . $e->getMessage());
            $this->jsonResponse(false, 'Failed to send payment instructions');
        }
    }
    
    private function getPaymentDetails($payment_method, $bank_name) {
        if ($payment_method === 'telebirr') {
            return '0935714446';
        }
        
        $stmt = $this->pdo->prepare("SELECT sms_code FROM bank_sms_codes WHERE bank_name = ? AND is_active = 1");
        $stmt->execute([$bank_name]);
        $result = $stmt->fetchColumn();
        
        return $result ?: 'Contact bank for details';
    }
    
    private function verifyConfirmCode() {
        try {
            $order_id = $_POST['order_id'] ?? 0;
            $confirm_code = $_POST['confirm_code'] ?? '';
            
            if (!$order_id || !$confirm_code) {
                $this->jsonResponse(false, 'Missing required parameters');
                return;
            }
            
            // Check if confirmation code exists and is still pending
            $stmt = $this->pdo->prepare("
                SELECT pc.id, pc.status 
                FROM payment_confirmations pc 
                WHERE pc.order_id = ? AND pc.confirm_code = ? AND pc.status = 'pending'
            ");
            $stmt->execute([$order_id, $confirm_code]);
            $confirmation = $stmt->fetch();
            
            if ($confirmation) {
                $this->jsonResponse(true, 'Confirmation code verified');
            } else {
                $this->jsonResponse(false, 'Invalid or already used confirmation code');
            }
            
        } catch (Exception $e) {
            error_log("Error in verifyConfirmCode: " . $e->getMessage());
            $this->jsonResponse(false, 'Failed to verify confirmation code');
        }
    }
    
    private function jsonResponse($success, $message) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    
    private function getPaymentInstructionsTemplate($payment_method, $bank_name, $payment_details, $total_amount, $order_id, $confirm_code) {
        $formatted_amount = number_format($total_amount);
        $current_year = date('Y');
        
        if ($payment_method === 'telebirr') {
            $instructions = "Pay $formatted_amount ETB via TeleBirr to phone: $payment_details";
            $amharic_instructions = "$formatted_amount ብር በቴሌብር ወደ ስልክ ቁጥር $payment_details ይክፈሉ";
            $detail_label = "Phone Number";
            $steps_html = $this->getTeleBirrSteps($payment_details, $formatted_amount);
        } else {
            $instructions = "Pay $formatted_amount ETB via $bank_name to account: $payment_details";
            $amharic_instructions = "$formatted_amount ብር በ$bank_name ወደ የባንክ አካውንት $payment_details ይክፈሉ";
            $detail_label = "Account Number";
            $steps_html = $this->getBankSteps($bank_name, $payment_details, $formatted_amount);
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; padding: 30px; text-align: center; position: relative; }
                .logo { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 15px; }
                .content { padding: 30px; }
                .payment-box { background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #8B4513; }
                .code-box { font-size: 24px; font-weight: bold; color: #8B4513; background: #fff8dc; padding: 15px; border-radius: 8px; text-align: center; margin: 15px 0; border: 2px dashed #8B4513; }
                .amharic-section { background: #fff8dc; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #D2691E; }
                .steps-section { margin: 25px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='ashru.jpeg' alt='Ashreka Logo' class='logo'>
                    <h1>Payment Instructions</h1>
                    <p>Order #$order_id</p>
                </div>
                <div class='content'>
                    <h2>Payment Details</h2>
                    
                    <div class='payment-box'>
                        <h4 style='color: #8B4513;'>🇺🇸 English Instructions</h4>
                        <p>$instructions</p>
                        <div class='code-box'>$payment_details</div>
                        <p><strong>$detail_label: $payment_details</strong></p>
                        <p><strong>Amount: $formatted_amount ETB</strong></p>
                    </div>
                    
                    <div class='amharic-section'>
                        <h4 style='color: #D2691E;'>🇪🇹 የአማርኛ መመሪያዎች</h4>
                        <p>$amharic_instructions</p>
                        <div class='code-box'>$payment_details</div>
                        <p><strong>$detail_label: $payment_details</strong></p>
                        <p><strong>መጠን: $formatted_amount ብር</strong></p>
                    </div>
                    
                    $steps_html
                    
                    <div style='text-align: center; margin: 30px 0; background: #fff8dc; padding: 20px; border-radius: 8px; border: 2px solid #8B4513;'>
                        <h4 style='color: #8B4513; margin-bottom: 15px;'>Payment Confirmation Code</h4>
                        <div style='font-size: 28px; font-weight: bold; color: #D2691E; margin: 15px 0; padding: 10px; background: white; border-radius: 5px;'>
                            $confirm_code
                        </div>
                        <p style='color: #666; margin: 10px 0;'>After completing your payment, enter this code to upload your receipt.</p>
                        <p style='color: #28a745; font-weight: bold;'>Code remains valid until used</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>Ashreka & Friends Pottery Association</p>
                    <p>&copy; $current_year All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTeleBirrSteps($phone_number, $amount) {
        return "
        <div class='steps-section'>
            <h3>📱 TeleBirr Payment Steps:</h3>
            <div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 15px 0;'>
                <ol style='margin: 0; padding-left: 20px;'>
                    <li><strong>Open TeleBirr App</strong> on your phone</li>
                    <li><strong>Select \"Send Money\"</strong> or \"Transfer\" option</li>
                    <li><strong>Enter recipient phone:</strong> <span style='background: #fff; padding: 3px 8px; border-radius: 4px; font-weight: bold;'>$phone_number</span></li>
                    <li><strong>Enter amount:</strong> <span style='background: #fff; padding: 3px 8px; border-radius: 4px; font-weight: bold;'>$amount ETB</span></li>
                    <li><strong>Add reference:</strong> \"Ashreka Pottery Order\"</li>
                    <li><strong>Confirm and send</strong> the payment</li>
                    <li><strong>Take screenshot</strong> of success confirmation</li>
                    <li><strong>Return here</strong> and enter your confirmation code to upload receipt</li>
                </ol>
            </div>
            
            <h3 style='color: #D2691E;'>📱 የቴሌብር ክፍያ ደረጃዎች:</h3>
            <div style='background: #fff8dc; padding: 20px; border-radius: 8px; margin: 15px 0;'>
                <ol style='margin: 0; padding-left: 20px;'>
                    <li><strong>የቴሌብር አፕሊኬሽን</strong> በስልክዎ ውስጥ ይክፈቱ</li>
                    <li><strong>\"ገንዘብ ላክ\"</strong> ወይም \"ትራንስፈር\" አማራጭ ይንረጡ</li>
                    <li><strong>የተቀባይ ስልክ ይግቡ:</strong> <span style='background: #fff; padding: 3px 8px; border-radius: 4px; font-weight: bold;'>$phone_number</span></li>
                    <li><strong>መጠን ይግቡ:</strong> <span style='background: #fff; padding: 3px 8px; border-radius: 4px; font-weight: bold;'>$amount ብር</span></li>
                    <li><strong>ሪፈረንስ ይጨምሩ:</strong> \"አስረቃ ፖተሪ ትዕዛዝ\"</li>
                    <li><strong>ያረጋግጡ እና ይላኩ</strong> ክፍያውን</li>
                    <li><strong>ስክሪንሾት ያንሱ</strong> የተሳካ ማረጋገጫ</li>
                    <li><strong>ወደ ዚህ ይመለሱ</strong> እና የማረጋገጫ ኮዶን በመግባት ደረሰኝ ይላኩ</li>
                </ol>
            </div>
        </div>
        ";
    }
    
    private function getBankSteps($bank_name, $account_number, $amount) {
        $steps_by_bank = [
            'CBE' => [
                'english' => [
                    'Visit CBE branch or use CBE Birr app',
                    'Select "Transfer" or "Send Money"',
                    'Enter account number: ' . $account_number,
                    'Enter amount: ' . $amount . ' ETB',
                    'Add reference: "Ashreka Pottery Order"',
                    'Complete the transaction',
                    'Keep your receipt/confirmation',
                    'Return here and enter confirmation code to upload receipt'
                ],
                'amharic' => [
                    'የCBE ብራንች ይመልከቱ ወይም CBE ብር አፕ ይጠቀሙ',
                    '"ትራንስፈር" ወይም "ገንዘብ ላክ" ይንረጡ',
                    'የአካውንት ቁጥር ይግቡ: ' . $account_number,
                    'መጠን ይግቡ: ' . $amount . ' ብር',
                    'ሪፈረንስ ይጨምሩ: "አስረቃ ፖተሪ ትዕዛዝ"',
                    'የጥርጥር ስራውን ያጠናቅቁ',
                    'ደረሰኝ/ማረጋገጫ ይዓስቡ',
                    'ወደ ዚህ ይመለሱ እና የማረጋገጫ ኮድ በመግባት ደረሰኝ ይላኩ'
                ]
            ],
            'Awash' => [
                'english' => [
                    'Visit Awash Bank branch or use mobile banking',
                    'Select "Fund Transfer" option',
                    'Enter account number: ' . $account_number,
                    'Enter amount: ' . $amount . ' ETB',
                    'Add reference: "Ashreka Pottery Order"',
                    'Confirm and complete transaction',
                    'Save your transaction receipt',
                    'Return here and enter confirmation code to upload receipt'
                ],
                'amharic' => [
                    'የአዋሽ ባንክ ብራንች ይመልከቱ ወይም ሞባይል ባንኪንግ ይጠቀሙ',
                    '"ፋንድ ትራንስፈር" አማራጭ ይንረጡ',
                    'የአካውንት ቁጥር ይግቡ: ' . $account_number,
                    'መጠን ይግቡ: ' . $amount . ' ብር',
                    'ሪፈረንስ ይጨምሩ: "አስረቃ ፖተሪ ትዕዛዝ"',
                    'ያረጋግጡ እና ስራውን ያጠናቅቁ',
                    'የስራው ደረሰኝ ያስቀምጡ',
                    'ወደ ዚህ ይመለሱ እና የማረጋገጫ ኮድ በመግባት ደረሰኝ ይላኩ'
                ]
            ],
            'Birhan' => [
                'english' => [
                    'Visit Birhan Bank branch or ATM',
                    'Use "Transfer Money" service',
                    'Enter destination account: ' . $account_number,
                    'Enter transfer amount: ' . $amount . ' ETB',
                    'Add memo: "Ashreka Pottery Order"',
                    'Verify details and confirm',
                    'Print or save transaction slip',
                    'Return here and enter confirmation code to upload receipt'
                ],
                'amharic' => [
                    'የብርሃን ባንክ ብራንች ወይም ATM ይመልከቱ',
                    '"ገንዘብ ትራንስፈር" አግልግሎት ይጠቀሙ',
                    'የአለማ አካውንት ይግቡ: ' . $account_number,
                    'የትራንስፈር መጠን ይግቡ: ' . $amount . ' ብር',
                    'ሜሞ ይጨምሩ: "አስረቃ ፖተሪ ትዕዛዝ"',
                    'ዝርዝሮችን ያረጋግጡ እና ያረጋግጡ',
                    'የስራ ወረቀት ይፕሪንት ወይም ያስቀምጡ',
                    'ወደ ዚህ ይመለሱ እና የማረጋገጫ ኮድ በመግባት ደረሰኝ ይላኩ'
                ]
            ]
        ];
        
        $bank_steps = $steps_by_bank[$bank_name] ?? [
            'english' => [
                'Visit your bank branch or use online banking',
                'Select money transfer option',
                'Enter account number: ' . $account_number,
                'Enter amount: ' . $amount . ' ETB',
                'Add reference: "Ashreka Pottery Order"',
                'Complete the transaction',
                'Keep your receipt',
                'Return here and enter confirmation code to upload receipt'
            ],
            'amharic' => [
                'የባንክ ብራንች ይመልከቱ ወይም ኦንላይን ባንኪንግ ይጠቀሙ',
                'የገንዘብ ትራንስፈር አማራጭ ይንረጡ',
                'የአካውንት ቁጥር ይግቡ: ' . $account_number,
                'መጠን ይግቡ: ' . $amount . ' ብር',
                'ሪፈረንስ ይጨምሩ: "አስረቃ ፖተሪ ትዕዛዝ"',
                'ስራውን ያጠናቅቁ',
                'ደረሰኝ ይዓስቡ',
                'ወደ ዚህ ይመለሱ እና የማረጋገጫ ኮድ በመግባት ደረሰኝ ይላኩ'
            ]
        ];
        
        $english_steps = '';
        $amharic_steps = '';
        
        foreach ($bank_steps['english'] as $step) {
            $english_steps .= '<li><strong>' . $step . '</strong></li>';
        }
        
        foreach ($bank_steps['amharic'] as $step) {
            $amharic_steps .= '<li><strong>' . $step . '</strong></li>';
        }
        
        return "
        <div class='steps-section'>
            <h3>🏦 $bank_name Bank Payment Steps:</h3>
            <div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 15px 0;'>
                <ol style='margin: 0; padding-left: 20px;'>
                    $english_steps
                </ol>
            </div>
            
            <h3 style='color: #D2691E;'>🏦 የ$bank_name ባንክ ክፍያ ደረጃዎች:</h3>
            <div style='background: #fff8dc; padding: 20px; border-radius: 8px; margin: 15px 0;'>
                <ol style='margin: 0; padding-left: 20px;'>
                    $amharic_steps
                </ol>
            </div>
        </div>
        ";
    }
    
    private function sendEmailWithPHPMailer($to, $subject, $body) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ethiopianartinfinite@gmail.com';
            $mail->Password = 'stivcprjwpjhjoxj';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            $mail->setFrom('ethiopianartinfinite@gmail.com', 'Ashreka Pottery');
            $mail->addAddress($to);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function submitReceipt() {
        try {
            $order_id = $_POST['order_id'] ?? 0;
            $payment_method = $_POST['payment_method'] ?? '';
            $bank_name = $_POST['bank_name'] ?? '';
            $email = $_POST['email'] ?? '';
            
            // Mark confirmation code as used
            $stmt = $this->pdo->prepare("
                UPDATE payment_confirmations 
                SET status = 'used' 
                WHERE order_id = ? AND status = 'pending'
            ");
            $stmt->execute([$order_id]);
            
            $receipt_image_path = null;
            if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/receipts/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
                $filename = 'receipt_' . $order_id . '_' . time() . '.' . $file_extension;
                $receipt_image_path = 'uploads/receipts/' . $filename;
                
                if (!move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_dir . $filename)) {
                    throw new Exception('Failed to upload receipt image');
                }
            }
            
            $receipt_link = $_POST['receipt_link'] ?? null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_receipts 
                (payment_id, order_id, customer_id, receipt_image, receipt_link, payment_method, bank_name) 
                VALUES (0, ?, 0, ?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $receipt_image_path, $receipt_link, $payment_method, $bank_name]);
            
            $stmt = $this->pdo->prepare("UPDATE orders SET status = 'payment_submitted' WHERE id = ?");
            $stmt->execute([$order_id]);
            
            $this->jsonResponse(true, 'Receipt submitted successfully');
            
        } catch (Exception $e) {
            error_log("Error in submitReceipt: " . $e->getMessage());
            $this->jsonResponse(false, 'Failed to submit receipt');
        }
    }
    

    
    private function getSMSMessage($payment_method, $bank_name, $payment_code, $total_amount, $order_id, $confirm_code) {
        $formatted_amount = number_format($total_amount);
        
        if ($payment_method === 'telebirr') {
            $english = "Ashreka Pottery - Order #{$order_id}\n" .
                      "Amount: {$formatted_amount} ETB\n" .
                      "Steps:\n" .
                      "1. Open TeleBirr App\n" .
                      "2. Select Send Money\n" .
                      "3. Enter: {$payment_code}\n" .
                      "4. Amount: {$formatted_amount} ETB\n" .
                      "5. Reference: Ashreka Order\n" .
                      "6. Confirm payment\n" .
                      "Confirm Code: {$confirm_code}";
            
            $amharic = "አስረቃ ትዕዛዝ #{$order_id}\n" .
                      "መጠን: {$formatted_amount} ብር\n" .
                      "ደረጃዎች:\n" .
                      "1. የቴሌብር አፕ ይክፈቱ\n" .
                      "2. ገንዘብ ላክ ይምረጡ\n" .
                      "3. ይግቡ: {$payment_code}\n" .
                      "4. መጠን: {$formatted_amount} ብር\n" .
                      "5. ሪፈረንስ: አስረቃ ትዕዛዝ\n" .
                      "6. ክፍያ ያረጋግጡ\n" .
                      "የማረጋገጫ ኮድ: {$confirm_code}";
        } else {
            $english = "Ashreka Pottery - Order #{$order_id}\n" .
                      "Amount: {$formatted_amount} ETB\n" .
                      "Steps:\n" .
                      "1. Visit {$bank_name} branch/app\n" .
                      "2. Select Transfer\n" .
                      "3. Account: {$payment_code}\n" .
                      "4. Amount: {$formatted_amount} ETB\n" .
                      "5. Reference: Ashreka Order\n" .
                      "6. Complete transaction\n" .
                      "Confirm Code: {$confirm_code}";
            
            $amharic = "አስረቃ ትዕዛዝ #{$order_id}\n" .
                      "መጠን: {$formatted_amount} ብር\n" .
                      "ደረጃዎች:\n" .
                      "1. የ{$bank_name} ብራንች/አፕ ይሂዱ\n" .
                      "2. ትራንስፈር ይምረጡ\n" .
                      "3. አካውንት: {$payment_code}\n" .
                      "4. መጠን: {$formatted_amount} ብር\n" .
                      "5. ሪፈረንስ: አስረቃ ትዕዛዝ\n" .
                      "6. ስራውን ያጠናቅቁ\n" .
                      "የማረጋገጫ ኮድ: {$confirm_code}";
        }
        
        return $english . "\n\n" . $amharic;
    }
    
    private function validateEthiopianPhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^(\+2519[0-9]{8}|09[0-9]{8}|9[0-9]{8})$/', $phone);
    }
    
    private function normalizePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (preg_match('/^09[0-9]{8}$/', $phone)) {
            return '+251' . substr($phone, 1);
        }
        
        if (preg_match('/^9[0-9]{8}$/', $phone)) {
            return '+251' . $phone;
        }
        
        if (preg_match('/^\+2519[0-9]{8}$/', $phone)) {
            return $phone;
        }
        
        return $phone;
    }
    
    private function sendSMS($phone, $message) {
        try {
            error_log("Attempting to send SMS to: $phone");
            error_log("SMS message: $message");
            
            $textbee_device_id = '694481d8fb73763bb262451f';
            $textbee_api_key = '105efcf3-2696-417a-add4-e3b60b4360a2';
            
            $data = [
                'message' => $message,
                'recipients' => [$phone]
            ];
            
            error_log("SMS API data: " . json_encode($data));
            
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
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("SMS API response code: $httpCode");
            error_log("SMS API response: $response");
            if ($curlError) {
                error_log("SMS cURL error: $curlError");
            }
            
            return ($httpCode >= 200 && $httpCode < 300);
            
        } catch (Exception $e) {
            error_log("SMS Exception: " . $e->getMessage());
            return false;
        }
    }
    

}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        $controller = new PaymentWorkflowController($pdo);
        $controller->handleRequest();
    } catch (Exception $e) {
        // Clear output buffer and send clean JSON error
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>