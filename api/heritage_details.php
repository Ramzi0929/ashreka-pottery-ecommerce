<?php
session_start();
require_once '../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM heritage_archive WHERE id = ?");
$stmt->execute([$_GET['id']]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($item);
?>