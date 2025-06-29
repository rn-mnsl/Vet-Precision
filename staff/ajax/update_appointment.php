<?php
// File: ajax/update_appointment.php

require_once '../../config/init.php';
// Allow both staff and admin roles to update appointments
if (!isLoggedIn() || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// --- Data Validation ---
$errors = [];
$appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
$appointment_date = $_POST['appointment_date'] ?? null;
$appointment_time = $_POST['appointment_time'] ?? null;
$status = $_POST['status'] ?? null;
$type = $_POST['type'] ?? null;
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS);
$notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_SPECIAL_CHARS);

// Basic validation
if (empty($appointment_id)) $errors[] = 'Appointment ID is missing.';
if (empty($appointment_date)) $errors[] = 'Appointment date is required.';
if (empty($appointment_time)) $errors[] = 'Appointment time is required.';

// Validate status against an allowed list
$allowed_statuses = ['requested', 'confirmed', 'completed', 'cancelled'];
if (empty($status) || !in_array($status, $allowed_statuses)) {
    $errors[] = 'Invalid status selected.';
}

// Validate type against an allowed list
$allowed_types = ['Checkup', 'Vaccination', 'Grooming', 'Surgery', 'Consultation', 'Emergency', 'Testing visit'];
if (empty($type) || !in_array($type, $allowed_types)) {
    $errors[] = 'Invalid appointment type selected.';
}


if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit();
}

// --- Database Update ---
try {
    $sql = "UPDATE appointments SET 
                appointment_date = :appointment_date,
                appointment_time = :appointment_time,
                status = :status,
                type = :type,
                reason = :reason,
                notes = :notes,
                updated_at = NOW()
            WHERE appointment_id = :appointment_id";
            
    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':appointment_date' => $appointment_date,
        ':appointment_time' => $appointment_time,
        ':status' => $status,
        ':type' => $type,
        ':reason' => $reason,
        ':notes' => $notes,
        ':appointment_id' => $appointment_id,
    ];
    
    $stmt->execute($params);

    // rowCount() > 0 means a change was made. 0 means no change was needed (not an error).
    echo json_encode(['success' => true, 'message' => 'Appointment updated successfully!']);

} catch (PDOException $e) {
    error_log("DB Error updating appointment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Could not update the appointment.']);
}

exit();
