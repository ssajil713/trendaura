<?php
$pageTitle = 'Order Details';
include __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setAlert('error', 'Invalid order ID.');
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

$db = db();
$stmt = $db->prepare("
    SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
        a1.full_name as ship_name, a1.phone as ship_phone, a1.street_address as ship_address,
        a1.city as ship_city, a1.state as ship_state, a1.postal_code as ship_zip, a1.country as ship_country,
        a2.full_name as bill_name, a2.phone as bill_phone, a2.street_address as bill_address,
        a2.city as bill_city, a2.state as bill_state, a2.postal_code as bill_zip, a2.country as bill_country
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a1 ON o.shipping_address_id = a1.id
    LEFT JOIN addresses a2 ON o.billing_address_id = a2.id
    WHERE o.id = :id
");
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    setAlert('error', 'Order not found.');
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

$orderItems = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$orderItems->execute(['order_id' => $id]);
$orderItems = $orderItems->fetchAll();

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

$orderStatuses = ['pending','processing','shipped','delivered','cancelled','refunded'];
$paymentStatuses = ['pending','paid','failed','refunded'];
?>

<a href="<?= ADMIN_URL ?>/pages/orders/index.php" class="btn btn-admin-outline mb-3"><i class="fas fa-arrow-left me-1"></i>Back to Orders</a>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="mb-0">Order #<?= sanitizeInput($order['order_number']) ?></h5>
                <small class="text-muted">Placed on <?= formatDate($order['created_at']) ?></small>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Image</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?= sanitizeInput($item['product_name']) ?></td>
                                    <td>
                                        <?php if ($item['product_image']): ?>
                                            <img src="<?= SITE_URL ?>/<?= $item['product_image'] ?>" alt="<?= sanitizeInput($item['product_name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                                        <?php else: ?>
                                            <div style="width:48px;height:48px;border-radius:6px;background:var(--admin-bg);display:flex;align-items:center;justify-content:center;color:var(--admin-muted);"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= formatPrice($item['unit_price']) ?></td>
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
                <div class="admin-card h-100">
                    <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-truck me-2 text-primary"></i>Shipping Address</h6></div>
                    <div class="admin-card-body">
                        <p class="mb-1 fw-medium"><?= sanitizeInput($order['ship_name']) ?></p>
                        <p class="mb-1"><?= sanitizeInput($order['ship_phone']) ?></p>
                        <p class="mb-0"><?= nl2br(sanitizeInput($order['ship_address'])) ?><br>
                        <?= sanitizeInput($order['ship_city']) ?>, <?= sanitizeInput($order['ship_state']) ?> <?= sanitizeInput($order['ship_zip']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card h-100">
                    <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-file-invoice me-2 text-success"></i>Billing Address</h6></div>
                    <div class="admin-card-body">
                        <p class="mb-1 fw-medium"><?= sanitizeInput($order['bill_name']) ?></p>
                        <p class="mb-1"><?= sanitizeInput($order['bill_phone']) ?></p>
                        <p class="mb-0"><?= nl2br(sanitizeInput($order['bill_address'])) ?><br>
                        <?= sanitizeInput($order['bill_city']) ?>, <?= sanitizeInput($order['bill_state']) ?> <?= sanitizeInput($order['bill_zip']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Summary</h6></div>
            <div class="admin-card-body">
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

        <div class="admin-card mb-4">
            <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Info</h6></div>
            <div class="admin-card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Method</span>
                    <span class="fw-medium"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Status</span>
                    <span class="badge <?= $paymentBadgeClasses[$order['payment_status']] ?? 'bg-secondary' ?>"><?= ucfirst($order['payment_status']) ?></span>
                </div>
                <?php if ($order['paid_at']): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Paid At</span>
                    <span><small><?= formatDate($order['paid_at']) ?></small></span>
                </div>
                <?php endif; ?>
                <?php if ($order['tracking_number']): ?>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Tracking</span>
                    <span><small><?= sanitizeInput($order['tracking_number']) ?></small></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-card mb-4">
            <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-user me-2"></i>Customer</h6></div>
            <div class="admin-card-body">
                <p class="mb-1 fw-medium"><?= sanitizeInput($order['customer_name']) ?></p>
                <p class="mb-1"><?= sanitizeInput($order['customer_email']) ?></p>
                <p class="mb-0"><?= sanitizeInput($order['customer_phone']) ?></p>
            </div>
        </div>

        <div class="admin-card mb-4">
            <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-edit me-2"></i>Update Order Status</h6></div>
            <div class="admin-card-body">
                <form method="POST" action="<?= ADMIN_URL ?>/pages/orders/update-status.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Order Status</label>
                        <select name="status" class="form-select">
                            <?php foreach ($orderStatuses as $os): ?>
                                <option value="<?= $os ?>" <?= $order['order_status'] === $os ? 'selected' : '' ?>><?= ucfirst($os) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="type" value="order_status">
                    <button type="submit" class="btn btn-admin-primary w-100">Update Order Status</button>
                </form>
            </div>
        </div>

        <div class="admin-card mb-4">
            <div class="admin-card-header"><h6 class="mb-0"><i class="fas fa-credit-card me-2"></i>Update Payment Status</h6></div>
            <div class="admin-card-body">
                <form method="POST" action="<?= ADMIN_URL ?>/pages/orders/update-status.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Payment Status</label>
                        <select name="status" class="form-select">
                            <?php foreach ($paymentStatuses as $ps): ?>
                                <option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="type" value="payment_status">
                    <button type="submit" class="btn btn-admin-primary w-100">Update Payment Status</button>
                </form>
            </div>
        </div>

        <a href="<?= ADMIN_URL ?>/pages/orders/invoice.php?id=<?= $order['id'] ?>" class="btn btn-admin-outline w-100" target="_blank">
            <i class="fas fa-print me-1"></i> Print Invoice
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
