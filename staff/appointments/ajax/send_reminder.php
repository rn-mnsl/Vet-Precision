<?php
require_once '../../../config/init.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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
    $stmt = $pdo->prepare(
        "SELECT a.appointment_date, a.appointment_time, a.status, a.reminder_sent,
                p.name AS pet_name, u.email, u.first_name
           FROM appointments a
           JOIN pets p ON a.pet_id = p.pet_id
           JOIN owners o ON p.owner_id = o.owner_id
           JOIN users u ON o.user_id = u.user_id
          WHERE a.appointment_id = :aid"
    );
    $stmt->execute([':aid' => $appointment_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Appointment not found']);
        exit();
    }

    // Compose email
    $site_name = defined('SITE_NAME') ? SITE_NAME : 'Vet Precision';
    $date = date('l, F j, Y', strtotime($appt['appointment_date']));
    $time = date('g:i A', strtotime($appt['appointment_time']));

    $subject = 'Appointment Reminder - ' . $site_name;
    $body = "<p>Dear " . htmlspecialchars($appt['first_name']) . ",</p>" .
            "<p>This is a friendly reminder of your upcoming appointment for <strong>" .
            htmlspecialchars($appt['pet_name']) . "</strong>.</p>" .
            "<p><strong>Date:</strong> {$date}<br><strong>Time:</strong> {$time}</p>" .
            "<p>We look forward to seeing you!</p>" .
            "<p>Sincerely,<br>The {$site_name} Team</p>";

    $alt = "Reminder: appointment for {$appt['pet_name']} on {$date} at {$time}.";

    $sent = sendClientNotification($appt['email'], $subject, $body, $alt);

    if ($sent) {
        // Mark reminder_sent flag
        $upd = $pdo->prepare("UPDATE appointments SET reminder_sent = 1 WHERE appointment_id = :aid");
        $upd->execute([':aid' => $appointment_id]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Send Reminder Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
