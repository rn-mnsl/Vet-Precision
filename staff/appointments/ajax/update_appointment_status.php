<?php
require_once '../../../config/init.php';

header('Content-Type: application/json');

// Check authentication
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? '';
$new_status = $_POST['status'] ?? '';

// Validate inputs
if (empty($appointment_id) || empty($new_status)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing appointment ID or status']);
    exit();
}

// Validate status
$valid_statuses = ['requested', 'confirmed', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit();
}

try {
    $sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $appointment_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Appointment not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}