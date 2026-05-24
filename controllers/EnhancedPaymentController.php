<?php
session_start();
require_once '../config/database_enhanced.php';
require_once '../includes/PaymentSMSService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    exit(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'process_payment':
        processPayment();
        break;
    case 'send_bank_sms':
        sendBankSMS();
        break;
    case 'send_telebirr_sms':
        sendTelebirrSMS();
        break;
    case 'upload_receipt':
        uploadReceipt();
        break;
    case 'get_banks':
        getBanks();
        break;
    default:
        exit(json_encode(['success' => false, 'message' => 'Invalid action']));
}

function processPayment() {
    global $pdo;
    
    try {
        $customer_id = $_SESSION['customer_id'] ?? null;
        if (!$customer_id) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $customer_id = $stmt->fetchColumn();
            $_SESSION['customer_id'] = $customer_id;
        }
        
        $payment_method = $_POST['payment_method'] ?? '';
        $total_amount = $_POST['total_amount'] ?? 0;
        $phone_number = $_POST['phone_number'] ?? '';
        $selected_bank = $_POST['selected_bank'] ?? '';
        
        if (!$payment_method || !$total_amount || !$phone_number) {
            exit(json_encode(['success' => false, 'message' => 'Missing required fields']));
        }
        
        // Validate phone number
        if (!preg_match('/^(\+2519[0-9]{8}|09[0-9]{8}|9[0-9]{8})$/', preg_replace('/[^0-9+]/', '', $phone_number))) {
            exit(json_encode(['success' => false, 'message' => 'Invalid Ethiopian phone number']));
        }
        
        // Get cart items - if empty, create a dummy order for testing
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            // Create dummy cart for testing
            $cart = [1 => 1]; // Assuming product ID 1 exists
        }
        
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (customer_id, type, total_amount, created_at) 
            VALUES (?, 'catalog', ?, NOW())
        ");
        $stmt->execute([$customer_id, $total_amount]);
        $order_id = $pdo->lastInsertId();
        
        // Add order items
        foreach ($cart as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $price = $stmt->fetchColumn();
            
            if ($price) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$order_id, $product_id, $quantity, $price]);
            } else {
                // If product doesn't exist, use the total amount as price
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, NULL, 1, ?)
                ");
                $stmt->execute([$order_id, $total_amount]);
            }
        }
        
        // Create payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (order_id, amount, payment_method, selected_bank, customer_phone, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$order_id, $total_amount, $payment_method, $selected_bank, $phone_number]);
        $payment_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        // Clear cart only if it was real
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order created successfully',
            'order_id' => $order_id,
            'payment_id' => $payment_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment processing failed']);
    }
}

function sendBankSMS() {
    global $pdo;
    
    try {
        $phone_number = $_POST['phone_number'] ?? '';
        $bank_name = $_POST['bank_name'] ?? '';
        $order_id = $_POST['order_id'] ?? null;
        
        if (!$phone_number || !$bank_name) {
            exit(json_encode(['success' => false, 'message' => 'Phone number and bank name required']));
        }
        
        $smsService = new PaymentSMSService($pdo);
        $result = $smsService->sendBankSMS($phone_number, $bank_name, $order_id);
        
        if ($result) {
            // Update payment record
            if ($order_id) {
                $stmt = $pdo->prepare("UPDATE payments SET sms_sent = 1, sms_message = ? WHERE order_id = ?");
                $stmt->execute(["Bank SMS sent for $bank_name", $order_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Bank SMS sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send SMS']);
        }
        
    } catch (Exception $e) {
        error_log("Bank SMS error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'SMS service error']);
    }
}

function sendTelebirrSMS() {
    global $pdo;
    
    try {
        $phone_number = $_POST['phone_number'] ?? '';
        $order_id = $_POST['order_id'] ?? null;
        
        if (!$phone_number) {
            exit(json_encode(['success' => false, 'message' => 'Phone number required']));
        }
        
        $smsService = new PaymentSMSService($pdo);
        $result = $smsService->sendTelebirrSMS($phone_number, $order_id);
        
        if ($result) {
            // Update payment record
            if ($order_id) {
                $stmt = $pdo->prepare("UPDATE payments SET sms_sent = 1, sms_message = ? WHERE order_id = ?");
                $stmt->execute(["Telebirr SMS sent", $order_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Telebirr SMS sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send SMS']);
        }
        
    } catch (Exception $e) {
        error_log("Telebirr SMS error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'SMS service error']);
    }
}

function uploadReceipt() {
    global $pdo;
    
    try {
        $order_id = $_POST['order_id'] ?? '';
        $payment_id = $_POST['payment_id'] ?? '';
        $receipt_link = $_POST['receipt_link'] ?? '';
        
        if (!$order_id || !$payment_id) {
            exit(json_encode(['success' => false, 'message' => 'Order ID and Payment ID required']));
        }
        
        $customer_id = $_SESSION['customer_id'];
        $receipt_image = null;
        
        // Handle file upload
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
            $filename = 'receipt_' . $order_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_path)) {
                $receipt_image = 'assets/uploads/receipts/' . $filename;
            }
        }
        
        if (!$receipt_image && !$receipt_link) {
            exit(json_encode(['success' => false, 'message' => 'Please provide either receipt image or link']));
        }
        
        // Get payment method and bank name
        $stmt = $pdo->prepare("SELECT payment_method, selected_bank FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment_info = $stmt->fetch();
        
        // Insert receipt record
        $stmt = $pdo->prepare("
            INSERT INTO payment_receipts (payment_id, order_id, customer_id, bank_name, payment_method, receipt_image, receipt_link, status, uploaded_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $payment_id, 
            $order_id, 
            $customer_id, 
            $payment_info['selected_bank'] ?? '', 
            $payment_info['payment_method'] ?? 'telebirr',
            $receipt_image, 
            $receipt_link
        ]);
        
        // Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET receipt_status = 'uploaded' WHERE id = ?");
        $stmt->execute([$payment_id]);
        
        // Update order payment receipt status
        $stmt = $pdo->prepare("UPDATE orders SET payment_receipt_status = 'pending' WHERE id = ?");
        $stmt->execute([$order_id]);
        
        echo json_encode(['success' => true, 'message' => 'Receipt uploaded successfully']);
        
    } catch (Exception $e) {
        error_log("Receipt upload error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Receipt upload failed']);
    }
}

function getBanks() {
    global $pdo;
    
    try {
        $smsService = new PaymentSMSService($pdo);
        $banks = $smsService->getAvailableBanks();
        
        echo json_encode(['success' => true, 'banks' => $banks]);
        
    } catch (Exception $e) {
        error_log("Get banks error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get banks']);
    }
}
?>