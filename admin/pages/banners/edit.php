<?php
$pageTitle = 'Edit Banner';
include __DIR__ . '/../../includes/header.php';

$db = db();

$bannerId = (int)($_GET['id'] ?? 0);
if ($bannerId <= 0) {
    setAlert('error', 'Invalid banner ID.');
    header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM banners WHERE id = :id");
$stmt->execute(['id' => $bannerId]);
$banner = $stmt->fetch();

if (!$banner) {
    setAlert('error', 'Banner not found.');
    header('Location: ' . ADMIN_URL . '/pages/banners/index.php');
    exit;
}

$errors = [];

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
                $imagePath = $banner['image'];

                if (!empty($_FILES['image']['name'])) {
                    if ($imagePath) {
                        deleteImage($imagePath);
                    }
                    $result = uploadImage($_FILES['image'], 'banners');
                    if ($result['success']) {
                        $imagePath = $result['path'];
                    } else {
                        $errors[] = 'Image upload failed: ' . $result['error'];
                    }
                }

                if (empty($errors)) {
                    $stmt = $db->prepare("
                        UPDATE banners SET
                            title = :title, subtitle = :subtitle, description = :description,
                            image = :image, link = :link, button_text = :button_text,
                            position = :position, sort_order = :sort_order, is_active = :is_active,
                            updated_at = NOW()
                        WHERE id = :id
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
                        'id' => $bannerId,
                    ]);

                    setAlert('success', 'Banner "' . sanitizeInput($title) . '" has been updated successfully.');
                    header('Location: ' . ADMIN_URL . '/pages/banners/edit.php?id=' . $bannerId);
                    exit;
                }

            } catch (Exception $e) {
                $errors[] = 'Failed to update banner: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM banners WHERE id = :id");
$stmt->execute(['id' => $bannerId]);
$banner = $stmt->fetch();
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
                    <input type="text" class="form-control" name="title" value="<?= sanitizeInput($banner['title']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subtitle</label>
                    <input type="text" class="form-control" name="subtitle" value="<?= sanitizeInput($banner['subtitle']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= sanitizeInput($banner['description']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Link URL</label>
                    <input type="text" class="form-control" name="link" value="<?= sanitizeInput($banner['link']) ?>" placeholder="e.g. /pages/products">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Button Text</label>
                    <input type="text" class="form-control" name="button_text" value="<?= sanitizeInput($banner['button_text']) ?>" placeholder="e.g. Shop Now">
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Image</h5>
        </div>
        <div class="admin-card-body">
            <?php if ($banner['image']): ?>
                <div class="mb-3">
                    <div style="position:relative;width:240px;height:144px;border:1px solid var(--admin-border);border-radius:8px;overflow:hidden;">
                        <img src="<?= SITE_URL ?>/<?= $banner['image'] ?>" alt="<?= sanitizeInput($banner['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                </div>
            <?php endif; ?>
            <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="text-muted">Accepted: JPG, PNG, WebP, GIF (max 2MB). Leave empty to keep current image.</small>
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
                        <option value="hero" <?= $banner['position'] === 'hero' ? 'selected' : '' ?>>Hero</option>
                        <option value="promo" <?= $banner['position'] === 'promo' ? 'selected' : '' ?>>Promo</option>
                        <option value="offer" <?= $banner['position'] === 'offer' ? 'selected' : '' ?>>Offer</option>
                        <option value="sidebar" <?= $banner['position'] === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sort Order</label>
                    <input type="number" min="0" class="form-control" name="sort_order" value="<?= sanitizeInput($banner['sort_order']) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= $banner['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Is Active</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Update Banner</button>
        <a href="<?= ADMIN_URL ?>/pages/banners/index.php" class="btn btn-admin-outline ms-2">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
