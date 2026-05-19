<?php
$pageTitle = 'Edit Category';
include __DIR__ . '/../../includes/header.php';

$db = db();

$categoryId = (int)($_GET['id'] ?? 0);
if ($categoryId <= 0) {
    setAlert('error', 'Invalid category ID.');
    header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute(['id' => $categoryId]);
$category = $stmt->fetch();

if (!$category) {
    setAlert('error', 'Category not found.');
    header('Location: ' . ADMIN_URL . '/pages/categories/index.php');
    exit;
}

$parentCategories = $db->query("SELECT id, name FROM categories WHERE parent_id IS NULL AND id != {$categoryId} ORDER BY sort_order ASC, name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $deleteImage = isset($_POST['delete_image']) ? 1 : 0;

        if (empty($name)) $errors[] = 'Category name is required.';

        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }

        if (empty($slug)) $slug = 'category';

        $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE slug = :slug AND id != :id");
        $stmt->execute(['slug' => $slug, 'id' => $categoryId]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
            while (true) {
                $stmt->execute(['slug' => $slug, 'id' => $categoryId]);
                if ((int)$stmt->fetchColumn() === 0) break;
                $slug = preg_replace('/-\d+$/', '', $slug) . '-' . (rand(100, 999));
            }
        }

        if (empty($errors)) {
            try {
                $imagePath = $category['image'];

                if ($deleteImage && $imagePath) {
                    deleteImage($imagePath);
                    $imagePath = null;
                }

                if (!empty($_FILES['image']['name'])) {
                    if ($imagePath) {
                        deleteImage($imagePath);
                    }
                    $result = uploadImage($_FILES['image'], 'categories');
                    if ($result['success']) {
                        $imagePath = $result['path'];
                    } else {
                        $errors[] = 'Image upload failed: ' . $result['error'];
                    }
                }

                if (empty($errors)) {
                    $stmt = $db->prepare("
                        UPDATE categories SET
                            name = :name, slug = :slug, description = :description,
                            image = :image, parent_id = :parent_id,
                            is_active = :is_active, sort_order = :sort_order,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'image' => $imagePath,
                        'parent_id' => $parentId,
                        'is_active' => $isActive,
                        'sort_order' => $sortOrder,
                        'id' => $categoryId,
                    ]);

                    setAlert('success', 'Category "' . sanitizeInput($name) . '" has been updated successfully.');
                    header('Location: ' . ADMIN_URL . '/pages/categories/edit.php?id=' . $categoryId);
                    exit;
                }

            } catch (Exception $e) {
                $errors[] = 'Failed to update category: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute(['id' => $categoryId]);
$category = $stmt->fetch();
?>

<div class="mb-4">
    <a href="<?= ADMIN_URL ?>/pages/categories/index.php" class="btn btn-admin-outline">
        <i class="fas fa-arrow-left me-1"></i> Back to Categories
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
                    <input type="text" class="form-control" name="name" id="categoryName" value="<?= sanitizeInput($category['name']) ?>" required oninput="autoSlug(this)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" id="categorySlug" value="<?= sanitizeInput($category['slug']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"><?= sanitizeInput($category['description']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Parent & Ordering</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Parent Category</label>
                    <select class="form-select" name="parent_id">
                        <option value="">None (Top Level)</option>
                        <?php foreach ($parentCategories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (int)$category['parent_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= sanitizeInput($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort Order</label>
                    <input type="number" class="form-control" name="sort_order" min="0" value="<?= (int)$category['sort_order'] ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Image</h5>
        </div>
        <div class="admin-card-body">
            <?php if ($category['image']): ?>
                <div class="mb-3">
                    <div style="position:relative;width:120px;height:120px;border:1px solid var(--admin-border);border-radius:8px;overflow:hidden;">
                        <img src="<?= SITE_URL ?>/<?= $category['image'] ?>" alt="<?= sanitizeInput($category['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="delete_image" id="delete_image" value="1">
                        <label class="form-check-label text-danger" for="delete_image">Delete current image</label>
                    </div>
                </div>
            <?php endif; ?>
            <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="text-muted">Accepted: JPG, PNG, WebP, GIF (max 2MB)</small>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Status</h5>
        </div>
        <div class="admin-card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= $category['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Is Active</label>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Update Category</button>
        <a href="<?= ADMIN_URL ?>/pages/categories/index.php" class="btn btn-admin-outline ms-2">Cancel</a>
    </div>
</form>

<script>
function autoSlug(input) {
    var slug = document.getElementById('categorySlug');
    if (!slug.dataset.edited) {
        slug.value = input.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    }
}
document.getElementById('categorySlug')?.addEventListener('input', function() {
    this.dataset.edited = '1';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
