<?php
require_once __DIR__ . '/db.php';

// ==================== SECURITY FUNCTIONS ====================

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[+]?[\d\s()-]{10,15}$/', $phone);
}

function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function generateOrderNumber() {
    $year = date('Y');
    $prefix = 'ORD-' . $year . '-';
    $db = db();
    $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING(order_number, LENGTH(:prefix) + 1) AS UNSIGNED)) as max_num FROM orders WHERE order_number LIKE :like_prefix");
    $likePrefix = $prefix . '%';
    $stmt->execute(['prefix' => $prefix, 'like_prefix' => $likePrefix]);
    $row = $stmt->fetch();
    $nextNum = ($row['max_num'] ?? 0) + 1;
    return $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
}

// ==================== FORMATTING FUNCTIONS ====================

function formatPrice($price) {
    return CURRENCY_SYMBOL . ' ' . number_format($price, 0);
}

function formatDate($date, $format = 'd M Y, h:i A') {
    return date($format, strtotime($date));
}

function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function getDiscountPercent($regular, $sale) {
    if ($regular > 0 && $sale > 0 && $sale < $regular) {
        return round((($regular - $sale) / $regular) * 100);
    }
    return 0;
}

// ==================== PRODUCT FUNCTIONS ====================

function getProduct($slug) {
    $db = db();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug,
                          b.name as brand_name, b.slug as brand_slug
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          LEFT JOIN brands b ON p.brand_id = b.id
                          WHERE p.slug = :slug AND p.is_active = 1");
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetch();
}

function getProductById($id) {
    $db = db();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug,
                          b.name as brand_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          LEFT JOIN brands b ON p.brand_id = b.id
                          WHERE p.id = :id AND p.is_active = 1");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

function getProductImages($productId) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, is_primary DESC");
    $stmt->execute(['product_id' => $productId]);
    return $stmt->fetchAll();
}

function getFeaturedProducts($limit = 8) {
    $db = db();
    $stmt = $db->prepare("SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                          FROM products p WHERE p.is_active = 1 AND p.is_featured = 1
                          ORDER BY p.created_at DESC LIMIT :limit");
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTrendingProducts($limit = 8) {
    $db = db();
    $stmt = $db->prepare("SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                          FROM products p WHERE p.is_active = 1 AND p.is_trending = 1
                          ORDER BY p.total_sales DESC LIMIT :limit");
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getNewArrivals($limit = 8) {
    $db = db();
    $stmt = $db->prepare("SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                          FROM products p WHERE p.is_active = 1 AND p.is_new = 1
                          ORDER BY p.created_at DESC LIMIT :limit");
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getBestSellers($limit = 8) {
    $db = db();
    $stmt = $db->prepare("SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                          FROM products p WHERE p.is_active = 1
                          ORDER BY p.total_sales DESC LIMIT :limit");
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRelatedProducts($categoryId, $productId, $limit = 4) {
    $db = db();
    $stmt = $db->prepare("SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                          FROM products p WHERE p.is_active = 1 AND p.category_id = :category_id AND p.id != :product_id
                          ORDER BY RAND() LIMIT :limit");
    $stmt->bindValue('category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindValue('product_id', $productId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function searchProducts($query, $limit = 5) {
    $db = db();
    $stmt = $db->prepare("SELECT p.id, p.name, p.slug, p.regular_price, p.sale_price,
                          (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                          FROM products p WHERE p.is_active = 1 AND (p.name LIKE :query OR p.description LIKE :query2)
                          LIMIT :limit");
    $searchQuery = '%' . $query . '%';
    $stmt->bindValue('query', $searchQuery, PDO::PARAM_STR);
    $stmt->bindValue('query2', $searchQuery, PDO::PARAM_STR);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ==================== CART FUNCTIONS ====================

function getCart() {
    $db = db();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    if ($userId) {
        $stmt = $db->prepare("SELECT * FROM carts WHERE user_id = :user_id ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM carts WHERE session_id = :session_id ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute(['session_id' => $sessionId]);
    }
    return $stmt->fetch();
}

function getCartItems($cartId) {
    $db = db();
    $stmt = $db->prepare("SELECT ci.*, p.name, p.slug, p.regular_price, p.sale_price, p.stock_quantity, p.stock_status,
                          (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
                          FROM cart_items ci
                          JOIN products p ON ci.product_id = p.id
                          WHERE ci.cart_id = :cart_id");
    $stmt->execute(['cart_id' => $cartId]);
    return $stmt->fetchAll();
}

function getCartCount() {
    $cart = getCart();
    if (!$cart) return 0;
    $items = getCartItems($cart['id']);
    return array_sum(array_column($items, 'quantity'));
}

function getCartTotal() {
    $cart = getCart();
    if (!$cart) return ['subtotal' => 0, 'discount' => 0, 'tax' => 0, 'shipping' => 0, 'total' => 0];
    $items = getCartItems($cart['id']);
    $subtotal = array_sum(array_column($items, 'total_price'));
    return [
        'subtotal' => $subtotal,
        'discount' => $cart['coupon_discount'],
        'tax' => $cart['tax'],
        'shipping' => $cart['shipping'],
        'total' => $cart['total']
    ];
}

// ==================== WISHLIST FUNCTIONS ====================

function getWishlist($userId) {
    $db = db();
    $stmt = $db->prepare("SELECT w.*, p.name, p.slug, p.regular_price, p.sale_price, p.stock_status,
                          (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
                          FROM wishlists w
                          JOIN products p ON w.product_id = p.id
                          WHERE w.user_id = :user_id
                          ORDER BY w.created_at DESC");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function isInWishlist($userId, $productId) {
    $db = db();
    $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    return $stmt->fetch() ? true : false;
}

// ==================== CATEGORY FUNCTIONS ====================

function getCategories($parentId = null, $activeOnly = true) {
    $db = db();
    $sql = "SELECT * FROM categories WHERE 1=1";
    $params = [];
    if ($parentId === null) {
        $sql .= " AND parent_id IS NULL";
    } else {
        $sql .= " AND parent_id = :parent_id";
        $params['parent_id'] = $parentId;
    }
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCategoryTree($parentId = null) {
    $categories = getCategories($parentId);
    foreach ($categories as &$category) {
        $children = getCategoryTree($category['id']);
        $category['children'] = $children;
    }
    return $categories;
}

// ==================== BRAND FUNCTIONS ====================

function getBrands($activeOnly = true) {
    $db = db();
    $sql = "SELECT * FROM brands";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    return $db->query($sql)->fetchAll();
}

// ==================== RATING FUNCTIONS ====================

function renderStars($rating, $size = 'sm') {
    $html = '<div class="stars stars-' . $size . '">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}

// ==================== SETTINGS FUNCTIONS ====================

function getSetting($key, $default = '') {
    $db = db();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :setting_key");
    $stmt->execute(['setting_key' => $key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function getSettings($group = null) {
    $db = db();
    if ($group) {
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_group = :setting_group");
        $stmt->execute(['setting_group' => $group]);
    } else {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    }
    $result = $stmt->fetchAll();
    $settings = [];
    foreach ($result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

// ==================== ALERT / NOTIFICATION FUNCTIONS ====================

function setAlert($type, $message) {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

function displayAlert() {
    $alert = getAlert();
    if ($alert) {
        $icons = [
            'success' => 'fa-check-circle',
            'error' => 'fa-times-circle',
            'warning' => 'fa-exclamation-triangle',
            'info' => 'fa-info-circle'
        ];
        $icon = $icons[$alert['type']] ?? 'fa-info-circle';
        return '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">
                    <i class="fas ' . $icon . ' me-2"></i>' . $alert['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    return '';
}

// ==================== LOG ACTIVITY ====================

function logActivity($userId, $action, $description, $isAdmin = false) {
    $db = db();
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, admin_id, action, description, ip_address, user_agent)
                          VALUES (:user_id, :admin_id, :action, :description, :ip_address, :user_agent)");
    $stmt->execute([
        'user_id' => $isAdmin ? null : $userId,
        'admin_id' => $isAdmin ? $userId : null,
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// ==================== PAGINATION ====================

function paginate($total, $currentPage, $perPage, $url = '?') {
    $totalPages = ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous
    $disabled = $currentPage <= 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $disabled . '">
                <a class="page-link" href="' . $url . 'page=' . ($currentPage - 1) . '" aria-label="Previous">
                    <i class="fas fa-chevron-left"></i>
                </a>
              </li>';

    // Pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '">
                    <a class="page-link" href="' . $url . 'page=' . $i . '">' . $i . '</a>
                  </li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }

    // Next
    $disabled = $currentPage >= $totalPages ? ' disabled' : '';
    $html .= '<li class="page-item' . $disabled . '">
                <a class="page-link" href="' . $url . 'page=' . ($currentPage + 1) . '" aria-label="Next">
                    <i class="fas fa-chevron-right"></i>
                </a>
              </li>';

    $html .= '</ul></nav>';
    return $html;
}

// ==================== IMAGE FUNCTIONS ====================

function uploadImage($file, $directory, $allowedTypes = ['jpg','jpeg','png','webp','gif'], $maxSize = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Maximum size: ' . ($maxSize / 1048576) . 'MB'];
    }

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadPath = UPLOAD_PATH . $directory . '/' . $filename;

    if (!is_dir(dirname($uploadPath))) {
        mkdir(dirname($uploadPath), 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'path' => 'assets/uploads/' . $directory . '/' . $filename];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

function deleteImage($path) {
    $fullPath = __DIR__ . '/../' . $path;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

// ==================== COUPON FUNCTIONS ====================

function validateCoupon($code, $subtotal) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code AND is_active = 1
                          AND (expires_at IS NULL OR expires_at > NOW())
                          AND (starts_at IS NULL OR starts_at <= NOW())
                          AND (usage_limit IS NULL OR used_count < usage_limit)");
    $stmt->execute(['code' => $code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'error' => 'Invalid or expired coupon code'];
    }

    if ($subtotal < $coupon['min_order_amount']) {
        return ['valid' => false, 'error' => 'Minimum order amount of ' . formatPrice($coupon['min_order_amount']) . ' required'];
    }

    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($subtotal * $coupon['discount_value']) / 100;
        if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = min($coupon['discount_value'], $subtotal);
    }

    return ['valid' => true, 'coupon' => $coupon, 'discount' => $discount];
}

// ==================== ORDER FUNCTIONS ====================

function getOrders($userId, $limit = 10) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getOrderItems($orderId) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $orderId]);
    return $stmt->fetchAll();
}

function getOrderDetails($orderNumber) {
    $db = db();
    $stmt = $db->prepare("SELECT o.*, a1.full_name as ship_name, a1.phone as ship_phone,
                          a1.street_address as ship_address, a1.city as ship_city,
                          a1.state as ship_state, a1.postal_code as ship_zip,
                          a2.full_name as bill_name, a2.phone as bill_phone,
                          a2.street_address as bill_address, a2.city as bill_city,
                          a2.state as bill_state, a2.postal_code as bill_zip
                          FROM orders o
                          LEFT JOIN addresses a1 ON o.shipping_address_id = a1.id
                          LEFT JOIN addresses a2 ON o.billing_address_id = a2.id
                          WHERE o.order_number = :order_number");
    $stmt->execute(['order_number' => $orderNumber]);
    return $stmt->fetch();
}

// ==================== RECENTLY VIEWED ====================

function addRecentlyViewed($productId) {
    if (!isset($_SESSION['recently_viewed'])) {
        $_SESSION['recently_viewed'] = [];
    }
    $key = array_search($productId, $_SESSION['recently_viewed']);
    if ($key !== false) {
        unset($_SESSION['recently_viewed'][$key]);
    }
    array_unshift($_SESSION['recently_viewed'], $productId);
    $_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 6);
}

function getRecentlyViewed($limit = 4) {
    if (empty($_SESSION['recently_viewed'])) return [];
    $ids = array_slice($_SESSION['recently_viewed'], 0, $limit);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db = db();
    $stmt = $db->prepare("SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
                          FROM products p WHERE p.id IN ($placeholders) AND p.is_active = 1");
    $stmt->execute($ids);
    return $stmt->fetchAll();
}
