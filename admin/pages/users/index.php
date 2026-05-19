<?php
$pageTitle = 'Users';
include __DIR__ . '/../../includes/header.php';

$db = db();

$where = 'WHERE 1=1';
$params = [];

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $where .= ' AND (u.full_name LIKE :search OR u.email LIKE :search2)';
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$perPage = ADMIN_ITEMS_PER_PAGE;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT u.*,
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as orders_count
    FROM users u
    $where
    ORDER BY u.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

$queryParams = $_GET;
unset($queryParams['page']);
$paginationUrl = '?' . http_build_query($queryParams) . (empty($queryParams) ? '' : '&');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="admin-search">
            <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?= sanitizeInput($search) ?>">
        </div>
        <button type="submit" class="btn btn-admin-outline"><i class="fas fa-search me-1"></i>Search</button>
        <?php if ($search !== ''): ?>
            <a href="<?= ADMIN_URL ?>/pages/users/index.php" class="btn btn-admin-outline"><i class="fas fa-times me-1"></i>Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p class="mt-2">No users found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/pages/users/view.php?id=<?= $user['id'] ?>" class="fw-medium"><?= sanitizeInput($user['full_name']) ?></a>
                                </td>
                                <td><?= sanitizeInput($user['email']) ?></td>
                                <td><?= sanitizeInput($user['phone'] ?: '-') ?></td>
                                <td><?= (int)$user['orders_count'] ?></td>
                                <td><small><?= formatDate($user['created_at'], 'd M Y') ?></small></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= ADMIN_URL ?>/pages/users/view.php?id=<?= $user['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/pages/users/toggle-status.php" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-admin-sm <?= $user['is_active'] ? 'btn-admin-warning' : 'btn-admin-success' ?>" title="<?= $user['is_active'] ? 'Block' : 'Unblock' ?>">
                                                <i class="fas <?= $user['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
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
    <small class="text-muted">Showing <?= min($total, $offset + 1) ?>-<?= min($total, $offset + $perPage) ?> of <?= $total ?> users</small>
    <?= paginate($total, $page, $perPage, $paginationUrl) ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
