<?php
// Database configuration for both local and server environments

// Detect environment
$isLocal = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '.local') !== false
);

if ($isLocal) {
    // Local development configuration (using SSH tunnel)
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3308');      // Your SSH tunnel port
    define('DB_NAME', 'vet_precision');
    define('DB_USER', 'dev_team');
    define('DB_PASS', 'VetPrecision2024!');
    define('DB_CHARSET', 'utf8mb4');
} else {
    // Production server configuration (direct connection to MySQL on server)
    define('DB_HOST', 'localhost');  // MySQL is on the same server
    define('DB_PORT', '3306');       // Default MySQL port
    define('DB_NAME', 'vet_precision');
    define('DB_USER', 'vet_precision_user');     // You'll need to create this user
    define('DB_PASS', 'VetPrecision2024Server!'); // Strong password for production
    define('DB_CHARSET', 'utf8mb4');
}

try {
    if ($isLocal) {
        // Local connection with port specification
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    } else {
        // Server connection without port (uses default)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    }
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Set timezone for database connection
    $pdo->exec("SET time_zone = '+08:00'"); // Philippines timezone
    
} catch(PDOException $e) {
    if ($isLocal && defined('DEBUG_MODE') && DEBUG_MODE) {
        // Show detailed error in development
        die("Database Connection failed: " . $e->getMessage());
    } else {
        // Log error and show generic message in production
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Database helper functions
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw $e;
    }
}

function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

function beginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

function commit() {
    global $pdo;
    return $pdo->commit();
}

function rollback() {
    global $pdo;
    return $pdo->rollBack();
}
?>