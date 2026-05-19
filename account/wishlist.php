<?php
require_once __DIR__ . '/../includes/session.php';
requireLogin();

$pageTitle = 'My Wishlist';
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('error', 'Invalid security token.');
    } else {
        $db = db();
        $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute(['uid' => $_SESSION['user_id'], 'pid' => (int)$_POST['remove_id']]);
        setAlert('success', 'Product removed from wishlist.');
    }
    header('Location: ' . SITE_URL . '/account/wishlist.php');
    exit;
}

$wishlistItems = getWishlist($_SESSION['user_id']);

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
                        <a href="<?= SITE_URL ?>/account/orders.php"><i class="fas fa-box"></i> Orders</a>
                        <a href="<?= SITE_URL ?>/account/wishlist.php" class="active"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="<?= SITE_URL ?>/account/profile.php"><i class="fas fa-user-cog"></i> Profile</a>
                        <a href="<?= SITE_URL ?>/account/profile.php?tab=password"><i class="fas fa-lock"></i> Change Password</a>
                        <hr>
                        <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9">
                <div class="account-content">
                    <h4 class="mb-4">My Wishlist (<?= count($wishlistItems) ?>)</h4>

                    <?php if (empty($wishlistItems)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-heart-broken fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Your wishlist is empty</h5>
                            <p class="text-muted mb-3">Save your favorite items here!</p>
                            <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary">Browse Products</a>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($wishlistItems as $item):
                                $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['regular_price'];
                                $inStock = $item['stock_status'] === 'in_stock';
                            ?>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="row g-0">
                                            <div class="col-4">
                                                <a href="<?= SITE_URL ?>/product.php?slug=<?= $item['slug'] ?>">
                                                    <img src="<?= SITE_URL ?>/<?= $item['image'] ?: 'assets/uploads/placeholder.png' ?>"
                                                         alt="<?= sanitizeInput($item['name']) ?>"
                                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem 0 0 0.5rem;">
                                                </a>
                                            </div>
                                            <div class="col-8">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-1">
                                                        <a href="<?= SITE_URL ?>/product.php?slug=<?= $item['slug'] ?>" class="text-decoration-none"><?= sanitizeInput($item['name']) ?></a>
                                                    </h6>
                                                    <div class="fw-bold text-primary mb-2"><?= formatPrice($price) ?></div>
                                                    <div class="mb-2">
                                                        <?php if ($inStock): ?>
                                                            <span class="badge bg-success"><i class="fas fa-check"></i> In Stock</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger"><i class="fas fa-times"></i> Out of Stock</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <a href="<?= SITE_URL ?>/cart.php?add=<?= $item['product_id'] ?>" class="btn btn-sm btn-primary <?= !$inStock ? 'disabled' : '' ?>">
                                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                                        </a>
                                                        <form method="POST" style="display:inline;">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="remove_id" value="<?= $item['product_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove from wishlist?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
