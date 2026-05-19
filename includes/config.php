<?php
// Database Configuration
define('DB_HOST', 'localhost:3307');
define('DB_NAME', 'ecomai');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('SITE_NAME', 'Trend_Aura');
define('SITE_TAGLINE', 'Your Premium Shopping Destination');
define('SITE_EMAIL', 'support@trendaura.com');
define('SITE_URL', 'http://localhost/e-com-ai');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// Security
define('CSRF_TOKEN_SECRET', 'ecomai_csrf_secret_key_2026');
define('SESSION_TIMEOUT', 86400); // 24 hours
define('REMEMBER_TOKEN_EXPIRY', 2592000); // 30 days

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// Currency
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');

// Tax & Shipping
define('TAX_RATE', 18); // percentage
define('FREE_SHIPPING_MIN', 499);
define('SHIPPING_CHARGE', 49);

// Session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// Error Reporting (disabled for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');
