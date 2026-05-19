<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Auto-login via remember me
checkRememberMe();

// Initialize CSRF token
generateCSRFToken();

// Get cart count for header
$cartCount = getCartCount();
