<?php
function validateAndRedirect($path, $fallback = null) {
    if (!file_exists($path)) {
        $role = $_SESSION['role'] ?? 'guest';
        
        if ($fallback && file_exists($fallback)) {
            header("Location: $fallback");
            exit;
        }
        
        // Role-based fallbacks
        $fallbacks = [
            'admin' => '../admin/dashboard.php',
            'manager' => '../manager/dashboard.php', 
            'artisan' => '../artisan/dashboard.php',
            'customer' => '../customer/dashboard.php'
        ];
        
        if (isset($fallbacks[$role]) && file_exists($fallbacks[$role])) {
            header("Location: " . $fallbacks[$role]);
            exit;
        }
        
        header("Location: ../../index.php");
        exit;
    }
}

function safeInclude($path, $fallback = null) {
    if (file_exists($path)) {
        include $path;
    } elseif ($fallback && file_exists($fallback)) {
        include $fallback;
    } else {
        echo '<div class="alert alert-warning">Content not available</div>';
    }
}
?>