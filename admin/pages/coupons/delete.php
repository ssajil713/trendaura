<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid coupon ID.');
    header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT * FROM coupons WHERE id = :id");
$stmt->execute(['id' => $id]);
$coupon = $stmt->fetch();

if (!$coupon) {
    setAlert('error', 'Coupon not found.');
    header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM coupons WHERE id = :id")->execute(['id' => $id]);
    setAlert('success', 'Coupon "' . sanitizeInput($coupon['code']) . '" has been deleted successfully.');
} catch (Exception $e) {
    setAlert('error', 'Failed to delete coupon: ' . $e->getMessage());
}

header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
exit;
