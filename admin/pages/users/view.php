<?php
$pageTitle = 'User Details';
include __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid user ID.');
    header('Location: ' . ADMIN_URL . '/pages/users/index.php');
    exit;
}

$db = db();
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    setAlert('error', 'User not found.');
    header('Location: ' . ADMIN_URL . '/pages/users/index.php');
    exit;
}

$orders = $db->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
$orders->execute(['user_id' => $id]);
$orders = $orders->fetchAll();

$addresses = $db->prepare("SELECT * FROM addresses WHERE user_id = :user_id ORDER BY address_type, is_default DESC");
$addresses->execute(['user_id' => $id]);
$addresses = $addresses->fetchAll();

$badgeClasses = [
    'pending' => 'bg-warning',
    'processing' => 'bg-info',
    'shipped' => 'bg-primary',
    'delivered' => 'bg-success',
    'cancelled' => 'bg-danger',
    'refunded' => 'bg-secondary'
];

$paymentBadgeClasses = [
    'pending' => 'bg-warning',
    'paid' => 'bg-success',
    'failed' => 'bg-danger',
    'refunded' => 'bg-secondary'
];
?>

<a href="<?= ADMIN_URL ?>/pages/users/index.php" class="btn btn-admin-outline mb-3"><i class="fas fa-arrow-left me-1"></i>Back to Users</a>

<div class="row">
    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-body text-center">
                <div style="width:80px;height:80px;border-radius:50%;background:var(--admin-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;margin:0 auto 12px;">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
                <h5 class="mb-1"><?= sanitizeInput($user['full_name']) ?></h5>
                <p class="text-muted mb-2"><?= sanitizeInput($user['email']) ?></p>
                <?php if ($user['is_active']): ?>
                    <span class="badge bg-success">Active</span>
                <?php else: ?>
                    <span class="badge bg-danger">Blocked</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-card mb-4">
            <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>User Info</h6></div>
            <div class="admin-card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Phone</span>
                    <span><?= sanitizeInput($user['phone'] ?: '-') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Email Verified</span>
                    <span><?= $user['email_verified_at'] ? 'Yes' : 'No' ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Registered</span>
                    <span><small><?= formatDate($user['created_at']) ?></small></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Last Updated</span>
                    <span><small><?= formatDate($user['updated_at']) ?></small></span>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= ADMIN_URL ?>/pages/users/toggle-status.php">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <?php if ($user['is_active']): ?>
                <button type="submit" class="btn btn-admin-warning w-100"><i class="fas fa-ban me-1"></i> Block User</button>
            <?php else: ?>
                <button type="submit" class="btn btn-admin-success w-100"><i class="fas fa-check me-1"></i> Unblock User</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="col-lg-8">
        <?php if (!empty($addresses)): ?>
            <div class="admin-card mb-4">
                <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Addresses</h6></div>
                <div class="admin-card-body">
                    <div class="row g-3">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <span class="badge bg-<?= $addr['address_type'] === 'billing' ? 'success' : 'primary' ?> mb-2"><?= ucfirst($addr['address_type']) ?></span>
                                    <?php if ($addr['is_default']): ?><span class="badge bg-warning ms-1">Default</span><?php endif; ?>
                                    <p class="mb-1 fw-medium mt-2"><?= sanitizeInput($addr['full_name']) ?></p>
                                    <p class="mb-1"><?= sanitizeInput($addr['phone']) ?></p>
                                    <p class="mb-0"><?= nl2br(sanitizeInput($addr['street_address'])) ?><br>
                                    <?= sanitizeInput($addr['city']) ?>, <?= sanitizeInput($addr['state']) ?> <?= sanitizeInput($addr['postal_code']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-box me-2"></i>Orders (<?= count($orders) ?>)</h6></div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Order Status</th>
                                <th>Date</th>
                                <th style="width:80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-box-open"></i>
                                            <p class="mt-2">No orders yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><a href="<?= ADMIN_URL ?>/pages/orders/view.php?id=<?= $order['id'] ?>" class="fw-medium"><?= sanitizeInput($order['order_number']) ?></a></td>
                                        <td><strong><?= formatPrice($order['total']) ?></strong></td>
                                        <td><span class="badge <?= $paymentBadgeClasses[$order['payment_status']] ?? 'bg-secondary' ?>"><?= ucfirst($order['payment_status']) ?></span></td>
                                        <td><span class="badge <?= $badgeClasses[$order['order_status']] ?? 'bg-secondary' ?>"><?= ucfirst($order['order_status']) ?></span></td>
                                        <td><small><?= formatDate($order['created_at'], 'd M Y') ?></small></td>
                                        <td>
                                            <a href="<?= ADMIN_URL ?>/pages/orders/view.php?id=<?= $order['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
