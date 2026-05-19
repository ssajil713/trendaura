<?php
require_once __DIR__ . '/includes/session.php';
requireLogin();

$db = db();
$cart = getCart();
$cartItems = $cart ? getCartItems($cart['id']) : [];

if (empty($cartItems)) {
    setAlert('warning', 'Your cart is empty. Please add items before checkout.');
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$formData = [];

// Calculate totals
$subtotal = array_sum(array_column($cartItems, 'total_price'));
$shipping = $subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_CHARGE;
$tax = ($subtotal * TAX_RATE) / 100;
$discount = (float)($cart['coupon_discount'] ?? 0);
$total = $subtotal + $shipping + $tax - $discount;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $formData = $_POST;

        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $streetAddress = trim($_POST['street_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? 'India');
        $paymentMethod = $_POST['payment_method'] ?? 'cod';
        $sameAsBilling = isset($_POST['same_as_billing']);

        // Validate billing fields
        if (empty($fullName)) $errors[] = 'Full name is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';
        if (empty($streetAddress)) $errors[] = 'Street address is required.';
        if (empty($city)) $errors[] = 'City is required.';
        if (empty($state)) $errors[] = 'State is required.';
        if (empty($postalCode)) $errors[] = 'Postal code is required.';

        if (!in_array($paymentMethod, ['cod', 'online', 'bank_transfer'])) {
            $paymentMethod = 'cod';
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // Save billing address
                $stmt = $db->prepare("INSERT INTO addresses (user_id, address_type, full_name, phone, street_address, city, state, postal_code, country) VALUES (:user_id, 'billing', :full_name, :phone, :street_address, :city, :state, :postal_code, :country)");
                $stmt->execute([
                    'user_id' => $userId,
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'street_address' => $streetAddress,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postalCode,
                    'country' => $country
                ]);
                $billingAddressId = $db->lastInsertId();

                // Save shipping address
                if ($sameAsBilling) {
                    $shippingAddressId = $billingAddressId;
                } else {
                    $shipFullName = trim($_POST['ship_full_name'] ?? $fullName);
                    $shipPhone = trim($_POST['ship_phone'] ?? $phone);
                    $shipStreet = trim($_POST['ship_street_address'] ?? $streetAddress);
                    $shipCity = trim($_POST['ship_city'] ?? $city);
                    $shipState = trim($_POST['ship_state'] ?? $state);
                    $shipPostal = trim($_POST['ship_postal_code'] ?? $postalCode);
                    $shipCountry = trim($_POST['ship_country'] ?? $country);

                    $stmt = $db->prepare("INSERT INTO addresses (user_id, address_type, full_name, phone, street_address, city, state, postal_code, country) VALUES (:user_id, 'shipping', :full_name, :phone, :street_address, :city, :state, :postal_code, :country)");
                    $stmt->execute([
                        'user_id' => $userId,
                        'full_name' => $shipFullName,
                        'phone' => $shipPhone,
                        'street_address' => $shipStreet,
                        'city' => $shipCity,
                        'state' => $shipState,
                        'postal_code' => $shipPostal,
                        'country' => $shipCountry
                    ]);
                    $shippingAddressId = $db->lastInsertId();
                }

                // Create order
                $orderNumber = generateOrderNumber();
                $stmt = $db->prepare("INSERT INTO orders (order_number, user_id, subtotal, tax, shipping, coupon_discount, total, payment_method, payment_status, order_status, shipping_address_id, billing_address_id) VALUES (:order_number, :user_id, :subtotal, :tax, :shipping, :discount, :total, :payment_method, :payment_status, 'pending', :shipping_address_id, :billing_address_id)");
                $paymentStatus = ($paymentMethod === 'cod') ? 'pending' : 'pending';
                $stmt->execute([
                    'order_number' => $orderNumber,
                    'user_id' => $userId,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'shipping' => $shipping,
                    'discount' => $discount,
                    'total' => $total,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'shipping_address_id' => $shippingAddressId,
                    'billing_address_id' => $billingAddressId
                ]);
                $orderId = $db->lastInsertId();

                // Save order items
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, unit_price, total_price) VALUES (:order_id, :product_id, :product_name, :product_image, :quantity, :unit_price, :total_price)");
                foreach ($cartItems as $item) {
                    $stmt->execute([
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'product_image' => $item['image'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price']
                    ]);
                }

                // Create payment record
                $stmt = $db->prepare("INSERT INTO payments (order_id, user_id, payment_method, payment_status, amount) VALUES (:order_id, :user_id, :payment_method, :payment_status, :amount)");
                $stmt->execute([
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'amount' => $total
                ]);

                // Clear cart items
                $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_id = :cart_id");
                $stmt->execute(['cart_id' => $cart['id']]);

                // Delete cart
                $stmt = $db->prepare("DELETE FROM carts WHERE id = :id");
                $stmt->execute(['id' => $cart['id']]);

                $db->commit();

                logActivity($userId, 'order_placed', 'Order placed: ' . $orderNumber);
                header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . $orderNumber);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'An error occurred while processing your order. Please try again.';
                error_log('Checkout Error: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
?>

<!-- ====== BREADCRUMB ====== -->
<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/cart.php">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ====== CHECKOUT SECTION ====== -->
<section class="checkout-section py-4">
    <div class="container">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                <li><?= sanitizeInput($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= SITE_URL ?>/checkout.php" id="checkoutForm">
            <?= csrfField() ?>
            <div class="row g-4">
                <!-- LEFT COLUMN -->
                <div class="col-lg-8">
                    <!-- Billing Details -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="fas fa-user me-2"></i>Billing Details</h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="full_name" value="<?= sanitizeInput($formData['full_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone" value="<?= sanitizeInput($formData['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Street Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="street_address" rows="2" required><?= sanitizeInput($formData['street_address'] ?? '') ?></textarea>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">City <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="city" value="<?= sanitizeInput($formData['city'] ?? '') ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">State <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="state" value="<?= sanitizeInput($formData['state'] ?? '') ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Postal Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="postal_code" value="<?= sanitizeInput($formData['postal_code'] ?? '') ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control" name="country" value="<?= sanitizeInput($formData['country'] ?? 'India') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Details -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="fas fa-truck me-2"></i>Shipping Details</h5>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="sameAsBilling" name="same_as_billing" value="1" <?= isset($formData['same_as_billing']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sameAsBilling">Same as billing details</label>
                            </div>
                            <div id="shippingFields">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="ship_full_name" value="<?= sanitizeInput($formData['ship_full_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="ship_phone" value="<?= sanitizeInput($formData['ship_phone'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Street Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="ship_street_address" rows="2"><?= sanitizeInput($formData['ship_street_address'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">City <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="ship_city" value="<?= sanitizeInput($formData['ship_city'] ?? '') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">State <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="ship_state" value="<?= sanitizeInput($formData['ship_state'] ?? '') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Postal Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="ship_postal_code" value="<?= sanitizeInput($formData['ship_postal_code'] ?? '') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Country</label>
                                        <input type="text" class="form-control" name="ship_country" value="<?= sanitizeInput($formData['ship_country'] ?? 'India') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="fas fa-credit-card me-2"></i>Payment Method</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="payment-option border rounded p-3 text-center <?= ($formData['payment_method'] ?? 'cod') === 'cod' ? 'border-primary' : '' ?>">
                                        <input type="radio" name="payment_method" value="cod" id="payCod" class="btn-check" <?= ($formData['payment_method'] ?? 'cod') === 'cod' ? 'checked' : '' ?>>
                                        <label for="payCod" class="d-block cursor-pointer mb-0">
                                            <i class="fas fa-money-bill-wave fa-2x mb-2 text-primary"></i>
                                            <h6 class="mb-0">Cash on Delivery</h6>
                                            <small class="text-muted">Pay when you receive</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="payment-option border rounded p-3 text-center <?= ($formData['payment_method'] ?? '') === 'online' ? 'border-primary' : '' ?>">
                                        <input type="radio" name="payment_method" value="online" id="payOnline" class="btn-check" <?= ($formData['payment_method'] ?? '') === 'online' ? 'checked' : '' ?>>
                                        <label for="payOnline" class="d-block cursor-pointer mb-0">
                                            <i class="fas fa-mobile-alt fa-2x mb-2 text-primary"></i>
                                            <h6 class="mb-0">Online Payment</h6>
                                            <small class="text-muted">UPI, Cards, Net Banking</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="payment-option border rounded p-3 text-center <?= ($formData['payment_method'] ?? '') === 'bank_transfer' ? 'border-primary' : '' ?>">
                                        <input type="radio" name="payment_method" value="bank_transfer" id="payBank" class="btn-check" <?= ($formData['payment_method'] ?? '') === 'bank_transfer' ? 'checked' : '' ?>>
                                        <label for="payBank" class="d-block cursor-pointer mb-0">
                                            <i class="fas fa-university fa-2x mb-2 text-primary"></i>
                                            <h6 class="mb-0">Bank Transfer</h6>
                                            <small class="text-muted">Direct bank deposit</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100" id="placeOrderBtn">
                        <i class="fas fa-check-circle me-2"></i>Place Order
                    </button>
                </div>

                <!-- RIGHT COLUMN / SIDEBAR -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                            <div class="checkout-items mb-3">
                                <?php foreach ($cartItems as $item):
                                    $img = $item['image'] ?: 'assets/uploads/placeholder.svg';
                                ?>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <img src="<?= SITE_URL . '/' . $img ?>" alt="<?= sanitizeInput($item['name']) ?>" width="60" height="60" style="object-fit:cover;border-radius:6px;" loading="lazy">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 small"><?= sanitizeInput($item['name']) ?></h6>
                                        <small class="text-muted">Qty: <?= (int)$item['quantity'] ?> x <?= formatPrice($item['unit_price']) ?></small>
                                    </div>
                                    <strong class="small"><?= formatPrice($item['total_price']) ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
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
                            <div class="d-flex justify-content-between">
                                <strong>Total</strong>
                                <strong class="h5 mb-0"><?= formatPrice($total) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#sameAsBilling').on('change', function() {
        if ($(this).is(':checked')) {
            $('#shippingFields').slideUp();
        } else {
            $('#shippingFields').slideDown();
        }
    }).trigger('change');

    $('.payment-option').on('click', function() {
        $('.payment-option').removeClass('border-primary');
        $(this).addClass('border-primary');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
