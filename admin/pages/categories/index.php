<?php
$pageTitle = 'Categories';
include __DIR__ . '/../../includes/header.php';

$db = db();

$stmt = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM products WHERE category_id = c.id) as products_count
    FROM categories c
    ORDER BY c.parent_id IS NULL DESC, c.sort_order ASC, c.name ASC
");
$categories = $stmt->fetchAll();

$parents = [];
$childrenByParent = [];
foreach ($categories as $cat) {
    if ($cat['parent_id'] === null) {
        $parents[] = $cat;
    } else {
        $childrenByParent[$cat['parent_id']][] = $cat;
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <a href="<?= ADMIN_URL ?>/pages/categories/add.php" class="btn btn-admin-primary">
            <i class="fas fa-plus me-1"></i> Add Category
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
                        <th>Parent Category</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Products</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parents) && empty($childrenByParent)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-list"></i>
                                    <p class="mt-2">No categories found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parents as $cat): ?>
                            <tr>
                                <td><strong><?= sanitizeInput($cat['name']) ?></strong></td>
                                <td><code><?= sanitizeInput($cat['slug']) ?></code></td>
                                <td><span class="text-muted">&mdash;</span></td>
                                <td>
                                    <?php if ($cat['image']): ?>
                                        <img src="<?= SITE_URL ?>/<?= $cat['image'] ?>" alt="<?= sanitizeInput($cat['name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                                    <?php else: ?>
                                        <div style="width:48px;height:48px;border-radius:6px;background:var(--admin-bg);display:flex;align-items:center;justify-content:center;color:var(--admin-muted);">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="<?= ADMIN_URL ?>/pages/categories/toggle-status.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $cat['is_active'] ? 'btn-admin-success' : 'btn-admin-outline' ?>" title="<?= $cat['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?= (int)$cat['products_count'] ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= ADMIN_URL ?>/pages/categories/edit.php?id=<?= $cat['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/pages/categories/delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-admin-sm btn-admin-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php if (isset($childrenByParent[$cat['id']])): ?>
                                <?php foreach ($childrenByParent[$cat['id']] as $child): ?>
                                    <tr>
                                        <td style="padding-left:40px;">
                                            <i class="fas fa-level-indent-alt text-muted me-2" style="font-size:0.7rem;"></i>
                                            <?= sanitizeInput($child['name']) ?>
                                        </td>
                                        <td><code><?= sanitizeInput($child['slug']) ?></code></td>
                                        <td><?= sanitizeInput($cat['name']) ?></td>
                                        <td>
                                            <?php if ($child['image']): ?>
                                                <img src="<?= SITE_URL ?>/<?= $child['image'] ?>" alt="<?= sanitizeInput($child['name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                                            <?php else: ?>
                                                <div style="width:48px;height:48px;border-radius:6px;background:var(--admin-bg);display:flex;align-items:center;justify-content:center;color:var(--admin-muted);">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?= ADMIN_URL ?>/pages/categories/toggle-status.php" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= $child['id'] ?>">
                                                <button type="submit" class="btn btn-sm <?= $child['is_active'] ? 'btn-admin-success' : 'btn-admin-outline' ?>" title="<?= $child['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                    <?= $child['is_active'] ? 'Active' : 'Inactive' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td><?= (int)$child['products_count'] ?></td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="<?= ADMIN_URL ?>/pages/categories/edit.php?id=<?= $child['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" action="<?= ADMIN_URL ?>/pages/categories/delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= $child['id'] ?>">
                                                    <button type="submit" class="btn btn-admin-sm btn-admin-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
