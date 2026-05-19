<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME . ' - ' . SITE_TAGLINE ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? $pageDesc : 'Shop the best products at amazing prices. ' . SITE_TAGLINE ?>">
    <meta name="keywords" content="ecommerce, online shopping, best deals">
    <meta property="og:title" content="<?= isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME ?>">
    <meta property="og:description" content="<?= isset($pageDesc) ? $pageDesc : SITE_TAGLINE ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL ?>">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <!-- Swiper Slider -->
    <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet">
    <!-- Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <!-- Main Stylesheet -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">
</head>
<body>
    <!-- ====== TOP BAR ====== -->
    <div class="top-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="top-bar-left">
                        <a href="mailto:<?= getSetting('site_email') ?>"><i class="fas fa-envelope"></i> <?= getSetting('site_email') ?></a>
                        <a href="tel:<?= getSetting('site_phone') ?>"><i class="fas fa-phone-alt"></i> <?= getSetting('site_phone') ?></a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="top-bar-right">
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= SITE_URL ?>/account/dashboard.php"><i class="fas fa-user"></i> My Account</a>
                            <a href="<?= SITE_URL ?>/account/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                            <a href="<?= SITE_URL ?>/account/orders.php"><i class="fas fa-box"></i> Orders</a>
                            <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                            <a href="<?= SITE_URL ?>/register.php"><i class="fas fa-user-plus"></i> Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== MAIN HEADER ====== -->
    <header class="main-header">
        <div class="container">
            <div class="header-wrapper">
                <div class="header-left">
                    <button class="mobile-menu-btn d-lg-none" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="<?= SITE_URL ?>" class="brand-logo">
                        <span class="brand-icon"><i class="fas fa-shopping-bag"></i></span>
                        <span class="brand-text"><?= SITE_NAME ?></span>
                    </a>
                </div>

                <div class="header-center">
                    <form class="search-form" action="<?= SITE_URL ?>/shop.php" method="GET">
                        <div class="search-group">
                            <input type="text" class="search-input" name="search" placeholder="Search for products..." autocomplete="off" id="searchInput">
                            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                            <div class="search-suggestions" id="searchSuggestions"></div>
                        </div>
                    </form>
                </div>

                <div class="header-right">
                    <a href="<?= SITE_URL ?>/account/wishlist.php" class="header-action">
                        <i class="fas fa-heart"></i>
                        <?php if (isLoggedIn()): $db = db(); $wcount = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?"); $wcount->execute([$_SESSION['user_id']]); $wc = $wcount->fetchColumn(); ?>
                            <?php if ($wc > 0): ?><span class="badge-count"><?= $wc ?></span><?php endif; ?>
                        <?php endif; ?>
                    </a>
                    <a href="<?= SITE_URL ?>/cart.php" class="header-action cart-action">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge-count" id="cartCount"><?= $cartCount ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- ====== NAVIGATION ====== -->
    <nav class="main-nav" id="mainNav">
        <div class="container">
            <button class="nav-close-btn d-lg-none" id="navCloseBtn"><i class="fas fa-times"></i></button>
            <ul class="nav-menu">
                <li class="nav-item"><a href="<?= SITE_URL ?>" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-item"><a href="<?= SITE_URL ?>/shop.php" class="nav-link"><i class="fas fa-th-list"></i> All Products</a></li>
                <?php
                $navCategories = getCategories();
                foreach ($navCategories as $cat):
                ?>
                <li class="nav-item dropdown">
                    <a href="<?= SITE_URL ?>/shop.php?category=<?= $cat['slug'] ?>" class="nav-link">
                        <?= sanitizeInput($cat['name']) ?>
                    </a>
                    <?php
                    $subCats = getCategories($cat['id']);
                    if (!empty($subCats)):
                    ?>
                    <ul class="dropdown-menu">
                        <?php foreach ($subCats as $sub): ?>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=<?= $sub['slug'] ?>"><?= sanitizeInput($sub['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
                <li class="nav-item"><a href="<?= SITE_URL ?>/shop.php?sort=deals" class="nav-link nav-deals"><i class="fas fa-fire"></i> Hot Deals</a></li>
            </ul>
        </div>
    </nav>
    <div class="nav-overlay" id="navOverlay"></div>

    <main>
        <?= displayAlert() ?>
