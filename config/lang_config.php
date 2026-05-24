<?php
// Global Language Configuration - Auto-loads on every page
if (!defined('LANG_SYSTEM_ACTIVE')) {
    define('LANG_SYSTEM_ACTIVE', true);
    
    // Auto-include language system
    require_once __DIR__ . '/../includes/auto_lang.php';
}
?>