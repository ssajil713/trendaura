<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';

$action = $_POST['action'] ?? 'toggle';
$productId = (int)($_POST['product_id'] ?? 0);
$csrf = $_POST['csrf_token'] ?? '';

if (!verifyCSRFToken($csrf)) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']); exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'redirect' => SITE_URL . '/login.php']); exit;
}

$db = db();
$userId = $_SESSION['user_id'];

if ($action === 'toggle') {
    $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare("DELETE FROM wishlists WHERE id = :id");
        $stmt->execute(['id' => $existing['id']]);
        echo json_encode(['success' => true, 'added' => false]);
    } else {
        $stmt = $db->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (:user_id, :product_id)");
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        echo json_encode(['success' => true, 'added' => true]);
    }
} elseif ($action === 'remove') {
    $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    echo json_encode(['success' => true]);
}
