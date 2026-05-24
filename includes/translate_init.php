<?php
// Universal Translation Initializer
// Include this in every page for automatic translation support

function includeGoogleTranslate() {
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $depth = substr_count($currentDir, '/') - 1;
    $relativePath = str_repeat('../', $depth);
    
    if (file_exists($relativePath . 'views/layouts/google_translate.php')) {
        include $relativePath . 'views/layouts/google_translate.php';
    } elseif (file_exists('views/layouts/google_translate.php')) {
        include 'views/layouts/google_translate.php';
    } elseif (file_exists('../layouts/google_translate.php')) {
        include '../layouts/google_translate.php';
    }
}

// Auto-include if not already included
if (!defined('GOOGLE_TRANSLATE_LOADED')) {
    define('GOOGLE_TRANSLATE_LOADED', true);
    includeGoogleTranslate();
}
?>