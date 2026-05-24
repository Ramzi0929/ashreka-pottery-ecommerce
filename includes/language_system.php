<?php
// Universal Language System Include
// Add this to any page for automatic Amharic-default language toggle

function includeLanguageToggle() {
    // Determine the correct path to the language toggle JS
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $depth = substr_count($currentDir, '/') - 1;
    $relativePath = str_repeat('../', max(0, $depth));
    
    // Possible paths to check
    $paths = [
        $relativePath . 'assets/js/language-toggle.js',
        'assets/js/language-toggle.js',
        '../assets/js/language-toggle.js',
        '../../assets/js/language-toggle.js'
    ];
    
    $scriptPath = null;
    foreach ($paths as $path) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/' . $path)) {
            $scriptPath = $path;
            break;
        }
    }
    
    if ($scriptPath) {
        echo '<script src="' . $scriptPath . '"></script>';
    } else {
        // Fallback - use CDN path
        echo '<script src="/ashreka-pottery-system Final/assets/js/language-toggle.js"></script>';
    }
}

// Auto-include if not in CLI mode
if (php_sapi_name() !== 'cli' && !defined('LANGUAGE_SYSTEM_LOADED')) {
    define('LANGUAGE_SYSTEM_LOADED', true);
    
    // Add to page footer automatically
    register_shutdown_function(function() {
        if (!headers_sent()) {
            includeLanguageToggle();
        }
    });
}
?>