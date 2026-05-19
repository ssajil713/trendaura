<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';

$code = $_POST['code'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';

if (!verifyCSRFToken($csrf)) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']); exit;
}

$cart = getCart();
if (!$cart) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']); exit;
}

$items = getCartItems($cart['id']);
$subtotal = array_sum(array_column($items, 'total_price'));

$result = validateCoupon($code, $subtotal);

if (!$result['valid']) {
    echo json_encode(['success' => false, 'error' => $result['error']]); exit;
}

// Apply coupon
$db = db();
$discount = $result['discount'];
$shipping = $subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_CHARGE;
$tax = ($subtotal * TAX_RATE) / 100;
$total = $subtotal + $tax + $shipping - $discount;
if ($total < 0) $total = 0;

$stmt = $db->prepare("UPDATE carts SET coupon_id = :coupon_id, coupon_discount = :discount, subtotal = :subtotal, tax = :tax, shipping = :shipping, total = :total WHERE id = :id");
$stmt->execute([
    'coupon_id' => $result['coupon']['id'],
    'discount' => $discount,
    'subtotal' => $subtotal,
    'tax' => $tax,
    'shipping' => $shipping,
    'total' => $total,
    'id' => $cart['id']
]);

// Increment usage
$stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
$stmt->execute(['id' => $result['coupon']['id']]);

echo json_encode([
    'success' => true,
    'discount' => $discount,
    'discount_display' => '- ' . formatPrice($discount),
    'total' => formatPrice($total)
]);
