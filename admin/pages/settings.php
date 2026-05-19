<?php
$pageTitle = 'Settings';
include __DIR__ . '/../includes/header.php';

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('error', 'Invalid security token. Please try again.');
    } else {
        $settings = $_POST['settings'] ?? [];

        try {
            $stmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_group, updated_at)
                VALUES (:key, :value, :group, NOW())
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");

            foreach ($settings as $group => $fields) {
                foreach ($fields as $key => $value) {
                    $stmt->execute([
                        'key' => $key,
                        'value' => trim($value),
                        'group' => $group,
                    ]);
                }
            }

            setAlert('success', 'Settings have been updated successfully.');
            header('Location: ' . ADMIN_URL . '/pages/settings.php');
            exit;

        } catch (Exception $e) {
            setAlert('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }
}

$stmt = $db->query("SELECT setting_key, setting_value, setting_group FROM settings");
$allSettings = $stmt->fetchAll();

$currentSettings = [];
foreach ($allSettings as $s) {
    $currentSettings[$s['setting_key']] = $s['setting_value'];
}
?>

<form method="POST" class="admin-form">
    <?= csrfField() ?>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5><i class="fas fa-info-circle me-2"></i>General</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Site Name</label>
                    <input type="text" class="form-control" name="settings[general][site_name]" value="<?= sanitizeInput($currentSettings['site_name'] ?? SITE_NAME) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Site Tagline</label>
                    <input type="text" class="form-control" name="settings[general][site_tagline]" value="<?= sanitizeInput($currentSettings['site_tagline'] ?? SITE_TAGLINE) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Site Email</label>
                    <input type="email" class="form-control" name="settings[general][site_email]" value="<?= sanitizeInput($currentSettings['site_email'] ?? SITE_EMAIL) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Site Phone</label>
                    <input type="text" class="form-control" name="settings[general][site_phone]" value="<?= sanitizeInput($currentSettings['site_phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Site Address</label>
                    <textarea class="form-control" name="settings[general][site_address]" rows="3"><?= sanitizeInput($currentSettings['site_address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5><i class="fas fa-dollar-sign me-2"></i>Currency</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Currency Symbol</label>
                    <input type="text" class="form-control" name="settings[currency][currency_symbol]" value="<?= sanitizeInput($currentSettings['currency_symbol'] ?? CURRENCY_SYMBOL) ?>" placeholder="e.g. $, ₹, €">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Currency Code</label>
                    <input type="text" class="form-control" name="settings[currency][currency_code]" value="<?= sanitizeInput($currentSettings['currency_code'] ?? CURRENCY_CODE) ?>" placeholder="e.g. USD, INR, EUR">
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5><i class="fas fa-truck me-2"></i>Tax & Shipping</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" name="settings[tax_shipping][tax_rate]" value="<?= sanitizeInput($currentSettings['tax_rate'] ?? TAX_RATE) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Free Shipping Min Amount</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= $currentSettings['currency_symbol'] ?? CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" class="form-control" name="settings[tax_shipping][free_shipping_min]" value="<?= sanitizeInput($currentSettings['free_shipping_min'] ?? FREE_SHIPPING_MIN) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Shipping Charge</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= $currentSettings['currency_symbol'] ?? CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" class="form-control" name="settings[tax_shipping][shipping_charge]" value="<?= sanitizeInput($currentSettings['shipping_charge'] ?? SHIPPING_CHARGE) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5><i class="fas fa-share-alt me-2"></i>Social Media</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Facebook URL</label>
                    <input type="url" class="form-control" name="settings[social][facebook_url]" value="<?= sanitizeInput($currentSettings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/yourpage">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Instagram URL</label>
                    <input type="url" class="form-control" name="settings[social][instagram_url]" value="<?= sanitizeInput($currentSettings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/yourpage">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Twitter / X URL</label>
                    <input type="url" class="form-control" name="settings[social][twitter_url]" value="<?= sanitizeInput($currentSettings['twitter_url'] ?? '') ?>" placeholder="https://twitter.com/yourpage">
                </div>
                <div class="col-md-6">
                    <label class="form-label">YouTube URL</label>
                    <input type="url" class="form-control" name="settings[social][youtube_url]" value="<?= sanitizeInput($currentSettings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/@yourchannel">
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Save Settings</button>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
