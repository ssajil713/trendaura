<?php
$pageTitle = 'Reviews';
include __DIR__ . '/../../includes/header.php';

$db = db();

$stmt = $db->query("
    SELECT r.*, p.name as product_name, u.full_name as user_name, u.email as user_email
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Title</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-star"></i>
                                    <p class="mt-2">No reviews found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/pages/products/edit.php?id=<?= $review['product_id'] ?>" class="fw-medium">
                                        <?= sanitizeInput($review['product_name'] ?: '(deleted)') ?>
                                    </a>
                                </td>
                                <td>
                                    <div><?= sanitizeInput($review['user_name'] ?: '(deleted)') ?></div>
                                    <small class="text-muted"><?= sanitizeInput($review['user_email']) ?></small>
                                </td>
                                <td>
                                    <span class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o text-muted' ?>" style="font-size:0.75rem;"></i>
                                        <?php endfor; ?>
                                    </span>
                                </td>
                                <td><?= sanitizeInput($review['title'] ?: '-') ?></td>
                                <td><small><?= sanitizeInput(truncateText($review['comment'], 60)) ?></small></td>
                                <td><small><?= formatDate($review['created_at'], 'd M Y') ?></small></td>
                                <td>
                                    <?php if ($review['is_approved']): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <form method="POST" action="<?= ADMIN_URL ?>/pages/reviews/approve.php" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                            <?php if ($review['is_approved']): ?>
                                                <button type="submit" class="btn btn-admin-sm btn-admin-warning" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-admin-sm btn-admin-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
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
