<?php
require_once __DIR__ . '/../includes/session.php';
requireLogin();

$pageTitle = 'My Orders';
$user = getCurrentUser();

$orders = getOrders($_SESSION['user_id']);

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
                        <a href="<?= SITE_URL ?>/account/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                        <a href="<?= SITE_URL ?>/account/orders.php" class="active"><i class="fas fa-box"></i> Orders</a>
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
                    <h4 class="mb-4">My Orders</h4>

                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No orders yet</h5>
                            <p class="text-muted mb-3">Looks like you haven't placed any orders yet.</p>
                            <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order):
                                        $db = db();
                                        $itemStmt = $db->prepare("SELECT SUM(quantity) FROM order_items WHERE order_id = :oid");
                                        $itemStmt->execute(['oid' => $order['id']]);
                                        $itemCount = $itemStmt->fetchColumn();

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
                                        <tr>
                                            <td><strong><?= sanitizeInput($order['order_number']) ?></strong></td>
                                            <td><?= formatDate($order['created_at'], 'd M Y') ?></td>
                                            <td><?= $itemCount ?></td>
                                            <td><?= formatPrice($order['total']) ?></td>
                                            <td><span class="badge <?= $badge ?>"><?= ucfirst($order['order_status']) ?></span></td>
                                            <td>
                                                <a href="<?= SITE_URL ?>/account/order-detail.php?order=<?= $order['order_number'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
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
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
