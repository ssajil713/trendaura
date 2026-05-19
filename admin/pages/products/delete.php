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

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid product ID.');
    header('Location: ' . ADMIN_URL . '/pages/products/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    setAlert('error', 'Product not found.');
    header('Location: ' . ADMIN_URL . '/pages/products/index.php');
    exit;
}

try {
    $db->beginTransaction();

    // Delete product images from filesystem
    $imgStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = :product_id");
    $imgStmt->execute(['product_id' => $id]);
    $images = $imgStmt->fetchAll();

    foreach ($images as $image) {
        deleteImage($image['image_path']);
    }

    // Delete from product_images table
    $db->prepare("DELETE FROM product_images WHERE product_id = :product_id")->execute(['product_id' => $id]);

    // Delete cart items referencing this product
    $db->prepare("DELETE FROM cart_items WHERE product_id = :product_id")->execute(['product_id' => $id]);

    // Delete wishlist items
    $db->prepare("DELETE FROM wishlists WHERE product_id = :product_id")->execute(['product_id' => $id]);

    // Delete product
    $db->prepare("DELETE FROM products WHERE id = :id")->execute(['id' => $id]);

    $db->commit();

    setAlert('success', 'Product "' . sanitizeInput($product['name']) . '" has been deleted successfully.');

} catch (Exception $e) {
    $db->rollback();
    setAlert('error', 'Failed to delete product: ' . $e->getMessage());
}

header('Location: ' . ADMIN_URL . '/pages/products/index.php');
exit;
