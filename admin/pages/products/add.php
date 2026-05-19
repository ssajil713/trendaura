<?php
$pageTitle = 'Add Product';
include __DIR__ . '/../../includes/header.php';

$db = db();
$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $regularPrice = (float)($_POST['regular_price'] ?? 0);
        $salePrice = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
        $stockStatus = $_POST['stock_status'] ?? 'in_stock';
        $weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
        $dimensions = trim($_POST['dimensions'] ?? '');
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isTrending = isset($_POST['is_trending']) ? 1 : 0;
        $isNew = isset($_POST['is_new']) ? 1 : 0;
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');

        if (empty($name)) $errors[] = 'Product name is required.';
        if (empty($sku)) $errors[] = 'SKU is required.';
        if ($regularPrice <= 0) $errors[] = 'Regular price must be greater than 0.';
        if (!in_array($stockStatus, ['in_stock', 'out_of_stock', 'on_backorder'])) $stockStatus = 'in_stock';

        // Validate slug uniqueness
        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }

        if (empty($slug)) $slug = 'product';

        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE slug = :slug");
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
                $db->beginTransaction();

                $stmt = $db->prepare("
                    INSERT INTO products (name, slug, description, short_description, sku, regular_price, sale_price,
                        category_id, brand_id, stock_quantity, stock_status, weight, dimensions,
                        is_featured, is_trending, is_new, is_active, meta_title, meta_description, created_at, updated_at)
                    VALUES (:name, :slug, :description, :short_description, :sku, :regular_price, :sale_price,
                        :category_id, :brand_id, :stock_quantity, :stock_status, :weight, :dimensions,
                        :is_featured, :is_trending, :is_new, 1, :meta_title, :meta_description, NOW(), NOW())
                ");
                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'short_description' => $shortDescription,
                    'sku' => $sku,
                    'regular_price' => $regularPrice,
                    'sale_price' => $salePrice,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'stock_quantity' => $stockQuantity,
                    'stock_status' => $stockStatus,
                    'weight' => $weight,
                    'dimensions' => $dimensions ?: null,
                    'is_featured' => $isFeatured,
                    'is_trending' => $isTrending,
                    'is_new' => $isNew,
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDescription,
                ]);

                $productId = $db->lastInsertId();

                // Upload images
                if (!empty($_FILES['images']['name'][0])) {
                    $files = $_FILES['images'];
                    $isPrimary = true;
                    $sortOrder = 0;

                    foreach ($files['name'] as $i => $fname) {
                        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                        $file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i],
                        ];

                        $result = uploadImage($file, 'products');
                        if ($result['success']) {
                            $stmt = $db->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (:product_id, :image_path, :is_primary, :sort_order)");
                            $stmt->execute([
                                'product_id' => $productId,
                                'image_path' => $result['path'],
                                'is_primary' => $isPrimary ? 1 : 0,
                                'sort_order' => $sortOrder,
                            ]);
                            $isPrimary = false;
                            $sortOrder++;
                        }
                    }
                }

                $db->commit();
                setAlert('success', 'Product "' . sanitizeInput($name) . '" has been created successfully.');
                header('Location: ' . ADMIN_URL . '/pages/products/index.php');
                exit;

            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Failed to create product: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="mb-4">
    <a href="<?= ADMIN_URL ?>/pages/products/index.php" class="btn btn-admin-outline">
        <i class="fas fa-arrow-left me-1"></i> Back to Products
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
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="productName" value="<?= sanitizeInput($old['name'] ?? '') ?>" required oninput="autoSlug(this)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" id="productSlug" value="<?= sanitizeInput($old['slug'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="5"><?= sanitizeInput($old['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Short Description</label>
                    <textarea class="form-control" name="short_description" rows="3"><?= sanitizeInput($old['short_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Pricing & Inventory</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="sku" value="<?= sanitizeInput($old['sku'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Regular Price <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="regular_price" step="0.01" min="0" value="<?= sanitizeInput($old['regular_price'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sale Price <small class="text-muted">(optional)</small></label>
                    <input type="number" class="form-control" name="sale_price" step="0.01" min="0" value="<?= sanitizeInput($old['sale_price'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= isset($old['category_id']) && (int)$old['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= sanitizeInput($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <select class="form-select" name="brand_id">
                        <option value="">Select Brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['id'] ?>" <?= isset($old['brand_id']) && (int)$old['brand_id'] === (int)$brand['id'] ? 'selected' : '' ?>><?= sanitizeInput($brand['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" class="form-control" name="stock_quantity" min="0" value="<?= sanitizeInput($old['stock_quantity'] ?? '0') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Status</label>
                    <select class="form-select" name="stock_status">
                        <option value="in_stock" <?= ($old['stock_status'] ?? 'in_stock') === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                        <option value="out_of_stock" <?= ($old['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        <option value="on_backorder" <?= ($old['stock_status'] ?? '') === 'on_backorder' ? 'selected' : '' ?>>On Backorder</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Shipping Details</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Weight <small class="text-muted">(kg, optional)</small></label>
                    <input type="number" class="form-control" name="weight" step="0.01" min="0" value="<?= sanitizeInput($old['weight'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dimensions <small class="text-muted">(L x W x H, optional)</small></label>
                    <input type="text" class="form-control" name="dimensions" placeholder="e.g. 10 x 5 x 3" value="<?= sanitizeInput($old['dimensions'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Status & Visibility</h5>
        </div>
        <div class="admin-card-body">
            <div class="d-flex flex-wrap gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= isset($old['is_featured']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">Is Featured</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending" value="1" <?= isset($old['is_trending']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_trending">Is Trending</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_new" id="is_new" value="1" <?= isset($old['is_new']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_new">Is New</label>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>SEO</h5>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Meta Title</label>
                    <input type="text" class="form-control" name="meta_title" value="<?= sanitizeInput($old['meta_title'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Description</label>
                    <textarea class="form-control" name="meta_description" rows="3"><?= sanitizeInput($old['meta_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h5>Product Images</h5>
        </div>
        <div class="admin-card-body">
            <div class="mb-3">
                <input type="file" class="form-control" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="text-muted">First image will be set as primary. Accepted: JPG, PNG, WebP, GIF (max 2MB each)</small>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-admin-primary px-4"><i class="fas fa-save me-1"></i> Create Product</button>
        <a href="<?= ADMIN_URL ?>/pages/products/index.php" class="btn btn-admin-outline ms-2">Cancel</a>
    </div>
</form>

<script>
function autoSlug(input) {
    var slug = document.getElementById('productSlug');
    if (!slug.dataset.edited) {
        slug.value = input.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    }
}
document.getElementById('productSlug')?.addEventListener('input', function() {
    this.dataset.edited = '1';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
