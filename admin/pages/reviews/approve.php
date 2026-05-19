<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/reviews/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/reviews/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid review ID.');
    header('Location: ' . ADMIN_URL . '/pages/reviews/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT id, is_approved FROM reviews WHERE id = :id");
$stmt->execute(['id' => $id]);
$review = $stmt->fetch();

if (!$review) {
    setAlert('error', 'Review not found.');
    header('Location: ' . ADMIN_URL . '/pages/reviews/index.php');
    exit;
}

$newStatus = $review['is_approved'] ? 0 : 1;
$stmt = $db->prepare("UPDATE reviews SET is_approved = :is_approved, updated_at = NOW() WHERE id = :id");
$stmt->execute(['is_approved' => $newStatus, 'id' => $id]);

$statusText = $newStatus ? 'approved' : 'rejected';
setAlert('success', 'Review has been ' . $statusText . ' successfully.');
header('Location: ' . ADMIN_URL . '/pages/reviews/index.php');
exit;
