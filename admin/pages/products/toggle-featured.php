<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/products/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/products/index.php');
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    setAlert('error', 'Invalid product ID.');
    header('Location: ' . ADMIN_URL . '/pages/products/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT id, is_featured FROM products WHERE id = :id");
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    setAlert('error', 'Product not found.');
    header('Location: ' . ADMIN_URL . '/pages/products/index.php');
    exit;
}

$newFeatured = $product['is_featured'] ? 0 : 1;
$stmt = $db->prepare("UPDATE products SET is_featured = :is_featured, updated_at = NOW() WHERE id = :id");
$stmt->execute(['is_featured' => $newFeatured, 'id' => $productId]);

setAlert('success', 'Product featured status has been updated.');
header('Location: ' . ADMIN_URL . '/pages/products/index.php');
exit;
