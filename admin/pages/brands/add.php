<?php
$pageTitle = 'Add Brand';
include __DIR__ . '/../../includes/header.php';

$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) $errors[] = 'Brand name is required.';

        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }

        if (empty($slug)) $slug = 'brand';

        $db = db();
        $stmt = $db->prepare("SELECT COUNT(*) FROM brands WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
            while (true) {
                $stmt->execute(['slug' => $slug]);
                if ((int)$stmt->fetchColumn() === 0) break;
                $slug = preg_replace('/-\d+$/', '', $slug) . '-' . (rand(100, 999));
            }
        }

        if (empty($errors)) {
            try {
                $logoPath = null;

                if (!empty($_FILES['logo']['name'])) {
                    $result = uploadImage($_FILES['logo'], 'brands');
                    if ($result['success']) {
                        $logoPath = $result['path'];
                    } else {
                        $errors[] = 'Logo upload failed: ' . $result['error'];
                    }
                }

                if (empty($errors)) {
                    $stmt = $db->prepare("
                        INSERT INTO brands (name, slug, description, logo, is_active, created_at, updated_at)
                        VALUES (:name, :slug, :description, :logo, :is_active, NOW(), NOW())
                    ");
                    $stmt->execute([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'logo' => $logoPath,
                        'is_active' => $isActive,
                    ]);

                    setAlert('success', 'Brand "' . sanitizeInput($name) . '" has been created successfully.');
                    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
                    exit;
                }

            } catch (Exception $e) {
                $errors[] = 'Failed to create brand: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="mb-4">
    <a href="<?= ADMIN_URL ?>/pages/brands/index.php" class="btn btn-admin-outline">
        <i class="fas fa-arrow-left me-1"></i> Back to Brands
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
            <h5>Basic Information</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="brandName" value="<?= sanitizeInput($old['name'] ?? '') ?>" required oninput="autoSlug(this)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" id="brandSlug" value="<?= sanitizeInput($old['slug'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"><?= sanitizeInput($old['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Logo</h5>
        </div>
        <div class="admin-card-body">
            <input type="file" class="form-control" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="text-muted">Accepted: JPG, PNG, WebP, GIF (max 2MB)</small>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Status</h5>
        </div>
        <div class="admin-card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= !isset($old['is_active']) || isset($old['is_active']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Is Active</label>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Create Brand</button>
        <a href="<?= ADMIN_URL ?>/pages/brands/index.php" class="btn btn-admin-outline ms-2">Cancel</a>
    </div>
</form>

<script>
function autoSlug(input) {
    var slug = document.getElementById('brandSlug');
    if (!slug.dataset.edited) {
        slug.value = input.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    }
}
document.getElementById('brandSlug')?.addEventListener('input', function() {
    this.dataset.edited = '1';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
