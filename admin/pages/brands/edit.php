<?php
$pageTitle = 'Edit Brand';
include __DIR__ . '/../../includes/header.php';

$db = db();

$brandId = (int)($_GET['id'] ?? 0);
if ($brandId <= 0) {
    setAlert('error', 'Invalid brand ID.');
    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM brands WHERE id = :id");
$stmt->execute(['id' => $brandId]);
$brand = $stmt->fetch();

if (!$brand) {
    setAlert('error', 'Brand not found.');
    header('Location: ' . ADMIN_URL . '/pages/brands/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $deleteLogo = isset($_POST['delete_logo']) ? 1 : 0;

        if (empty($name)) $errors[] = 'Brand name is required.';

        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }

        if (empty($slug)) $slug = 'brand';

        $stmt = $db->prepare("SELECT COUNT(*) FROM brands WHERE slug = :slug AND id != :id");
        $stmt->execute(['slug' => $slug, 'id' => $brandId]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
            while (true) {
                $stmt->execute(['slug' => $slug, 'id' => $brandId]);
                if ((int)$stmt->fetchColumn() === 0) break;
                $slug = preg_replace('/-\d+$/', '', $slug) . '-' . (rand(100, 999));
            }
        }

        if (empty($errors)) {
            try {
                $logoPath = $brand['logo'];

                if ($deleteLogo && $logoPath) {
                    deleteImage($logoPath);
                    $logoPath = null;
                }

                if (!empty($_FILES['logo']['name'])) {
                    if ($logoPath) {
                        deleteImage($logoPath);
                    }
                    $result = uploadImage($_FILES['logo'], 'brands');
                    if ($result['success']) {
                        $logoPath = $result['path'];
                    } else {
                        $errors[] = 'Logo upload failed: ' . $result['error'];
                    }
                }

                if (empty($errors)) {
                    $stmt = $db->prepare("
                        UPDATE brands SET
                            name = :name, slug = :slug, description = :description,
                            logo = :logo, is_active = :is_active,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'logo' => $logoPath,
                        'is_active' => $isActive,
                        'id' => $brandId,
                    ]);

                    setAlert('success', 'Brand "' . sanitizeInput($name) . '" has been updated successfully.');
                    header('Location: ' . ADMIN_URL . '/pages/brands/edit.php?id=' . $brandId);
                    exit;
                }

            } catch (Exception $e) {
                $errors[] = 'Failed to update brand: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM brands WHERE id = :id");
$stmt->execute(['id' => $brandId]);
$brand = $stmt->fetch();
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
                    <input type="text" class="form-control" name="name" id="brandName" value="<?= sanitizeInput($brand['name']) ?>" required oninput="autoSlug(this)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" id="brandSlug" value="<?= sanitizeInput($brand['slug']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"><?= sanitizeInput($brand['description']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Logo</h5>
        </div>
        <div class="admin-card-body">
            <?php if ($brand['logo']): ?>
                <div class="mb-3">
                    <div style="position:relative;width:120px;height:120px;border:1px solid var(--admin-border);border-radius:8px;overflow:hidden;">
                        <img src="<?= SITE_URL ?>/<?= $brand['logo'] ?>" alt="<?= sanitizeInput($brand['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="delete_logo" id="delete_logo" value="1">
                        <label class="form-check-label text-danger" for="delete_logo">Delete current logo</label>
                    </div>
                </div>
            <?php endif; ?>
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
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= $brand['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Is Active</label>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Update Brand</button>
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
