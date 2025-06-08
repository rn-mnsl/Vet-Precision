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
?>