<?php
require_once __DIR__ . '/../includes/session.php';
requireLogin();

$pageTitle = 'My Profile';
$user = getCurrentUser();
$tab = $_GET['tab'] ?? 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        setAlert('error', 'Invalid security token.');
    } else {
        $db = db();

        if (isset($_POST['update_profile'])) {
            $fullName = sanitizeInput($_POST['full_name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');

            if (empty($fullName) || empty($email)) {
                setAlert('error', 'Name and email are required.');
            } elseif (!validateEmail($email)) {
                setAlert('error', 'Invalid email address.');
            } elseif (!empty($phone) && !validatePhone($phone)) {
                setAlert('error', 'Invalid phone number.');
            } else {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $stmt->execute(['email' => $email, 'id' => $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    setAlert('error', 'Email already in use by another account.');
                } else {
                    $stmt = $db->prepare("UPDATE users SET full_name = :name, email = :email, phone = :phone WHERE id = :id");
                    $stmt->execute([
                        'name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'id' => $_SESSION['user_id']
                    ]);
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;
                    setAlert('success', 'Profile updated successfully.');
                    $user = getCurrentUser();
                }
            }
        } elseif (isset($_POST['update_password'])) {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (empty($current) || empty($new) || empty($confirm)) {
                setAlert('error', 'All password fields are required.');
            } elseif ($new !== $confirm) {
                setAlert('error', 'New passwords do not match.');
            } elseif (strlen($new) < 8) {
                setAlert('error', 'Password must be at least 8 characters.');
            } else {
                $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $userRow = $stmt->fetch();

                if (!password_verify($current, $userRow['password'])) {
                    setAlert('error', 'Current password is incorrect.');
                } else {
                    $hashed = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $stmt->execute(['password' => $hashed, 'id' => $_SESSION['user_id']]);
                    setAlert('success', 'Password changed successfully.');
                }
            }
        }
    }
    header('Location: ' . SITE_URL . '/account/profile.php' . ($tab === 'password' ? '?tab=password' : ''));
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<section class="account-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="account-sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <h5><?= sanitizeInput($user['full_name']) ?></h5>
                        <small class="text-muted"><?= sanitizeInput($user['email']) ?></small>
                    </div>
                    <nav class="account-nav">
                        <a href="<?= SITE_URL ?>/account/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                        <a href="<?= SITE_URL ?>/account/orders.php"><i class="fas fa-box"></i> Orders</a>
                        <a href="<?= SITE_URL ?>/account/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="<?= SITE_URL ?>/account/profile.php" class="<?= $tab === 'profile' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Profile</a>
                        <a href="<?= SITE_URL ?>/account/profile.php?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>"><i class="fas fa-lock"></i> Change Password</a>
                        <hr>
                        <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9">
                <div class="account-content">
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'profile' ? 'active' : '' ?>" href="<?= SITE_URL ?>/account/profile.php">
                                <i class="fas fa-user-cog"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $tab === 'password' ? 'active' : '' ?>" href="<?= SITE_URL ?>/account/profile.php?tab=password">
                                <i class="fas fa-lock"></i> Change Password
                            </a>
                        </li>
                    </ul>

                    <?php if ($tab === 'password'): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-4">Change Password</h5>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="8">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                                    </div>
                                    <button type="submit" name="update_password" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="mb-4">Profile Information</h5>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" name="full_name" class="form-control" value="<?= sanitizeInput($user['full_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" name="email" class="form-control" value="<?= sanitizeInput($user['email']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" name="phone" class="form-control" value="<?= sanitizeInput($user['phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
