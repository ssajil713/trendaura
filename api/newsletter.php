<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';

$email = $_POST['email'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';

if (!verifyCSRFToken($csrf)) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']); exit;
}

if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address']); exit;
}

$db = db();
$stmt = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = :email");
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Already subscribed']); exit;
}

$stmt = $db->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:email)");
if ($stmt->execute(['email' => $email])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Subscription failed']);
}
