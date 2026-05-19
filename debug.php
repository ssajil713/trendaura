<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Step 1: Loading config...</h2>";
require_once __DIR__ . '/includes/config.php';
echo "OK - SITE_NAME: " . SITE_NAME . "<br>";

echo "<h2>Step 2: Loading db...</h2>";
require_once __DIR__ . '/includes/db.php';
echo "OK - class Database loaded<br>";

echo "<h2>Step 3: Testing DB connection...</h2>";
try {
    $conn = db();
    echo "OK - Connected to MySQL " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 4: Loading functions...</h2>";
require_once __DIR__ . '/includes/functions.php';
echo "OK - functions loaded<br>";

echo "<h2>Step 5: Loading auth...</h2>";
require_once __DIR__ . '/includes/auth.php';
echo "OK - auth loaded<br>";

echo "<h2>Step 6: Test query...</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as c FROM products");
    $row = $stmt->fetch();
    echo "OK - Products found: " . $row['c'] . "<br>";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 7: Rendering header...</h2>";
$pageTitle = 'Test';
try {
    include __DIR__ . '/includes/header.php';
    echo "Header rendered OK";
    include __DIR__ . '/includes/footer.php';
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "<br>";
}
