<?php
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'Shop';
$pageDesc = 'Browse our complete collection of products';
include __DIR__ . '/includes/header.php';

$db = db();

// Get filter params
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';
$brandSlug = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$ratingFilter = isset($_GET['rating']) && is_numeric($_GET['rating']) ? (int)$_GET['rating'] : 0;
$selectedCategories = isset($_GET['categories']) ? $_GET['categories'] : [];
$selectedBrands = isset($_GET['brands']) ? $_GET['brands'] : [];
$viewMode = isset($_GET['view']) && $_GET['view'] === 'list' ? 'list' : 'grid';
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build WHERE conditions
$where = "WHERE p.is_active = 1";
$params = [];

if (!empty($search)) {
    $where .= " AND (p.name LIKE :search OR p.description LIKE :search2)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

if (!empty($categorySlug)) {
    $where .= " AND (c.slug = :category_slug OR c.parent_id IN (SELECT id FROM categories WHERE slug = :category_slug2))";
    $params['category_slug'] = $categorySlug;
    $params['category_slug2'] = $categorySlug;
}

if (!empty($selectedCategories) && is_array($selectedCategories)) {
    $catPlaceholders = [];
    foreach ($selectedCategories as $i => $catId) {
        $key = "cat_$i";
        $catPlaceholders[] = ":$key";
        $params[$key] = (int)$catId;
    }
    $where .= " AND p.category_id IN (" . implode(',', $catPlaceholders) . ")";
}

if (!empty($brandSlug)) {
    $where .= " AND b.slug = :brand_slug";
    $params['brand_slug'] = $brandSlug;
}

if (!empty($selectedBrands) && is_array($selectedBrands)) {
    $brandPlaceholders = [];
    foreach ($selectedBrands as $i => $brandId) {
        $key = "brand_$i";
        $brandPlaceholders[] = ":$key";
        $params[$key] = (int)$brandId;
    }
    $where .= " AND p.brand_id IN (" . implode(',', $brandPlaceholders) . ")";
}

if ($minPrice > 0) {
    $where .= " AND COALESCE(p.sale_price, p.regular_price) >= :min_price";
    $params['min_price'] = $minPrice;
}

if ($maxPrice > 0) {
    $where .= " AND COALESCE(p.sale_price, p.regular_price) <= :max_price";
    $params['max_price'] = $maxPrice;
}

if ($ratingFilter > 0) {
    $where .= " AND p.average_rating >= :rating";
    $params['rating'] = $ratingFilter;
}

// Build ORDER BY
$orderBy = "ORDER BY p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $orderBy = "ORDER BY COALESCE(p.sale_price, p.regular_price) ASC";
        break;
    case 'price_high':
        $orderBy = "ORDER BY COALESCE(p.sale_price, p.regular_price) DESC";
        break;
    case 'popularity':
        $orderBy = "ORDER BY p.total_sales DESC";
        break;
    case 'best_selling':
        $orderBy = "ORDER BY p.total_sales DESC";
        break;
    case 'rating':
        $orderBy = "ORDER BY p.average_rating DESC";
        break;
    case 'deals':
        $where .= " AND p.sale_price IS NOT NULL AND p.sale_price < p.regular_price";
        $orderBy = "ORDER BY p.discount_percent DESC";
        break;
    case 'newest':
    default:
        $orderBy = "ORDER BY p.created_at DESC";
        break;
}

// Count query
$countSql = "SELECT COUNT(*) FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Main query
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
        b.name as brand_name, b.slug as brand_slug,
        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        $where
        $orderBy
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Get categories & brands for sidebar
$allCategories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll();
$allBrands = $db->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Build URL for pagination
$urlParams = [];
if (!empty($search)) $urlParams['search'] = $search;
if (!empty($categorySlug)) $urlParams['category'] = $categorySlug;
if (!empty($brandSlug)) $urlParams['brand'] = $brandSlug;
if (!empty($sort)) $urlParams['sort'] = $sort;
if ($minPrice > 0) $urlParams['min_price'] = $minPrice;
if ($maxPrice > 0) $urlParams['max_price'] = $maxPrice;
if ($ratingFilter > 0) $urlParams['rating'] = $ratingFilter;
if ($viewMode === 'list') $urlParams['view'] = 'list';
if (!empty($selectedCategories)) $urlParams['categories'] = $selectedCategories;
if (!empty($selectedBrands)) $urlParams['brands'] = $selectedBrands;
$queryString = http_build_query($urlParams);
$baseUrl = SITE_URL . '/shop.php' . (!empty($queryString) ? '?' . $queryString . '&' : '?');

// Generate products HTML
ob_start();
if (!empty($products)):
    foreach ($products as $product):
        $img = $product['primary_image'] ?: 'assets/uploads/placeholder.svg';
        $price = $product['sale_price'] ?: $product['regular_price'];
?>
<?php if ($viewMode === 'list'): ?>
<div class="col-12">
    <div class="product-card product-card-list d-flex">
        <div class="product-image" style="width:250px;min-width:250px;">
            <img src="<?= SITE_URL . '/' . $img ?>" alt="<?= sanitizeInput($product['name']) ?>" loading="lazy">
            <div class="product-actions">
                <button class="btn-wishlist" data-product-id="<?= $product['id'] ?>" title="Add to Wishlist"><i class="far fa-heart"></i></button>
            </div>
        </div>
        <div class="product-body flex-grow-1 d-flex flex-column justify-content-between">
            <div>
                <?php if ($product['discount_percent'] > 0): ?><span class="badge badge-discount">-<?= $product['discount_percent'] ?>%</span><?php endif; ?>
                <?php if ($product['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?>
                <?php if ($product['is_featured']): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                <div class="product-category mt-2"><?= sanitizeInput($product['category_name'] ?? '') ?></div>
                <h5 class="product-title"><a href="<?= SITE_URL ?>/product.php?slug=<?= $product['slug'] ?>"><?= sanitizeInput($product['name']) ?></a></h5>
                <p class="text-muted small"><?= sanitizeInput($product['short_description'] ?? truncateText($product['description'], 120)) ?></p>
                <div class="product-rating">
                    <?= renderStars($product['average_rating']) ?>
                    <span>(<?= $product['review_count'] ?>)</span>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="product-price">
                    <span class="current-price"><?= formatPrice($price) ?></span>
                    <?php if ($product['sale_price']): ?><span class="old-price"><?= formatPrice($product['regular_price']) ?></span><?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn-add-cart" data-product-id="<?= $product['id'] ?>" <?= $product['stock_status'] !== 'in_stock' ? 'disabled' : '' ?>>
                        <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="col-6 col-md-4 col-lg-3">
    <div class="product-card">
        <div class="product-badge">
            <?php if ($product['discount_percent'] > 0): ?><span class="badge badge-discount">-<?= $product['discount_percent'] ?>%</span><?php endif; ?>
            <?php if ($product['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?>
            <?php if ($product['is_featured']): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
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
<?php endif; ?>
<?php
    endforeach;
else:
?>
<div class="col-12">
    <div class="text-center py-5">
        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
        <h4>No products found</h4>
        <p class="text-muted">Try adjusting your filters or search terms.</p>
        <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary">Clear All Filters</a>
    </div>
</div>
<?php endif;
$productsHtml = ob_get_clean();

// Pagination HTML
$paginationUrl = $baseUrl;
$paginationHtml = paginate($totalProducts, $page, $perPage, $paginationUrl);

// Count text
$startCount = $totalProducts > 0 ? $offset + 1 : 0;
$endCount = min($offset + $perPage, $totalProducts);
$countText = "Showing $startCount–$endCount of $totalProducts results";

// AJAX response
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    echo json_encode([
        'html' => $productsHtml,
        'pagination' => $paginationHtml,
        'count' => $countText,
        'url' => $_SERVER['REQUEST_URI']
    ]);
    exit;
}

// Get min/max price range for filter
$priceRangeStmt = $db->query("SELECT MIN(COALESCE(sale_price, regular_price)) as min_price, MAX(COALESCE(sale_price, regular_price)) as max_price FROM products WHERE is_active = 1");
$priceRange = $priceRangeStmt->fetch();
$globalMinPrice = floor($priceRange['min_price'] / 100) * 100;
$globalMaxPrice = ceil($priceRange['max_price'] / 100) * 100;
?>

<!-- ====== BREADCRUMB ====== -->
<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ====== SHOP SECTION ====== -->
<section class="shop-section py-4">
    <div class="container">
        <div class="row">
            <!-- SIDEBAR -->
            <aside class="col-lg-3 mb-4 mb-lg-0">
                <div class="shop-sidebar">
                    <div class="d-flex d-lg-none justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Filters</h5>
                        <button class="btn btn-sm btn-outline-secondary" id="closeSidebar"><i class="fas fa-times"></i></button>
                    </div>

                    <!-- Categories -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title">Categories</h5>
                        <div class="widget-content">
                            <?php foreach ($allCategories as $cat): ?>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" id="cat_<?= $cat['id'] ?>" data-filter="categories" <?= in_array($cat['id'], (array)$selectedCategories) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cat_<?= $cat['id'] ?>"><?= sanitizeInput($cat['name']) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Brands -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title">Brands</h5>
                        <div class="widget-content">
                            <?php foreach ($allBrands as $brand): ?>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="brands[]" value="<?= $brand['id'] ?>" id="brand_<?= $brand['id'] ?>" data-filter="brands" <?= in_array($brand['id'], (array)$selectedBrands) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="brand_<?= $brand['id'] ?>"><?= sanitizeInput($brand['name']) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title">Price Range</h5>
                        <div class="widget-content">
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm price-filter" name="min_price" placeholder="Min" value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm price-filter" name="max_price" placeholder="Max" value="<?= $maxPrice > 0 ? $maxPrice : '' ?>" min="0">
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary w-100 mt-2" id="applyPrice">Apply</button>
                        </div>
                    </div>

                    <!-- Rating -->
                    <div class="sidebar-widget">
                        <h5 class="widget-title">Rating</h5>
                        <div class="widget-content">
                            <?php for ($r = 5; $r >= 1; $r--): ?>
                            <div class="form-check">
                                <input class="form-check-input rating-filter" type="radio" name="rating" value="<?= $r ?>" id="rating_<?= $r ?>" <?= $ratingFilter === $r ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rating_<?= $r ?>">
                                    <?= renderStars($r) ?> &amp; up
                                </label>
                            </div>
                            <?php endfor; ?>
                            <div class="form-check">
                                <input class="form-check-input rating-filter" type="radio" name="rating" value="0" id="rating_0" <?= $ratingFilter === 0 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rating_0">All Ratings</label>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <div class="col-lg-9">
                <!-- Toolbar -->
                <div class="shop-toolbar d-flex flex-wrap align-items-center justify-content-between mb-3 p-3 bg-light rounded">
                    <div class="d-flex align-items-center gap-3">
                        <div class="result-count"><?= $countText ?></div>
                        <div class="sort-select">
                            <select class="form-select form-select-sm" id="sortSelect">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="popularity" <?= $sort === 'popularity' ? 'selected' : '' ?>>Popularity</option>
                                <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>Best Selling</option>
                                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating</option>
                                <option value="deals" <?= $sort === 'deals' ? 'selected' : '' ?>>Hot Deals</option>
                            </select>
                        </div>
                    </div>
                    <div class="view-toggle d-flex gap-1">
                        <button class="btn btn-sm <?= $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-view="grid" title="Grid View"><i class="fas fa-th"></i></button>
                        <button class="btn btn-sm <?= $viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-view="list" title="List View"><i class="fas fa-list"></i></button>
                        <button class="btn btn-sm btn-outline-secondary d-lg-none ms-2" id="filterToggle"><i class="fas fa-filter"></i> Filters</button>
                    </div>
                </div>

                <!-- Products Grid/List -->
                <div class="row g-3 products-container" id="productsContainer">
                    <?= $productsHtml ?>
                </div>

                <!-- Pagination -->
                <div class="pagination-container mt-4" id="paginationContainer">
                    <?= $paginationHtml ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    let isLoading = false;

    function getFilterParams() {
        const params = new URLSearchParams();
        const search = '<?= sanitizeInput($search) ?>';
        if (search) params.set('search', search);

        const sort = $('#sortSelect').val();
        if (sort !== 'newest') params.set('sort', sort);

        const view = $('.view-toggle .btn-primary').data('view');
        if (view === 'list') params.set('view', 'list');

        $('.filter-checkbox:checked').each(function() {
            const name = $(this).data('filter');
            const vals = params.getAll(name);
            vals.push($(this).val());
            params.delete(name);
            vals.forEach(v => params.append(name, v));
        });

        const minPrice = $('input[name="min_price"]').val();
        const maxPrice = $('input[name="max_price"]').val();
        if (minPrice) params.set('min_price', minPrice);
        if (maxPrice) params.set('max_price', maxPrice);

        const rating = $('input[name="rating"]:checked').val();
        if (rating && rating !== '0') params.set('rating', rating);

        return params;
    }

    function loadProducts(page) {
        if (isLoading) return;
        isLoading = true;

        const params = getFilterParams();
        params.set('ajax', '1');
        if (page) params.set('page', page);

        const url = '<?= SITE_URL ?>/shop.php?' + params.toString();

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#productsContainer').html(response.html);
                $('#paginationContainer').html(response.pagination);
                $('.result-count').text(response.count);
                window.history.replaceState({}, '', response.url);
            },
            complete: function() {
                isLoading = false;
            }
        });
    }

    // Sort change
    $('#sortSelect').on('change', function() {
        loadProducts(1);
    });

    // View toggle
    $('.view-toggle button').on('click', function() {
        $('.view-toggle button').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
        loadProducts(1);
    });

    // Filter checkboxes
    $('.filter-checkbox').on('change', function() {
        loadProducts(1);
    });

    // Rating filter
    $('.rating-filter').on('change', function() {
        loadProducts(1);
    });

    // Price filter
    $('#applyPrice').on('click', function() {
        loadProducts(1);
    });

    // Pagination click (delegated)
    $(document).on('click', '#paginationContainer .page-link', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const match = href.match(/[?&]page=(\d+)/);
        if (match) {
            loadProducts(parseInt(match[1]));
        }
    });

    // Mobile filter toggle
    $('#filterToggle').on('click', function() {
        $('.shop-sidebar').toggleClass('show');
    });

    $('#closeSidebar').on('click', function() {
        $('.shop-sidebar').removeClass('show');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
