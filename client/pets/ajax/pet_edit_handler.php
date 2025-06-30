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

    // =========================================================================
    // --- START: NEW SERVER-SIDE VALIDATION FOR PET PHOTO ---
    // This is the same logic from your main page, now applied to the AJAX data.
    // =========================================================================

    // First, check if the database even has a photo_url value.
    if (!empty($petDetails['photo_url'])) {
        
        // The path in your DB is like '../../uploads/pets/image.jpg'.
        // This path is relative to the file that originally saved it.
        // We need to build a reliable server path from the location of *this* handler file.
        // __DIR__ is the directory of this file: /.../client/ajax/
        // So, we go up two directories to the root, then down to 'uploads/pets/'.
        
        // Clean the stored path to get just the filename. This is safer.
        $filename = basename($petDetails['photo_url']);

        // Construct the full, absolute server path to the image file.
        $absolute_path_to_image = __DIR__ . '/../../uploads/pets/' . $filename;
        
        // Now, check if that file ACTUALLY exists on the server's hard drive.
        if (!file_exists($absolute_path_to_image)) {
            // The file path is in the DB, but the file is missing!
            // Set the photo_url to null so the JavaScript knows to use the default emoji.
            $petDetails['photo_url'] = null; 
        }
    }
    // =========================================================================
    // --- END: NEW SERVER-SIDE VALIDATION FOR PET PHOTO ---
    // =========================================================================


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
        'details' => $petDetails, // This now contains the validated photo_url
        'upcoming_appointments' => $upcomingAppointments,
        'medical_history' => $medicalHistory
    ];

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $responseData]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // In production, log the error instead of echoing it.
    // error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>
