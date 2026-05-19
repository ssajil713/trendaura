<?php
$pageTitle = 'Orders';
include __DIR__ . '/../../includes/header.php';

$db = db();

$where = 'WHERE 1=1';
$params = [];

$status = $_GET['status'] ?? '';
if ($status !== '' && in_array($status, ['pending','processing','shipped','delivered','cancelled','refunded'])) {
    $where .= ' AND o.order_status = :status';
    $params['status'] = $status;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $where .= ' AND o.order_number LIKE :search';
    $params['search'] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$perPage = ADMIN_ITEMS_PER_PAGE;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT o.*, u.full_name as customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $where
    ORDER BY o.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$queryParams = $_GET;
unset($queryParams['page']);
$paginationUrl = '?' . http_build_query($queryParams) . (empty($queryParams) ? '' : '&');

$orderStatuses = ['pending','processing','shipped','delivered','cancelled','refunded'];

$badgeClasses = [
    'pending' => 'bg-warning',
    'processing' => 'bg-info',
    'shipped' => 'bg-primary',
    'delivered' => 'bg-success',
    'cancelled' => 'bg-danger',
    'refunded' => 'bg-secondary'
];

$paymentBadgeClasses = [
    'pending' => 'bg-warning',
    'paid' => 'bg-success',
    'failed' => 'bg-danger',
    'refunded' => 'bg-secondary'
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="admin-search">
            <input type="text" class="form-control" name="search" placeholder="Search by order number..." value="<?= sanitizeInput($search) ?>">
        </div>
        <select name="status" class="form-select" style="width:auto;">
            <option value="">All Status</option>
            <?php foreach ($orderStatuses as $os): ?>
                <option value="<?= $os ?>" <?= $status === $os ? 'selected' : '' ?>><?= ucfirst($os) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-admin-outline"><i class="fas fa-filter me-1"></i>Filter</button>
        <?php if ($search !== '' || $status !== ''): ?>
            <a href="<?= ADMIN_URL ?>/pages/orders/index.php" class="btn btn-admin-outline"><i class="fas fa-times me-1"></i>Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment Method</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Date</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-truck"></i>
                                    <p class="mt-2">No orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $itemStmt = $db->prepare("SELECT COUNT(*) as cnt, SUM(quantity) as qty FROM order_items WHERE order_id = :oid");
                            $itemStmt->execute(['oid' => $order['id']]);
                            $itemData = $itemStmt->fetch();
                            $itemCount = $itemData['cnt'];
                            $itemQty = $itemData['qty'];
                            ?>
                            <tr>
                                <td><a href="<?= ADMIN_URL ?>/pages/orders/view.php?id=<?= $order['id'] ?>" class="fw-medium"><?= sanitizeInput($order['order_number']) ?></a></td>
                                <td><?= sanitizeInput($order['customer_name']) ?></td>
                                <td><?= $itemCount ?> (<?= $itemQty ?> qty)</td>
                                <td><strong><?= formatPrice($order['total']) ?></strong></td>
                                <td><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></td>
                                <td><span class="badge <?= $paymentBadgeClasses[$order['payment_status']] ?? 'bg-secondary' ?>"><?= ucfirst($order['payment_status']) ?></span></td>
                                <td><span class="badge <?= $badgeClasses[$order['order_status']] ?? 'bg-secondary' ?>"><?= ucfirst($order['order_status']) ?></span></td>
                                <td><small><?= formatDate($order['created_at'], 'd M Y') ?></small></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= ADMIN_URL ?>/pages/orders/view.php?id=<?= $order['id'] ?>" class="btn btn-admin-sm btn-admin-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
    <small class="text-muted">Showing <?= min($total, $offset + 1) ?>-<?= min($total, $offset + $perPage) ?> of <?= $total ?> orders</small>
    <?= paginate($total, $page, $perPage, $paginationUrl) ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
