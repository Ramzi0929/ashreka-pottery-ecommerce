<?php
require_once '../config/database_enhanced.php';

$type = $_GET['type'] ?? '';
$user_id = $_GET['user_id'] ?? '';

if (!$type || !$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

if ($type === 'artisan') {
    $stmt = $pdo->prepare("SELECT approval_status FROM artisans WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $status = $result['approval_status'] ?? 'pending';
} else {
    $stmt = $pdo->prepare("SELECT is_loyal, u.status FROM customers c JOIN users u ON c.user_id = u.id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $status = ($result['is_loyal'] && $result['status'] === 'active') ? 'approved' : 'pending';
}

header('Content-Type: application/json');
echo json_encode(['status' => $status]);