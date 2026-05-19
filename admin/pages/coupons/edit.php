<?php
$pageTitle = 'Edit Coupon';
include __DIR__ . '/../../includes/header.php';

$db = db();

$couponId = (int)($_GET['id'] ?? 0);
if ($couponId <= 0) {
    setAlert('error', 'Invalid coupon ID.');
    header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM coupons WHERE id = :id");
$stmt->execute(['id' => $couponId]);
$coupon = $stmt->fetch();

if (!$coupon) {
    setAlert('error', 'Coupon not found.');
    header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $discountType = $_POST['discount_type'] ?? 'percentage';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
        $maxDiscount = (float)($_POST['max_discount'] ?? 0);
        $usageLimit = (int)($_POST['usage_limit'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $startsAt = trim($_POST['starts_at'] ?? '');
        $expiresAt = trim($_POST['expires_at'] ?? '');

        if (empty($code)) $errors[] = 'Coupon code is required.';
        if ($discountValue <= 0) $errors[] = 'Discount value must be greater than zero.';
        if ($discountType === 'percentage' && $discountValue > 100) $errors[] = 'Percentage discount cannot exceed 100%.';

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM coupons WHERE code = :code AND id != :id");
                $stmt->execute(['code' => $code, 'id' => $couponId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $errors[] = 'Coupon code "' . sanitizeInput($code) . '" already exists.';
                } else {
                    $stmt = $db->prepare("
                        UPDATE coupons SET
                            code = :code, description = :description,
                            discount_type = :discount_type, discount_value = :discount_value,
                            min_order_amount = :min_order_amount, max_discount = :max_discount,
                            usage_limit = :usage_limit, is_active = :is_active,
                            starts_at = :starts_at, expires_at = :expires_at,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'code' => $code,
                        'description' => $description,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'min_order_amount' => $minOrderAmount,
                        'max_discount' => $maxDiscount ?: null,
                        'usage_limit' => $usageLimit ?: null,
                        'is_active' => $isActive,
                        'starts_at' => $startsAt ?: null,
                        'expires_at' => $expiresAt ?: null,
                        'id' => $couponId,
                    ]);

                    setAlert('success', 'Coupon "' . sanitizeInput($code) . '" has been updated successfully.');
                    header('Location: ' . ADMIN_URL . '/pages/coupons/edit.php?id=' . $couponId);
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'Failed to update coupon: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM coupons WHERE id = :id");
$stmt->execute(['id' => $couponId]);
$coupon = $stmt->fetch();

$startsAt = $coupon['starts_at'] ? date('Y-m-d\TH:i', strtotime($coupon['starts_at'])) : '';
$expiresAt = $coupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($coupon['expires_at'])) : '';
?>

<div class="mb-4">
    <a href="<?= ADMIN_URL ?>/pages/coupons/index.php" class="btn btn-admin-outline">
        <i class="fas fa-arrow-left me-1"></i> Back to Coupons
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" class="admin-form">
    <?= csrfField() ?>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Coupon Details</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="code" value="<?= sanitizeInput($coupon['code']) ?>" required placeholder="e.g. SUMMER20">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="description" value="<?= sanitizeInput($coupon['description']) ?>" placeholder="Optional description">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="discount_type" id="discountType">
                        <option value="percentage" <?= $coupon['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                        <option value="fixed" <?= $coupon['discount_type'] === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" id="discountPrefix"><?= $coupon['discount_type'] === 'percentage' ? '%' : CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" class="form-control" name="discount_value" value="<?= sanitizeInput($coupon['discount_value']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Discount</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" class="form-control" name="max_discount" value="<?= sanitizeInput($coupon['max_discount']) ?>" placeholder="Leave empty for no limit">
                    </div>
                    <small class="text-muted">Only applies to percentage discounts</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min Order Amount</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" class="form-control" name="min_order_amount" value="<?= sanitizeInput($coupon['min_order_amount']) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usage Limit</label>
                    <input type="number" min="0" class="form-control" name="usage_limit" value="<?= sanitizeInput($coupon['usage_limit']) ?>" placeholder="Leave empty for unlimited">
                    <small class="text-muted">Used: <?= (int)$coupon['used_count'] ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Schedule & Status</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="datetime-local" class="form-control" name="starts_at" value="<?= $startsAt ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="datetime-local" class="form-control" name="expires_at" value="<?= $expiresAt ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= $coupon['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Is Active</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Update Coupon</button>
        <a href="<?= ADMIN_URL ?>/pages/coupons/index.php" class="btn btn-admin-outline ms-2">Cancel</a>
    </div>
</form>

<script>
document.getElementById('discountType')?.addEventListener('change', function() {
    var prefix = document.getElementById('discountPrefix');
    prefix.textContent = this.value === 'percentage' ? '%' : '<?= CURRENCY_SYMBOL ?>';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
