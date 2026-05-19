<?php
require_once __DIR__ . '/../includes/session.php';
requireLogin();

$pageTitle = 'My Dashboard';
$user = getCurrentUser();

$recentOrders = getOrders($_SESSION['user_id'], 5);

$db = db();
$wishlistCountStmt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = :user_id");
$wishlistCountStmt->execute(['user_id' => $_SESSION['user_id']]);
$wishlistCount = $wishlistCountStmt->fetchColumn();

$orderCountStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
$orderCountStmt->execute(['user_id' => $_SESSION['user_id']]);
$orderCount = $orderCountStmt->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<section class="account-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="account-sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <h5><?= sanitizeInput($user['full_name']) ?></h5>
                        <small class="text-muted"><?= sanitizeInput($user['email']) ?></small>
                    </div>
                    <nav class="account-nav">
                        <a href="<?= SITE_URL ?>/account/dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
                        <a href="<?= SITE_URL ?>/account/orders.php"><i class="fas fa-box"></i> Orders</a>
                        <a href="<?= SITE_URL ?>/account/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="<?= SITE_URL ?>/account/profile.php"><i class="fas fa-user-cog"></i> Profile</a>
                        <a href="<?= SITE_URL ?>/account/profile.php?tab=password"><i class="fas fa-lock"></i> Change Password</a>
                        <hr>
                        <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9">
                <div class="account-content">
                    <h4 class="mb-4">Welcome, <?= sanitizeInput($user['full_name']) ?>!</h4>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm p-3 text-center">
                                <div class="fs-3 text-primary mb-2"><i class="fas fa-box"></i></div>
                                <h3 class="mb-0"><?= $orderCount ?></h3>
                                <small class="text-muted">Total Orders</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm p-3 text-center">
                                <div class="fs-3 text-danger mb-2"><i class="fas fa-heart"></i></div>
                                <h3 class="mb-0"><?= $wishlistCount ?></h3>
                                <small class="text-muted">Wishlist Items</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm p-3 text-center">
                                <div class="fs-3 text-success mb-2"><i class="fas fa-calendar-alt"></i></div>
                                <h3 class="mb-0"><?= date('M Y', strtotime($user['created_at'])) ?></h3>
                                <small class="text-muted">Member Since</small>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Orders</h5>
                            <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentOrders)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No orders yet.</p>
                                    <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td><a href="<?= SITE_URL ?>/account/order-detail.php?order=<?= $order['order_number'] ?>"><?= sanitizeInput($order['order_number']) ?></a></td>
                                                    <td><?= formatDate($order['created_at'], 'd M Y') ?></td>
                                                    <td>
                                                        <?php
                                                        $itemStmt = $db->prepare("SELECT SUM(quantity) FROM order_items WHERE order_id = :oid");
                                                        $itemStmt->execute(['oid' => $order['id']]);
                                                        echo $itemStmt->fetchColumn();
                                                        ?>
                                                    </td>
                                                    <td><?= formatPrice($order['total']) ?></td>
                                                    <td>
                                                        <?php
                                                        $badgeClasses = [
                                                            'pending' => 'bg-warning',
                                                            'processing' => 'bg-info',
                                                            'shipped' => 'bg-primary',
                                                            'delivered' => 'bg-success',
                                                            'cancelled' => 'bg-danger',
                                                            'refunded' => 'bg-secondary'
                                                        ];
                                                        $badge = $badgeClasses[$order['order_status']] ?? 'bg-secondary';
                                                        ?>
                                                        <span class="badge <?= $badge ?>"><?= ucfirst($order['order_status']) ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
