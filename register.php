<?php
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'Create Account';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/account/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!empty($phone) && !validatePhone($phone)) {
        $error = 'Please enter a valid phone number.';
    } else {
        $result = registerUser($fullName, $email, $password, $phone);
        if ($result['success']) {
            header('Location: ' . SITE_URL . '/account/dashboard.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h3>Create Account</h3>
                <p>Join us and start shopping for amazing products</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-times-circle me-2"></i><?= sanitizeInput($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= SITE_URL ?>/register.php">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= sanitizeInput($_POST['full_name'] ?? '') ?>" placeholder="Enter your full name" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?= sanitizeInput($_POST['email'] ?? '') ?>" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= sanitizeInput($_POST['phone'] ?? '') ?>" placeholder="Enter your phone number (optional)">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Create a password (min 8 characters)" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="auth-divider">
                <span>Already have an account?</span>
            </div>

            <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
