<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        setAlert('warning', 'Please login to continue');
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = db();
    $stmt = $db->prepare("SELECT id, full_name, email, phone, avatar, created_at FROM users WHERE id = :id AND is_active = 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    return $stmt->fetch();
}

function loginUser($email, $password, $remember = false) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];

        if ($remember) {
            $token = bin2hex(random_bytes(64));
            $expires = date('Y-m-d H:i:s', time() + REMEMBER_TOKEN_EXPIRY);
            $stmt = $db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
            $stmt->execute(['token' => $token, 'id' => $user['id']]);
            setcookie('remember_token', $token, time() + REMEMBER_TOKEN_EXPIRY, '/', '', false, true);
            setcookie('remember_user', $user['id'], time() + REMEMBER_TOKEN_EXPIRY, '/', '', false, true);
        }

        logActivity($user['id'], 'login', 'User logged in');
        return true;
    }
    return false;
}

function registerUser($fullName, $email, $password, $phone = '') {
    $db = db();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered'];
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (:full_name, :email, :password, :phone)");
    $success = $stmt->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password' => $hashedPassword,
        'phone' => $phone
    ]);

    if ($success) {
        $userId = $db->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        logActivity($userId, 'register', 'New user registered');
        return ['success' => true];
    }
    return ['success' => false, 'error' => 'Registration failed'];
}

function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    setcookie('remember_user', '', time() - 3600, '/', '', false, true);
    session_destroy();
}

function checkRememberMe() {
    if (!isLoggedIn() && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND remember_token = :token AND is_active = 1");
        $stmt->execute([
            'id' => $_COOKIE['remember_user'],
            'token' => $_COOKIE['remember_token']
        ]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
        }
    }
}

// Admin functions

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/index.php');
        exit;
    }
    // Check session timeout
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > SESSION_TIMEOUT)) {
        logoutAdmin();
        header('Location: ' . ADMIN_URL . '/index.php?expired=1');
        exit;
    }
    $_SESSION['admin_last_activity'] = time();
}

function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    $db = db();
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = :id AND is_active = 1");
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    return $stmt->fetch();
}

function loginAdmin($email, $password) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM admins WHERE email = :email AND is_active = 1");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_last_activity'] = time();

        $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
        $stmt->execute(['id' => $admin['id']]);

        logActivity($admin['id'], 'admin_login', 'Admin logged in', true);
        return true;
    }
    return false;
}

function logoutAdmin() {
    if (isset($_SESSION['admin_id'])) {
        logActivity($_SESSION['admin_id'], 'admin_logout', 'Admin logged out', true);
    }
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_role']);
    unset($_SESSION['admin_last_activity']);
}

function hasRole($roles) {
    if (!is_array($roles)) $roles = [$roles];
    return isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], $roles);
}
