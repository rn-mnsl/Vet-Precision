<?php
require_once '../../config/init.php';
// Security: Ensures a logged-in client is making the request.
requireClient();

// Security: Check if a pet ID is provided.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid Pet ID.']);
    exit();
}

$pet_id = (int)$_GET['id'];
$owner_id = $_SESSION['owner_id']; // Get the logged-in owner's ID from the session.

try {
    // --- 1. Fetch Basic Pet Details ---
    // CRITICAL SECURITY CHECK: Also verifies that the pet belongs to the logged-in owner.
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = :pet_id AND owner_id = :owner_id");
    $stmt->execute(['pet_id' => $pet_id, 'owner_id' => $owner_id]);
    $petDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no pet is found, it's either the wrong ID or doesn't belong to this owner.
    if (!$petDetails) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Pet not found or you do not have permission to view it.']);
        exit();
    }

    // --- 2. Fetch Upcoming Appointments ---
    $stmt = $pdo->prepare("
        SELECT * FROM appointments 
        WHERE pet_id = :pet_id AND appointment_date >= CURDATE() AND status != 'cancelled'
        ORDER BY appointment_date, appointment_time
    ");
    $stmt->execute(['pet_id' => $pet_id]);
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 3. Fetch Medical History ---
    // Joins with appointments to get the reason/type for that visit.
    $stmt = $pdo->prepare("
        SELECT mr.*, a.type as visit_type, a.reason as visit_reason
        FROM medical_records mr
        LEFT JOIN appointments a ON mr.appointment_id = a.appointment_id
        WHERE mr.pet_id = :pet_id
        ORDER BY mr.visit_date DESC
    ");
    $stmt->execute(['pet_id' => $pet_id]);
    $medicalHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // --- 4. Combine all data and send as a JSON response ---
    $responseData = [
        'details' => $petDetails,
        'upcoming_appointments' => $upcomingAppointments,
        'medical_history' => $medicalHistory
    ];

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $responseData]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // In production, log the error instead of echoing it.
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>