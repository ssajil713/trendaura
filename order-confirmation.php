<?php
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'Order Confirmed';

$orderNumber = $_GET['order'] ?? '';
$order = null;
$orderItems = [];

if (!empty($orderNumber)) {
    $order = getOrderDetails($orderNumber);
    if ($order) {
        $orderItems = getOrderItems($order['id']);
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <?php if (!$order): ?>
        <div class="text-center py-5">
            <i class="fas fa-exclamation-circle text-danger" style="font-size: 4rem;"></i>
            <h3 class="mt-3">Order Not Found</h3>
            <p class="text-muted">The order you are looking for could not be found.</p>
            <a href="<?= SITE_URL ?>" class="btn btn-primary mt-3">Back to Home</a>
        </div>
        <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Thank You for Your Order!</h2>
                    <p class="text-muted">Your order has been placed successfully.</p>
                    <h5 class="fw-bold mt-3">Order Number: <span class="text-primary"><?= sanitizeInput($order['order_number']) ?></span></h5>
                    <p class="text-muted small">Placed on <?= formatDate($order['created_at']) ?></p>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($item['product_image']): ?>
                                                <img src="<?= SITE_URL . '/' . $item['product_image'] ?>" alt="<?= sanitizeInput($item['product_name']) ?>" width="50" height="50" style="object-fit:cover;border-radius:6px;" loading="lazy">
                                                <?php endif; ?>
                                                <span><?= sanitizeInput($item['product_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                        <td class="text-end"><?= formatPrice($item['unit_price']) ?></td>
                                        <td class="text-end fw-bold"><?= formatPrice($item['total_price']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-credit-card me-2"></i>Payment Details</h5>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal</span>
                                    <span><?= formatPrice($order['subtotal']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Shipping</span>
                                    <span><?= $order['shipping'] > 0 ? formatPrice($order['shipping']) : '<span class="text-success">Free</span>' ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Tax</span>
                                    <span><?= formatPrice($order['tax']) ?></span>
                                </div>
                                <?php if ($order['coupon_discount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Discount</span>
                                    <span class="text-success">- <?= formatPrice($order['coupon_discount']) ?></span>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total</strong>
                                    <strong class="h5 mb-0"><?= formatPrice($order['total']) ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-2">
                                    <span class="text-muted">Payment Method</span>
                                    <p class="fw-bold mb-0"><?= sanitizeInput(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></p>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted">Payment Status</span>
                                    <p class="fw-bold mb-0"><?= sanitizeInput(ucwords($order['payment_status'])) ?></p>
                                </div>
                                <div>
                                    <span class="text-muted">Order Status</span>
                                    <p class="fw-bold mb-0"><?= sanitizeInput(ucwords($order['order_status'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-truck me-2"></i>Shipping Address</h5>
                        <p class="mb-1 fw-bold"><?= sanitizeInput($order['ship_name']) ?></p>
                        <p class="mb-1"><?= sanitizeInput($order['ship_phone']) ?></p>
                        <p class="mb-0"><?= sanitizeInput($order['ship_address']) ?></p>
                        <p><?= sanitizeInput($order['ship_city']) ?>, <?= sanitizeInput($order['ship_state']) ?> - <?= sanitizeInput($order['ship_zip']) ?></p>
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-muted mb-3">You will receive an email confirmation shortly.</p>
                    <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-primary me-2">
                        <i class="fas fa-box me-2"></i>View My Orders
                    </a>
                    <a href="<?= SITE_URL ?>/shop.php" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
