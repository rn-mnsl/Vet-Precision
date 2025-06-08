<?php
echo "<h2>Testing SSH Tunnel Connection</h2>";

try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3308;dbname=vet_precision",
        'dev_team',
        'VetPrecision2024!'
    );
    echo "✓ Connected via SSH tunnel!<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Found " . $result['count'] . " users in database<br>";
    
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage();
}
?>