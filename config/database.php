<?php
// Database configuration using SSH tunnel
define('DB_HOST', '127.0.0.1'); // localhost through tunnel
define('DB_PORT', '3308');      // Your tunnel port
define('DB_NAME', 'vet_precision');
define('DB_USER', 'dev_team');
define('DB_PASS', 'VetPrecision2024!');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    // In production, log this error instead of displaying
    die("Connection failed: " . $e->getMessage());
}
?>