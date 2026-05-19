<?php
$pageTitle = 'Add Banner';
include __DIR__ . '/../../includes/header.php';

$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $buttonText = trim($_POST['button_text'] ?? '');
        $position = $_POST['position'] ?? 'hero';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) $errors[] = 'Banner title is required.';

        if (empty($errors)) {
            try {
                $imagePath = null;

                if (!empty($_FILES['image']['name'])) {
                    $result = uploadImage($_FILES['image'], 'banners');
                    if ($result['success']) {
                        $imagePath = $result['path'];
                    } else {
                        $errors[] = 'Image upload failed: ' . $result['error'];
                    }
                } else {
                    $errors[] = 'Banner image is required.';
                }

                if (empty($errors)) {
                    $db = db();
                    $stmt = $db->prepare("
                        INSERT INTO banners (title, subtitle, description, image, link, button_text, position, sort_order, is_active, created_at, updated_at)
                        VALUES (:title, :subtitle, :description, :image, :link, :button_text, :position, :sort_order, :is_active, NOW(), NOW())
                    ");
                    $stmt->execute([
                        'title' => $title,
                        'subtitle' => $subtitle,
                        'description' => $description,
                        'image' => $imagePath,
                        'link' => $link,
                        'button_text' => $buttonText,
                        'position' => $position,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                    ]);

                    setAlert('success', 'Banner "' . sanitizeInput($title) . '" has been created successfully.');
                    header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
                    exit;
                }

            } catch (Exception $e) {
                $errors[] = 'Failed to create banner: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="mb-4">
    <a href="<?= ADMIN_URL ?>/pages/banners/index.php" class="btn btn-admin-outline">
        <i class="fas fa-arrow-left me-1"></i> Back to Banners
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

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Banner Content</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="title" value="<?= sanitizeInput($old['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subtitle</label>
                    <input type="text" class="form-control" name="subtitle" value="<?= sanitizeInput($old['subtitle'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= sanitizeInput($old['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Link URL</label>
                    <input type="text" class="form-control" name="link" value="<?= sanitizeInput($old['link'] ?? '') ?>" placeholder="e.g. /pages/products">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Button Text</label>
                    <input type="text" class="form-control" name="button_text" value="<?= sanitizeInput($old['button_text'] ?? '') ?>" placeholder="e.g. Shop Now">
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Image</h5>
        </div>
        <div class="admin-card-body">
            <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
            <small class="text-muted">Accepted: JPG, PNG, WebP, GIF (max 2MB). Recommended size: 1920x600 for hero banners.</small>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Display Settings</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Position</label>
                    <select class="form-select" name="position">
                        <option value="hero" <?= ($old['position'] ?? '') === 'hero' ? 'selected' : '' ?>>Hero</option>
                        <option value="promo" <?= ($old['position'] ?? '') === 'promo' ? 'selected' : '' ?>>Promo</option>
                        <option value="offer" <?= ($old['position'] ?? '') === 'offer' ? 'selected' : '' ?>>Offer</option>
                        <option value="sidebar" <?= ($old['position'] ?? '') === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sort Order</label>
                    <input type="number" min="0" class="form-control" name="sort_order" value="<?= sanitizeInput($old['sort_order'] ?? '0') ?>">
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
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Create Banner</button>
        <a href="<?= ADMIN_URL ?>/pages/banners/index.php" class="btn btn-admin-outline ms-2">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
