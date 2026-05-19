<?php
$pageTitle = 'Brands';
include __DIR__ . '/../../includes/header.php';

$db = db();

$stmt = $db->query("
    SELECT b.*,
        (SELECT COUNT(*) FROM products WHERE brand_id = b.id) as products_count
    FROM brands b
    ORDER BY b.name ASC
");
$brands = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <a href="<?= ADMIN_URL ?>/pages/brands/add.php" class="btn btn-admin-primary">
            <i class="fas fa-plus me-1"></i> Add Brand
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Logo</th>
                        <th>Status</th>
                        <th>Products</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($brands)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-tag"></i>
                                    <p class="mt-2">No brands found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/pages/brands/edit.php?id=<?= $brand['id'] ?>" class="fw-medium"><?= sanitizeInput($brand['name']) ?></a>
                                </td>
                                <td><code><?= sanitizeInput($brand['slug']) ?></code></td>
                                <td>
                                    <?php if ($brand['logo']): ?>
                                        <img src="<?= SITE_URL ?>/<?= $brand['logo'] ?>" alt="<?= sanitizeInput($brand['name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                                    <?php else: ?>
                                        <div style="width:48px;height:48px;border-radius:6px;background:var(--admin-bg);display:flex;align-items:center;justify-content:center;color:var(--admin-muted);">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="<?= ADMIN_URL ?>/pages/brands/toggle-status.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $brand['is_active'] ? 'btn-admin-success' : 'btn-admin-outline' ?>" title="<?= $brand['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <?= $brand['is_active'] ? 'Active' : 'Inactive' ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?= (int)$brand['products_count'] ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= ADMIN_URL ?>/pages/brands/edit.php?id=<?= $brand['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/pages/brands/delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this brand?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $brand['id'] ?>">
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
