<?php
// Helper script to insert test confirmation codes
header('Content-Type: application/json');
require_once '../config/database_enhanced.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

$orderId = $_POST['order_id'] ?? null;
$confirmCode = $_POST['confirm_code'] ?? null;
$email = $_POST['email'] ?? 'test@example.com';

if (!$orderId || !$confirmCode) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Insert test confirmation code
    $stmt = $pdo->prepare("
        INSERT INTO payment_confirmations (order_id, confirm_code, email, phone, status, created_at)
        VALUES (?, ?, ?, '+251912345678', 'pending', NOW())
        ON DUPLICATE KEY UPDATE
        confirm_code = VALUES(confirm_code),
        status = 'pending',
        created_at = NOW()
    ");
    
    $stmt->execute([$orderId, $confirmCode, $email]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test confirmation code inserted',
        'order_id' => $orderId,
        'confirm_code' => $confirmCode
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>