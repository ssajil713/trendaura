<?php
$pageTitle = '404 - Page Not Found';
include __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="text-center">
            <div style="font-size: 8rem; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1;">404</div>
            <h2 class="mt-3 mb-2">Page Not Found</h2>
            <p class="text-muted mb-4">The page you are looking for doesn't exist or has been moved.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= SITE_URL ?>" class="btn btn-primary px-4 py-2" style="background: var(--primary); border: none; border-radius: 50px;">Go Home</a>
                <a href="<?= SITE_URL ?>/shop.php" class="btn btn-outline-dark px-4 py-2" style="border-radius: 50px;">Shop Now</a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
