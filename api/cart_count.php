<?php
session_start();

$cart = $_SESSION['cart'] ?? [];
$count = array_sum($cart);

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>