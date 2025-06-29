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
        $info = $pdo->prepare(
            "SELECT u.user_id, CONCAT(u.first_name,' ',u.last_name) AS client_name, p.name AS pet_name, a.appointment_date, a.appointment_time
             FROM appointments a
             JOIN pets p ON a.pet_id = p.pet_id
             JOIN owners o ON p.owner_id = o.owner_id
             JOIN users u ON o.user_id = u.user_id
             WHERE a.appointment_id = ?"
        );
        $info->execute([$appointment_id]);
        $row = $info->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $date = formatDate($row['appointment_date']);
            $time = formatTime($row['appointment_time']);
            if ($new_status === 'confirmed') {
                $msg = "Your appointment for {$row['pet_name']} on {$date} at {$time} has been approved.";
                addNotification($row['user_id'], $msg, 'appointment', $appointment_id);
            } elseif ($new_status === 'cancelled') {
                $msg = "Your appointment for {$row['pet_name']} on {$date} at {$time} was cancelled by the clinic.";
                addNotification($row['user_id'], $msg, 'appointment', $appointment_id);
            }
        }       
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Appointment not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}