<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/users/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/users/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid user ID.');
    header('Location: ' . ADMIN_URL . '/pages/users/index.php');
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT id, is_active FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    setAlert('error', 'User not found.');
    header('Location: ' . ADMIN_URL . '/pages/users/index.php');
    exit;
}

$newStatus = $user['is_active'] ? 0 : 1;
$stmt = $db->prepare("UPDATE users SET is_active = :is_active, updated_at = NOW() WHERE id = :id");
$stmt->execute(['is_active' => $newStatus, 'id' => $id]);

$action = $newStatus ? 'unblocked' : 'blocked';
setAlert('success', "User has been $action.");

$referrer = $_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/pages/users/index.php';
header('Location: ' . $referrer);
exit;
