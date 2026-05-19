<?php
require_once __DIR__ . '/includes/session.php';

logoutUser();
header('Location: ' . SITE_URL . '/index.php');
exit;
