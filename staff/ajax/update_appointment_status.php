<?php
require_once '../../../config/init.php';

header('Content-Type: application/json');

// --- AUTHENTICATION ---
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? '';
$new_status = $_POST['status'] ?? '';

// --- VALIDATION ---
if (empty($appointment_id) || empty($new_status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing appointment ID or status']);
    exit();
}

$valid_statuses = ['requested', 'confirmed', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status provided']);
    exit();
}

try {
    // --- 1. GET OLD DETAILS & CLIENT INFO BEFORE UPDATING ---
    $stmt_details = $pdo->prepare("
        SELECT 
            a.status AS old_status,
            a.appointment_date,
            a.appointment_time,
            p.name AS pet_name,
            u.email AS client_email,
            CONCAT_WS(' ', u.first_name, u.last_name) AS client_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN owners o ON p.owner_id = o.owner_id
        JOIN users u ON o.user_id = u.user_id
        WHERE a.appointment_id = :appointment_id
    ");
    $stmt_details->execute([':appointment_id' => $appointment_id]);
    $details = $stmt_details->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Appointment not found.']);
        exit;
    }

    $old_status = $details['old_status'];
    $client_email = $details['client_email'];

    // --- 2. PERFORM THE UPDATE ---
    $stmt_update = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?");
    $stmt_update->execute([$new_status, $appointment_id]);
    
    // --- 3. SEND EMAIL IF STATUS HAS ACTUALLY CHANGED ---
    if ($stmt_update->rowCount() > 0 && $old_status !== $new_status && !empty($client_email)) {
        
        $subject = '';
        $email_body = '';
        $alt_body = '';
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Vet Precision';
        
        $formatted_date = date("l, F j, Y", strtotime($details['appointment_date']));
        $formatted_time = date("g:i A", strtotime($details['appointment_time']));

        switch ($new_status) {
            case 'confirmed':
                $subject = "Your Appointment is Confirmed - " . $site_name;
                $email_body = "
                    <p>Dear " . htmlspecialchars($details['client_name']) . ",</p>
                    <p>This is a confirmation that your appointment for <strong>" . htmlspecialchars($details['pet_name']) . "</strong> has been scheduled and confirmed.</p>
                    <p><strong>Date:</strong> " . $formatted_date . "</p>
                    <p><strong>Time:</strong> " . $formatted_time . "</p>
                    <p>We look forward to seeing you and your pet!</p>
                ";
                $alt_body = "Your appointment for {$details['pet_name']} on {$formatted_date} at {$formatted_time} is confirmed.";
                break;
            
            case 'completed':
                $subject = "Your Appointment is Complete - " . $site_name;
                $email_body = "
                    <p>Dear " . htmlspecialchars($details['client_name']) . ",</p>
                    <p>Thank you for visiting us today. This email confirms that your appointment for <strong>" . htmlspecialchars($details['pet_name']) . "</strong> on {$formatted_date} is now complete.</p>
                    <p>If you have any follow-up questions, please don't hesitate to contact us.</p>
                ";
                $alt_body = "Your appointment for {$details['pet_name']} on {$formatted_date} is now complete. Thank you for visiting!";
                break;

            case 'cancelled':
                $subject = "Your Appointment has been Cancelled - " . $site_name;
                $email_body = "
                    <p>Dear " . htmlspecialchars($details['client_name']) . ",</p>
                    <p>This email is to inform you that your appointment for <strong>" . htmlspecialchars($details['pet_name']) . "</strong>, originally scheduled for {$formatted_date} at {$formatted_time}, has been cancelled by our staff.</p>
                    <p>If you believe this is an error or wish to reschedule, please contact our clinic directly.</p>
                ";
                $alt_body = "Your appointment for {$details['pet_name']} scheduled for {$formatted_date} has been cancelled by our staff. Please contact us to reschedule.";
                break;
        }

        // Send the email if a valid template was generated
        if (!empty($subject)) {
            $email_body_full = "<html><body>" . $email_body . "<p>Sincerely,<br>The " . $site_name . " Team</p></body></html>";
            sendClientNotification($client_email, $subject, $email_body_full, $alt_body);
        }
    }
    
    // Send success response regardless of email sending
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
    
} catch (PDOException $e) {
    http_response_code(500);
    // Log the detailed error for the developer
    error_log("Staff Update Status Error: " . $e->getMessage());
    // Return a generic error to the client
    echo json_encode(['success' => false, 'error' => 'A database error occurred.']);
}