<?php
require_once '../../config/init.php';
requireStaff();

$errors = [];
$success_message = '';

// Configuration for prescription photo uploads
if (!defined('PRESCRIPTION_UPLOAD_DIR')) {
    define('PRESCRIPTION_UPLOAD_DIR', 'uploads/prescription/');
}

// Create upload directory if it doesn't exist
if (!file_exists(PRESCRIPTION_UPLOAD_DIR)) {
    mkdir(PRESCRIPTION_UPLOAD_DIR, 0755, true);
}

// Get record ID from URL
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$record_id) {
    header('Location: index.php');
    exit();
}

// Fetch medical record with related data - FIXED: Only completed appointments
$medical_record = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            mr.*,
            p.name as pet_name,
            p.species,
            p.breed,
            p.date_of_birth,
            a.appointment_date,
            a.appointment_time,
            a.status as appointment_status,
            CONCAT(u.first_name, ' ', u.last_name) as owner_name,
            CONCAT(staff.first_name, ' ', staff.last_name) as created_by_name
        FROM medical_records mr
        JOIN pets p ON mr.pet_id = p.pet_id
        JOIN appointments a ON mr.appointment_id = a.appointment_id
        JOIN owners o ON p.owner_id = o.owner_id
        JOIN users u ON o.user_id = u.user_id
        JOIN users staff ON mr.created_by = staff.user_id
        WHERE mr.record_id = ? AND a.status = 'completed'
    ");
    $stmt->execute([$record_id]);
    $medical_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$medical_record) {
        $_SESSION['error_message'] = "Medical record not found or not from a completed appointment.";
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching medical record.";
    header('Location: index.php');
    exit();
}

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $visit_date = $_POST['visit_date'] ?? '';
    $weight = $_POST['weight'] ?? null;
    $temperature = $_POST['temperature'] ?? null;
    $heart_rate = $_POST['heart_rate'] ?? null;
    $respiratory_rate = $_POST['respiratory_rate'] ?? null;
    $symptoms = trim($_POST['symptoms'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $prescription = $medical_record['prescription']; // Keep existing prescription path by default
    $follow_up_required = isset($_POST['follow_up_required']) ? 1 : 0;
    $follow_up_date = $_POST['follow_up_date'] ?? null;

    // Validation
    if (empty($visit_date)) $errors[] = "Please enter the visit date.";
    if (empty($symptoms)) $errors[] = "Please enter symptoms.";
    if (empty($diagnosis)) $errors[] = "Please enter diagnosis.";
    if (empty($treatment)) $errors[] = "Please enter treatment.";

    // Validate numeric fields
    if (!empty($weight) && (!is_numeric($weight) || $weight <= 0)) {
        $errors[] = "Weight must be a positive number.";
    }
    if (!empty($temperature) && (!is_numeric($temperature) || $temperature < 30 || $temperature > 45)) {
        $errors[] = "Temperature must be between 30-45°C.";
    }
    if (!empty($heart_rate) && (!is_numeric($heart_rate) || $heart_rate <= 0)) {
        $errors[] = "Heart rate must be a positive number.";
    }
    if (!empty($respiratory_rate) && (!is_numeric($respiratory_rate) || $respiratory_rate <= 0)) {
        $errors[] = "Respiratory rate must be a positive number.";
    }

    // Handle prescription photo upload
    if (isset($_FILES['prescription_photo']) && $_FILES['prescription_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['prescription_photo'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = mime_content_type($fileTmpName);

        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validation for prescription photo
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if ($fileSize > $maxFileSize) {
            $errors[] = 'Prescription photo is too large. Maximum size is 5MB.';
        }
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Invalid file type for prescription. Only JPEG, PNG, GIF, WEBP images are allowed.';
        }
        if (!in_array($fileExt, $allowedExtensions)) {
            $errors[] = 'Invalid file extension for prescription. Only .jpg, .jpeg, .png, .gif, .webp are allowed.';
        }

        if (empty($errors)) {
            // Generate a unique file name
            $newFileName = 'prescription_' . $record_id . '_' . uniqid() . '_' . time() . '.' . $fileExt;
            $uploadPath = PRESCRIPTION_UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Successfully uploaded new prescription photo
                // Delete old prescription photo if it exists
                if (!empty($medical_record['prescription']) && file_exists($medical_record['prescription']) && $medical_record['prescription'] != $uploadPath) {
                    unlink($medical_record['prescription']);
                }
                $prescription = $uploadPath; // Update prescription path
            } else {
                $errors[] = 'Failed to upload prescription photo. Please try again.';
            }
        }
    } elseif (isset($_FILES['prescription_photo']) && $_FILES['prescription_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'An upload error occurred with prescription photo. Please check file size/type.';
    }

    // Handle prescription photo removal
    if (isset($_POST['remove_prescription']) && $_POST['remove_prescription'] == '1') {
        if (!empty($medical_record['prescription']) && file_exists($medical_record['prescription'])) {
            unlink($medical_record['prescription']);
        }
        $prescription = null;
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE medical_records SET 
                visit_date = ?, weight = ?, temperature = ?, heart_rate = ?, 
                respiratory_rate = ?, symptoms = ?, diagnosis = ?, treatment = ?, 
                prescription = ?, follow_up_required = ?, follow_up_date = ?
                WHERE record_id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $visit_date,
                $weight ?: null,
                $temperature ?: null,
                $heart_rate ?: null,
                $respiratory_rate ?: null,
                $symptoms,
                $diagnosis,
                $treatment,
                $prescription,
                $follow_up_required,
                $follow_up_date ?: null,
                $record_id
            ]);

            $success_message = "Medical record updated successfully!";
            
            // Refresh the record data
            $stmt = $pdo->prepare("
                SELECT 
                    mr.*,
                    p.name as pet_name,
                    p.species,
                    p.breed,
                    p.date_of_birth,
                    a.appointment_date,
                    a.appointment_time,
                    a.status as appointment_status,
                    CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                    CONCAT(staff.first_name, ' ', staff.last_name) as created_by_name
                FROM medical_records mr
                JOIN pets p ON mr.pet_id = p.pet_id
                JOIN appointments a ON mr.appointment_id = a.appointment_id
                JOIN owners o ON p.owner_id = o.owner_id
                JOIN users u ON o.user_id = u.user_id
                JOIN users staff ON mr.created_by = staff.user_id
                WHERE mr.record_id = ?
            ");
            $stmt->execute([$record_id]);
            $medical_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $errors[] = "Error updating medical record: " . $e->getMessage();
        }
    }
}

$pageTitle = 'View Medical Record - ' . SITE_NAME;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../../includes/favicon.php'; ?>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
            margin: 0;
        }

        .breadcrumb {
            margin-top: 0.5rem;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .record-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .info-section h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            gap: 0.75rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .form-control textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        /* Prescription Photo Styles */
        .prescription-photo-preview {
            margin-top: 1rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .prescription-photo-preview img {
            max-width: 300px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #ddd;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .prescription-photo-preview img:hover {
            transform: scale(1.05);
        }

        .prescription-photo-placeholder {
            width: 300px;
            height: 200px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ccc;
            background-color: #f9f9f9;
            flex-direction: column;
            gap: 0.5rem;
        }

        .prescription-photo-placeholder .placeholder-text {
            font-size: 0.9rem;
            color: #666;
        }

        .remove-prescription-checkbox {
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .remove-prescription-checkbox input[type="checkbox"] {
            cursor: pointer;
        }

        .remove-prescription-checkbox label {
            font-size: 0.875rem;
            color: #666;
            cursor: pointer;
        }

        .view-prescription-btn {
            margin-top: 0.5rem;
            text-align: center;
        }

        .view-prescription-btn a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: background-color 0.2s ease;
        }

        .view-prescription-btn a:hover {
            background-color: #0056b3;
        }

        /* Modal for prescription photo viewing */
        .prescription-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0,0,0,0.9);
            justify-content: center;
            align-items: center;
            padding: 60px 20px 20px 20px; /* Extra top padding for close button */
            box-sizing: border-box;
        }

        .prescription-modal.show {
            display: flex !important;
        }

        .prescription-modal-content {
            position: relative;
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 80px); /* Account for close button space */
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
        }

        .prescription-modal img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.8);
            background: white;
            padding: 4px;
            /* Ensure image never exceeds viewport */
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 120px);
        }

        .prescription-modal-close {
            position: fixed;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2.5rem;
            cursor: pointer;
            background: rgba(0,0,0,0.8);
            border: 2px solid rgba(255,255,255,0.3);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
            z-index: 10001;
            line-height: 1;
        }

        .prescription-modal-close:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.6);
            transform: scale(1.1);
        }

        .prescription-modal-close:focus {
            outline: 3px solid white;
            outline-offset: 2px;
        }

        /* Add zoom info indicator */
        .zoom-info {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
            z-index: 10001;
        }

        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .prescription-modal {
                padding: 70px 10px 10px 10px;
            }
            
            .prescription-modal-close {
                top: 15px;
                right: 15px;
                font-size: 2rem;
                width: 50px;
                height: 50px;
            }
            
            .prescription-modal-content {
                max-width: calc(100vw - 20px);
                max-height: calc(100vh - 100px);
            }

            .prescription-modal img {
                max-width: calc(100vw - 20px);
                max-height: calc(100vh - 130px);
            }

            .zoom-info {
                font-size: 0.8rem;
                padding: 8px 16px;
            }
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .alert-danger {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }

        .alert-success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }

        .alert ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-follow-up {
            background: #fff3cd;
            color: #856404;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .record-header {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }

            .prescription-photo-preview img,
            .prescription-photo-placeholder {
                max-width: 100%;
                width: 250px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar-staff.php'; ?>
    <?php include '../../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Medical Record #<?php echo $record_id; ?></h1>
            <div class="breadcrumb">
                <a href="index.php">Medical Records</a> / View Record
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Records
                </a>
                <button type="button" class="btn btn-primary" onclick="toggleEditMode()">
                    <i class="fas fa-edit"></i> Edit Record
                </button>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Record Information (Read-only) -->
        <div class="card">
            <div class="record-header">
                <div class="info-section">
                    <h3><i class="fas fa-paw"></i> Pet Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($medical_record['pet_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Species:</span>
                            <span class="info-value"><?php echo htmlspecialchars($medical_record['species']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Breed:</span>
                            <span class="info-value"><?php echo htmlspecialchars($medical_record['breed'] ?: 'Not specified'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Birth:</span>
                            <span class="info-value"><?php echo $medical_record['date_of_birth'] ? date('M j, Y', strtotime($medical_record['date_of_birth'])) : 'Not specified'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h3><i class="fas fa-calendar-check"></i> Appointment Details</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Owner:</span>
                            <span class="info-value"><?php echo htmlspecialchars($medical_record['owner_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Appointment Date:</span>
                            <span class="info-value"><?php echo date('M j, Y', strtotime($medical_record['appointment_date'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Appointment Time:</span>
                            <span class="info-value"><?php echo date('g:i A', strtotime($medical_record['appointment_time'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created By:</span>
                            <span class="info-value"><?php echo htmlspecialchars($medical_record['created_by_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="status-badge <?php echo $medical_record['follow_up_required'] ? 'status-follow-up' : 'status-active'; ?>">
                                <?php echo $medical_record['follow_up_required'] ? 'Follow-up Required' : 'Complete'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editable Medical Record Form -->
        <div class="card">
            <form method="POST" id="medicalRecordForm" enctype="multipart/form-data">
                <div class="form-section">
                    <h3><i class="fas fa-calendar"></i> Visit Information</h3>
                    <div class="form-group">
                        <label for="visit_date">Visit Date <span class="required">*</span></label>
                        <input type="date" name="visit_date" id="visit_date" class="form-control" 
                               value="<?php echo htmlspecialchars($medical_record['visit_date']); ?>" required disabled>
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                        <div class="form-section">
                            <h3><i class="fas fa-heartbeat"></i> Vital Signs</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="weight">Weight (kg)</label>
                                    <input type="number" name="weight" id="weight" class="form-control" 
                                           step="0.1" min="0" max="200" 
                                           value="<?php echo htmlspecialchars($medical_record['weight'] ?: ''); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="temperature">Temperature (°C)</label>
                                    <input type="number" name="temperature" id="temperature" class="form-control" 
                                           step="0.1" min="30" max="45" 
                                           value="<?php echo htmlspecialchars($medical_record['temperature'] ?: ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="heart_rate">Heart Rate (bpm)</label>
                                    <input type="number" name="heart_rate" id="heart_rate" class="form-control" 
                                           min="0" max="500" 
                                           value="<?php echo htmlspecialchars($medical_record['heart_rate'] ?: ''); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="respiratory_rate">Respiratory Rate (rpm)</label>
                                    <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" 
                                           min="0" max="100" 
                                           value="<?php echo htmlspecialchars($medical_record['respiratory_rate'] ?: ''); ?>" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="form-section">
                            <h3><i class="fas fa-notes-medical"></i> Medical Information</h3>
                            <div class="form-group">
                                <label for="symptoms">Symptoms <span class="required">*</span></label>
                                <textarea name="symptoms" id="symptoms" class="form-control" required disabled 
                                          placeholder="Describe the symptoms observed..."><?php echo htmlspecialchars($medical_record['symptoms']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="diagnosis">Diagnosis <span class="required">*</span></label>
                                <textarea name="diagnosis" id="diagnosis" class="form-control" required disabled 
                                          placeholder="Enter the diagnosis..."><?php echo htmlspecialchars($medical_record['diagnosis']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-prescription-bottle-alt"></i> Treatment & Prescription</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="treatment">Treatment <span class="required">*</span></label>
                            <textarea name="treatment" id="treatment" class="form-control" required disabled 
                                      placeholder="Describe the treatment provided..."><?php echo htmlspecialchars($medical_record['treatment']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="prescription_photo">Prescription Photo</label>
                            
                            <!-- Prescription Photo Preview Area -->
                            <div class="prescription-photo-preview">
                                <?php $hasPrescription = !empty($medical_record['prescription']) && file_exists($medical_record['prescription']); ?>
                                
                                <img 
                                    id="prescription-preview" 
                                    src="<?php echo $hasPrescription ? htmlspecialchars($medical_record['prescription']) : ''; ?>" 
                                    alt="Prescription Photo"
                                    style="display: <?php echo $hasPrescription ? 'block' : 'none'; ?>;"
                                    onclick="openPrescriptionModal()"
                                >
                                
                                <div 
                                    id="prescription-placeholder" 
                                    class="prescription-photo-placeholder" 
                                    style="display: <?php echo $hasPrescription ? 'none' : 'inline-flex'; ?>;"
                                >
                                    <i class="fas fa-prescription"></i>
                                    <span class="placeholder-text">No prescription photo</span>
                                </div>
                            </div>

                            <?php if ($hasPrescription): ?>
                                <div class="view-prescription-btn">
                                    <a href="<?php echo htmlspecialchars($medical_record['prescription']); ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> View Full Size
                                    </a>
                                </div>
                                <div class="remove-prescription-checkbox">
                                    <input type="checkbox" id="remove_prescription" name="remove_prescription" value="1" disabled>
                                    <label for="remove_prescription">Remove current prescription photo</label>
                                </div>
                            <?php endif; ?>

                            <input 
                                type="file" 
                                id="prescription_photo" 
                                name="prescription_photo" 
                                class="form-control"
                                accept="image/jpeg, image/png, image/gif, image/webp"
                                disabled
                            >
                            <div class="form-hint" style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                                Upload a photo of the prescription. Max file size: 5MB. Allowed types: JPG, PNG, GIF, WEBP.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-calendar-plus"></i> Follow-up</h3>
                    <div class="checkbox-group">
                        <input type="checkbox" name="follow_up_required" id="follow_up_required" 
                               <?php echo $medical_record['follow_up_required'] ? 'checked' : ''; ?>
                               onchange="toggleFollowUpDate()" disabled>
                        <label for="follow_up_required">Follow-up appointment required</label>
                    </div>
                    <div class="form-group" id="followUpDateGroup" style="display: <?php echo $medical_record['follow_up_required'] ? 'block' : 'none'; ?>;">
                        <label for="follow_up_date">Follow-up Date</label>
                        <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" 
                               value="<?php echo htmlspecialchars($medical_record['follow_up_date'] ?: ''); ?>" disabled>
                    </div>
                </div>

                <div class="form-actions" id="editActions" style="display: none;">
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="update_record" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prescription Photo Modal -->
    <div id="prescriptionModal" class="prescription-modal">
        <div class="prescription-modal-content">
            <img id="prescriptionModalImg" src="" alt="Prescription Photo Full Size" style="cursor: zoom-in;">
        </div>
        <button class="prescription-modal-close" onclick="closePrescriptionModal()" aria-label="Close modal">&times;</button>
    </div>

    <script>
        let isEditMode = false;

        function toggleEditMode() {
            isEditMode = !isEditMode;
            const formElements = document.querySelectorAll('.form-control, input[type="checkbox"]');
            const editActions = document.getElementById('editActions');
            const editButton = document.querySelector('.btn-primary');

            formElements.forEach(element => {
                element.disabled = !isEditMode;
            });

            if (isEditMode) {
                editActions.style.display = 'flex';
                editButton.innerHTML = '<i class="fas fa-times"></i> Cancel Edit';
                editButton.onclick = cancelEdit;
            } else {
                editActions.style.display = 'none';
                editButton.innerHTML = '<i class="fas fa-edit"></i> Edit Record';
                editButton.onclick = toggleEditMode;
            }
        }

        function cancelEdit() {
            window.location.reload();
        }

        function toggleFollowUpDate() {
            const checkbox = document.getElementById('follow_up_required');
            const dateGroup = document.getElementById('followUpDateGroup');
            
            if (checkbox.checked) {
                dateGroup.style.display = 'block';
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('follow_up_date').min = tomorrow.toISOString().split('T')[0];
            } else {
                dateGroup.style.display = 'none';
                document.getElementById('follow_up_date').value = '';
            }
        }

        // Prescription photo preview functionality
        document.getElementById('prescription_photo').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('prescription-preview');
            const placeholder = document.getElementById('prescription-placeholder');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Prescription modal functions - Enhanced version with better sizing
        function openPrescriptionModal() {
            console.log('Opening prescription modal...'); // Debug log
            
            const modal = document.getElementById('prescriptionModal');
            const modalImg = document.getElementById('prescriptionModalImg');
            const prescriptionImg = document.getElementById('prescription-preview');
            
            if (!modal || !modalImg || !prescriptionImg) {
                console.error('Modal elements not found');
                return;
            }
            
            if (prescriptionImg.src && prescriptionImg.src !== window.location.href) {
                console.log('Setting modal image source:', prescriptionImg.src); // Debug log
                modalImg.src = prescriptionImg.src;
                modal.classList.add('show');
                modal.style.display = 'flex';
                
                // Prevent body scroll when modal is open
                document.body.style.overflow = 'hidden';
                
                // Add zoom info
                addZoomInfo();
                
                // Focus on close button for accessibility
                setTimeout(() => {
                    document.querySelector('.prescription-modal-close')?.focus();
                }, 100);
                
                // Add click-to-zoom functionality
                modalImg.addEventListener('click', toggleImageZoom);
                
            } else {
                console.error('No valid image source found');
            }
        }

        function closePrescriptionModal() {
            console.log('Closing prescription modal...'); // Debug log
            
            const modal = document.getElementById('prescriptionModal');
            const modalImg = document.getElementById('prescriptionModalImg');
            
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                
                // Restore body scroll
                document.body.style.overflow = '';
                
                // Remove zoom info
                removeZoomInfo();
                
                // Reset image zoom
                if (modalImg) {
                    modalImg.style.transform = '';
                    modalImg.style.cursor = 'zoom-in';
                    modalImg.removeEventListener('click', toggleImageZoom);
                }
            }
        }

        function addZoomInfo() {
            // Remove existing zoom info if present
            removeZoomInfo();
            
            const zoomInfo = document.createElement('div');
            zoomInfo.className = 'zoom-info';
            zoomInfo.id = 'zoomInfo';
            zoomInfo.innerHTML = 'Click image to zoom • Press ESC to close';
            document.body.appendChild(zoomInfo);
        }

        function removeZoomInfo() {
            const existing = document.getElementById('zoomInfo');
            if (existing) {
                existing.remove();
            }
        }

        let isZoomed = false;
        function toggleImageZoom(e) {
            e.stopPropagation();
            const img = e.target;
            
            if (!isZoomed) {
                // Zoom in
                img.style.transform = 'scale(1.5)';
                img.style.cursor = 'zoom-out';
                isZoomed = true;
                
                // Update zoom info
                const zoomInfo = document.getElementById('zoomInfo');
                if (zoomInfo) {
                    zoomInfo.innerHTML = 'Click image to zoom out • Press ESC to close';
                }
            } else {
                // Zoom out
                img.style.transform = '';
                img.style.cursor = 'zoom-in';
                isZoomed = false;
                
                // Update zoom info
                const zoomInfo = document.getElementById('zoomInfo');
                if (zoomInfo) {
                    zoomInfo.innerHTML = 'Click image to zoom • Press ESC to close';
                }
            }
        }

        // Enhanced modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('prescriptionModal');
            const closeBtn = document.querySelector('.prescription-modal-close');
            const prescriptionImg = document.getElementById('prescription-preview');
            
            // Close modal when clicking outside of image
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closePrescriptionModal();
                    }
                });
            }
            
            // Close modal with close button
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closePrescriptionModal();
                });
            }
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePrescriptionModal();
                }
            });
            
            // Add click event to prescription image
            if (prescriptionImg) {
                prescriptionImg.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Prescription image clicked'); // Debug log
                    openPrescriptionModal();
                });
                
                // Add visual feedback
                prescriptionImg.style.cursor = 'pointer';
                prescriptionImg.title = 'Click to view full size';
            }
            
            // Initialize other functions
            toggleFollowUpDate();
        });
    </script>
</body>
</html>