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

$stmt = $db->prepare("SELECT * FROM brands WHERE id = :id");
$stmt->execute(['id' => $id]);
$brand = $stmt->fetch();

if (!$brand) {
    setAlert('error', 'Brand not found.');
    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
    exit;
}

try {
    $db->beginTransaction();

    if ($brand['logo']) {
        deleteImage($brand['logo']);
    }

    $db->prepare("UPDATE products SET brand_id = NULL WHERE brand_id = :id")->execute(['id' => $id]);

    $db->prepare("DELETE FROM brands WHERE id = :id")->execute(['id' => $id]);

    $db->commit();

    setAlert('success', 'Brand "' . sanitizeInput($brand['name']) . '" has been deleted successfully.');

} catch (Exception $e) {
    $db->rollback();
    setAlert('error', 'Failed to delete brand: ' . $e->getMessage());
}

header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
exit;
