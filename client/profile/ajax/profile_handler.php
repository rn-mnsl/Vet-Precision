<?php
require_once '../../../config/init.php';

// Security: Ensure a user is logged in before proceeding.
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? null;
$userId = $_SESSION['user_id']; // Use the secure session ID

try {
    switch ($action) {
        case 'fetch':
            fetch_staff_profile($pdo, $userId);
            break;
        case 'update':
            update_staff_profile($pdo, $userId);
            break;
        case 'change_password':
            // The password change logic is identical for all users, so we can reuse it.
            change_user_password($pdo, $userId);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Fetches the logged-in staff member's profile data from the 'users' table.
 */
function fetch_staff_profile($pdo, $userId) {
    // This query is simpler: it only needs the users table.
    $stmt = $pdo->prepare("
        SELECT user_id, email, first_name, last_name
        FROM users
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception('Staff profile not found.');
    }

    echo json_encode(['success' => true, 'data' => $profile]);
}

/**
 * Updates the staff member's personal information in the 'users' table.
 */
function update_staff_profile($pdo, $userId) {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?");
    $stmt->execute([$firstName, $lastName, $userId]);

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
}

/**
 * Changes any user's password after verifying the current one.
 * (This function is generic and can be used by both clients and staff).
 */
function change_user_password($pdo, $userId) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        throw new Exception('Current and new password fields are required.');
    }
    if ($newPassword !== $confirmPassword) {
        throw new Exception('New passwords do not match.');
    }
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long.');
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        throw new Exception('Incorrect current password.');
    }

    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $updateStmt->execute([$newHashedPassword, $userId]);

    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
}