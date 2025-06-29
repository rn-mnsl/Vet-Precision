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
    // --- [MODIFIED] SECURITY CHECK & DATA FETCH ---
    // Combine the security check with fetching the details needed for the email.
    // This is more efficient than running a second query later.
    $details_sql = "
        SELECT 
            a.appointment_date, 
            a.appointment_time,
            p.name AS pet_name,
            CONCAT_WS(' ', u.first_name, u.last_name) AS client_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN owners o ON p.owner_id = o.owner_id
        JOIN users u ON o.user_id = u.user_id
        WHERE a.appointment_id = :appointment_id AND o.user_id = :user_id
    ";
    $details_stmt = $pdo->prepare($details_sql);
    $details_stmt->execute([
        ':appointment_id' => $appointment_id,
        ':user_id' => $user_id
    ]);

    $appointment_details = $details_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment_details) {
        // If no row is found, the user is not the owner or the appointment doesn't exist.
        echo json_encode(['success' => false, 'error' => 'Authorization failed. You do not have permission to cancel this appointment.']);
        exit();
    }

    // --- UPDATE THE STATUS ---
    // If the security check passed, proceed with the update.
    $update_sql = "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE appointment_id = :appointment_id";
    $update_stmt = $pdo->prepare($update_sql);
    $success = $update_stmt->execute([':appointment_id' => $appointment_id]);

    if ($success) {
        // --- [NEW] SEND NOTIFICATION EMAIL ---
        // Prepare the email content using the details we fetched earlier.
        $subject = "Appointment Cancelled: " . htmlspecialchars($appointment_details['pet_name']);
        $formatted_date = date("F j, Y", strtotime($appointment_details['appointment_date']));
        $formatted_time = date("g:i A", strtotime($appointment_details['appointment_time']));

        $email_body = "
            <html><body>
                <h2>Appointment Cancellation Notice</h2>
                <p>An appointment has been cancelled by a client through the portal.</p>
                <ul style='list-style-type: none; padding: 0;'>
                    <li style='margin-bottom: 10px;'><strong>Client:</strong> " . htmlspecialchars($appointment_details['client_name']) . "</li>
                    <li style='margin-bottom: 10px;'><strong>Pet:</strong> " . htmlspecialchars($appointment_details['pet_name']) . "</li>
                    <li style='margin-bottom: 10px;'><strong>Original Date:</strong> " . $formatted_date . " at " . $formatted_time . "</li>
                </ul>
                <p>This time slot may now be available for other bookings.</p>
            </body></html>
        ";
        
        $alt_body = "Appointment Cancelled. Client: {$appointment_details['client_name']}, Pet: {$appointment_details['pet_name']}, Original Date: {$formatted_date} at {$formatted_time}.";

        // Send the email using our reusable function from init.php
        sendAdminNotification($subject, $email_body, $alt_body);
        $msg = "Appointment for {$appointment_details['pet_name']} on {$formatted_date} at {$formatted_time} was cancelled by {$appointment_details['client_name']}.";
        notifyStaff($msg, 'appointment', $appointment_id);
        // --- END OF EMAIL CODE ---

        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully.']);

    } else {
        throw new Exception("Failed to update the appointment status in the database.");
    }

} catch (PDOException | Exception $e) {
    // Log the actual error for debugging, but show a generic message to the user.
    error_log("Cancellation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please try again later.']);
}