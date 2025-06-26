<?php
require_once '../../../config/init.php';

header('Content-Type: application/json');

// Get parameters
$owner_id = $_GET['owner_id'] ?? null;
$search = $_GET['search'] ?? '';
$search = trim($search);

// Immediately stop and return an empty array if no owner_id is provided.
if (!$owner_id) {
    echo json_encode([]);
    exit();
}

try {
    $sql = "
        SELECT 
            pet_id, 
            name, 
            species, 
            breed 
        FROM pets 
        WHERE owner_id = ? 
        AND is_active = 1
    ";
    
    $params = [$owner_id];
    
    // Add search condition if search term is provided
    if ($search !== '' && $search !== null) {
        $sql .= " AND (name LIKE ? OR species LIKE ? OR breed LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pets);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query for pets failed: ' . $e->getMessage()]);
}