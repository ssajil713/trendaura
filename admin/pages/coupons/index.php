<?php
$pageTitle = 'Coupons';
include __DIR__ . '/../../includes/header.php';

$db = db();

$stmt = $db->query("
    SELECT * FROM coupons
    ORDER BY created_at DESC
");
$coupons = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <a href="<?= ADMIN_URL ?>/pages/coupons/add.php" class="btn btn-admin-primary">
            <i class="fas fa-plus me-1"></i> Add Coupon
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Min Order</th>
                        <th>Usage</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-percent"></i>
                                    <p class="mt-2">No coupons found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $coupon): ?>
                            <?php
                            $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) < time();
                            $isActive = $coupon['is_active'] && !$isExpired;
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/pages/coupons/edit.php?id=<?= $coupon['id'] ?>" class="fw-medium"><?= sanitizeInput($coupon['code']) ?></a>
                                </td>
                                <td>
                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                        <?= (int)$coupon['discount_value'] ?>%
                                    <?php else: ?>
                                        <?= formatPrice($coupon['discount_value']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $coupon['min_order_amount'] > 0 ? formatPrice($coupon['min_order_amount']) : '-' ?></td>
                                <td>
                                    <?= (int)$coupon['used_count'] ?> / <?= $coupon['usage_limit'] ? (int)$coupon['usage_limit'] : '&infin;' ?>
                                </td>
                                <td>
                                    <?php if ($coupon['expires_at']): ?>
                                        <small><?= formatDate($coupon['expires_at'], 'd M Y') ?></small>
                                        <?php if ($isExpired): ?>
                                            <br><span class="badge bg-danger mt-1">Expired</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No expiry</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= ADMIN_URL ?>/pages/coupons/edit.php?id=<?= $coupon['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/pages/coupons/delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                            <button type="submit" class="btn btn-admin-sm btn-admin-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
