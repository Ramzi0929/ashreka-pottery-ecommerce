<?php
require_once '../config/database.php';
require_once '../includes/upload_handler.php';

header('Content-Type: application/json');

if ($_FILES) {
    $uploadHandler = new UploadHandler();
    $result = [];
    
    if (isset($_FILES['images'])) {
        $result = $uploadHandler->uploadProductImages($_FILES['images'], $_POST['product_id'] ?? 0);
    } elseif (isset($_FILES['video'])) {
        $result = $uploadHandler->uploadProductVideo($_FILES['video'], $_POST['product_id'] ?? 0);
    }
    
    echo json_encode(['success' => true, 'files' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
}
?>