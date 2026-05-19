<?php
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'Shopping Cart';
include __DIR__ . '/includes/header.php';

$cart = getCart();
$cartItems = $cart ? getCartItems($cart['id']) : [];
$isEmpty = empty($cartItems);

if (!$isEmpty) {
    $subtotal = array_sum(array_column($cartItems, 'total_price'));
    $shipping = $subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_CHARGE;
    $tax = ($subtotal * TAX_RATE) / 100;
    $discount = (float)($cart['coupon_discount'] ?? 0);
    $total = $subtotal + $shipping + $tax - $discount;
}
?>

<!-- ====== BREADCRUMB ====== -->
<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ====== CART SECTION ====== -->
<section class="cart-section py-4">
    <div class="container">
        <?php if ($isEmpty): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>Your Cart is Empty</h3>
            <p class="text-muted">Looks like you haven't added anything to your cart yet.</p>
            <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary btn-lg mt-2">
                <i class="fas fa-arrow-left me-2"></i>Start Shopping
            </a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title mb-0">Cart Items (<?= count($cartItems) ?>)</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle cart-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item):
                                        $img = $item['image'] ?: 'assets/uploads/placeholder.svg';
                                        $price = $item['sale_price'] ?: $item['regular_price'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= SITE_URL . '/' . $img ?>" alt="<?= sanitizeInput($item['name']) ?>" width="80" height="80" style="object-fit:cover;border-radius:8px;" loading="lazy">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <a href="<?= SITE_URL ?>/product.php?slug=<?= $item['slug'] ?>" class="text-decoration-none text-dark">
                                                            <?= sanitizeInput($item['name']) ?>
                                                        </a>
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= formatPrice($price) ?></td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm text-center cart-qty-input" style="width:70px;margin:0 auto;" value="<?= (int)$item['quantity'] ?>" min="1" data-item-id="<?= $item['id'] ?>">
                                        </td>
                                        <td class="text-center fw-bold"><?= formatPrice($item['total_price']) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-danger btn-remove-cart" data-item-id="<?= $item['id'] ?>" title="Remove Item">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span><?= formatPrice($subtotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping</span>
                            <span><?= $shipping > 0 ? formatPrice($shipping) : '<span class="text-success">Free</span>' ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tax (<?= TAX_RATE ?>%)</span>
                            <span><?= formatPrice($tax) ?></span>
                        </div>
                        <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Coupon Discount</span>
                            <span class="text-success">- <?= formatPrice($discount) ?></span>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong class="h5 mb-0"><?= formatPrice($total) ?></strong>
                        </div>
                        <div class="coupon-section mb-3">
                            <form id="couponForm" method="POST" action="<?= SITE_URL ?>/api/coupon.php">
                                <?= csrfField() ?>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="coupon_code" placeholder="Enter coupon code" value="<?= sanitizeInput($cart['coupon_id'] ? 'Applied' : '') ?>">
                                    <button type="submit" class="btn btn-outline-primary btn-apply-coupon">Apply</button>
                                </div>
                            </form>
                        </div>
                        <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary w-100 btn-lg">
                            <i class="fas fa-lock me-2"></i>Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
$(document).ready(function() {
    $('.cart-qty-input').on('change', function() {
        const itemId = $(this).data('item-id');
        const qty = $(this).val();
        $.ajax({
            url: '<?= SITE_URL ?>/api/cart.php',
            method: 'POST',
            data: { action: 'update', item_id: itemId, quantity: qty, csrf_token: '<?= generateCSRFToken() ?>' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    toastr.error(res.error || 'Failed to update quantity');
                }
            }
        });
    });

    $('.btn-remove-cart').on('click', function() {
        const itemId = $(this).data('item-id');
        if (!confirm('Remove this item from cart?')) return;
        $.ajax({
            url: '<?= SITE_URL ?>/api/cart.php',
            method: 'POST',
            data: { action: 'remove', item_id: itemId, csrf_token: '<?= generateCSRFToken() ?>' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success('Item removed from cart');
                    $('#cartCount').text(res.cart_count);
                    if (res.cart_empty) {
                        location.reload();
                    } else {
                        location.reload();
                    }
                } else {
                    toastr.error(res.error || 'Failed to remove item');
                }
            }
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
