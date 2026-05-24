<?php
// Auto-inject language toggle on ALL pages
if (!defined('LANG_AUTO_LOADED')) {
    define('LANG_AUTO_LOADED', true);
    
    // Auto-inject at page end
    register_shutdown_function(function() {
        if (!headers_sent() && ob_get_level() > 0) {
            $content = ob_get_contents();
            if ($content && strpos($content, '</body>') !== false) {
                $langScript = getLangScript();
                $content = str_replace('</body>', $langScript . '</body>', $content);
                ob_clean();
                echo $content;
            }
        }
    });
    
    // Start output buffering
    ob_start();
}

function getLangScript() {
    // Determine script path
    $depth = substr_count($_SERVER['REQUEST_URI'], '/') - 2;
    $path = str_repeat('../', max(0, $depth)) . 'assets/js/lang.js';
    
    return '<script src="' . $path . '"></script>';
}

// Auto-include in common files
$autoIncludeFiles = ['header.php', 'navbar.php', 'sidebar.php', 'footer.php'];
$currentFile = basename($_SERVER['PHP_SELF']);

if (in_array($currentFile, $autoIncludeFiles)) {
    echo '<script>
    if (typeof initLanguage === "undefined") {
        const script = document.createElement("script");
        script.src = "../../assets/js/lang.js";
        document.head.appendChild(script);
    }
    </script>';
}
?>