<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid brand ID.');
    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT id, is_active FROM brands WHERE id = :id");
$stmt->execute(['id' => $id]);
$brand = $stmt->fetch();

if (!$brand) {
    setAlert('error', 'Brand not found.');
    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
    exit;
}

$newStatus = $brand['is_active'] ? 0 : 1;
$stmt = $db->prepare("UPDATE brands SET is_active = :is_active, updated_at = NOW() WHERE id = :id");
$stmt->execute(['is_active' => $newStatus, 'id' => $id]);

setAlert('success', 'Brand status has been updated.');
header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
exit;
