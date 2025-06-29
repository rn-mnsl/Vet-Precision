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

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_basic':
            $pet_id = $_POST['pet_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $species = $_POST['species'] ?? '';
            $breed = trim($_POST['breed'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $date_of_birth = $_POST['date_of_birth'] ?? '';
            
            if (empty($pet_id) || empty($name) || empty($species)) {
                echo json_encode(['success' => false, 'message' => 'Pet name and species are required']);
                break;
            }
            
            // Verify pet belongs to this user
            $verify_stmt = $pdo->prepare("
                SELECT p.pet_id FROM pets p 
                JOIN owners o ON p.owner_id = o.owner_id 
                WHERE p.pet_id = ? AND o.user_id = ?
            ");
            $verify_stmt->execute([$pet_id, $_SESSION['user_id']]);
            
            if (!$verify_stmt->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Pet not found or access denied']);
                break;
            }
            
            // Handle photo upload if provided
            $photo_url = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Build the absolute path to the uploads directory. Using __DIR__
                // ensures this works regardless of where this handler is located.
                $upload_dir = dirname(__FILE__, 3) . '/uploads/pets/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload JPG, PNG, or GIF files only.']);
                    break;
                }
                
                if ($_FILES['photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
                    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
                    break;
                }
                
                $filename = 'pet_' . $pet_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
                    $photo_url = '../../uploads/pets/' . $filename;
                    
                    // Delete old photo if it exists. The path stored in the
                    // database is relative, so we resolve it from the uploads
                    // directory using only the file's basename.
                    $old_photo_stmt = $pdo->prepare("SELECT photo_url FROM pets WHERE pet_id = ?");
                    $old_photo_stmt->execute([$pet_id]);
                    $old_photo = $old_photo_stmt->fetchColumn();
                    
                    if ($old_photo) {
                        $old_filename = basename($old_photo);
                        $old_file_path = $upload_dir . $old_filename;
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                }
            }
            
            // Update pet information
            if ($photo_url) {
                $stmt = $pdo->prepare("
                    UPDATE pets 
                    SET name = ?, species = ?, breed = ?, gender = ?, date_of_birth = ?, photo_url = ?, updated_at = NOW() 
                    WHERE pet_id = ?
                ");
                $result = $stmt->execute([$name, $species, $breed, $gender, $date_of_birth, $photo_url, $pet_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE pets 
                    SET name = ?, species = ?, breed = ?, gender = ?, date_of_birth = ?, updated_at = NOW() 
                    WHERE pet_id = ?
                ");
                $result = $stmt->execute([$name, $species, $breed, $gender, $date_of_birth, $pet_id]);
            }
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Pet information updated successfully'];
                if ($photo_url) {
                    $response['photo_url'] = $photo_url;
                }
                echo json_encode($response);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update pet information']);
            }
            break;

        case 'update_details':
            $pet_id = $_POST['pet_id'] ?? '';
            $color = trim($_POST['color'] ?? '');
            $weight = $_POST['weight'] ?? null;
            $microchip_id = trim($_POST['microchip_id'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            
            if (empty($pet_id)) {
                echo json_encode(['success' => false, 'message' => 'Pet ID is required']);
                break;
            }
            
            // Verify pet belongs to this user
            $verify_stmt = $pdo->prepare("
                SELECT p.pet_id FROM pets p 
                JOIN owners o ON p.owner_id = o.owner_id 
                WHERE p.pet_id = ? AND o.user_id = ?
            ");
            $verify_stmt->execute([$pet_id, $_SESSION['user_id']]);
            
            if (!$verify_stmt->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Pet not found or access denied']);
                break;
            }
            
            // Update pet details
            $stmt = $pdo->prepare("
                UPDATE pets 
                SET color = ?, weight = ?, microchip_id = ?, notes = ?, updated_at = NOW() 
                WHERE pet_id = ?
            ");
            $result = $stmt->execute([
                $color ?: null, 
                $weight ?: null, 
                $microchip_id ?: null, 
                $notes ?: null, 
                $pet_id
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Pet details updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update pet details']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Database error in pet edit handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in pet edit handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>