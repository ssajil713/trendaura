<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

$db = db();
$stmt = $db->prepare("
    SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
        a1.full_name as ship_name, a1.phone as ship_phone, a1.street_address as ship_address,
        a1.city as ship_city, a1.state as ship_state, a1.postal_code as ship_zip,
        a2.full_name as bill_name, a2.phone as bill_phone, a2.street_address as bill_address,
        a2.city as bill_city, a2.state as bill_state, a2.postal_code as bill_zip
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a1 ON o.shipping_address_id = a1.id
    LEFT JOIN addresses a2 ON o.billing_address_id = a2.id
    WHERE o.id = :id
");
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . ADMIN_URL . '/pages/orders/index.php');
    exit;
}

$orderItems = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$orderItems->execute(['order_id' => $id]);
$orderItems = $orderItems->fetchAll();

$storeName = getSetting('site_name', SITE_NAME);
$storeAddress = getSetting('site_address', '');
$storePhone = getSetting('site_phone', '');
$storeEmail = getSetting('site_email', SITE_EMAIL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= sanitizeInput($order['order_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #333;
            padding: 40px;
        }
        .invoice-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3436;
        }
        .invoice-meta dt {
            color: #636e72;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-meta dd {
            font-weight: 600;
            margin-bottom: 8px;
        }
        .table-invoice th {
            background: #f8f9fa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom-width: 1px;
        }
        .table-invoice td, .table-invoice th {
            padding: 12px;
            vertical-align: middle;
        }
        .total-row td {
            border-top: 2px solid #dee2e6;
            font-weight: 600;
        }
        .grand-total td {
            font-size: 18px;
            font-weight: 700;
            color: #2d3436;
        }
        .footer-note {
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
            margin-top: 40px;
            font-size: 13px;
            color: #636e72;
        }
        @media print {
            body { padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print mb-4">
        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        <button class="btn btn-secondary" onclick="window.close()">Close</button>
    </div>

    <div class="invoice-header d-flex justify-content-between align-items-start">
        <div>
            <h1 class="invoice-title"><?= sanitizeInput($storeName) ?></h1>
            <p class="mb-1"><?= nl2br(sanitizeInput($storeAddress)) ?></p>
            <p class="mb-1">Phone: <?= sanitizeInput($storePhone) ?></p>
            <p class="mb-0">Email: <?= sanitizeInput($storeEmail) ?></p>
        </div>
        <div class="text-end">
            <h2 class="invoice-title">INVOICE</h2>
            <p class="mb-1"><strong>Invoice #:</strong> <?= sanitizeInput($order['invoice_number'] ?: $order['order_number']) ?></p>
            <p class="mb-1"><strong>Order #:</strong> <?= sanitizeInput($order['order_number']) ?></p>
            <p class="mb-0"><strong>Date:</strong> <?= formatDate($order['created_at'], 'd M Y') ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-sm-6">
            <dl class="invoice-meta">
                <dt>Bill To</dt>
                <dd><?= sanitizeInput($order['bill_name']) ?></dd>
                <dd><?= sanitizeInput($order['bill_phone']) ?></dd>
                <dd><?= sanitizeInput($order['bill_address']) ?></dd>
                <dd><?= sanitizeInput($order['bill_city']) ?>, <?= sanitizeInput($order['bill_state']) ?> <?= sanitizeInput($order['bill_zip']) ?></dd>
            </dl>
        </div>
        <div class="col-sm-6">
            <dl class="invoice-meta">
                <dt>Ship To</dt>
                <dd><?= sanitizeInput($order['ship_name']) ?></dd>
                <dd><?= sanitizeInput($order['ship_phone']) ?></dd>
                <dd><?= sanitizeInput($order['ship_address']) ?></dd>
                <dd><?= sanitizeInput($order['ship_city']) ?>, <?= sanitizeInput($order['ship_state']) ?> <?= sanitizeInput($order['ship_zip']) ?></dd>
            </dl>
        </div>
    </div>

    <table class="table table-invoice table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= sanitizeInput($item['product_name']) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end"><?= formatPrice($item['unit_price']) ?></td>
                    <td class="text-end"><?= formatPrice($item['total_price']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" rowspan="4"></td>
                <td class="text-end">Subtotal</td>
                <td class="text-end"><?= formatPrice($order['subtotal']) ?></td>
            </tr>
            <tr>
                <td class="text-end">Shipping</td>
                <td class="text-end"><?= $order['shipping'] > 0 ? formatPrice($order['shipping']) : 'Free' ?></td>
            </tr>
            <?php if ($order['coupon_discount'] > 0): ?>
            <tr>
                <td class="text-end">Discount</td>
                <td class="text-end text-danger">-<?= formatPrice($order['coupon_discount']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="text-end">Tax</td>
                <td class="text-end"><?= formatPrice($order['tax']) ?></td>
            </tr>
            <tr class="grand-total">
                <td colspan="3"></td>
                <td class="text-end">Total</td>
                <td class="text-end"><?= formatPrice($order['total']) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="row mt-4">
        <div class="col-sm-6">
            <dl class="invoice-meta">
                <dt>Payment Method</dt>
                <dd><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></dd>
                <dt>Payment Status</dt>
                <dd><?= ucfirst($order['payment_status']) ?></dd>
                <dt>Order Status</dt>
                <dd><?= ucfirst($order['order_status']) ?></dd>
            </dl>
        </div>
        <?php if ($order['notes']): ?>
        <div class="col-sm-6">
            <dl class="invoice-meta">
                <dt>Notes</dt>
                <dd><?= nl2br(sanitizeInput($order['notes'])) ?></dd>
            </dl>
        </div>
        <?php endif; ?>
    </div>

    <div class="footer-note text-center">
        <p class="mb-0">Thank you for your business!</p>
        <small><?= sanitizeInput($storeName) ?> | <?= sanitizeInput($storeEmail) ?> | <?= sanitizeInput($storePhone) ?></small>
    </div>
</body>
</html>
<?php $db = null; ?>
