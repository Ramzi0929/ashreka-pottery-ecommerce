<?php
// Test API for order details preview - generates sample data for testing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $confirm_code = $input['confirm_code'] ?? '';
    
    // Test codes that return different scenarios
    $test_data = [
        '123456' => [
            'success' => true,
            'items' => [
                [
                    'name' => 'Traditional Coffee Cup Set',
                    'quantity' => 2,
                    'price' => '450.00',
                    'image' => 'assets/images/products/coffee-cup.jpg'
                ],
                [
                    'name' => 'Decorative Vase',
                    'quantity' => 1,
                    'price' => '850.00',
                    'image' => 'assets/images/products/vase.jpg'
                ]
            ],
            'total' => '1,300.00'
        ],
        '654321' => [
            'success' => true,
            'items' => [
                [
                    'name' => 'Ceramic Dinner Plate',
                    'quantity' => 4,
                    'price' => '320.00',
                    'image' => 'assets/images/products/plate.jpg'
                ]
            ],
            'total' => '320.00'
        ],
        '111111' => [
            'success' => true,
            'items' => [
                [
                    'name' => 'Handmade Bowl Set',
                    'quantity' => 3,
                    'price' => '675.00',
                    'image' => 'assets/images/products/bowl.jpg'
                ],
                [
                    'name' => 'Clay Water Jug',
                    'quantity' => 1,
                    'price' => '425.00',
                    'image' => 'assets/images/products/jug.jpg'
                ],
                [
                    'name' => 'Pottery Mug',
                    'quantity' => 2,
                    'price' => '180.00',
                    'image' => 'assets/images/products/mug.jpg'
                ]
            ],
            'total' => '1,280.00'
        ]
    ];
    
    if (isset($test_data[$confirm_code])) {
        echo json_encode($test_data[$confirm_code]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid confirmation code']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>