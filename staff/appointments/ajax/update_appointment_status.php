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
<<<<<<< HEAD
=======
        // Fetch client user and email
        $info = $pdo->prepare(
            "SELECT u.user_id, u.email, p.name AS pet_name, a.appointment_date, a.appointment_time
             FROM appointments a
             JOIN pets p ON a.pet_id = p.pet_id
             JOIN owners o ON p.owner_id = o.owner_id
             JOIN users u ON o.user_id = u.user_id
             WHERE a.appointment_id = :aid"
        );
        $info->execute([':aid' => $appointment_id]);
        $client = $info->fetch();

        if ($client) {
            $date = formatDate($client['appointment_date']);
            $time = formatTime($client['appointment_time']);
            switch ($new_status) {
                case 'confirmed':
                    $msg = "Your appointment for {$client['pet_name']} on {$date} at {$time} has been approved.";
                    break;
                case 'cancelled':
                    $msg = "Your appointment for {$client['pet_name']} on {$date} at {$time} was cancelled.";
                    break;
                case 'completed':
                    $msg = "Your appointment for {$client['pet_name']} on {$date} at {$time} is completed.";
                    break;
                default:
                    $msg = '';
            }
            if ($msg !== '') {
                addNotification($client['user_id'], $msg, 'appointment', $appointment_id);
                sendEmail($client['email'], 'Appointment Update', $msg);
            }
        }

>>>>>>> master
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Appointment not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}