<?php
require_once __DIR__ . '/../includes/session.php';
requireLogin();

$orderNumber = $_GET['order'] ?? '';
if (empty($orderNumber)) {
    header('Location: ' . SITE_URL . '/account/orders.php');
    exit;
}

$order = getOrderDetails($orderNumber);

if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    setAlert('error', 'Order not found or access denied.');
    header('Location: ' . SITE_URL . '/account/orders.php');
    exit;
}

$pageTitle = 'Order #' . $orderNumber;
$user = getCurrentUser();
$orderItems = getOrderItems($order['id']);

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
                    <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Orders</a>

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="mb-1">Order #<?= sanitizeInput($order['order_number']) ?></h4>
                            <small class="text-muted">Placed on <?= formatDate($order['created_at']) ?></small>
                        </div>
                        <div>
                            <span class="badge <?= $badgeClasses[$order['order_status']] ?? 'bg-secondary' ?> fs-6"><?= ucfirst($order['order_status']) ?></span>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Ordered Items</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?= SITE_URL ?>/<?= $item['product_image'] ?: 'assets/uploads/placeholder.png' ?>"
                                                             alt="<?= sanitizeInput($item['product_name']) ?>"
                                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                                        <div>
                                                            <span class="fw-medium"><?= sanitizeInput($item['product_name']) ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= formatPrice($item['unit_price']) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td><?= formatPrice($item['total_price']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="fas fa-truck text-primary me-2"></i>Shipping Address</h6>
                                    <p class="mb-1"><?= sanitizeInput($order['ship_name']) ?></p>
                                    <p class="mb-1"><?= sanitizeInput($order['ship_phone']) ?></p>
                                    <p class="mb-0"><?= sanitizeInput($order['ship_address']) ?><br>
                                    <?= sanitizeInput($order['ship_city']) ?>, <?= sanitizeInput($order['ship_state']) ?> <?= sanitizeInput($order['ship_zip']) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="fas fa-file-invoice text-success me-2"></i>Billing Address</h6>
                                    <p class="mb-1"><?= sanitizeInput($order['bill_name']) ?></p>
                                    <p class="mb-1"><?= sanitizeInput($order['bill_phone']) ?></p>
                                    <p class="mb-0"><?= sanitizeInput($order['bill_address']) ?><br>
                                    <?= sanitizeInput($order['bill_city']) ?>, <?= sanitizeInput($order['bill_state']) ?> <?= sanitizeInput($order['bill_zip']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">Payment Information</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Payment Method</span>
                                        <span class="fw-medium"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Payment Status</span>
                                        <span class="badge <?= $paymentBadgeClasses[$order['payment_status']] ?? 'bg-secondary' ?>"><?= ucfirst($order['payment_status']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">Order Summary</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Subtotal</span>
                                        <span><?= formatPrice($order['subtotal']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Shipping</span>
                                        <span><?= $order['shipping'] > 0 ? formatPrice($order['shipping']) : 'Free' ?></span>
                                    </div>
                                    <?php if ($order['coupon_discount'] > 0): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Discount</span>
                                            <span class="text-danger">-<?= formatPrice($order['coupon_discount']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Tax</span>
                                        <span><?= formatPrice($order['tax']) ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Total</span>
                                        <span class="fw-bold fs-5 text-primary"><?= formatPrice($order['total']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
