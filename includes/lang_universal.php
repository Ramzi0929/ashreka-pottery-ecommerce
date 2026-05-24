<?php
// Universal Language Toggle - Auto-include for all pages
$current_dir = dirname($_SERVER['PHP_SELF']);
$depth = substr_count($current_dir, '/') - 1;
$js_path = str_repeat('../', max(0, $depth)) . 'assets/js/lang.js';

// Handle different directory structures
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/' . $js_path)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/ashreka-pottery-system Final/assets/js/lang.js')) {
        $js_path = '/ashreka-pottery-system Final/assets/js/lang.js';
    } else {
        $js_path = 'assets/js/lang.js';
    }
}

echo '<script src="' . $js_path . '"></script>';
?>