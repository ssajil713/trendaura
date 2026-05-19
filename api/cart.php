<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

$userId = $_SESSION['user_id'] ?? null;
$sessionId = session_id();

if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']); exit;
    }

    $db = db();

    // Verify product exists and is in stock
    $stmt = $db->prepare("SELECT id, regular_price, sale_price, stock_quantity, stock_status FROM products WHERE id = :id AND is_active = 1");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found']); exit;
    }

    $price = $product['sale_price'] ?: $product['regular_price'];

    // Get or create cart
    if ($userId) {
        $stmt = $db->prepare("SELECT id FROM carts WHERE user_id = :user_id ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->prepare("SELECT id FROM carts WHERE session_id = :session_id ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute(['session_id' => $sessionId]);
    }
    $cart = $stmt->fetch();

    if (!$cart) {
        $stmt = $db->prepare("INSERT INTO carts (user_id, session_id) VALUES (:user_id, :session_id)");
        $stmt->execute(['user_id' => $userId, 'session_id' => $sessionId]);
        $cartId = $db->lastInsertId();
    } else {
        $cartId = $cart['id'];
    }

    // Check if item already in cart
    $stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id");
    $stmt->execute(['cart_id' => $cartId, 'product_id' => $productId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        $stmt = $db->prepare("UPDATE cart_items SET quantity = :quantity, total_price = :total_price WHERE id = :id");
        $stmt->execute([
            'quantity' => $newQty,
            'total_price' => $newQty * $price,
            'id' => $existing['id']
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, unit_price, total_price) VALUES (:cart_id, :product_id, :quantity, :unit_price, :total_price)");
        $stmt->execute([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $price,
            'total_price' => $quantity * $price
        ]);
    }

    // Update cart totals
    $stmt = $db->prepare("SELECT SUM(total_price) as subtotal FROM cart_items WHERE cart_id = :cart_id");
    $stmt->execute(['cart_id' => $cartId]);
    $subtotal = $stmt->fetch()['subtotal'] ?? 0;
    $shipping = $subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_CHARGE;
    $tax = ($subtotal * TAX_RATE) / 100;
    $total = $subtotal + $tax + $shipping;

    $stmt = $db->prepare("UPDATE carts SET subtotal = :subtotal, tax = :tax, shipping = :shipping, total = :total WHERE id = :id");
    $stmt->execute(['subtotal' => $subtotal, 'tax' => $tax, 'shipping' => $shipping, 'total' => $total, 'id' => $cartId]);

    // Get cart count
    $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = :cart_id");
    $stmt->execute(['cart_id' => $cartId]);
    $cartCount = $stmt->fetch()['count'] ?? 0;

    echo json_encode(['success' => true, 'cart_count' => $cartCount]);

} elseif ($action === 'update') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']); exit;
    }

    $db = db();
    $stmt = $db->prepare("SELECT ci.id, ci.cart_id, ci.unit_price FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE ci.id = :id AND (c.user_id = :user_id OR c.session_id = :session_id)");
    $stmt->execute(['id' => $itemId, 'user_id' => $userId, 'session_id' => $sessionId]);
    $item = $stmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item not found']); exit;
    }

    $unitPrice = $item['unit_price'];
    $totalPrice = $unitPrice * $quantity;

    $stmt = $db->prepare("UPDATE cart_items SET quantity = :quantity, total_price = :total_price WHERE id = :id");
    $stmt->execute(['quantity' => $quantity, 'total_price' => $totalPrice, 'id' => $itemId]);

    // Update cart totals
    $cartId = $item['cart_id'];
    $stmt = $db->prepare("SELECT SUM(total_price) as subtotal FROM cart_items WHERE cart_id = :cart_id");
    $stmt->execute(['cart_id' => $cartId]);
    $subtotal = $stmt->fetch()['subtotal'] ?? 0;
    $shipping = $subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_CHARGE;
    $tax = ($subtotal * TAX_RATE) / 100;
    $total = $subtotal + $tax + $shipping;

    // Check for coupon
    $stmt = $db->prepare("SELECT coupon_id, coupon_discount FROM carts WHERE id = :id");
    $stmt->execute(['id' => $cartId]);
    $cartData = $stmt->fetch();
    $discount = $cartData['coupon_discount'] ?? 0;
    $total -= $discount;
    if ($total < 0) $total = 0;

    $stmt = $db->prepare("UPDATE carts SET subtotal = :subtotal, tax = :tax, shipping = :shipping, coupon_discount = :discount, total = :total WHERE id = :id");
    $stmt->execute(['subtotal' => $subtotal, 'tax' => $tax, 'shipping' => $shipping, 'discount' => $discount, 'total' => $total, 'id' => $cartId]);

    // Cart count
    $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = :cart_id");
    $stmt->execute(['cart_id' => $cartId]);
    $cartCount = $stmt->fetch()['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'item_total' => formatPrice($totalPrice),
        'subtotal' => formatPrice($subtotal),
        'total' => formatPrice($total),
        'cart_count' => $cartCount,
        'shipping' => $shipping,
        'shipping_display' => $shipping > 0 ? formatPrice($shipping) : 'Free',
        'discount' => $discount,
        'discount_display' => $discount > 0 ? '- ' . formatPrice($discount) : '₹ 0'
    ]);

} elseif ($action === 'remove') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']); exit;
    }

    $db = db();
    $stmt = $db->prepare("SELECT cart_id FROM cart_items WHERE id = :id");
    $stmt->execute(['id' => $itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item not found']); exit;
    }

    $cartId = $item['cart_id'];
    $stmt = $db->prepare("DELETE FROM cart_items WHERE id = :id");
    $stmt->execute(['id' => $itemId]);

    // Recalculate
    $stmt = $db->prepare("SELECT SUM(total_price) as subtotal, SUM(quantity) as count FROM cart_items WHERE cart_id = :cart_id");
    $stmt->execute(['cart_id' => $cartId]);
    $data = $stmt->fetch();
    $subtotal = $data['subtotal'] ?? 0;
    $cartCount = $data['count'] ?? 0;

    $shipping = $subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_CHARGE;
    $tax = ($subtotal * TAX_RATE) / 100;
    $total = $subtotal + $tax + $shipping;

    // Check for coupon
    $stmt = $db->prepare("SELECT coupon_discount FROM carts WHERE id = :id");
    $stmt->execute(['id' => $cartId]);
    $cartData = $stmt->fetch();
    $discount = $cartData['coupon_discount'] ?? 0;
    $total -= $discount;
    if ($total < 0) $total = 0;

    $stmt = $db->prepare("UPDATE carts SET subtotal = :subtotal, tax = :tax, shipping = :shipping, total = :total WHERE id = :id");
    $stmt->execute(['subtotal' => $subtotal, 'tax' => $tax, 'shipping' => $shipping, 'total' => $total, 'id' => $cartId]);

    echo json_encode([
        'success' => true,
        'subtotal' => formatPrice($subtotal),
        'total' => formatPrice($total),
        'cart_count' => $cartCount,
        'cart_empty' => $cartCount == 0
    ]);

} else {
    echo json_encode($response);
}
