<?php
$pageTitle = 'Products';
include __DIR__ . '/../../includes/header.php';

$db = db();

// Build query filters
$where = 'WHERE 1=1';
$params = [];

// Search
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $where .= ' AND (p.name LIKE :search OR p.sku LIKE :search2)';
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

// Category filter
$categoryId = (int)($_GET['category_id'] ?? 0);
if ($categoryId > 0) {
    $where .= ' AND p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

// Stock status filter
$stockStatus = $_GET['stock_status'] ?? '';
if ($stockStatus !== '') {
    if ($stockStatus === 'low') {
        $where .= ' AND p.stock_quantity < 10';
    } elseif (in_array($stockStatus, ['in_stock', 'out_of_stock', 'on_backorder'])) {
        $where .= ' AND p.stock_status = :stock_status';
        $params['stock_status'] = $stockStatus;
    }
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$perPage = ADMIN_ITEMS_PER_PAGE;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Fetch products
$stmt = $db->prepare("
    SELECT p.*,
        c.name as category_name,
        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $where
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch categories for filter
$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Build pagination URL
$queryParams = $_GET;
unset($queryParams['page']);
$paginationUrl = '?' . http_build_query($queryParams) . (empty($queryParams) ? '' : '&');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="<?= ADMIN_URL ?>/pages/products/add.php" class="btn btn-admin-primary">
            <i class="fas fa-plus me-1"></i> Add Product
        </a>
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <div class="admin-search">
                <input type="text" class="form-control" name="search" placeholder="Search by name or SKU..." value="<?= sanitizeInput($search) ?>">
            </div>
            <select name="category_id" class="form-select" style="width:auto;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= sanitizeInput($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="stock_status" class="form-select" style="width:auto;">
                <option value="">All Stock</option>
                <option value="in_stock" <?= $stockStatus === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                <option value="out_of_stock" <?= $stockStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                <option value="on_backorder" <?= $stockStatus === 'on_backorder' ? 'selected' : '' ?>>On Backorder</option>
                <option value="low" <?= $stockStatus === 'low' ? 'selected' : '' ?>>Low Stock (&lt;10)</option>
            </select>
            <button type="submit" class="btn btn-admin-outline"><i class="fas fa-filter me-1"></i>Filter</button>
            <?php if ($search !== '' || $categoryId > 0 || $stockStatus !== ''): ?>
                <a href="<?= ADMIN_URL ?>/pages/products/index.php" class="btn btn-admin-outline"><i class="fas fa-times me-1"></i>Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Image</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <p class="mt-2">No products found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['primary_image']): ?>
                                        <img src="<?= SITE_URL ?>/<?= $product['primary_image'] ?>" alt="<?= sanitizeInput($product['name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                                    <?php else: ?>
                                        <div style="width:48px;height:48px;border-radius:6px;background:var(--admin-bg);display:flex;align-items:center;justify-content:center;color:var(--admin-muted);">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/pages/products/edit.php?id=<?= $product['id'] ?>" class="fw-medium"><?= sanitizeInput($product['name']) ?></a>
                                    <?php if ($product['category_name']): ?>
                                        <br><small class="text-muted"><?= sanitizeInput($product['category_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= sanitizeInput($product['sku']) ?></code></td>
                                <td>
                                    <?php if ($product['sale_price'] > 0): ?>
                                        <span class="text-decoration-line-through text-muted"><?= formatPrice($product['regular_price']) ?></span>
                                        <br><strong class="text-danger"><?= formatPrice($product['sale_price']) ?></strong>
                                    <?php else: ?>
                                        <strong><?= formatPrice($product['regular_price']) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <span class="badge bg-success"><?= $product['stock_quantity'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">0</span>
                                    <?php endif; ?>
                                    <br><small class="text-muted"><?= ucfirst(str_replace('_', ' ', $product['stock_status'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="<?= ADMIN_URL ?>/pages/products/toggle-featured.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $product['is_featured'] ? 'btn-admin-warning' : 'btn-admin-outline' ?>" title="<?= $product['is_featured'] ? 'Remove from featured' : 'Set as featured' ?>">
                                            <i class="fas fa-star <?= $product['is_featured'] ? '' : 'far' ?>"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= ADMIN_URL ?>/pages/products/edit.php?id=<?= $product['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/pages/products/delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
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

<div class="mt-4 d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= min($total, $offset + 1) ?>-<?= min($total, $offset + $perPage) ?> of <?= $total ?> products</small>
    <?= paginate($total, $page, $perPage, $paginationUrl) ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
