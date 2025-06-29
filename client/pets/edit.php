<?php
require_once '../../config/init.php';
requireClient(); // Ensure only logged-in clients can access

$pageTitle = 'Edit Pet - ' . SITE_NAME;

// --- Configuration for File Uploads ---
// --- Configuration for File Uploads ---
// REMINDER: Use defined() to prevent "already defined" warnings.
// This checks if the constant exists before trying to create it.
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', '../../uploads/pets/');
}
$petIdParam = 'id';
// 1. Get Pet ID from URL
if (!isset($_GET[$petIdParam]) || !is_numeric($_GET[$petIdParam])) {
    setFlash('Invalid pet ID provided for editing.', 'danger');
    redirect('/client/pets/index.php'); // Redirect to pets list if ID is missing or invalid
}

$petId = (int)$_GET[$petIdParam];
$ownerId = $_SESSION['owner_id'];

$errors = [];
$formData = []; // Will be populated from DB or POST

// --- Fetch Existing Pet Data ---
try {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = :pet_id AND owner_id = :owner_id AND is_active = 1");
    $stmt->execute(['pet_id' => $petId, 'owner_id' => $ownerId]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        setFlash('Pet not found or you do not have permission to edit it, or it has been deactivated.', 'danger');
        redirect('/client/pets/index.php');
    }

    // Pre-populate formData with existing pet details
    $formData = [
        'pet_id' => $pet['pet_id'], // Keep ID for update query
        'name' => $pet['name'],
        'species' => $pet['species'],
        'breed' => $pet['breed'],
        'date_of_birth' => $pet['date_of_birth'],
        'gender' => $pet['gender'],
        'color' => $pet['color'],
        'weight' => $pet['weight'],
        'microchip_id' => $pet['microchip_id'],
        'notes' => $pet['notes'],
        'photo_url' => $pet['photo_url'] // Store existing photo path
    ];

    // Update page title
    $pageTitle = 'Edit ' . sanitize($pet['name']) . ' - ' . SITE_NAME;

} catch (PDOException $e) {
    // error_log("Failed to fetch pet for edit: " . $e->getMessage());
    setFlash('An error occurred while loading pet details for editing. Please try again.', 'danger');
    redirect('/client/pets/index.php');
}

// --- Handle Form Submission ---
if (isPost()) {
    // Collect form data (including potential updates)
    $formData = [
        'pet_id' => $petId, // Ensure petId is carried over
        'name' => sanitize($_POST['name'] ?? ''),
        'species' => sanitize($_POST['species'] ?? ''),
        'breed' => sanitize($_POST['breed'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'color' => sanitize($_POST['color'] ?? ''),
        'weight' => $_POST['weight'] ?? '',
        'microchip_id' => sanitize($_POST['microchip_id'] ?? ''),
        'notes' => sanitize($_POST['notes'] ?? ''),
        'photo_url' => $pet['photo_url'] // Start with existing photo path, will update if new photo uploaded
    ];
    
    // --- Validation ---
    if (empty($formData['name'])) {
        $errors['name'] = 'Pet name is required';
    } elseif (strlen($formData['name']) > 50) {
        $errors['name'] = 'Pet name must be less than 50 characters';
    }
    
    if (empty($formData['species'])) {
        $errors['species'] = 'Species is required';
    }
    
    if (!empty($formData['date_of_birth'])) {
        $dob = strtotime($formData['date_of_birth']);
        if ($dob === false) {
            $errors['date_of_birth'] = 'Invalid date format';
        } elseif ($dob > time()) {
            $errors['date_of_birth'] = 'Birth date cannot be in the future';
        } elseif ($dob < strtotime('-30 years')) {
            $errors['date_of_birth'] = 'Please verify the birth date (too far in the past)';
        }
    }
    
    if (!empty($formData['weight'])) {
        if (!is_numeric($formData['weight']) || $formData['weight'] < 0 || $formData['weight'] > 500) {
            $errors['weight'] = 'Please enter a valid weight between 0 and 500 kg';
        }
    }
    
    if (!empty($formData['gender']) && !in_array($formData['gender'], ['male', 'female'])) {
        $errors['gender'] = 'Invalid gender selection';
    }

    if (!empty($formData['microchip_id']) && strlen($formData['microchip_id']) > 30) {
        $errors['microchip_id'] = 'Microchip ID cannot exceed 30 characters.';
    }

    // --- Photo Upload Handling ---
    if (isset($_FILES['pet_photo']) && $_FILES['pet_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['pet_photo'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = mime_content_type($fileTmpName); // Get actual MIME type for security

        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validation for photo
        if ($fileSize > MAX_FILE_SIZE) {
            $errors['pet_photo'] = 'File is too large. Maximum size is ' . (MAX_FILE_SIZE / (1024 * 1024)) . 'MB.';
        }
        if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
            $errors['pet_photo'] = 'Invalid file type. Only JPEG, PNG, GIF, WEBP images are allowed.';
        }
        // Optional: check extension as well, although MIME type is more robust
        if (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $errors['pet_photo'] = 'Invalid file extension. Only .jpg, .jpeg, .png, .gif, .webp are allowed.';
        }

        if (empty($errors['pet_photo'])) {
            // Generate a unique file name to prevent collisions
            $newFileName = uniqid('pet_') . '_' . time() . '.' . $fileExt;
            $uploadPath = UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Successfully uploaded new photo
                // Delete old photo if it exists and is different from the new one
                if (!empty($pet['photo_url']) && file_exists($pet['photo_url']) && $pet['photo_url'] != $uploadPath) {
                    unlink($pet['photo_url']); // Delete the old file
                }
                $formData['photo_url'] = $uploadPath; // Update photo path in formData
            } else {
                $errors['pet_photo'] = 'Failed to upload photo. Please try again.';
            }
        }
    } elseif (isset($_FILES['pet_photo']) && $_FILES['pet_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Other upload errors (e.g., UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, etc.)
        $errors['pet_photo'] = 'An upload error occurred. Please check file size/type.';
    }

    // --- If no errors, update the pet ---
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE pets SET
                    name = :name,
                    species = :species,
                    breed = :breed,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    color = :color,
                    weight = :weight,
                    microchip_id = :microchip_id,
                    notes = :notes,
                    photo_url = :photo_url,
                    updated_at = NOW()
                WHERE pet_id = :pet_id AND owner_id = :owner_id
            ");
            
            $stmt->execute([
                'pet_id' => $formData['pet_id'],
                'owner_id' => $ownerId,
                'name' => $formData['name'],
                'species' => $formData['species'],
                'breed' => !empty($formData['breed']) ? $formData['breed'] : null,
                'date_of_birth' => !empty($formData['date_of_birth']) ? $formData['date_of_birth'] : null,
                'gender' => !empty($formData['gender']) ? $formData['gender'] : null,
                'color' => !empty($formData['color']) ? $formData['color'] : null,
                'weight' => !empty($formData['weight']) ? (float)$formData['weight'] : null,
                'microchip_id' => !empty($formData['microchip_id']) ? $formData['microchip_id'] : null,
                'notes' => !empty($formData['notes']) ? $formData['notes'] : null,
                'photo_url' => $formData['photo_url'] // This will be the new path or null
            ]);
            
            // (Optional but Recommended) Check if any rows were actually updated
            if ($stmt->rowCount() > 0) {
                setFlash(sanitize($formData['name']) . ' has been successfully updated!', 'success');
            } else {
                setFlash('No changes were detected for ' . sanitize($formData['name']) . '.', 'info');
            }
            redirect('/client/pets/index.php');
            
        } catch (PDOException $e) {
            // error_log("Failed to update pet: " . $e->getMessage()); 
            $errors['general'] = 'Failed to update pet. Please try again. If the problem persists, contact support.';
        }
    }
}

// Common pet species and breeds (same as add.php)
$commonSpecies = [
    'Dog' => ['Labrador Retriever', 'German Shepherd', 'Golden Retriever', 'Bulldog', 'Beagle', 'Poodle', 'Rottweiler', 'Yorkshire Terrier', 'Dachshund', 'Siberian Husky', 'Shih Tzu', 'Pomeranian', 'Chihuahua', 'Mixed Breed', 'Other'],
    'Cat' => ['Persian', 'Maine Coon', 'Siamese', 'Ragdoll', 'British Shorthair', 'American Shorthair', 'Scottish Fold', 'Sphynx', 'Russian Blue', 'Bengal', 'Mixed Breed', 'Other'],
    'Bird' => ['Parakeet', 'Cockatiel', 'Parrot', 'Canary', 'Finch', 'Lovebird', 'Cockatoo', 'Conure', 'Other'],
    'Rabbit' => ['Holland Lop', 'Netherland Dwarf', 'Lionhead', 'Rex', 'Flemish Giant', 'Other'],
    'Hamster' => ['Syrian', 'Dwarf', 'Chinese', 'Roborovski', 'Other'],
    'Guinea Pig' => ['American', 'Abyssinian', 'Peruvian', 'Other'],
    'Fish' => ['Goldfish', 'Betta', 'Guppy', 'Tetra', 'Other'],
    'Reptile' => ['Turtle', 'Lizard', 'Snake', 'Gecko', 'Other'],
    'Other' => []
];

function getPetEmoji($species) {
    if (!$species) return '🐾';
    
    $species = strtolower($species);

    if (strpos($species, 'dog') !== false) return '🐕';
    if (strpos($species, 'cat') !== false) return '🐈';
    if (strpos($species, 'bird') !== false) return '🦜';
    if (strpos($species, 'rabbit') !== false) return '🐰';
    if (strpos($species, 'hamster') !== false) return '🐹';
    if (strpos($species, 'fish') !== false) return '🐠';
    if (strpos($species, 'turtle') !== false) return '🐢';
    if (strpos($species, 'guinea pig') !== false) return '🐹';

    return '🐾'; // Default fallback
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <?php include '../../includes/favicon.php'; ?>
    <style>
        /* Reset and Base Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: #f8f9fa; 
            color: #333; 
            line-height: 1.6;
        }
        .dashboard-layout { 
            display: flex; 
            min-height: 100vh; 
            overflow-x: hidden;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            margin-left: 250px; 
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
            width: calc(100% - 250px);
            max-width: calc(100% - 250px);
            overflow-x: hidden;
        }

        .page-header {
            width: 100%;
            margin-bottom: 2rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .breadcrumb a {
            color: #ff6b6b;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .breadcrumb a:hover {
            color: #ff5252;
            text-decoration: underline;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .page-icon {
            font-size: 2.5rem;
        }

        /* Edit Container */
        .edit-container {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            height: fit-content;
        }

        .edit-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            height: fit-content;
            transition: all 0.3s ease;
        }

        .edit-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .edit-card h2 {
            font-size: 1.375rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #ff6b6b;
            padding-bottom: 1rem;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-card h2 i {
            color: #ff6b6b;
            font-size: 1.25rem;
        }

        /* Form Styles */
        .form-group { 
            margin-bottom: 1.5rem; 
        }

        .form-row { 
            display: flex; 
            gap: 1rem; 
            margin-bottom: 1.5rem;
        }

        .form-row .form-group { 
            flex: 1; 
            margin-bottom: 0;
        }

        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 0.5rem; 
            color: #495057;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #fff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .form-group input.is-invalid,
        .form-group select.is-invalid,
        .form-group textarea.is-invalid {
            border-color: #e74c3c;
        }

        .invalid-feedback {
            display: block;
            margin-top: 0.25rem;
            color: #e74c3c;
            font-size: 0.813rem;
        }

        .form-hint {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.813rem;
        }

        select.form-control {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
            appearance: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Species Grid */
        .species-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .species-card {
            text-align: center;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .species-card:hover {
            border-color: #ff6b6b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .species-card.selected {
            border-color: #ff6b6b;
            background: #fff5f5;
        }

        .species-card input[type="radio"] {
            display: none;
        }

        .species-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .species-name {
            font-size: 0.875rem;
            color: #333;
            font-weight: 500;
        }

        /* Radio Group */
        .radio-group {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            margin-right: 0.5rem;
            cursor: pointer;
        }

        .radio-option label {
            cursor: pointer;
            user-select: none;
        }

        /* Photo Upload Styles */
        .pet-photo-preview {
            margin: 1rem 0;
            text-align: center;
        }

        .pet-photo-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 1px solid #ddd;
            object-fit: cover;
        }
        
        .pet-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ccc;
            background-color: #f9f9f9;
            margin: 0 auto;
        }

        .remove-photo-checkbox {
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .remove-photo-checkbox input[type="checkbox"] {
            cursor: pointer;
        }

        .remove-photo-checkbox label {
            font-size: 0.875rem;
            color: #666;
            cursor: pointer;
        }

        /* Form Actions */
        .form-actions { 
            margin-top: 2rem; 
            text-align: right;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
            gap: 1rem;
            display: flex;
            justify-content: flex-end;
        }

        .btn { 
            padding: 0.875rem 1.75rem; 
            border: none; 
            border-radius: 8px; 
            font-size: 0.875rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
        }

        .btn-primary { 
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
            color: white; 
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }

        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 107, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #e6ffe6, #d4edda);
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border: 1px solid #b8daff;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .edit-container {
                gap: 1.5rem;
            }
            
            .edit-card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .edit-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .page-header {
                margin-bottom: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .sidebar { 
                transform: translateX(-100%); 
                transition: transform 0.3s ease-in-out; 
                z-index: 1100; 
                position: fixed; 
                top: 0; 
                height: 100vh; 
                margin-top: 0; 
            }
            
            .main-content { 
                margin-left: 0; 
                width: 100%;
                max-width: 100%;
                padding: 1rem;
                padding-top: 85px; 
            }
            
            body.sidebar-is-open .sidebar { 
                transform: translateX(0); 
                box-shadow: 0 0 20px rgba(0,0,0,0.25); 
            }
            
            body.sidebar-is-open .sidebar-overlay { 
                opacity: 1; 
                visibility: visible; 
            }

            .edit-card {
                padding: 1.25rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-row .form-group {
                margin-bottom: 1.5rem;
            }

            .species-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0.75rem;
                padding-top: 85px;
            }
            
            .edit-card {
                padding: 1rem;
            }
            
            .page-title h1 {
                font-size: 1.5rem;
            }
            
            .edit-card h2 {
                font-size: 1.125rem;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <!-- Include the correct sidebar for clients -->
    <?php include '../../includes/sidebar-client.php'; ?>
    <?php include '../../includes/navbar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <nav class="breadcrumb">
                <a href="../index.php">Dashboard</a>
                <span>›</span>
                <a href="index.php">My Pets</a>
                <span>›</span>
                <a href="view.php?id=<?php echo $petId; ?>"><?php echo sanitize($pet['name']); ?></a>
                <span>›</span>
                <span>Edit Profile</span>
            </nav>
            <div class="page-title">
                <span class="page-icon">✏️</span>
                <h1>Edit <?php echo sanitize($pet['name']); ?>'s Profile</h1>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php $flashMessages = getFlash(); ?>
        <?php if (!empty($flashMessages)): ?>
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?php echo sanitize($type); ?>">
                        <span>
                            <?php 
                                if ($type == 'success') echo '✅';
                                else if ($type == 'warning') echo '⚠️';
                                else if ($type == 'danger') echo '❌';
                                else echo 'ℹ️';
                            ?>
                        </span>
                        <?php echo sanitize($message); ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <?php echo sanitize($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="edit-container">
                <!-- Basic Information & Photo Card -->
                <div class="edit-card">
                    <h2>
                        <i class="fas fa-paw"></i>
                        Basic Information & Photo
                    </h2>
                    
                    <!-- Photo Upload Section -->
                    <div class="form-group">
                        <label class="form-label">Pet Photo</label>
                        <div class="pet-photo-preview">
                            <?php $hasPhoto = !empty($formData['photo_url']) && file_exists($formData['photo_url']); ?>
                            
                            <img 
                                id="image-preview" 
                                src="<?php echo $hasPhoto ? sanitize($formData['photo_url']) : ''; ?>" 
                                alt="Pet Photo Preview"
                                style="display: <?php echo $hasPhoto ? 'block' : 'none'; ?>;"
                            >
                            
                            <div 
                                id="image-placeholder" 
                                class="pet-photo-placeholder" 
                                style="display: <?php echo $hasPhoto ? 'none' : 'inline-flex'; ?>;"
                            >
                                <i class="fas fa-image"></i>
                            </div>
                        </div>

                        <input 
                            type="file" 
                            id="pet_photo" 
                            name="pet_photo" 
                            class="form-control <?php echo isset($errors['pet_photo']) ? 'is-invalid' : ''; ?>"
                            accept="image/jpeg, image/png, image/gif, image/webp"
                        >
                        <?php if (isset($errors['pet_photo'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['pet_photo']; ?></div>
                        <?php endif; ?>
                        <span class="form-hint">Max file size: <?php echo (MAX_FILE_SIZE / (1024 * 1024)); ?>MB. Allowed types: JPG, PNG, GIF, WEBP.</span>
                    </div>

                    <div class="form-group">
                        <label for="name" class="form-label">
                            Pet Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                            value="<?php echo sanitize($formData['name']); ?>"
                            placeholder="Enter your pet's name"
                            required
                            autofocus
                            maxlength="50"
                        >
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Species <span class="required">*</span>
                        </label>
                        <div class="species-grid">
                            <?php
                            $speciesIcons = [
                                'Dog' => '🐶',
                                'Cat' => '🐱',
                                'Bird' => '🐦',
                                'Rabbit' => '🐇',
                                'Hamster' => '🐹',
                                'Other' => '🐾'
                            ];
                            
                            foreach ($speciesIcons as $species => $icon):
                            ?>
                                <label class="species-card <?php echo $formData['species'] == $species ? 'selected' : ''; ?>">
                                    <input 
                                        type="radio" 
                                        name="species" 
                                        value="<?php echo sanitize($species); ?>"
                                        <?php echo $formData['species'] == $species ? 'checked' : ''; ?>
                                        onchange="updateBreedOptions(this.value); updateSpeciesCardSelection();"
                                    >
                                    <span class="species-icon"><?php echo $icon; ?></span>
                                    <span class="species-name"><?php echo sanitize($species); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errors['species'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['species']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" id="breedGroup" style="display: <?php echo !empty($formData['species']) ? 'block' : 'none'; ?>">
                        <label for="breed" class="form-label">Breed</label>
                        <select id="breed" name="breed" class="form-control">
                            <option value="">Select breed (optional)</option>
                            <!-- Breeds will be populated by JavaScript -->
                        </select>
                        <span class="form-hint">Can't find your pet's breed? Select "Other" from the species options or leave blank.</span>
                    </div>
                </div>

                <!-- Detailed Information Card -->
                <div class="edit-card">
                    <h2>
                        <i class="fas fa-info-circle"></i>
                        Detailed Information
                    </h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input 
                                type="date" 
                                id="date_of_birth" 
                                name="date_of_birth" 
                                class="form-control <?php echo isset($errors['date_of_birth']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo sanitize($formData['date_of_birth']); ?>"
                                max="<?php echo date('Y-m-d'); ?>"
                            >
                            <?php if (isset($errors['date_of_birth'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['date_of_birth']; ?></div>
                            <?php endif; ?>
                            <span class="form-hint">Leave blank if unknown</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input 
                                        type="radio" 
                                        id="gender_male" 
                                        name="gender" 
                                        value="male"
                                        <?php echo $formData['gender'] == 'male' ? 'checked' : ''; ?>
                                    >
                                    <label for="gender_male">Male</label>
                                </div>
                                <div class="radio-option">
                                    <input 
                                        type="radio" 
                                        id="gender_female" 
                                        name="gender" 
                                        value="female"
                                        <?php echo $formData['gender'] == 'female' ? 'checked' : ''; ?>
                                    >
                                    <label for="gender_female">Female</label>
                                </div>
                            </div>
                            <?php if (isset($errors['gender'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['gender']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="color" class="form-label">Color/Markings</label>
                            <input 
                                type="text" 
                                id="color" 
                                name="color" 
                                class="form-control"
                                value="<?php echo sanitize($formData['color']); ?>"
                                placeholder="e.g., Brown, Black and white, Tabby"
                                maxlength="50"
                            >
                            <span class="form-hint">Describe your pet's primary color and any distinct markings.</span>
                        </div>

                        <div class="form-group">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input 
                                type="number" 
                                id="weight" 
                                name="weight" 
                                class="form-control <?php echo isset($errors['weight']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo sanitize($formData['weight']); ?>"
                                step="0.1"
                                min="0"
                                max="500"
                                placeholder="0.0"
                            >
                            <?php if (isset($errors['weight'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['weight']; ?></div>
                            <?php endif; ?>
                            <span class="form-hint">Estimate if exact weight is unknown.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="microchip_id" class="form-label">Microchip ID</label>
                        <input 
                            type="text" 
                            id="microchip_id" 
                            name="microchip_id" 
                            class="form-control <?php echo isset($errors['microchip_id']) ? 'is-invalid' : ''; ?>"
                            value="<?php echo sanitize($formData['microchip_id']); ?>"
                            placeholder="Enter microchip number if available"
                            maxlength="30"
                        >
                        <?php if (isset($errors['microchip_id'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['microchip_id']; ?></div>
                        <?php endif; ?>
                        <span class="form-hint">This helps us identify your pet if they get lost.</span>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            class="form-control"
                            rows="4" 
                            placeholder="Any other important information about your pet, e.g., known allergies, behavioral issues, special needs."
                        ><?php echo sanitize($formData['notes']); ?></textarea>
                        <span class="form-hint">Optional: Provide any relevant notes for our veterinary staff.</span>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo SITE_URL; ?>/client/pets/index.php" class="btn btn-secondary">
                            <i class="fas fa-times-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
// PHP array converted to JavaScript for dynamic breed population
const commonSpeciesBreeds = <?php echo json_encode($commonSpecies); ?>;
const breedSelect = document.getElementById('breed');
const breedGroup = document.getElementById('breedGroup');
const speciesRadioButtons = document.querySelectorAll('input[name="species"]');
const speciesCards = document.querySelectorAll('.species-card');

// Function to update breed options based on selected species
function updateBreedOptions(selectedSpecies) {
    breedSelect.innerHTML = '<option value="">Select breed (optional)</option>'; // Reset options

    if (selectedSpecies && commonSpeciesBreeds[selectedSpecies]) {
        const breeds = commonSpeciesBreeds[selectedSpecies];
        breeds.forEach(breed => {
            const option = document.createElement('option');
            option.value = breed;
            option.textContent = breed;
            breedSelect.appendChild(option);
        });
        breedGroup.style.display = 'block'; // Show breed field
    } else {
        breedGroup.style.display = 'none'; // Hide breed field if no species selected or "Other"
    }

    // Restore previously selected breed if available (after validation error or initial load)
    const preSelectedBreed = "<?php echo sanitize($formData['breed']); ?>";
    if (preSelectedBreed) {
        breedSelect.value = preSelectedBreed;
    }
}

// Function to update species card visual selection
function updateSpeciesCardSelection() {
    speciesCards.forEach(card => {
        const radio = card.querySelector('input[type="radio"]');
        if (radio.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
}

// Initialize breed options and species card selection on page load
document.addEventListener('DOMContentLoaded', () => {
    const initialSpecies = "<?php echo sanitize($formData['species']); ?>";
    if (initialSpecies) {
        updateBreedOptions(initialSpecies);
    }
    updateSpeciesCardSelection(); // Set initial visual state for species cards

    // Add event listeners for species card clicks
    speciesRadioButtons.forEach(radio => {
        radio.addEventListener('change', () => {
            updateSpeciesCardSelection();
        });
    });

    // Image preview functionality
    const fileInput = document.getElementById('pet_photo');
    const imagePreview = document.getElementById('image-preview');
    const imagePlaceholder = document.getElementById('image-placeholder');

    fileInput.addEventListener('change', function() {
        const file = this.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                imagePlaceholder.style.display = 'none';
            };

            reader.readAsDataURL(file);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.querySelector('.hamburger-menu');
    const overlay = document.querySelector('.sidebar-overlay');
    const body = document.body;

    if (hamburgerBtn && body) {
        hamburgerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            body.classList.toggle('sidebar-is-open');
        });
    }
    
    if (overlay && body) {
        overlay.addEventListener('click', function() {
            body.classList.remove('sidebar-is-open');
        });
    }
});
</script>
</body>
</html>