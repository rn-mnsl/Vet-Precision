<?php
require_once '../../../config/init.php';

// Set header to return JSON
header('Content-Type: application/json');

// --- SESSION & AUTHENTICATION ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure a client is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? null;

if (empty($appointment_id)) {
    echo json_encode(['success' => false, 'error' => 'Appointment ID is missing.']);
    exit();
}

try {
    // --- SECURITY CHECK ---
    // First, verify that the appointment belongs to the logged-in user.
    // This is crucial to prevent one user from cancelling another's appointment.
    $check_sql = "
        SELECT a.appointment_id
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN owners o ON p.owner_id = o.owner_id
        WHERE a.appointment_id = :appointment_id AND o.user_id = :user_id
    ";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([
        ':appointment_id' => $appointment_id,
        ':user_id' => $user_id
    ]);

    if ($check_stmt->fetch() === false) {
        // If no row is found, the user is not the owner of this appointment.
        echo json_encode(['success' => false, 'error' => 'Authorization failed. You do not have permission to cancel this appointment.']);
        exit();
    }

    // --- UPDATE THE STATUS ---
    // If the security check passed, proceed with the update.
    $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = :appointment_id";
    $update_stmt = $pdo->prepare($update_sql);
    $success = $update_stmt->execute([':appointment_id' => $appointment_id]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully.']);
    } else {
        throw new Exception("Failed to update the appointment status in the database.");
    }

} catch (PDOException | Exception $e) {
    // Log the actual error for debugging, but show a generic message to the user.
    error_log("Cancellation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please try again later.']);
}