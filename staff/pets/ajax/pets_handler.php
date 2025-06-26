<?php
require_once '../../../config/init.php';

// --- AUTHENTICATION & PERMISSIONS ---
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'You do not have permission to perform this action.']);
    exit();
}

header('Content-Type: application/json');
$response = [];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    switch ($action) {
        case 'fetch_all':
            // REMINDER: This query joins all three tables to get the owner's name.
            $stmt = $pdo->query("
                SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS owner_name
                FROM pets p
                JOIN owners o ON p.owner_id = o.owner_id
                JOIN users u ON o.user_id = u.user_id
                ORDER BY p.name ASC
            ");
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'add':
            // REMINDER: Handles adding a new pet.
            $sql = "INSERT INTO pets (name, owner_id, species, breed, date_of_birth, gender, color, weight, microchip_id, notes, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['name'], $_POST['owner_id'], $_POST['species'], $_POST['breed'], 
                $_POST['date_of_birth'], $_POST['gender'], $_POST['color'], 
                $_POST['weight'] ?: null, $_POST['microchip_id'] ?: null, $_POST['notes'] ?: null
            ]);
            $response['success'] = true;
            $response['message'] = 'Pet added successfully!';
            break;

        case 'update':
            // REMINDER: Handles updating an existing pet.
            $sql = "UPDATE pets SET name=?, owner_id=?, species=?, breed=?, date_of_birth=?, gender=?, color=?, weight=?, microchip_id=?, notes=?, updated_at=NOW() WHERE pet_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['name'], $_POST['owner_id'], $_POST['species'], $_POST['breed'],
                $_POST['date_of_birth'], $_POST['gender'], $_POST['color'],
                $_POST['weight'] ?: null, $_POST['microchip_id'] ?: null, $_POST['notes'] ?: null,
                $_POST['pet_id']
            ]);
            $response['success'] = true;
            $response['message'] = 'Pet updated successfully!';
            break;

        case 'delete':
            // REMINDER: Handles deleting a pet.
            $stmt = $pdo->prepare("DELETE FROM pets WHERE pet_id = ?");
            $stmt->execute([$_POST['pet_id']]);
            $response['success'] = true;
            $response['message'] = 'Pet deleted successfully!';
            break;

        default:
            $response['success'] = false;
            $response['error'] = 'Invalid action specified.';
            header('HTTP/1.1 400 Bad Request');
            break;
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $response['success'] = false;
    $response['error'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);