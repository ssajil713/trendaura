-- eComAI - Complete eCommerce Database Schema
-- MySQL 8+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS ecomai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecomai;

-- ============================
-- USERS TABLE
-- ============================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB;

-- ============================
-- USER ADDRESSES TABLE
-- ============================
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_type ENUM('billing','shipping') DEFAULT 'shipping',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    street_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'India',
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_addresses_user (user_id)
) ENGINE=InnoDB;

-- ============================
-- PASSWORD RESETS TABLE
-- ============================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_token (token)
) ENGINE=InnoDB;

-- ============================
-- ADMINS TABLE
-- ============================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('super_admin','admin','manager') DEFAULT 'admin',
    avatar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admins_email (email)
) ENGINE=InnoDB;

-- ============================
-- CATEGORIES TABLE
-- ============================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    parent_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_categories_slug (slug),
    INDEX idx_categories_active (is_active),
    INDEX idx_categories_parent (parent_id)
) ENGINE=InnoDB;

-- ============================
-- BRANDS TABLE
-- ============================
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    logo VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_brands_slug (slug)
) ENGINE=InnoDB;

-- ============================
-- PRODUCTS TABLE
-- ============================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    specifications JSON,
    sku VARCHAR(50) UNIQUE,
    regular_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    discount_percent INT DEFAULT 0,
    category_id INT DEFAULT NULL,
    brand_id INT DEFAULT NULL,
    stock_quantity INT DEFAULT 0,
    stock_status ENUM('in_stock','out_of_stock','on_backorder') DEFAULT 'in_stock',
    is_featured TINYINT(1) DEFAULT 0,
    is_trending TINYINT(1) DEFAULT 0,
    is_new TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    weight DECIMAL(8,2) DEFAULT NULL,
    dimensions VARCHAR(100) DEFAULT NULL,
    meta_title VARCHAR(200),
    meta_description VARCHAR(500),
    total_sales INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    INDEX idx_products_slug (slug),
    INDEX idx_products_category (category_id),
    INDEX idx_products_brand (brand_id),
    INDEX idx_products_active (is_active),
    INDEX idx_products_featured (is_featured),
    INDEX idx_products_price (regular_price, sale_price),
    INDEX idx_products_rating (average_rating),
    INDEX idx_products_sales (total_sales),
    FULLTEXT INDEX idx_products_search (name, description)
) ENGINE=InnoDB;

-- ============================
-- PRODUCT IMAGES TABLE
-- ============================
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB;

-- ============================
-- CARTS TABLE
-- ============================
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(255) DEFAULT NULL,
    coupon_id INT DEFAULT NULL,
    coupon_discount DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    shipping DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_carts_user (user_id),
    INDEX idx_carts_session (session_id)
) ENGINE=InnoDB;

-- ============================
-- CART ITEMS TABLE
-- ============================
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_cart_items_cart (cart_id),
    INDEX idx_cart_items_product (product_id)
) ENGINE=InnoDB;

-- ============================
-- WISHLISTS TABLE
-- ============================
CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uk_wishlist_user_product (user_id, product_id),
    INDEX idx_wishlists_user (user_id)
) ENGINE=InnoDB;

-- ============================
-- ORDERS TABLE
-- ============================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0.00,
    shipping DECIMAL(10,2) DEFAULT 0.00,
    coupon_discount DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cod','online','bank_transfer') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    order_status ENUM('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    shipping_address_id INT DEFAULT NULL,
    billing_address_id INT DEFAULT NULL,
    notes TEXT,
    invoice_number VARCHAR(50),
    tracking_number VARCHAR(100),
    paid_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_orders_number (order_number),
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (order_status),
    INDEX idx_orders_payment (payment_status),
    INDEX idx_orders_date (created_at)
) ENGINE=InnoDB;

-- ============================
-- ORDER ITEMS TABLE
-- ============================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_image VARCHAR(255),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB;

-- ============================
-- PAYMENTS TABLE
-- ============================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_method ENUM('cod','online','bank_transfer') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    payment_data JSON,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_payments_order (order_id),
    INDEX idx_payments_transaction (transaction_id)
) ENGINE=InnoDB;

-- ============================
-- COUPONS TABLE
-- ============================
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    discount_type ENUM('percentage','fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_coupons_code (code),
    INDEX idx_coupons_active (is_active)
) ENGINE=InnoDB;

-- ============================
-- REVIEWS TABLE
-- ============================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reviews_product (product_id),
    INDEX idx_reviews_user (user_id),
    INDEX idx_reviews_approved (is_approved)
) ENGINE=InnoDB;

-- ============================
-- BANNERS TABLE
-- ============================
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    subtitle VARCHAR(500),
    description TEXT,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(500),
    btn_text VARCHAR(50) DEFAULT 'Shop Now',
    position ENUM('hero','promo','offer','sidebar') DEFAULT 'hero',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_banners_active (is_active),
    INDEX idx_banners_position (position)
) ENGINE=InnoDB;

-- ============================
-- SETTINGS TABLE
-- ============================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_settings_key (setting_key),
    INDEX idx_settings_group (setting_group)
) ENGINE=InnoDB;

-- ============================
-- ACTIVITY LOGS TABLE
-- ============================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_user (user_id),
    INDEX idx_logs_admin (admin_id),
    INDEX idx_logs_action (action),
    INDEX idx_logs_date (created_at)
) ENGINE=InnoDB;

-- ============================
-- NEWSLETTER TABLE
-- ============================
CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_newsletter_email (email)
) ENGINE=InnoDB;

-- ============================
-- INSERT DEFAULT DATA
-- ============================

-- Default Admin (password: admin123)
INSERT INTO admins (full_name, email, password, role) VALUES
('Super Admin', 'admin@ecomai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Default Settings
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'Trend_Aura', 'general'),
('site_tagline', 'Your Premium Shopping Destination', 'general'),
('site_email', 'support@trendaura.com', 'general'),
('site_phone', '+91 1800-123-4567', 'general'),
('site_address', '123 Business Park, Mumbai, India 400001', 'general'),
('currency_symbol', '₹', 'general'),
('currency_code', 'INR', 'general'),
('tax_rate', '18', 'general'),
('free_shipping_min', '499', 'general'),
('shipping_charge', '49', 'general'),
('facebook_url', 'https://facebook.com/trendaura', 'social'),
('instagram_url', 'https://instagram.com/trendaura', 'social'),
('twitter_url', 'https://twitter.com/trendaura', 'social'),
('youtube_url', 'https://youtube.com/@trendaura', 'social');

-- Sample Brands
INSERT INTO brands (name, slug, description) VALUES
('TechPro', 'techpro', 'Premium electronics and gadgets'),
('StyleCraft', 'stylecraft', 'Modern fashion and apparel'),
('HomeElite', 'homeelite', 'Luxury home & living products'),
('SportFlex', 'sportflex', 'Performance sports & fitness gear'),
('BeautyGlow', 'beautyglow', 'Premium beauty & skincare products');

-- Sample Categories
INSERT INTO categories (name, slug, description, parent_id, sort_order) VALUES
('Electronics', 'electronics', 'Latest electronics & gadgets', NULL, 1),
('Fashion', 'fashion', 'Trendy fashion & apparel', NULL, 2),
('Home & Living', 'home-living', 'Beautiful home decor & furniture', NULL, 3),
('Sports & Fitness', 'sports-fitness', 'Sports equipment & fitness gear', NULL, 4),
('Beauty & Health', 'beauty-health', 'Beauty products & healthcare', NULL, 5),
('Mobile Phones', 'mobile-phones', 'Smartphones & accessories', 1, 1),
('Laptops', 'laptops', 'Laptops & computers', 1, 2),
('Headphones', 'headphones', 'Audio & headphones', 1, 3),
('Men Fashion', 'men-fashion', 'Clothing for men', 2, 1),
('Women Fashion', 'women-fashion', 'Clothing for women', 2, 2),
('Kids Fashion', 'kids-fashion', 'Clothing for kids', 2, 3);

-- Sample Products
INSERT INTO products (name, slug, description, short_description, sku, regular_price, sale_price, discount_percent, category_id, brand_id, stock_quantity, is_featured, is_trending, is_new, total_sales, average_rating, review_count, meta_title) VALUES
('Wireless Noise-Cancelling Headphones Pro', 'wireless-noise-cancelling-headphones-pro', 'Experience crystal-clear audio with our premium wireless headphones featuring active noise cancellation, 30-hour battery life, and premium memory foam ear cushions. Perfect for music lovers and professionals.', 'Premium wireless headphones with ANC, 30hr battery, premium comfort', 'TECH-HP-001', 2999.00, 1999.00, 33, 8, 1, 150, 1, 1, 0, 342, 4.5, 128, 'Wireless Noise-Cancelling Headphones - Trend_Aura'),
('Smart Watch Ultra X2', 'smart-watch-ultra-x2', 'Advanced smartwatch with AMOLED display, GPS tracking, heart rate monitor, SpO2 sensor, sleep tracking, and 14-day battery life. Water resistant to 50m.', 'Advanced smartwatch with AMOLED, GPS, health tracking', 'TECH-SW-002', 4999.00, 3999.00, 20, 1, 1, 100, 1, 1, 1, 567, 4.7, 234, 'Smart Watch Ultra X2 - Trend_Aura'),
('Premium Cotton Casual Shirt', 'premium-cotton-casual-shirt', 'Premium quality 100% organic cotton casual shirt with modern fit design. Available in multiple colors. Breathable fabric perfect for all seasons.', '100% organic cotton casual shirt, modern fit, breathable', 'FASH-CS-001', 1499.00, 999.00, 33, 9, 2, 200, 1, 0, 1, 891, 4.3, 89, 'Premium Cotton Casual Shirt - Trend_Aura'),
('Designer Handbag Collection', 'designer-handbag-collection', 'Elegant designer handbag crafted from premium vegan leather. Features multiple compartments, gold-tone hardware, and adjustable shoulder strap.', 'Elegant vegan leather handbag with multiple compartments', 'FASH-HB-001', 3999.00, 2499.00, 38, 10, 2, 75, 1, 1, 0, 445, 4.6, 167, 'Designer Handbag Collection - Trend_Aura'),
('Ergonomic Office Chair', 'ergonomic-office-chair', 'Professional ergonomic office chair with lumbar support, adjustable armrests, breathable mesh back, and premium cushioning for all-day comfort.', 'Professional ergonomic chair with lumbar support, mesh back', 'HOME-CH-001', 12999.00, 8999.00, 31, 3, 3, 50, 1, 0, 0, 234, 4.4, 92, 'Ergonomic Office Chair - Trend_Aura'),
('Performance Running Shoes', 'performance-running-shoes', 'Lightweight performance running shoes with responsive cushioning, breathable mesh upper, and durable outsole. Designed for serious runners.', 'Lightweight running shoes with responsive cushioning', 'SPORT-RS-001', 4999.00, 3499.00, 30, 4, 4, 120, 1, 1, 1, 678, 4.8, 312, 'Performance Running Shoes - Trend_Aura'),
('Organic Skincare Kit', 'organic-skincare-kit', 'Complete organic skincare routine with face wash, toner, serum, and moisturizer. Made with natural ingredients. Suitable for all skin types.', 'Complete organic skincare routine, natural ingredients', 'BEAUTY-SK-001', 2499.00, 1499.00, 40, 5, 5, 90, 1, 1, 1, 523, 4.6, 198, 'Organic Skincare Kit - Trend_Aura'),
('Bluetooth Portable Speaker', 'bluetooth-portable-speaker', 'Waterproof portable Bluetooth speaker with 360-degree sound, 20-hour battery, and built-in microphone. Perfect for outdoor adventures.', 'Waterproof Bluetooth speaker, 360 sound, 20hr battery', 'TECH-SP-003', 1999.00, 1299.00, 35, 1, 1, 180, 0, 1, 0, 756, 4.4, 145, 'Bluetooth Portable Speaker - Trend_Aura'),
('Slim Fit Denim Jeans', 'slim-fit-denim-jeans', 'Classic slim fit denim jeans crafted from premium stretch denim. Modern look with comfortable fit. Available in multiple washes.', 'Premium stretch denim jeans, slim fit, modern look', 'FASH-DJ-002', 1999.00, 1299.00, 35, 9, 2, 160, 0, 0, 0, 667, 4.2, 76, 'Slim Fit Denim Jeans - Trend_Aura'),
('LED Desk Lamp', 'led-desk-lamp', 'Modern LED desk lamp with touch control, adjustable brightness, USB charging port, and flexible neck. Eye-care technology reduces strain.', 'Touch control LED lamp with USB port, adjustable', 'HOME-LM-002', 2499.00, 1499.00, 40, 3, 3, 110, 0, 0, 0, 345, 4.3, 88, 'LED Desk Lamp - Trend_Aura'),
('Fitness Tracker Band', 'fitness-tracker-band', 'Sleek fitness tracker with heart rate monitor, step counter, sleep analysis, call notifications, and 7-day battery life. Water resistant.', 'Sleek fitness band with HR monitor, sleep tracking', 'SPORT-FT-002', 1999.00, 999.00, 50, 4, 4, 200, 0, 0, 1, 890, 4.5, 234, 'Fitness Tracker Band - Trend_Aura'),
('Vitamin C Brightening Serum', 'vitamin-c-brightening-serum', 'Powerful Vitamin C serum with hyaluronic acid and vitamin E. Brightens skin, reduces dark spots, and boosts collagen production.', 'Vitamin C serum with hyaluronic acid, brightening', 'BEAUTY-VC-002', 1299.00, 799.00, 38, 5, 5, 85, 0, 0, 0, 432, 4.7, 156, 'Vitamin C Brightening Serum - Trend_Aura'),
('USB-C Fast Charger 65W', 'usb-c-fast-charger-65w', 'GaN technology 65W fast charger compatible with laptops, tablets, and smartphones. Compact design with universal compatibility.', '65W GaN fast charger, universal compatibility', 'TECH-CH-004', 2499.00, 1499.00, 40, 1, 1, 250, 0, 0, 1, 567, 4.6, 189, 'USB-C Fast Charger 65W - Trend_Aura'),
('Women Summer Floral Dress', 'women-summer-floral-dress', 'Beautiful summer floral dress in premium cotton fabric. Features elegant floral print, comfortable fit, and adjustable waist tie.', 'Premium cotton floral dress, elegant summer style', 'FASH-WD-003', 2499.00, 1799.00, 28, 10, 2, 95, 0, 0, 1, 534, 4.4, 112, 'Women Summer Floral Dress - Trend_Aura'),
('Stainless Steel Water Bottle', 'stainless-steel-water-bottle', 'Double-wall vacuum insulated water bottle. Keeps drinks cold 24hrs or hot 12hrs. BPA-free, leak-proof design. 750ml capacity.', 'Vacuum insulated bottle, 24hr cold, BPA-free', 'HOME-BT-003', 999.00, 699.00, 30, 3, 3, 300, 0, 0, 0, 789, 4.5, 167, 'Stainless Steel Water Bottle - Trend_Aura');

-- Sample Product Images
INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
(1, 'assets/uploads/products/headphones-1.jpg', 1, 1),
(1, 'assets/uploads/products/headphones-2.jpg', 0, 2),
(2, 'assets/uploads/products/smartwatch-1.jpg', 1, 1),
(2, 'assets/uploads/products/smartwatch-2.jpg', 0, 2),
(3, 'assets/uploads/products/shirt-1.jpg', 1, 1),
(4, 'assets/uploads/products/handbag-1.jpg', 1, 1),
(5, 'assets/uploads/products/chair-1.jpg', 1, 1),
(6, 'assets/uploads/products/shoes-1.jpg', 1, 1),
(7, 'assets/uploads/products/skincare-1.jpg', 1, 1),
(8, 'assets/uploads/products/speaker-1.jpg', 1, 1),
(9, 'assets/uploads/products/jeans-1.jpg', 1, 1),
(10, 'assets/uploads/products/lamp-1.jpg', 1, 1),
(11, 'assets/uploads/products/fitness-band-1.jpg', 1, 1),
(12, 'assets/uploads/products/serum-1.jpg', 1, 1),
(13, 'assets/uploads/products/charger-1.jpg', 1, 1),
(14, 'assets/uploads/products/dress-1.jpg', 1, 1),
(15, 'assets/uploads/products/bottle-1.jpg', 1, 1);

-- Sample Banners
INSERT INTO banners (title, subtitle, description, image, link, btn_text, position, sort_order) VALUES
('Summer Sale Extravaganza', 'Up to 60% Off on Premium Collection', 'Discover the latest trends with amazing discounts on fashion, electronics, and more. Limited time offer!', 'assets/uploads/banners/banner-1.jpg', 'shop.php', 'Shop Sale', 'hero', 1),
('New Arrivals 2026', 'Fresh Styles Just Dropped', 'Be the first to explore our newest collection featuring cutting-edge designs and premium quality products.', 'assets/uploads/banners/banner-2.jpg', 'shop.php?sort=newest', 'Explore Now', 'hero', 2),
('Premium Electronics', 'Tech That Elevates Your Life', 'From wireless headphones to smartwatches - get the best deals on top-rated electronics.', 'assets/uploads/banners/banner-3.jpg', 'shop.php?category=1', 'Shop Electronics', 'hero', 3),
('Free Shipping', 'On orders above ₹499', 'Enjoy free delivery on all prepaid orders above ₹499. Shop from the comfort of your home!', 'assets/uploads/banners/promo-1.jpg', 'shop.php', 'Shop Now', 'promo', 1);

-- Sample Coupons
INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, usage_limit, expires_at) VALUES
('WELCOME20', 'Welcome discount for new customers', 'percentage', 20, 999.00, 500.00, 500, '2027-12-31 23:59:59'),
('SAVE500', 'Flat ₹500 off on orders above ₹2999', 'fixed', 500, 2999.00, NULL, 200, '2026-12-31 23:59:59'),
('FREESHIP', 'Free shipping on all orders', 'percentage', 100, 0, 0, NULL, '2026-12-31 23:59:59'),
('TRENDY15', '15% off on fashion collection', 'percentage', 15, 1499.00, 750.00, 300, '2026-12-31 23:59:59');

-- ============================
-- INSERT SAMPLE USERS
-- ============================
INSERT INTO users (full_name, email, password, phone, is_active) VALUES
('Rahul Sharma', 'rahul@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98765 43210', 1),
('Priya Patel', 'priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 87654 32109', 1);

-- Sample Reviews
INSERT INTO reviews (product_id, user_id, rating, title, comment, is_approved) VALUES
(1, 1, 5, 'Amazing sound quality!', 'The noise cancellation is incredible. Battery life is outstanding. Highly recommended!', 1),
(2, 1, 4, 'Great smartwatch', 'Excellent features for the price. The AMOLED display is vibrant and responsive.', 1),
(3, 1, 5, 'Perfect fit!', 'The fabric is super comfortable and the fit is perfect. Great for casual wear.', 1);

-- Sample Orders
INSERT INTO orders (order_number, user_id, subtotal, tax, shipping, total, payment_method, payment_status, order_status, created_at) VALUES
('ORD-2026-00001', 1, 3999.00, 719.82, 0.00, 4718.82, 'cod', 'pending', 'pending', '2026-05-15 10:30:00'),
('ORD-2026-00002', 1, 1999.00, 359.82, 49.00, 2407.82, 'online', 'paid', 'delivered', '2026-05-10 14:15:00'),
('ORD-2026-00003', 2, 6498.00, 1169.64, 0.00, 7667.64, 'cod', 'paid', 'processing', '2026-05-18 09:45:00');

-- Sample Order Items
INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, unit_price, total_price) VALUES
(1, 4, 'Designer Handbag Collection', 'assets/uploads/products/handbag-1.jpg', 1, 3999.00, 3999.00),
(2, 3, 'Premium Cotton Casual Shirt', 'assets/uploads/products/shirt-1.jpg', 2, 999.00, 1998.00),
(3, 2, 'Smart Watch Ultra X2', 'assets/uploads/products/smartwatch-1.jpg', 1, 4999.00, 4999.00),
(3, 8, 'Bluetooth Portable Speaker', 'assets/uploads/products/speaker-1.jpg', 1, 1499.00, 1499.00);
