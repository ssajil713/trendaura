<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid banner ID.');
    header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT * FROM banners WHERE id = :id");
$stmt->execute(['id' => $id]);
$banner = $stmt->fetch();

if (!$banner) {
    setAlert('error', 'Banner not found.');
    header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
    exit;
}

try {
    if ($banner['image']) {
        deleteImage($banner['image']);
    }

    $db->prepare("DELETE FROM banners WHERE id = :id")->execute(['id' => $id]);

    setAlert('success', 'Banner "' . sanitizeInput($banner['title']) . '" has been deleted successfully.');
} catch (Exception $e) {
    setAlert('error', 'Failed to delete banner: ' . $e->getMessage());
}

header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
exit;
