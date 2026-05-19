<?php
require_once __DIR__ . '/../includes/session.php';
logoutAdmin();
setAlert('info', 'You have been logged out');
header('Location: ' . ADMIN_URL . '/index.php');
exit;
