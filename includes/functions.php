<?php
// Utility functions for Ashreka Pottery System

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    // Ethiopian phone number validation
    $pattern = '/^\+?251[0-9]{9}$/';
    return preg_match($pattern, str_replace(' ', '', $phone));
}

function generateRandomCode($length = 6) {
    return sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
}

function formatCurrency($amount) {
    return number_format($amount, 2) . ' ETB';
}

function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return formatDate($datetime);
}

function uploadFile($file, $directory, $allowedTypes = [], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds limit');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
        throw new Exception('Invalid file type');
    }
    
    $uploadDir = "assets/uploads/$directory/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    throw new Exception('Failed to move uploaded file');
}

function sendEmail($to, $subject, $message, $from = 'noreply@ashrekapottery.com') {
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function logActivity($userId, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function checkUserPermission($requiredRole) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function resizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 600) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $sourceType = $imageInfo[2];
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $newWidth = intval($sourceWidth * $ratio);
    $newHeight = intval($sourceHeight * $ratio);
    
    // Create source image
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Create target image
    $targetImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($sourceType == IMAGETYPE_PNG || $sourceType == IMAGETYPE_GIF) {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save image
    $result = false;
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($targetImage, $targetPath, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($targetImage, $targetPath);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($targetImage, $targetPath);
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    
    return $result;
}

function createThumbnail($sourcePath, $thumbnailPath, $size = 150) {
    return resizeImage($sourcePath, $thumbnailPath, $size, $size);
}

function getFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function isValidImageType($filename) {
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedTypes);
}

function isValidVideoType($filename) {
    $allowedTypes = ['mp4', 'webm', 'avi', 'mov'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedTypes);
}

function generateOrderNumber() {
    return 'ASH' . date('Ymd') . sprintf('%04d', mt_rand(1, 9999));
}

function calculateDeliveryDate($orderType = 'catalog', $complexity = 'medium') {
    $baseDays = $orderType === 'catalog' ? 3 : 14;
    
    $complexityMultiplier = [
        'simple' => 0.8,
        'medium' => 1.0,
        'complex' => 1.5
    ];
    
    $days = $baseDays * ($complexityMultiplier[$complexity] ?? 1.0);
    
    return date('Y-m-d', strtotime("+$days days"));
}

function getOrderStatusBadge($status) {
    $badges = [
        'pending' => 'bg-secondary',
        'approved' => 'bg-warning',
        'in_progress' => 'bg-info',
        'completed' => 'bg-primary',
        'delivered' => 'bg-success',
        'cancelled' => 'bg-danger',
        'rejected' => 'bg-danger'
    ];
    
    $class = $badges[$status] ?? 'bg-secondary';
    return "<span class='badge $class'>" . ucfirst($status) . "</span>";
}

function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function redirectTo($url, $message = null) {
    if ($message) {
        $_SESSION['flash_message'] = $message;
    }
    header("Location: $url");
    exit();
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirectTo('views/auth/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        die('Access denied');
    }
}

function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>