<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setAlert('error', 'Invalid security token.');
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$type = $_POST['type'] ?? '';

if ($id <= 0 || $status === '' || !in_array($type, ['order_status', 'payment_status'])) {
    setAlert('error', 'Invalid request parameters.');
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

$orderStatuses = ['pending','processing','shipped','delivered','cancelled','refunded'];
$paymentStatuses = ['pending','paid','failed','refunded'];

if ($type === 'order_status' && !in_array($status, $orderStatuses)) {
    setAlert('error', 'Invalid order status.');
    header('Location: ' . ADMIN_URL . '/pages/orders/view.php?id=' . $id);
    exit;
}

if ($type === 'payment_status' && !in_array($status, $paymentStatuses)) {
    setAlert('error', 'Invalid payment status.');
    header('Location: ' . ADMIN_URL . '/pages/orders/view.php?id=' . $id);
    exit;
}

$db = db();

$stmt = $db->prepare("SELECT id FROM orders WHERE id = :id");
$stmt->execute(['id' => $id]);
if (!$stmt->fetch()) {
    setAlert('error', 'Order not found.');
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

$extra = '';
if ($type === 'order_status' && $status === 'delivered') {
    $extra = ', delivered_at = NOW()';
}
if ($type === 'payment_status' && $status === 'paid') {
    $extra = ', paid_at = NOW()';
}

$stmt = $db->prepare("UPDATE orders SET $type = :status $extra, updated_at = NOW() WHERE id = :id");
$stmt->execute(['status' => $status, 'id' => $id]);

setAlert('success', 'Order status has been updated.');
header('Location: ' . ADMIN_URL . '/pages/orders/view.php?id=' . $id);
exit;
