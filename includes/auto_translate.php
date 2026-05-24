<?php
// Auto-include Google Translate on every page
// Works with PHP, HTML, and Markdown files

if (!defined('TRANSLATE_INCLUDED')) {
    define('TRANSLATE_INCLUDED', true);
    
    // Force HTML output for markdown files
    $current_file = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($current_file, '.md') !== false) {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Document</title><style>body{font-family:Arial;padding:20px;}</style></head><body>';
    }
    
    // Determine the correct path to google_translate.php
    $possible_paths = [
        'views/layouts/google_translate.php',
        '../layouts/google_translate.php', 
        '../../views/layouts/google_translate.php',
        '../../../views/layouts/google_translate.php',
        dirname(__FILE__) . '/../views/layouts/google_translate.php'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            include $path;
            break;
        }
    }
    
    // Start markdown content wrapper
    if (strpos($current_file, '.md') !== false) {
        echo '<div class="markdown-content">';
        register_shutdown_function(function() {
            echo '</div></body></html>';
        });
    }
}
?>