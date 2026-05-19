<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';

$query = $_GET['q'] ?? '';
$query = trim($query);

if (strlen($query) < 2) {
    echo json_encode([]); exit;
}

$products = searchProducts($query);
echo json_encode($products);
