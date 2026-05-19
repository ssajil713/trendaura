<?php
$pageTitle = 'Add Coupon';
include __DIR__ . '/../../includes/header.php';

$errors = [];
$old = $_POST;

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
                $db = db();
                $stmt = $db->prepare("SELECT COUNT(*) FROM coupons WHERE code = :code");
                $stmt->execute(['code' => $code]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $errors[] = 'Coupon code "' . sanitizeInput($code) . '" already exists.';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, usage_limit, is_active, starts_at, expires_at, created_at, updated_at)
                        VALUES (:code, :description, :discount_type, :discount_value, :min_order_amount, :max_discount, :usage_limit, :is_active, :starts_at, :expires_at, NOW(), NOW())
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
                    ]);

                    setAlert('success', 'Coupon "' . sanitizeInput($code) . '" has been created successfully.');
                    header('Location: ' . ADMIN_URL . '/pages/coupons/index.php');
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'Failed to create coupon: ' . $e->getMessage();
            }
        }
    }
}
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
                    <input type="text" class="form-control text-uppercase" name="code" value="<?= sanitizeInput($old['code'] ?? '') ?>" required placeholder="e.g. SUMMER20">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="description" value="<?= sanitizeInput($old['description'] ?? '') ?>" placeholder="Optional description">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="discount_type" id="discountType">
                        <option value="percentage" <?= ($old['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                        <option value="fixed" <?= ($old['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" id="discountPrefix">%</span>
                        <input type="number" step="0.01" min="0" class="form-control" name="discount_value" value="<?= sanitizeInput($old['discount_value'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Discount</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" class="form-control" name="max_discount" value="<?= sanitizeInput($old['max_discount'] ?? '') ?>" placeholder="Leave empty for no limit">
                    </div>
                    <small class="text-muted">Only applies to percentage discounts</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min Order Amount</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" class="form-control" name="min_order_amount" value="<?= sanitizeInput($old['min_order_amount'] ?? '0') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usage Limit</label>
                    <input type="number" min="0" class="form-control" name="usage_limit" value="<?= sanitizeInput($old['usage_limit'] ?? '') ?>" placeholder="Leave empty for unlimited">
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
                    <input type="datetime-local" class="form-control" name="starts_at" value="<?= sanitizeInput($old['starts_at'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="datetime-local" class="form-control" name="expires_at" value="<?= sanitizeInput($old['expires_at'] ?? '') ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= !isset($old['is_active']) || isset($old['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Is Active</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Create Coupon</button>
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
