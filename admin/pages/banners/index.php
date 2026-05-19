<?php
$pageTitle = 'Banners';
include __DIR__ . '/../../includes/header.php';

$db = db();

$stmt = $db->query("
    SELECT * FROM banners
    ORDER BY position ASC, sort_order ASC, created_at DESC
");
$banners = $stmt->fetchAll();

$positionLabels = [
    'hero' => 'Hero',
    'promo' => 'Promo',
    'offer' => 'Offer',
    'sidebar' => 'Sidebar',
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <a href="<?= ADMIN_URL ?>/pages/banners/add.php" class="btn btn-admin-primary">
            <i class="fas fa-plus me-1"></i> Add Banner
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Position</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($banners)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-images"></i>
                                    <p class="mt-2">No banners found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td>
                                    <?php if ($banner['image']): ?>
                                        <img src="<?= SITE_URL ?>/<?= $banner['image'] ?>" alt="<?= sanitizeInput($banner['title']) ?>" style="width:80px;height:48px;object-fit:cover;border-radius:6px;">
                                    <?php else: ?>
                                        <div style="width:80px;height:48px;border-radius:6px;background:var(--admin-bg);display:flex;align-items:center;justify-content:center;color:var(--admin-muted);">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/pages/banners/edit.php?id=<?= $banner['id'] ?>" class="fw-medium"><?= sanitizeInput($banner['title']) ?></a>
                                </td>
                                <td><span class="badge bg-info"><?= $positionLabels[$banner['position']] ?? ucfirst($banner['position']) ?></span></td>
                                <td><?= (int)$banner['sort_order'] ?></td>
                                <td>
                                    <span class="badge <?= $banner['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $banner['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= ADMIN_URL ?>/pages/banners/edit.php?id=<?= $banner['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/pages/banners/delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this banner?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $banner['id'] ?>">
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
