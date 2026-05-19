<?php
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'Login';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/account/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (loginUser($email, $password, $remember)) {
        $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : SITE_URL . '/account/dashboard.php';
        $allowedHost = parse_url(SITE_URL, PHP_URL_HOST);
        $redirectHost = parse_url($redirect, PHP_URL_HOST);
        if ($redirectHost && $redirectHost !== $allowedHost) {
            $redirect = SITE_URL . '/account/dashboard.php';
        }
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h3>Welcome Back</h3>
                <p>Sign in to your account to continue shopping</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-times-circle me-2"></i><?= sanitizeInput($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= SITE_URL ?>/login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?= sanitizeInput($_POST['email'] ?? '') ?>" placeholder="Enter your email" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>

            <div class="auth-divider">
                <span>Don't have an account?</span>
            </div>

            <a href="<?= SITE_URL ?>/register.php" class="btn btn-outline-primary w-100">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
