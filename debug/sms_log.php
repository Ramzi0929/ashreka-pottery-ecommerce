<?php
$logFile = 'C:/xampp/apache/logs/error.log';

if (file_exists($logFile)) {
    $lines = file($logFile);
    $smsLines = [];
    
    // Get last 50 lines and filter for SMS-related entries
    $lastLines = array_slice($lines, -50);
    
    foreach ($lastLines as $line) {
        if (strpos($line, 'SMS') !== false || strpos($line, 'Attempting to send') !== false) {
            $smsLines[] = $line;
        }
    }
    
    echo "<h2>SMS Debug Log</h2>";
    if (empty($smsLines)) {
        echo "<p>No SMS-related log entries found. Try testing the checkout again.</p>";
    } else {
        echo "<pre>";
        foreach ($smsLines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    }
} else {
    echo "<p>Error log file not found at: $logFile</p>";
}
?>