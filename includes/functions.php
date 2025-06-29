<?php
// Utility functions

// Redirect to a URL
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Check if request is POST
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Set flash message
function setFlash($message, $type = 'info') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type // success, danger, warning, info
    ];
}

// Get and clear flash message
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Format date
function formatDate($date) {
    return date(DATE_FORMAT, strtotime($date));
}

// Format time
function formatTime($time) {
    return date(TIME_FORMAT, strtotime($time));
}

// Format datetime
function formatDateTime($datetime) {
    return date(DATETIME_FORMAT, strtotime($datetime));
}

// Generate random string
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// Handle file upload
function uploadFile($file, $directory, $allowedTypes = null) {
    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_IMAGE_TYPES;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $filename = generateRandomString(20) . '.' . $extension;
    $filepath = UPLOADS_PATH . '/' . $directory . '/' . $filename;
    
    if (!is_dir(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'error' => 'Failed to save file'];
}

// Get user's full name
function getUserFullName($user) {
    return $user['first_name'] . ' ' . $user['last_name'];
}

// Check if email exists
function emailExists($email, $excludeUserId = null) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
    $params = ['email' => $email];
    
    if ($excludeUserId) {
        $sql .= " AND user_id != :user_id";
        $params['user_id'] = $excludeUserId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchColumn() > 0;
}

// Debug function (remove in production)
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

// ----------------- Notification Utilities -----------------

// Send a basic email (uses PHP's mail function)
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . ADMIN_EMAIL . "\r\n";

    // Suppress warnings if mail is not configured
    @mail($to, $subject, $message, $headers);
}

// Create a notification record
function addNotification($userId, $message, $type = 'info', $appointmentId = null) {
    global $pdo;

    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, appointment_id, message, type, is_read, created_at)
         VALUES (:user_id, :appointment_id, :message, :type, 0, NOW())"
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':appointment_id' => $appointmentId,
        ':message' => $message,
        ':type' => $type
    ]);
}

// Get all active staff user IDs
function getStaffUserIds() {
    global $pdo;
    $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'staff' AND is_active = 1");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Convenience: add a notification for every staff/admin user
function notifyStaff($message, $type = 'info', $appointmentId = null) {
    $staffIds = getStaffUserIds();
    foreach ($staffIds as $sid) {
        addNotification($sid, $message, $type, $appointmentId);
    }
}


// Get unread notification count
function getUnreadNotificationCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
    $stmt->execute([':user_id' => $userId]);
    return (int)$stmt->fetchColumn();
}

// Fetch recent notifications
function getRecentNotifications($userId, $limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit"
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mark all notifications as read
function markNotificationsRead($userId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
}

// Send reminder notifications for upcoming appointments (1 day before)
function checkAndSendAppointmentReminders($userId) {
    global $pdo;

    // Find appointments happening exactly one day from now
    $stmt = $pdo->prepare(
        "SELECT a.appointment_id, a.appointment_date, a.appointment_time, u.email
         FROM appointments a
         JOIN pets p ON a.pet_id = p.pet_id
         JOIN owners o ON p.owner_id = o.owner_id
         JOIN users u ON o.user_id = u.user_id
         WHERE o.user_id = :user_id
           AND a.status = 'confirmed'
           AND a.appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
    );
    $stmt->execute([':user_id' => $userId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appointments as $appt) {
        // Check if reminder already exists
        $check = $pdo->prepare(
            "SELECT 1 FROM notifications WHERE user_id = :user_id AND appointment_id = :aid AND type = 'reminder'"
        );
        $check->execute([':user_id' => $userId, ':aid' => $appt['appointment_id']]);

        if (!$check->fetch()) {
            $date = formatDate($appt['appointment_date']);
            $time = formatTime($appt['appointment_time']);
            $message = "Reminder: You have an appointment on {$date} at {$time}.";

            addNotification($userId, $message, 'reminder', $appt['appointment_id']);
            sendEmail($appt['email'], 'Appointment Reminder', $message);
        }
    }
}

// Automatically send reminders for tomorrow's appointments
function sendAutomaticAppointmentReminders() {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reminder_sent,
                p.name AS pet_name, u.email, u.first_name
           FROM appointments a
           JOIN pets p ON a.pet_id = p.pet_id
           JOIN owners o ON p.owner_id = o.owner_id
           JOIN users u ON o.user_id = u.user_id
          WHERE a.status = 'confirmed'
            AND a.reminder_sent = 0
            AND a.appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $date = formatDate($r['appointment_date']);
        $time = formatTime($r['appointment_time']);

        $subject = 'Appointment Reminder - ' . (defined('SITE_NAME') ? SITE_NAME : 'Vet Precision');
        $body = '<p>Dear ' . htmlspecialchars($r['first_name']) . ',</p>' .
                '<p>This is a friendly reminder of your upcoming appointment for <strong>' .
                htmlspecialchars($r['pet_name']) . '</strong>.</p>' .
                '<p><strong>Date:</strong> ' . $date . '<br><strong>Time:</strong> ' . $time . '</p>' .
                '<p>We look forward to seeing you!</p>' .
                '<p>Sincerely,<br>The ' . (defined('SITE_NAME') ? SITE_NAME : 'Vet Precision') . ' Team</p>';
        $alt = "Reminder: appointment for {$r['pet_name']} on {$date} at {$time}.";

        if (sendClientNotification($r['email'], $subject, $body, $alt)) {
            $upd = $pdo->prepare("UPDATE appointments SET reminder_sent = 1 WHERE appointment_id = :aid");
            $upd->execute([':aid' => $r['appointment_id']]);
        }
    }
}
?>