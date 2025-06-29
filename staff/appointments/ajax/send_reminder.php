<?php
<?php
require_once '../../../config/init.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? '';
if (empty($appointment_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing appointment ID']);
    exit();
}

try {
    // Fetch appointment and client details
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason,
               p.name AS pet_name, p.species, p.breed,
               u.email, u.first_name, u.last_name,
               o.phone
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN owners o ON p.owner_id = o.owner_id
        JOIN users u ON o.user_id = u.user_id
        WHERE a.appointment_id = ?
    ");
    $stmt->execute([$appointment_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Appointment not found']);
        exit();
    }

    // Check if appointment is in the future
    $appointment_datetime = $appt['appointment_date'] . ' ' . $appt['appointment_time'];
    if (strtotime($appointment_datetime) <= time()) {
        echo json_encode(['success' => false, 'error' => 'Cannot send reminder for past appointments']);
        exit();
    }

    // Format date and time for display
    $site_name = defined('SITE_NAME') ? SITE_NAME : 'Vet Precision';
    $date = date('l, F j, Y', strtotime($appt['appointment_date']));
    $time = date('g:i A', strtotime($appt['appointment_time']));
    
    // Create email content
    $subject = "Appointment Reminder - {$site_name}";
    
    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #1DBAA8, #28a745); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h1 style='color: white; margin: 0; font-size: 24px;'>Appointment Reminder</h1>
        </div>
        
        <div style='background: white; padding: 30px; border: 1px solid #e9ecef; border-top: none;'>
            <p style='font-size: 16px; margin-bottom: 20px;'>Dear " . htmlspecialchars($appt['first_name']) . ",</p>
            
            <p style='font-size: 16px; margin-bottom: 25px;'>This is a friendly reminder about your upcoming appointment at {$site_name}.</p>
            
            <div style='background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #1DBAA8;'>
                <h3 style='margin-top: 0; color: #2c3e50; font-size: 18px;'>📅 Appointment Details</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #495057;'>Pet:</td>
                        <td style='padding: 8px 0; color: #2c3e50;'>" . htmlspecialchars($appt['pet_name']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #495057;'>Date:</td>
                        <td style='padding: 8px 0; color: #2c3e50;'>{$date}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #495057;'>Time:</td>
                        <td style='padding: 8px 0; color: #2c3e50;'>{$time}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #495057;'>Reason:</td>
                        <td style='padding: 8px 0; color: #2c3e50;'>" . htmlspecialchars($appt['reason']) . "</td>
                    </tr>
                </table>
            </div>
            
            <div style='background-color: #e8f5e8; padding: 20px; border-radius: 8px; margin: 25px 0;'>
                <p style='margin: 0; font-size: 15px; color: #155724;'>
                    <strong>📋 Please Note:</strong> Arrive 10-15 minutes early for check-in. 
                    If you need to reschedule or cancel, please contact us as soon as possible.
                </p>
            </div>
            
            <p style='font-size: 16px; margin-bottom: 10px;'>We look forward to seeing you and " . htmlspecialchars($appt['pet_name']) . "!</p>
            
            <p style='font-size: 16px; color: #6c757d; margin-top: 30px;'>
                Best regards,<br>
                <strong>The {$site_name} Team</strong>
            </p>
        </div>
        
        <div style='background: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; border: 1px solid #e9ecef; border-top: none;'>
            <p style='margin: 0; font-size: 12px; color: #6c757d;'>
                This is an automated reminder. Please do not reply to this email.
            </p>
        </div>
    </div>";
    
    // Plain text version
    $text_body = "Appointment Reminder - {$site_name}\n\n";
    $text_body .= "Dear " . $appt['first_name'] . ",\n\n";
    $text_body .= "This is a friendly reminder about your upcoming appointment.\n\n";
    $text_body .= "Appointment Details:\n";
    $text_body .= "Pet: " . $appt['pet_name'] . "\n";
    $text_body .= "Date: {$date}\n";
    $text_body .= "Time: {$time}\n";
    $text_body .= "Reason: " . $appt['reason'] . "\n\n";
    $text_body .= "Please arrive 10-15 minutes early for check-in.\n\n";
    $text_body .= "Best regards,\nThe {$site_name} Team";

    // Try to send email
    $email_sent = false;
    
    // Method 1: Try using your existing email function
    if (function_exists('sendClientNotification')) {
        try {
            $email_sent = sendClientNotification($appt['email'], $subject, $html_body, $text_body);
        } catch (Exception $e) {
            error_log("Email function error: " . $e->getMessage());
        }
    }
    
    // Method 2: Fallback to PHP mail() function
    if (!$email_sent) {
        try {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . (defined('SITE_EMAIL') ? SITE_EMAIL : 'noreply@vetprecision.com'),
                'Reply-To: ' . (defined('SITE_EMAIL') ? SITE_EMAIL : 'noreply@vetprecision.com'),
                'X-Mailer: PHP/' . phpversion()
            ];
            
            $email_sent = mail($appt['email'], $subject, $html_body, implode("\r\n", $headers));
        } catch (Exception $e) {
            error_log("PHP mail error: " . $e->getMessage());
        }
    }

    if ($email_sent) {
        // Try to update reminder_sent flag (if column exists)
        try {
            $check_column = $pdo->query("SHOW COLUMNS FROM appointments LIKE 'reminder_sent'");
            if ($check_column->rowCount() > 0) {
                $upd = $pdo->prepare("UPDATE appointments SET reminder_sent = 1 WHERE appointment_id = ?");
                $upd->execute([$appointment_id]);
            }
        } catch (PDOException $e) {
            // Column doesn't exist, that's okay
            error_log("Reminder flag update failed (column may not exist): " . $e->getMessage());
        }
        
        // Log successful reminder
        error_log("Reminder sent successfully for appointment ID: {$appointment_id} to {$appt['email']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Reminder sent successfully to ' . $appt['email']
        ]);
    } else {
        error_log("Failed to send reminder for appointment ID: {$appointment_id}");
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to send email reminder. Please check email configuration.'
        ]);
    }

} catch (PDOException $e) {
    error_log('Database error in send_reminder.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error in send_reminder.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
}
?>