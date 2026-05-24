<?php
// Direct API test
$confirm_code = '826047';

echo "<h3>Testing API directly with code: $confirm_code</h3>";

// Simulate the API call
$url = 'http://localhost/ashreka-pottery-system%20advanced/api/get_order_details_public.php';
$data = json_encode(['confirm_code' => $confirm_code]);

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => $data
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<h4>API Response:</h4>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";

$decoded = json_decode($result, true);
echo "<h4>Decoded Response:</h4>";
echo "<pre>" . print_r($decoded, true) . "</pre>";
?>