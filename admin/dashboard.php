<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';

$db = db();

// Stats
$totalRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE order_status NOT IN ('cancelled','refunded')")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$lowStockCount = $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10 AND is_active = 1")->fetchColumn();

// Recent orders
$recentOrders = $db->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// Monthly sales for chart (last 6 months)
$stmt = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as revenue, COUNT(*) as orders_count
    FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND order_status NOT IN ('cancelled','refunded')
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC
");
$monthlyData = $stmt->fetchAll();

$months = [];
$revenues = [];
foreach ($monthlyData as $row) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $revenues[] = (float)$row['revenue'];
}

// Top selling products
$topProducts = $db->query("
    SELECT p.name, p.slug, p.total_sales, p.stock_quantity, p.regular_price, p.sale_price,
    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
    FROM products p WHERE p.is_active = 1 ORDER BY p.total_sales DESC LIMIT 5
")->fetchAll();
?>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card stat-revenue">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-detail">
                <h3><?= formatPrice($totalRevenue) ?></h3>
                <span>Total Revenue</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card stat-orders">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-detail">
                <h3><?= $totalOrders ?></h3>
                <span>Total Orders</span>
                <?php if ($pendingOrders > 0): ?><small class="text-warning d-block"><?= $pendingOrders ?> pending</small><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card stat-products">
            <div class="stat-icon"><i class="fas fa-box"></i></div>
            <div class="stat-detail">
                <h3><?= $totalProducts ?></h3>
                <span>Total Products</span>
                <?php if ($lowStockCount > 0): ?><small class="text-danger d-block"><?= $lowStockCount ?> low stock</small><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card stat-users">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-detail">
                <h3><?= $totalUsers ?></h3>
                <span>Registered Users</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5>Revenue Analytics</h5>
            </div>
            <div class="admin-card-body">
                <canvas id="revenueChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5>Top Selling Products</h5>
            </div>
            <div class="admin-card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($topProducts as $i => $p): ?>
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span class="badge bg-primary rounded-circle"><?= $i + 1 ?></span>
                        <div class="flex-grow-1">
                            <strong class="d-block small"><?= sanitizeInput($p['name']) ?></strong>
                            <small class="text-muted"><?= $p['total_sales'] ?> sales</small>
                        </div>
                        <span class="fw-bold small"><?= formatPrice($p['sale_price'] ?: $p['regular_price']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-xl-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5>Recent Orders</h5>
                <a href="<?= ADMIN_URL ?>/pages/orders/index.php" class="btn btn-sm btn-admin-primary">View All</a>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><a href="<?= ADMIN_URL ?>/pages/orders/view.php?id=<?= $order['id'] ?>" class="fw-medium">#<?= $order['order_number'] ?></a></td>
                                <td><?= sanitizeInput($order['full_name']) ?></td>
                                <td><?= formatPrice($order['total']) ?></td>
                                <td><span class="order-status <?= $order['order_status'] ?>"><?= ucfirst($order['order_status']) ?></span></td>
                                <td class="text-muted small"><?= formatDate($order['created_at'], 'd M Y') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5>Low Stock Alerts</h5>
                <a href="<?= ADMIN_URL ?>/pages/products/index.php?stock=low" class="btn btn-sm btn-admin-primary">View All</a>
            </div>
            <div class="admin-card-body p-0">
                <?php
                $lowStock = $db->query("SELECT id, name, slug, sku, stock_quantity, regular_price, sale_price FROM products WHERE stock_quantity < 10 AND is_active = 1 ORDER BY stock_quantity ASC LIMIT 5")->fetchAll();
                ?>
                <?php if (empty($lowStock)): ?>
                <div class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success me-2"></i>All products are well-stocked</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($lowStock as $p): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <strong class="d-block small"><?= sanitizeInput($p['name']) ?></strong>
                            <small class="text-muted">SKU: <?= $p['sku'] ?></small>
                        </div>
                        <span class="badge bg-<?= $p['stock_quantity'] <= 0 ? 'danger' : 'warning' ?> rounded-pill">
                            <?= $p['stock_quantity'] ?> left
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const revenueMonths = <?= json_encode($months) ?>;
const revenueData = <?= json_encode($revenues) ?>;
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
