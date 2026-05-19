<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid category ID.');
    header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT id, is_active FROM categories WHERE id = :id");
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    setAlert('error', 'Category not found.');
    header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
    exit;
}

$newStatus = $category['is_active'] ? 0 : 1;
$stmt = $db->prepare("UPDATE categories SET is_active = :is_active, updated_at = NOW() WHERE id = :id");
$stmt->execute(['is_active' => $newStatus, 'id' => $id]);

setAlert('success', 'Category status has been updated.');
header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
exit;
