<?php
require_once '../../../config/init.php';

// Ensure the user is logged in before proceeding
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? null;
$userId = $_SESSION['user_id']; // Get user ID from the session for security

try {
    switch ($action) {
        case 'fetch':
            fetch_profile($pdo, $userId);
            break;
        case 'update':
            update_profile($pdo, $userId);
            break;
        case 'change_password':
            change_password($pdo, $userId);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Fetches the logged-in user's profile data.
 */
function fetch_profile($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id, u.email, u.first_name, u.last_name,
            o.phone, o.address, o.city
        FROM users u
        LEFT JOIN owners o ON u.user_id = o.user_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception('Profile not found.');
    }

    echo json_encode(['success' => true, 'data' => $profile]);
}

/**
 * Updates the user's personal information.
 */
function update_profile($pdo, $userId) {
    // Collect data from POST request
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';

    $pdo->beginTransaction();

    // 1. Update the 'users' table
    $stmtUser = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?");
    $stmtUser->execute([$firstName, $lastName, $userId]);

    // 2. Insert or Update the 'owners' table.
    // This is robust: it creates the owner record if it doesn't exist,
    // or updates it if it does. Requires `user_id` to be a UNIQUE key in `owners`.
    $stmtOwner = $pdo->prepare("
        INSERT INTO owners (user_id, phone, address, city) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE phone = VALUES(phone), address = VALUES(address), city = VALUES(city)
    ");
    $stmtOwner->execute([$userId, $phone, $address, $city]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
}

/**
 * Changes the user's password after verifying the current one.
 */
function change_password($pdo, $userId) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('All password fields are required.');
    }
    if ($newPassword !== $confirmPassword) {
        throw new Exception('New passwords do not match.');
    }
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long.');
    }

    // Fetch the current hashed password from the DB
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the current password
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        throw new Exception('Incorrect current password.');
    }

    // Hash the new password and update the database
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $updateStmt->execute([$newHashedPassword, $userId]);

    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
}
