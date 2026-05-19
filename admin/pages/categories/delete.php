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

$stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    setAlert('error', 'Category not found.');
    header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
    exit;
}

try {
    $db->beginTransaction();

    if ($category['image']) {
        deleteImage($category['image']);
    }

    $db->prepare("UPDATE products SET category_id = NULL WHERE category_id = :id")->execute(['id' => $id]);

    $db->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = :id")->execute(['id' => $id]);

    $db->prepare("DELETE FROM categories WHERE id = :id")->execute(['id' => $id]);

    $db->commit();

    setAlert('success', 'Category "' . sanitizeInput($category['name']) . '" has been deleted successfully.');

} catch (Exception $e) {
    $db->rollback();
    setAlert('error', 'Failed to delete category: ' . $e->getMessage());
}

header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
exit;
