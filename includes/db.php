<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $hostParts = explode(':', DB_HOST);
            $host = $hostParts[0];
            $port = $hostParts[1] ?? '3306';
            $dsn = "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            echo "<div style='background:#FEF2F2;color:#991B1B;padding:20px;margin:20px;border-radius:8px;font-family:sans-serif;'>";
            echo "<h3>Database Connection Failed</h3>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>DSN:</strong> mysql:host={$host};port={$port};dbname=" . DB_NAME . "</p>";
            echo "<p><strong>Check:</strong> MySQL is running, DB '" . DB_NAME . "' exists, and credentials in config.php are correct.</p>";
            echo "</div>";
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }

    public function query($sql) {
        return $this->pdo->query($sql);
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }
}

function db() {
    return Database::getInstance()->getConnection();
}
