<?php
require_once '../../../config/init.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'fetch':
            // Get user data with owner information
            $stmt = $pdo->prepare("
                SELECT u.user_id, u.email, u.first_name, u.last_name, u.role,
                       o.phone, o.address, o.city
                FROM users u
                LEFT JOIN owners o ON u.user_id = o.user_id
                WHERE u.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            break;

        case 'update':
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            
            if (empty($first_name) || empty($last_name)) {
                echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
                break;
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Update users table
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, updated_at = NOW() 
                    WHERE user_id = ?
                ");
                $result = $stmt->execute([$first_name, $last_name, $_SESSION['user_id']]);
                
                if (!$result) {
                    throw new Exception('Failed to update user information');
                }
                
                // Check if owner record exists
                $check_stmt = $pdo->prepare("SELECT owner_id FROM owners WHERE user_id = ?");
                $check_stmt->execute([$_SESSION['user_id']]);
                $owner_id = $check_stmt->fetchColumn();
                
                if ($owner_id) {
                    // Update existing owner record
                    $owner_stmt = $pdo->prepare("
                        UPDATE owners 
                        SET phone = ?, address = ?, city = ?, updated_at = NOW() 
                        WHERE user_id = ?
                    ");
                    $owner_result = $owner_stmt->execute([
                        $phone ?: null, 
                        $address ?: null, 
                        $city ?: null, 
                        $_SESSION['user_id']
                    ]);
                } else {
                    // Create new owner record
                    $owner_stmt = $pdo->prepare("
                        INSERT INTO owners (user_id, phone, address, city, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                    ");
                    $owner_result = $owner_stmt->execute([
                        $_SESSION['user_id'],
                        $phone ?: null, 
                        $address ?: null, 
                        $city ?: null
                    ]);
                }
                
                if (!$owner_result) {
                    throw new Exception('Failed to update contact information');
                }
                
                // Update session data
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                
                $pdo->commit();
                
                // Return updated data
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.email, u.first_name, u.last_name, u.role,
                           o.phone, o.address, o.city
                    FROM users u
                    LEFT JOIN owners o ON u.user_id = o.user_id
                    WHERE u.user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profile updated successfully',
                    'data' => $updated_user
                ]);
                
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;

        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            
            if (empty($current_password) || empty($new_password)) {
                echo json_encode(['success' => false, 'message' => 'Both current and new passwords are required']);
                break;
            }
            
            if (strlen($new_password) < 8) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
                break;
            }
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $stored_password = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $stored_password)) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                break;
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $result = $update_stmt->execute([$hashed_password, $_SESSION['user_id']]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Database error in profile handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in profile handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>