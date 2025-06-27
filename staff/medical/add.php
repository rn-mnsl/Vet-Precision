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

// Get completed appointments for dropdown - FIXED: Only completed appointments
$confirmed_appointments = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            p.name as pet_name,
            p.pet_id,
            p.species,
            CONCAT(u.first_name, ' ', u.last_name) as owner_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN owners o ON p.owner_id = o.owner_id
        JOIN users u ON o.user_id = u.user_id
        WHERE a.status = 'completed'
        AND a.appointment_id NOT IN (SELECT appointment_id FROM medical_records WHERE appointment_id IS NOT NULL)
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute();
    $confirmed_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching appointments: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_record'])) {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $visit_date = $_POST['visit_date'] ?? '';
    $weight = $_POST['weight'] ?? null;
    $temperature = $_POST['temperature'] ?? null;
    $heart_rate = $_POST['heart_rate'] ?? null;
    $respiratory_rate = $_POST['respiratory_rate'] ?? null;
    $symptoms = trim($_POST['symptoms'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $prescription = null; // Will be set if photo is uploaded
    $follow_up_required = isset($_POST['follow_up_required']) ? 1 : 0;
    $follow_up_date = $_POST['follow_up_date'] ?? null;

    // Validation
    if (empty($appointment_id)) $errors[] = "Please select an appointment.";
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
            $newFileName = 'prescription_new_' . uniqid() . '_' . time() . '.' . $fileExt;
            $uploadPath = PRESCRIPTION_UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $prescription = $uploadPath; // Set prescription path
            } else {
                $errors[] = 'Failed to upload prescription photo. Please try again.';
            }
        }
    } elseif (isset($_FILES['prescription_photo']) && $_FILES['prescription_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'An upload error occurred with prescription photo. Please check file size/type.';
    }

    if (empty($errors)) {
        try {
            // Get pet_id from selected appointment
            $stmt = $pdo->prepare("SELECT pet_id FROM appointments WHERE appointment_id = ?");
            $stmt->execute([$appointment_id]);
            $pet_id = $stmt->fetchColumn();

            if (!$pet_id) {
                $errors[] = "Invalid appointment selected.";
            } else {
                // Insert medical record
                $sql = "INSERT INTO medical_records (
                    pet_id, appointment_id, visit_date, weight, temperature, 
                    heart_rate, respiratory_rate, symptoms, diagnosis, treatment, 
                    prescription, follow_up_required, follow_up_date, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $pet_id,
                    $appointment_id,
                    $visit_date,
                    $weight ?: null,
                    $temperature ?: null,
                    $heart_rate ?: null,
                    $respiratory_rate ?: null,
                    $symptoms,
                    $diagnosis,
                    $treatment,
                    $prescription, // This will be the file path or null
                    $follow_up_required,
                    $follow_up_date ?: null,
                    $_SESSION['user_id']
                ]);

                $_SESSION['success_message'] = "Medical record created successfully!";
                header('Location: index.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Error creating medical record: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Medical Record - ' . SITE_NAME;
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

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2.5rem;
            width: 100%;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
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
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
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

        .appointment-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1rem;
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .detail-item {
            font-size: 0.95rem;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
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

        /* Modal for prescription photo viewing */
        .prescription-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .prescription-modal-content {
            position: relative;
            max-width: 95%;
            max-height: 95%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .prescription-modal img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }

        .prescription-modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: rgba(0,0,0,0.7);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .prescription-modal-close:hover {
            background: rgba(0,0,0,0.9);
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

        .alert ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        /* Enhanced appointment selection styling */
        .form-control select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .appointment-info strong {
            display: block;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        @media (max-width: 1200px) {
            .form-grid {
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .appointment-details {
                grid-template-columns: 1fr;
                gap: 1rem;
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
            <h1>Create Medical Record</h1>
            <div class="breadcrumb">
                <a href="index.php">Medical Records</a> / Create New (Completed Appointments Only)
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

        <div class="card">
            <form method="POST" id="medicalRecordForm" enctype="multipart/form-data">
                <div class="form-section">
                    <h3><i class="fas fa-calendar-check"></i> Completed Appointment Information</h3>
                    <div class="form-group">
                        <label for="appointment_id">Select Completed Appointment <span class="required">*</span></label>
                        <select name="appointment_id" id="appointment_id" class="form-control" required onchange="updateAppointmentInfo()">
                            <option value="">Choose a completed appointment...</option>
                            <?php if (empty($confirmed_appointments)): ?>
                                <option value="" disabled>No completed appointments without medical records available</option>
                            <?php else: ?>
                                <?php foreach ($confirmed_appointments as $appt): ?>
                                    <option value="<?php echo $appt['appointment_id']; ?>" 
                                            data-pet-name="<?php echo htmlspecialchars($appt['pet_name']); ?>"
                                            data-pet-species="<?php echo htmlspecialchars($appt['species']); ?>"
                                            data-owner-name="<?php echo htmlspecialchars($appt['owner_name']); ?>"
                                            data-date="<?php echo $appt['appointment_date']; ?>"
                                            data-time="<?php echo $appt['appointment_time']; ?>"
                                            <?php echo (isset($_POST['appointment_id']) && $_POST['appointment_id'] == $appt['appointment_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($appt['pet_name']); ?> - 
                                        <?php echo date('M j, Y g:i A', strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div id="appointmentInfo" class="appointment-info" style="display: none;">
                        <strong>Completed Appointment Details:</strong>
                        <div class="appointment-details">
                            <div class="detail-item">
                                <span class="detail-label">Pet:</span> <span id="selectedPetName"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Species:</span> <span id="selectedPetSpecies"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Owner:</span> <span id="selectedOwnerName"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date & Time:</span> <span id="selectedDateTime"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="visit_date">Visit Date <span class="required">*</span></label>
                        <input type="date" name="visit_date" id="visit_date" class="form-control" 
                               value="<?php echo isset($_POST['visit_date']) ? htmlspecialchars($_POST['visit_date']) : date('Y-m-d'); ?>" required>
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
                                           value="<?php echo isset($_POST['weight']) ? htmlspecialchars($_POST['weight']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="temperature">Temperature (°C)</label>
                                    <input type="number" name="temperature" id="temperature" class="form-control" 
                                           step="0.1" min="30" max="45" 
                                           value="<?php echo isset($_POST['temperature']) ? htmlspecialchars($_POST['temperature']) : ''; ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="heart_rate">Heart Rate (bpm)</label>
                                    <input type="number" name="heart_rate" id="heart_rate" class="form-control" 
                                           min="0" max="500" 
                                           value="<?php echo isset($_POST['heart_rate']) ? htmlspecialchars($_POST['heart_rate']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="respiratory_rate">Respiratory Rate (rpm)</label>
                                    <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" 
                                           min="0" max="100" 
                                           value="<?php echo isset($_POST['respiratory_rate']) ? htmlspecialchars($_POST['respiratory_rate']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="form-section">
                            <h3><i class="fas fa-notes-medical"></i> Medical Information</h3>
                            <div class="form-group">
                                <label for="symptoms">Symptoms <span class="required">*</span></label>
                                <textarea name="symptoms" id="symptoms" class="form-control" required 
                                          placeholder="Describe the symptoms observed..."><?php echo isset($_POST['symptoms']) ? htmlspecialchars($_POST['symptoms']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="diagnosis">Diagnosis <span class="required">*</span></label>
                                <textarea name="diagnosis" id="diagnosis" class="form-control" required 
                                          placeholder="Enter the diagnosis..."><?php echo isset($_POST['diagnosis']) ? htmlspecialchars($_POST['diagnosis']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-prescription-bottle-alt"></i> Treatment & Prescription</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="treatment">Treatment <span class="required">*</span></label>
                            <textarea name="treatment" id="treatment" class="form-control" required 
                                      placeholder="Describe the treatment provided..."><?php echo isset($_POST['treatment']) ? htmlspecialchars($_POST['treatment']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="prescription_photo">Prescription Photo</label>
                            
                            <!-- Prescription Photo Preview Area -->
                            <div class="prescription-photo-preview">
                                <img 
                                    id="prescription-preview" 
                                    src="" 
                                    alt="Prescription Photo Preview"
                                    style="display: none;"
                                    onclick="openPrescriptionModal()"
                                >
                                
                                <div 
                                    id="prescription-placeholder" 
                                    class="prescription-photo-placeholder" 
                                    style="display: inline-flex;"
                                >
                                    <i class="fas fa-prescription"></i>
                                    <span class="placeholder-text">No prescription photo</span>
                                </div>
                            </div>

                            <input 
                                type="file" 
                                id="prescription_photo" 
                                name="prescription_photo" 
                                class="form-control"
                                accept="image/jpeg, image/png, image/gif, image/webp"
                            >
                            <span class="form-hint" style="font-size: 0.8rem; color: #666; margin-top: 0.5rem; display: block;">
                                Upload a photo of the prescription. Max file size: 5MB. Allowed types: JPG, PNG, GIF, WEBP.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-calendar-plus"></i> Follow-up</h3>
                    <div class="checkbox-group">
                        <input type="checkbox" name="follow_up_required" id="follow_up_required" 
                               <?php echo (isset($_POST['follow_up_required'])) ? 'checked' : ''; ?>
                               onchange="toggleFollowUpDate()">
                        <label for="follow_up_required">Follow-up appointment required</label>
                    </div>
                    <div class="form-group" id="followUpDateGroup" style="display: none;">
                        <label for="follow_up_date">Follow-up Date</label>
                        <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" 
                               value="<?php echo isset($_POST['follow_up_date']) ? htmlspecialchars($_POST['follow_up_date']) : ''; ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="create_record" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prescription Photo Modal -->
    <div id="prescriptionModal" class="prescription-modal">
        <div class="prescription-modal-content">
            <button class="prescription-modal-close" onclick="closePrescriptionModal()">&times;</button>
            <img id="prescriptionModalImg" src="" alt="Prescription Photo">
        </div>
    </div>

    <script>
        function updateAppointmentInfo() {
            const select = document.getElementById('appointment_id');
            const info = document.getElementById('appointmentInfo');
            const option = select.options[select.selectedIndex];

            if (option.value) {
                document.getElementById('selectedPetName').textContent = option.dataset.petName;
                document.getElementById('selectedPetSpecies').textContent = option.dataset.petSpecies;
                document.getElementById('selectedOwnerName').textContent = option.dataset.ownerName;
                
                const date = new Date(option.dataset.date + ' ' + option.dataset.time);
                document.getElementById('selectedDateTime').textContent = date.toLocaleString();
                
                info.style.display = 'block';
                
                // Auto-fill visit date with appointment date
                document.getElementById('visit_date').value = option.dataset.date;
            } else {
                info.style.display = 'none';
            }
        }

        function toggleFollowUpDate() {
            const checkbox = document.getElementById('follow_up_required');
            const dateGroup = document.getElementById('followUpDateGroup');
            
            if (checkbox.checked) {
                dateGroup.style.display = 'block';
                // Set minimum date to tomorrow
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

        // Prescription modal functions
        function openPrescriptionModal() {
            const modal = document.getElementById('prescriptionModal');
            const modalImg = document.getElementById('prescriptionModalImg');
            const prescriptionImg = document.getElementById('prescription-preview');
            
            if (prescriptionImg.src) {
                modalImg.src = prescriptionImg.src;
                modal.style.display = 'flex';
            }
        }

        function closePrescriptionModal() {
            document.getElementById('prescriptionModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        document.getElementById('prescriptionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePrescriptionModal();
            }
        });

        // Initialize follow-up date visibility
        document.addEventListener('DOMContentLoaded', function() {
            toggleFollowUpDate();
            updateAppointmentInfo();
        });
    </script>
</body>
</html>