<?php
// Server-side error logger for checkout monitoring
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$error = json_decode($input, true);

if (!$error) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Log file
$logFile = 'checkout_errors.log';

// Format error for logging
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'client_timestamp' => $error['timestamp'] ?? 'unknown',
    'type' => $error['type'] ?? 'unknown',
    'message' => $error['message'] ?? 'no message',
    'details' => $error['details'] ?? [],
    'url' => $error['url'] ?? 'unknown',
    'user_agent' => $error['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

// Write to log file
$logLine = json_encode($logEntry) . "\n";
file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// Also log to PHP error log for critical errors
if (in_array($error['type'], ['JavaScript Error', 'Network Error', 'Network Failure'])) {
    error_log("CHECKOUT ERROR: {$error['type']} - {$error['message']} - URL: {$error['url']}");
}

echo json_encode(['success' => true, 'logged' => true]);
?>