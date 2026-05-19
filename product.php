<?php
require_once __DIR__ . '/includes/session.php';

$db = db();

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><i class="fas fa-exclamation-triangle fa-4x text-muted mb-3"></i><h3>Product not found</h3><a href="' . SITE_URL . '/shop.php" class="btn btn-primary mt-3">Back to Shop</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$product = getProduct($slug);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = 'Product Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><i class="fas fa-exclamation-triangle fa-4x text-muted mb-3"></i><h3>Product not found</h3><a href="' . SITE_URL . '/shop.php" class="btn btn-primary mt-3">Back to Shop</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = sanitizeInput($product['name']);
$pageDesc = sanitizeInput($product['meta_description'] ?: $product['short_description'] ?: truncateText($product['description'], 160));
include __DIR__ . '/includes/header.php';

$images = getProductImages($product['id']);
$primaryImage = $product['primary_image'] ?? (!empty($images) ? $images[0]['image_path'] : 'assets/uploads/placeholder.svg');
$price = $product['sale_price'] ?: $product['regular_price'];
$discountPercent = getDiscountPercent($product['regular_price'], $product['sale_price']);

// Get reviews
$reviewsStmt = $db->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = :product_id AND r.is_approved = 1 ORDER BY r.created_at DESC");
$reviewsStmt->execute(['product_id' => $product['id']]);
$reviews = $reviewsStmt->fetchAll();

// Get related products
$relatedProducts = getRelatedProducts($product['category_id'], $product['id']);

// Track recently viewed
addRecentlyViewed($product['id']);

// Decode specifications
$specifications = [];
if (!empty($product['specifications'])) {
    $specs = is_string($product['specifications']) ? json_decode($product['specifications'], true) : $product['specifications'];
    if (is_array($specs)) {
        $specifications = $specs;
    }
}

// Handle review submission
$reviewError = '';
$reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $reviewError = 'Please login to submit a review.';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $reviewError = 'Invalid security token.';
    } else {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $title = trim($_POST['review_title'] ?? '');
        $comment = trim($_POST['review_comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $reviewError = 'Please select a rating.';
        } elseif (empty($comment)) {
            $reviewError = 'Please write a review comment.';
        } else {
            $checkStmt = $db->prepare("SELECT id FROM reviews WHERE product_id = :product_id AND user_id = :user_id");
            $checkStmt->execute(['product_id' => $product['id'], 'user_id' => $_SESSION['user_id']]);
            if ($checkStmt->fetch()) {
                $reviewError = 'You have already reviewed this product.';
            } else {
                $insertStmt = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, title, comment, is_approved) VALUES (:product_id, :user_id, :rating, :title, :comment, 0)");
                $insertStmt->execute([
                    'product_id' => $product['id'],
                    'user_id' => $_SESSION['user_id'],
                    'rating' => $rating,
                    'title' => $title,
                    'comment' => $comment
                ]);
                $reviewSuccess = 'Thank you! Your review has been submitted and is awaiting approval.';
            }
        }
    }
}
?>

<!-- ====== BREADCRUMB ====== -->
<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/shop.php">Shop</a></li>
                <?php if (!empty($product['category_slug'])): ?>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/shop.php?category=<?= $product['category_slug'] ?>"><?= sanitizeInput($product['category_name']) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= sanitizeInput($product['name']) ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- ====== PRODUCT DETAILS ====== -->
<section class="product-details-section py-4">
    <div class="container">
        <div class="row g-4">
            <!-- LEFT COLUMN - Images -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <div class="main-image-wrapper">
                        <img src="<?= SITE_URL . '/' . $primaryImage ?>" alt="<?= sanitizeInput($product['name']) ?>" class="main-image" id="mainProductImage">
                        <?php if ($discountPercent > 0): ?>
                        <span class="badge badge-discount position-absolute top-0 start-0 m-3">-<?= $discountPercent ?>%</span>
                        <?php endif; ?>
                        <?php if ($product['is_new']): ?>
                        <span class="badge badge-new position-absolute top-0 end-0 m-3">New</span>
                        <?php endif; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="thumbnail-gallery mt-3 d-flex gap-2 flex-wrap">
                        <?php foreach ($images as $img): ?>
                        <div class="thumbnail-item <?= $img['image_path'] === $primaryImage || ($img['is_primary'] && $primaryImage === $product['primary_image']) ? 'active' : '' ?>">
                            <img src="<?= SITE_URL . '/' . $img['image_path'] ?>" alt="<?= sanitizeInput($product['name']) ?>" onclick="changeMainImage(this.src)">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN - Product Info -->
            <div class="col-lg-6">
                <div class="product-info">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <?php if (!empty($product['category_name'])): ?>
                        <span class="product-category"><?= sanitizeInput($product['category_name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($product['brand_name'])): ?>
                        <span class="product-brand"><?= sanitizeInput($product['brand_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <h1 class="product-title h3"><?= sanitizeInput($product['name']) ?></h1>

                    <div class="product-rating mb-3 d-flex align-items-center gap-2">
                        <?= renderStars($product['average_rating'], 'md') ?>
                        <span class="rating-value"><?= number_format($product['average_rating'], 1) ?></span>
                        <span class="text-muted">(<?= $product['review_count'] ?> reviews)</span>
                    </div>

                    <div class="product-price mb-3">
                        <span class="current-price h2"><?= formatPrice($price) ?></span>
                        <?php if ($product['sale_price']): ?>
                        <span class="old-price h5 text-muted text-decoration-line-through ms-2"><?= formatPrice($product['regular_price']) ?></span>
                        <span class="discount-tag ms-2">Save <?= formatPrice($product['regular_price'] - $product['sale_price']) ?> (<?= $discountPercent ?>%)</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product['short_description'])): ?>
                    <p class="product-short-desc text-muted mb-3"><?= sanitizeInput($product['short_description']) ?></p>
                    <?php endif; ?>

                    <div class="product-stock mb-3">
                        <?php if ($product['stock_status'] === 'in_stock'): ?>
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i> In Stock</span>
                        <?php if ($product['stock_quantity'] > 0 && $product['stock_quantity'] <= 10): ?>
                        <span class="text-muted ms-2 small">Only <?= $product['stock_quantity'] ?> left</span>
                        <?php endif; ?>
                        <?php elseif ($product['stock_status'] === 'on_backorder'): ?>
                        <span class="text-warning"><i class="fas fa-clock me-1"></i> On Backorder</span>
                        <?php else: ?>
                        <span class="text-danger"><i class="fas fa-times-circle me-1"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-meta small text-muted mb-3">
                        <?php if (!empty($product['sku'])): ?>
                        <div><strong>SKU:</strong> <?= sanitizeInput($product['sku']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($product['category_name'])): ?>
                        <div><strong>Category:</strong> <a href="<?= SITE_URL ?>/shop.php?category=<?= $product['category_slug'] ?>"><?= sanitizeInput($product['category_name']) ?></a></div>
                        <?php endif; ?>
                        <?php if (!empty($product['brand_name'])): ?>
                        <div><strong>Brand:</strong> <?= sanitizeInput($product['brand_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Quantity & Actions -->
                    <div class="product-actions-section mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="quantity-selector d-flex align-items-center border rounded">
                                <button class="btn btn-sm btn-qty-decrease px-3" onclick="decrementQty()"><i class="fas fa-minus"></i></button>
                                <input type="number" class="form-control form-control-sm text-center border-0 qty-input" id="qtyInput" value="1" min="1" max="<?= $product['stock_quantity'] ?: 99 ?>" readonly style="width:60px;">
                                <button class="btn btn-sm btn-qty-increase px-3" onclick="incrementQty()"><i class="fas fa-plus"></i></button>
                            </div>

                            <button class="btn btn-primary btn-lg flex-grow-1 btn-add-cart" data-product-id="<?= $product['id'] ?>" <?= $product['stock_status'] !== 'in_stock' ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                            </button>

                            <button class="btn btn-success btn-lg btn-buy-now" data-product-id="<?= $product['id'] ?>" <?= $product['stock_status'] !== 'in_stock' ? 'disabled' : '' ?>>
                                <i class="fas fa-bolt me-2"></i> Buy Now
                            </button>

                            <button class="btn btn-outline-danger btn-lg btn-wishlist" data-product-id="<?= $product['id'] ?>" title="Add to Wishlist">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Full Description -->
                    <?php if (!empty($product['description'])): ?>
                    <div class="product-description mb-3">
                        <h5>Description</h5>
                        <p><?= nl2br(sanitizeInput($product['description'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Highlights -->
                    <div class="product-highlights row g-2 mt-2">
                        <div class="col-6 col-md-3">
                            <div class="highlight-card text-center p-3 border rounded">
                                <i class="fas fa-truck fa-lg text-primary mb-2"></i>
                                <small class="d-block">Free Shipping</small>
                                <small class="text-muted">On orders above <?= formatPrice(FREE_SHIPPING_MIN) ?></small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="highlight-card text-center p-3 border rounded">
                                <i class="fas fa-undo fa-lg text-primary mb-2"></i>
                                <small class="d-block">Easy Returns</small>
                                <small class="text-muted">7-day return policy</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="highlight-card text-center p-3 border rounded">
                                <i class="fas fa-lock fa-lg text-primary mb-2"></i>
                                <small class="d-block">Secure Payment</small>
                                <small class="text-muted">100% secure checkout</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="highlight-card text-center p-3 border rounded">
                                <i class="fas fa-headset fa-lg text-primary mb-2"></i>
                                <small class="d-block">24/7 Support</small>
                                <small class="text-muted">Dedicated support</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== SPECIFICATIONS ====== -->
<?php if (!empty($specifications)): ?>
<section class="specifications-section py-4 bg-light">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Product <span>Specifications</span></h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <table class="table table-bordered specs-table">
                    <tbody>
                        <?php foreach ($specifications as $key => $value): ?>
                        <tr>
                            <th class="w-25 bg-light"><?= sanitizeInput(ucwords(str_replace('_', ' ', $key))) ?></th>
                            <td><?= sanitizeInput($value) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ====== CUSTOMER REVIEWS ====== -->
<section class="reviews-section py-4">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Customer <span>Reviews</span></h2>
            <p class="section-subtitle">What our customers say about this product</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <?php if (!empty($reviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?= sanitizeInput($review['full_name']) ?></strong>
                                    <div class="review-rating"><?= renderStars($review['rating']) ?></div>
                                </div>
                                <small class="text-muted"><?= formatDate($review['created_at'], 'd M Y') ?></small>
                            </div>
                            <?php if (!empty($review['title'])): ?>
                            <h6 class="review-title"><?= sanitizeInput($review['title']) ?></h6>
                            <?php endif; ?>
                            <p class="review-comment mb-0"><?= nl2br(sanitizeInput($review['comment'])) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="far fa-comment-dots fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No reviews yet for this product.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <div class="review-form-wrapper border rounded p-4">
                    <h5 class="mb-3"><?= isLoggedIn() ? 'Write a Review' : 'Login to Review' ?></h5>

                    <?php if ($reviewError): ?>
                    <div class="alert alert-danger"><?= sanitizeInput($reviewError) ?></div>
                    <?php endif; ?>
                    <?php if ($reviewSuccess): ?>
                    <div class="alert alert-success"><?= sanitizeInput($reviewSuccess) ?></div>
                    <?php endif; ?>

                    <?php if (isLoggedIn()): ?>
                    <form method="POST" action="<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">Your Rating <span class="text-danger">*</span></label>
                            <div class="star-rating-select">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" class="btn-check" <?= $i === 5 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-warning btn-sm star-label" for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="review_title" class="form-label">Review Title</label>
                            <input type="text" class="form-control" id="review_title" name="review_title" placeholder="Summarize your review" maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label for="review_comment" class="form-label">Review <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="review_comment" name="review_comment" rows="4" placeholder="Share your experience with this product..." required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i> Submit Review
                        </button>
                    </form>
                    <?php else: ?>
                    <p class="text-muted">Please <a href="<?= SITE_URL ?>/login.php">login</a> to write a review.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== RELATED PRODUCTS ====== -->
<?php if (!empty($relatedProducts)): ?>
<section class="related-products-section py-4 bg-light">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Related <span>Products</span></h2>
            <p class="section-subtitle">You might also like these products</p>
        </div>
        <div class="row g-3">
            <?php foreach ($relatedProducts as $rp):
                $rpImg = $rp['primary_image'] ?: 'assets/uploads/placeholder.svg';
                $rpPrice = $rp['sale_price'] ?: $rp['regular_price'];
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <div class="product-badge">
                        <?php if ($rp['discount_percent'] > 0): ?><span class="badge badge-discount">-<?= $rp['discount_percent'] ?>%</span><?php endif; ?>
                        <?php if ($rp['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?>
                    </div>
                    <div class="product-image">
                        <img src="<?= SITE_URL . '/' . $rpImg ?>" alt="<?= sanitizeInput($rp['name']) ?>" loading="lazy">
                        <div class="product-actions">
                            <button class="btn-wishlist" data-product-id="<?= $rp['id'] ?>" title="Add to Wishlist"><i class="far fa-heart"></i></button>
                            <button class="btn-quick-view" onclick="window.location.href='<?= SITE_URL ?>/product.php?slug=<?= $rp['slug'] ?>'" title="Quick View"><i class="far fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= sanitizeInput($rp['category_name'] ?? '') ?></div>
                        <h5 class="product-title"><a href="<?= SITE_URL ?>/product.php?slug=<?= $rp['slug'] ?>"><?= sanitizeInput($rp['name']) ?></a></h5>
                        <div class="product-rating">
                            <?= renderStars($rp['average_rating']) ?>
                            <span>(<?= $rp['review_count'] ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price"><?= formatPrice($rpPrice) ?></span>
                            <?php if ($rp['sale_price']): ?><span class="old-price"><?= formatPrice($rp['regular_price']) ?></span><?php endif; ?>
                        </div>
                        <button class="btn-add-cart" data-product-id="<?= $rp['id'] ?>" <?= $rp['stock_status'] !== 'in_stock' ? 'disabled' : '' ?>>
                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function changeMainImage(src) {
    document.getElementById('mainProductImage').src = src;
    document.querySelectorAll('.thumbnail-item').forEach(el => el.classList.remove('active'));
    event.target.closest('.thumbnail-item')?.classList.add('active');
}

function decrementQty() {
    const input = document.getElementById('qtyInput');
    const val = parseInt(input.value);
    if (val > 1) input.value = val - 1;
}

function incrementQty() {
    const input = document.getElementById('qtyInput');
    const val = parseInt(input.value);
    const max = parseInt(input.max);
    if (val < max) input.value = val + 1;
}

$(document).ready(function() {
    $('.btn-add-cart').on('click', function() {
        const productId = $(this).data('product-id');
        const qty = $('#qtyInput').val() || 1;
        $.ajax({
            url: '<?= SITE_URL ?>/api/cart.php',
            method: 'POST',
            data: { action: 'add', product_id: productId, quantity: qty, csrf_token: '<?= generateCSRFToken() ?>' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success('Product added to cart!');
                    $('#cartCount').text(res.cart_count);
                } else {
                    toastr.error(res.error || 'Failed to add to cart');
                }
            }
        });
    });

    $('.btn-buy-now').on('click', function() {
        const productId = $(this).data('product-id');
        const qty = $('#qtyInput').val() || 1;
        $.ajax({
            url: '<?= SITE_URL ?>/api/cart.php',
            method: 'POST',
            data: { action: 'add', product_id: productId, quantity: qty, csrf_token: '<?= generateCSRFToken() ?>' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    window.location.href = '<?= SITE_URL ?>/checkout.php';
                } else {
                    toastr.error(res.error || 'Failed to add to cart');
                }
            }
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
