<?php
// Inject error monitor into checkout page
$checkoutFile = '../views/customer/checkout.php';
$monitorScript = file_get_contents('checkout_error_monitor.js');

if (!file_exists($checkoutFile)) {
    die("Checkout file not found: $checkoutFile");
}

$checkoutContent = file_get_contents($checkoutFile);

// Check if monitor is already injected
if (strpos($checkoutContent, 'checkout_error_monitor.js') !== false) {
    echo "Error monitor already injected in checkout page.\n";
} else {
    // Find the closing </body> tag and inject before it
    $injection = "\n    <script src=\"../../debug/checkout_error_monitor.js\"></script>\n</body>";
    $checkoutContent = str_replace('</body>', $injection, $checkoutContent);
    
    if (file_put_contents($checkoutFile, $checkoutContent)) {
        echo "✅ Error monitor successfully injected into checkout page!\n";
        echo "The checkout page now has real-time error monitoring.\n";
    } else {
        echo "❌ Failed to inject error monitor into checkout page.\n";
    }
}

echo "\n🔥 BAD BOY TESTER TOOLS CREATED:\n";
echo "1. Main Tester: http://localhost/ashreka-pottery-system%20advanced/debug/checkout_tester.php\n";
echo "2. Live Monitor: http://localhost/ashreka-pottery-system%20advanced/debug/checkout_monitor.php\n";
echo "3. Flow Simulator: http://localhost/ashreka-pottery-system%20advanced/debug/checkout_simulator.php\n";
echo "4. Checkout with Error Monitor: http://localhost/ashreka-pottery-system%20advanced/views/customer/checkout.php\n";
echo "\n💡 USAGE:\n";
echo "- Run checkout_tester.php for comprehensive system analysis\n";
echo "- Use checkout_monitor.php for real-time debugging\n";
echo "- Use checkout_simulator.php to test user flows\n";
echo "- The checkout page now shows live errors in top-right corner\n";
?>