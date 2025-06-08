<?php
echo "<h2>Testing Database Connection</h2>";

// Test 1: Check if we can reach the server
$host = 'vet-precision.roanmanansala.com';
echo "Testing connection to: $host<br>";

// Test 2: Try to ping the server
if (checkdnsrr($host, 'A')) {
    echo "✓ Domain resolves correctly<br>";
} else {
    echo "✗ Cannot resolve domain<br>";
}

// Test 3: Check port 3308
$connection = @fsockopen($host, 3308, $errno, $errstr, 5);
if ($connection) {
    echo "✓ Port 3308 is open<br>";
    fclose($connection);
} else {
    echo "✗ Port 3308 is blocked: $errstr ($errno)<br>";
}

// Test 4: Try MySQL connection
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=vet_precision;port=3308",
        'dev_team',
        'VetPrecision2024!',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ MySQL connection successful!<br>";
} catch (PDOException $e) {
    echo "✗ MySQL connection failed: " . $e->getMessage() . "<br>";
}
?>