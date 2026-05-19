<?php
require_once __DIR__ . '/../includes/session.php';

// Admin login page
$pageTitle = 'Admin Login';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        $error = 'Invalid token';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (loginAdmin($email, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_NAME ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-login-body">
    <div class="admin-login-wrapper">
        <div class="admin-login-card">
            <div class="text-center mb-4">
                <div class="admin-login-logo">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3>Admin Panel</h3>
                <p class="text-muted">Sign in to manage your store</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning">Your session has expired. Please login again.</div>
            <?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" name="email" required placeholder="admin@ecomai.com">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" required placeholder="Enter password">
                    </div>
                </div>
                <button type="submit" class="btn btn-admin-primary w-100 py-2">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="<?= SITE_URL ?>" class="text-muted small"><i class="fas fa-arrow-left me-1"></i> Back to Store</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
