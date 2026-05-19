<?php
require_once __DIR__ . '/../../includes/session.php';
requireAdminLogin();

$currentAdmin = getCurrentAdmin();
$pageTitle = $pageTitle ?? 'Dashboard';

// Get unread counts for sidebar
$db = db();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$lowStockProducts = $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10 AND is_active = 1")->fetchColumn();
$pendingReviews = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_NAME ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
    <link href="<?= ADMIN_URL ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="<?= ADMIN_URL ?>/dashboard.php" class="sidebar-brand">
                    <i class="fas fa-shopping-bag"></i>
                    <span><?= SITE_NAME ?></span>
                </a>
                <button class="sidebar-close d-xl-none" id="sidebarClose"><i class="fas fa-times"></i></button>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">Main</div>
                <a href="<?= ADMIN_URL ?>/dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i><span>Dashboard</span>
                </a>
                <div class="nav-section">Management</div>
                <a href="<?= ADMIN_URL ?>/pages/products/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'products') !== false ? 'active' : '' ?>">
                    <i class="fas fa-box"></i><span>Products</span>
                </a>
                <a href="<?= ADMIN_URL ?>/pages/categories/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'categories') !== false ? 'active' : '' ?>">
                    <i class="fas fa-list"></i><span>Categories</span>
                </a>
                <a href="<?= ADMIN_URL ?>/pages/brands/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'brands') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tag"></i><span>Brands</span>
                </a>
                <a href="<?= ADMIN_URL ?>/pages/orders/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'orders') !== false ? 'active' : '' ?>">
                    <i class="fas fa-truck"></i><span>Orders</span>
                    <?php if ($pendingOrders > 0): ?><span class="badge bg-warning"><?= $pendingOrders ?></span><?php endif; ?>
                </a>
                <a href="<?= ADMIN_URL ?>/pages/coupons/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'coupons') !== false ? 'active' : '' ?>">
                    <i class="fas fa-percent"></i><span>Coupons</span>
                </a>
                <a href="<?= ADMIN_URL ?>/pages/reviews/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'reviews') !== false ? 'active' : '' ?>">
                    <i class="fas fa-star"></i><span>Reviews</span>
                    <?php if ($pendingReviews > 0): ?><span class="badge bg-warning"><?= $pendingReviews ?></span><?php endif; ?>
                </a>
                <a href="<?= ADMIN_URL ?>/pages/banners/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'banners') !== false ? 'active' : '' ?>">
                    <i class="fas fa-images"></i><span>Banners</span>
                </a>
                <div class="nav-section">Users</div>
                <a href="<?= ADMIN_URL ?>/pages/users/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users"></i><span>Users</span>
                </a>
                <div class="nav-section">System</div>
                <a href="<?= ADMIN_URL ?>/pages/settings.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i><span>Settings</span>
                </a>
                <a href="<?= ADMIN_URL ?>/logout.php" class="nav-item text-danger">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </nav>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-topbar">
                <button class="topbar-toggle d-xl-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <div class="topbar-title"><?= $pageTitle ?></div>
                <div class="topbar-right">
                    <div class="dropdown">
                        <button class="btn btn-admin-ghost dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?= sanitizeInput($currentAdmin['full_name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>"><i class="fas fa-external-link-alt me-2"></i>View Store</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= ADMIN_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            <div class="admin-content">
                <?php
                $alert = getAlert();
                if ($alert): ?>
                <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
                    <?= $alert['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
