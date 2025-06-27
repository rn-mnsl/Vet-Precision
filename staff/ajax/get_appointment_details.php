<?php
require_once '../../config/init.php'; // Go up two levels to reach config
requireStaff(); // Security check: ensure only staff can access

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Check if an appointment ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing Appointment ID.']);
    exit();
}

$appointmentId = (int)$_GET['id'];

try {
    // This query joins all necessary tables to get comprehensive details
    $stmt = $pdo->prepare("
        SELECT 
            a.appointment_id, a.appointment_date, a.appointment_time, a.duration_minutes, 
            a.status, a.type, a.reason, a.notes, a.created_at as appointment_created_at,
            p.pet_id, p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth,
            o.owner_id, o.phone as owner_phone, o.address as owner_address, 
            o.emergency_contact, o.emergency_phone,
            u.user_id, CONCAT(u.first_name, ' ', u.last_name) as owner_name, u.email as owner_email,
            created_by_user.first_name as creator_first_name,
            created_by_user.last_name as creator_last_name
        FROM appointments a
        LEFT JOIN pets p ON a.pet_id = p.pet_id
        LEFT JOIN owners o ON p.owner_id = o.owner_id
        LEFT JOIN users u ON o.user_id = u.user_id
        LEFT JOIN users created_by_user ON a.created_by = created_by_user.user_id
        WHERE a.appointment_id = :appointment_id
    ");
    $stmt->execute(['appointment_id' => $appointmentId]);
    $appointmentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($appointmentDetails) {
        // Send a successful response with the data
        echo json_encode(['success' => true, 'details' => $appointmentDetails]);
    } else {
        // Handle case where appointment is not found
        echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
    }

} catch (PDOException $e) {
    // Handle database errors
    error_log("AJAX Error fetching appointment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}

exit();