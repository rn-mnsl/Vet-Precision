<?php
require_once '../../../config/init.php';

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
$search = trim($search); // Trim whitespace

try {
    $sql = "
        SELECT 
            o.owner_id, 
            u.first_name, 
            u.last_name, 
            u.email, 
            o.phone 
        FROM owners o
        JOIN users u ON o.user_id = u.user_id
        WHERE u.role = 'client'
    ";

    $params = [];
    
    // Only add search condition if we actually have a search term
    if ($search !== '' && $search !== null) {
        $sql .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ? OR o.phone LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
    }

    $sql .= " ORDER BY u.first_name, u.last_name LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clients);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}