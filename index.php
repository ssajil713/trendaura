<?php
require_once __DIR__ . '/includes/session.php';
$pageTitle = 'Home';
$pageDesc = 'Your Premium Shopping Destination - Shop the best products at amazing prices';
include __DIR__ . '/includes/header.php';

$db = db();

// Get banners
$stmt = $db->prepare("SELECT * FROM banners WHERE is_active = 1 AND position = 'hero' ORDER BY sort_order ASC");
$stmt->execute();
$banners = $stmt->fetchAll();

$promoBanners = $db->prepare("SELECT * FROM banners WHERE is_active = 1 AND position = 'promo' ORDER BY sort_order ASC");
$promoBanners->execute();
$promos = $promoBanners->fetchAll();

$featured = getFeaturedProducts(8);
$trending = getTrendingProducts(8);
$newArrivals = getNewArrivals(8);
$bestSellers = getBestSellers(8);
$categories = getCategories();
?>

<!-- ====== HERO SLIDER ====== -->
<section class="hero-section">
    <div class="container">
        <div class="swiper hero-slider">
            <div class="swiper-wrapper">
                <?php foreach ($banners as $banner): ?>
                <div class="swiper-slide hero-slide">
                    <div class="hero-bg" style="background-image: linear-gradient(135deg, <?= $banner['id'] % 2 == 0 ? '#1E293B, #0F172A' : '#1a1a2e, #16213e' ?>), url('<?= SITE_URL . '/' . $banner['image'] ?>');"></div>
                    <div class="hero-content animate-fade-in-up">
                        <span class="hero-tag"><?= sanitizeInput($banner['title'] ?? 'New Collection') ?></span>
                        <h1><?= sanitizeInput($banner['subtitle'] ?? '') ?></h1>
                        <p><?= sanitizeInput($banner['description'] ?? '') ?></p>
                        <a href="<?= $banner['link'] ?: SITE_URL ?>" class="btn-hero"><?= sanitizeInput($banner['btn_text'] ?: 'Shop Now') ?> <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>
</section>

<!-- ====== PROMO BANNERS ====== -->
<?php if (!empty($promos)): ?>
<section class="promo-section">
    <div class="container">
        <div class="row g-3">
            <?php foreach ($promos as $promo): ?>
            <div class="col-md-<?= count($promos) >= 3 ? '4' : '6' ?>">
                <div class="promo-card">
                    <div class="promo-bg" style="background: linear-gradient(135deg, <?= $promo['id'] % 2 == 0 ? '#6C3BF7, #5A2DE0' : '#FF6B35, #E85D2C' ?>);"></div>
                    <div class="promo-content">
                        <h4><?= sanitizeInput($promo['subtitle'] ?? $promo['title']) ?></h4>
                        <p><?= sanitizeInput($promo['description'] ?? '') ?></p>
                        <a href="<?= $promo['link'] ?: SITE_URL ?>" class="btn-promo"><?= sanitizeInput($promo['btn_text'] ?: 'Shop Now') ?> <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ====== CATEGORIES ====== -->
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Shop by <span>Category</span></h2>
            <p class="section-subtitle">Browse our wide range of product categories</p>
        </div>
        <div class="row g-3">
            <?php $catIcons = ['fa-mobile-alt', 'fa-tshirt', 'fa-couch', 'fa-running', 'fa-spa', 'fa-laptop', 'fa-headphones', 'fa-male', 'fa-female', 'fa-child', 'fa-clock', 'fa-gem'];
            foreach ($categories as $i => $cat): ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <a href="<?= SITE_URL ?>/shop.php?category=<?= $cat['slug'] ?>" class="category-card">
                    <div class="category-icon"><i class="fas <?= $catIcons[$i % count($catIcons)] ?>"></i></div>
                    <h5><?= sanitizeInput($cat['name']) ?></h5>
                    <p><?= $cat['description'] ? truncateText($cat['description'], 40) : 'Explore collection' ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====== FEATURED PRODUCTS ====== -->
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured <span>Products</span></h2>
            <p class="section-subtitle">Hand-picked products just for you</p>
        </div>
        <div class="row g-3">
            <?php foreach ($featured as $product):
                $img = $product['primary_image'] ?: 'assets/uploads/placeholder.svg';
                $price = $product['sale_price'] ?: $product['regular_price'];
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card animate-fade-in-up">
                    <div class="product-badge">
                        <?php if ($product['discount_percent'] > 0): ?><span class="badge badge-discount">-<?= $product['discount_percent'] ?>%</span><?php endif; ?>
                        <?php if ($product['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?>
                    </div>
                    <div class="product-image">
                        <img src="<?= SITE_URL . '/' . $img ?>" alt="<?= sanitizeInput($product['name']) ?>" loading="lazy">
                        <div class="product-actions">
                            <button class="btn-wishlist" data-product-id="<?= $product['id'] ?>" title="Add to Wishlist"><i class="far fa-heart"></i></button>
                            <button class="btn-quick-view" onclick="window.location.href='<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>'" title="Quick View"><i class="far fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= sanitizeInput($product['category_name'] ?? '') ?></div>
                        <h5 class="product-title"><a href="<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>"><?= sanitizeInput($product['name']) ?></a></h5>
                        <div class="product-rating">
                            <?= renderStars($product['average_rating']) ?>
                            <span>(<?= $product['review_count'] ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price"><?= formatPrice($price) ?></span>
                            <?php if ($product['sale_price']): ?><span class="old-price"><?= formatPrice($product['regular_price']) ?></span><?php endif; ?>
                            <?php if ($product['discount_percent'] > 0): ?><span class="discount-tag">-<?= $product['discount_percent'] ?>%</span><?php endif; ?>
                        </div>
                        <div class="product-stock <?= $product['stock_status'] === 'in_stock' ? 'in-stock' : 'out-of-stock' ?>">
                            <i class="fas fa-<?= $product['stock_status'] === 'in_stock' ? 'check-circle' : 'times-circle' ?>"></i>
                            <?= $product['stock_status'] === 'in_stock' ? 'In Stock' : 'Out of Stock' ?>
                        </div>
                        <button class="btn-add-cart" data-product-id="<?= $product['id'] ?>" <?= $product['stock_status'] !== 'in_stock' ? 'disabled' : '' ?>>
                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====== TRENDING PRODUCTS ====== -->
<section class="trending-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Trending <span>Now</span></h2>
            <p class="section-subtitle">Most popular products loved by our customers</p>
        </div>
        <div class="row g-3">
            <?php foreach ($trending as $product):
                $img = $product['primary_image'] ?: 'assets/uploads/placeholder.svg';
                $price = $product['sale_price'] ?: $product['regular_price'];
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <div class="product-badge">
                        <span class="badge badge-trending">Trending</span>
                        <?php if ($product['discount_percent'] > 0): ?><span class="badge badge-discount">-<?= $product['discount_percent'] ?>%</span><?php endif; ?>
                    </div>
                    <div class="product-image">
                        <img src="<?= SITE_URL . '/' . $img ?>" alt="<?= sanitizeInput($product['name']) ?>" loading="lazy">
                        <div class="product-actions">
                            <button class="btn-wishlist" data-product-id="<?= $product['id'] ?>"><i class="far fa-heart"></i></button>
                            <button class="btn-quick-view" onclick="window.location.href='<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>'"><i class="far fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= sanitizeInput($product['category_name'] ?? '') ?></div>
                        <h5 class="product-title"><a href="<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>"><?= sanitizeInput($product['name']) ?></a></h5>
                        <div class="product-rating">
                            <?= renderStars($product['average_rating']) ?>
                            <span>(<?= $product['review_count'] ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price"><?= formatPrice($price) ?></span>
                            <?php if ($product['sale_price']): ?><span class="old-price"><?= formatPrice($product['regular_price']) ?></span><?php endif; ?>
                        </div>
                        <div class="product-stock in-stock">
                            <i class="fas fa-check-circle"></i> In Stock
                        </div>
                        <button class="btn-add-cart" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====== NEW ARRIVALS ====== -->
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">New <span>Arrivals</span></h2>
            <p class="section-subtitle">Check out our latest products</p>
        </div>
        <div class="row g-3">
            <?php foreach ($newArrivals as $product):
                $img = $product['primary_image'] ?: 'assets/uploads/placeholder.svg';
                $price = $product['sale_price'] ?: $product['regular_price'];
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <div class="product-badge">
                        <span class="badge badge-new">New</span>
                        <?php if ($product['discount_percent'] > 0): ?><span class="badge badge-discount">-<?= $product['discount_percent'] ?>%</span><?php endif; ?>
                    </div>
                    <div class="product-image">
                        <img src="<?= SITE_URL . '/' . $img ?>" alt="<?= sanitizeInput($product['name']) ?>" loading="lazy">
                        <div class="product-actions">
                            <button class="btn-wishlist" data-product-id="<?= $product['id'] ?>"><i class="far fa-heart"></i></button>
                            <button class="btn-quick-view" onclick="window.location.href='<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>'"><i class="far fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= sanitizeInput($product['category_name'] ?? '') ?></div>
                        <h5 class="product-title"><a href="<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>"><?= sanitizeInput($product['name']) ?></a></h5>
                        <div class="product-rating">
                            <?= renderStars($product['average_rating']) ?>
                            <span>(<?= $product['review_count'] ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price"><?= formatPrice($price) ?></span>
                            <?php if ($product['sale_price']): ?><span class="old-price"><?= formatPrice($product['regular_price']) ?></span><?php endif; ?>
                        </div>
                        <div class="product-stock in-stock">
                            <i class="fas fa-check-circle"></i> In Stock
                        </div>
                        <button class="btn-add-cart" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====== BEST SELLERS ====== -->
<section class="trending-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Best <span>Sellers</span></h2>
            <p class="section-subtitle">Our most popular products based on sales</p>
        </div>
        <div class="row g-3">
            <?php foreach ($bestSellers as $product):
                $img = $product['primary_image'] ?: 'assets/uploads/placeholder.svg';
                $price = $product['sale_price'] ?: $product['regular_price'];
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <div class="product-badge">
                        <span class="badge badge-featured">Best Seller</span>
                        <?php if ($product['discount_percent'] > 0): ?><span class="badge badge-discount">-<?= $product['discount_percent'] ?>%</span><?php endif; ?>
                    </div>
                    <div class="product-image">
                        <img src="<?= SITE_URL . '/' . $img ?>" alt="<?= sanitizeInput($product['name']) ?>" loading="lazy">
                        <div class="product-actions">
                            <button class="btn-wishlist" data-product-id="<?= $product['id'] ?>"><i class="far fa-heart"></i></button>
                            <button class="btn-quick-view" onclick="window.location.href='<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>'"><i class="far fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= sanitizeInput($product['category_name'] ?? '') ?></div>
                        <h5 class="product-title"><a href="<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>"><?= sanitizeInput($product['name']) ?></a></h5>
                        <div class="product-rating">
                            <?= renderStars($product['average_rating']) ?>
                            <span>(<?= $product['review_count'] ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price"><?= formatPrice($price) ?></span>
                            <?php if ($product['sale_price']): ?><span class="old-price"><?= formatPrice($product['regular_price']) ?></span><?php endif; ?>
                        </div>
                        <div class="product-stock in-stock">
                            <i class="fas fa-check-circle"></i> In Stock
                        </div>
                        <button class="btn-add-cart" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====== BRANDS ====== -->
<section class="brands-section">
    <div class="container">
        <div class="row align-items-center justify-content-center g-4">
            <?php foreach (getBrands() as $brand): ?>
            <div class="col-4 col-md-2">
                <div class="brand-logo-item">
                    <h5 class="fw-bold mb-0" style="font-size: 1.1rem; color: var(--dark-3);"><?= sanitizeInput($brand['name']) ?></h5>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====== TESTIMONIALS ====== -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" style="color:#fff;">What Our <span>Customers Say</span></h2>
            <p class="section-subtitle" style="color: rgba(255,255,255,0.6);">Real reviews from real customers</p>
        </div>
        <div class="swiper testimonial-carousel">
            <div class="swiper-wrapper">
                <?php
                $testimonials = [
                    ['name' => 'Rahul Sharma', 'initial' => 'R', 'rating' => 5, 'text' => 'Absolutely love the quality of products! The delivery was super fast and the customer service was exceptional. Will definitely shop again.'],
                    ['name' => 'Priya Patel', 'initial' => 'P', 'rating' => 5, 'text' => 'Best online shopping experience! The prices are unbeatable and the product quality exceeded my expectations. Highly recommended!'],
                    ['name' => 'Amit Singh', 'initial' => 'A', 'rating' => 4, 'text' => 'Great collection of products. The website is easy to navigate and the checkout process was smooth. Happy with my purchase.'],
                    ['name' => 'Sneha Reddy', 'initial' => 'S', 'rating' => 5, 'text' => 'I am impressed with the variety and quality. The discounts make it even better. My go-to place for online shopping now!'],
                ];
                foreach ($testimonials as $t):
                ?>
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <div class="rating mb-3"><?= renderStars($t['rating']) ?></div>
                        <p class="quote">"<?= $t['text'] ?>"</p>
                        <div class="author">
                            <div class="author-img"><?= $t['initial'] ?></div>
                            <div class="author-info">
                                <h6><?= $t['name'] ?></h6>
                                <span>Verified Buyer</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination mt-4"></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
