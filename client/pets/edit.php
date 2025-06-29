<?php
require_once '../../config/init.php';
requireClient();

$pageTitle = 'Edit Pet - ' . SITE_NAME;

// Get pet ID from URL
$pet_id = $_GET['id'] ?? null;
if (!$pet_id) {
    header('Location: ../index.php');
    exit();
}

// Verify pet belongs to this owner
$stmt = $pdo->prepare("
    SELECT p.* 
    FROM pets p 
    JOIN owners o ON p.owner_id = o.owner_id 
    WHERE p.pet_id = ? AND o.user_id = ?
");
$stmt->execute([$pet_id, $_SESSION['user_id']]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    $_SESSION['error_message'] = 'Pet not found or access denied.';
    header('Location: ../index.php');
    exit();
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header h1 { 
            font-size: 2rem; 
            font-weight: 600; 
            color: var(--dark-color);
            margin: 0;
        }

        .edit-toggle-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .edit-toggle-btn:hover {
            background: #ff5252;
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }

        .edit-toggle-btn.cancel {
            background: #6c757d;
        }

        .edit-toggle-btn.cancel:hover {
            background: #5a6268;
        }

        /* Pet Edit Container - Side by Side Layout */
        .pet-edit-container {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            height: fit-content;
            max-height: calc(100vh - 200px);
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

        .form-group input[readonly] { 
            background-color: #f8f9fa; 
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* View Mode Styles */
        .pet-view-mode .form-group input,
        .pet-view-mode .form-group select,
        .pet-view-mode .form-group textarea {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            cursor: default;
            pointer-events: none;
        }

        .pet-view-mode .form-actions {
            display: none;
        }

        .pet-view-mode .photo-upload-section {
            pointer-events: none;
        }

        /* Edit Mode Styles */
        .pet-edit-mode .form-group input:not([readonly]),
        .pet-edit-mode .form-group select,
        .pet-edit-mode .form-group textarea {
            background-color: #fff;
            border-color: #ced4da;
        }

        .pet-edit-mode .form-actions {
            display: block;
        }

        /* Photo Upload Section */
        .photo-upload-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .current-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            background-size: cover;
            background-position: center;
            border: 4px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .current-photo:hover {
            border-color: #ff6b6b;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-top: 1rem;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #6c757d;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .file-input-label:hover {
            background: #5a6268;
        }

        .photo-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
            margin: 1rem auto;
            display: none;
            border: 4px solid #ff6b6b;
        }

        /* Form Actions */
        .form-actions { 
            margin-top: 2rem; 
            text-align: right;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
            display: none; /* Hidden by default */
            gap: 1rem;
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

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.2);
        }

        /* Notification */
        #notification { 
            position: fixed; 
            top: 100px; 
            right: 20px; 
            padding: 1rem 1.5rem; 
            border-radius: 8px; 
            color: white; 
            z-index: 9999; 
            opacity: 0; 
            transform: translateX(100%); 
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 300px;
        }

        #notification.show { 
            opacity: 1; 
            transform: translateX(0); 
        }

        #notification.success { 
            background: linear-gradient(135deg, #28a745, #20c997); 
        }

        #notification.error { 
            background: linear-gradient(135deg, #dc3545, #e74c3c); 
        }

        /* Loading States */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin-top: -10px;
            margin-left: -10px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .pet-edit-container {
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
            
            .pet-edit-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                max-height: none;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .edit-toggle-btn {
                width: 100%;
                justify-content: center;
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

            #notification {
                right: 10px;
                left: 10px;
                max-width: none;
            }

            .current-photo {
                width: 120px;
                height: 120px;
                font-size: 3rem;
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
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .edit-card h2 {
                font-size: 1.125rem;
            }

            .current-photo {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
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
            <h1>Edit Pet Profile</h1>
            <div style="display: flex; gap: 1rem;">
                <a href="../index.php" class="edit-toggle-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Pets
                </a>
                <button type="button" class="edit-toggle-btn" id="editToggleBtn">
                    <i class="fas fa-edit"></i>
                    Edit Information
                </button>
            </div>
        </div>

        <div class="pet-edit-container pet-view-mode" id="petContainer">
            <!-- Pet Photo & Basic Info Card -->
            <div class="edit-card">
                <h2>
                    <i class="fas fa-camera"></i>
                    Photo & Basic Information
                </h2>
                
                <!-- Photo Upload Section -->
                <div class="photo-upload-section">
                    <?php
                    $photo_url = '';
                    $photo_content = '';
                    
                    if (!empty($pet['photo_url'])) {
                        $cleaned_path = str_replace('../../', '', $pet['photo_url']);
                        $photo_path = '../../' . $cleaned_path;
                        
                        if (file_exists($photo_path)) {
                            $photo_url = $photo_path;
                        }
                    }
                    
                    if (empty($photo_url)) {
                        $photo_content = getPetEmoji($pet['species']);
                    }
                    ?>
                    
                    <?php if ($photo_url): ?>
                        <div class="current-photo" style="background-image: url('<?php echo $photo_url; ?>');" id="currentPhoto"></div>
                    <?php else: ?>
                        <div class="current-photo" id="currentPhoto"><?php echo $photo_content; ?></div>
                    <?php endif; ?>
                    
                    <div class="file-input-wrapper">
                        <input type="file" id="petPhoto" name="photo" accept="image/*" class="file-input">
                        <label for="petPhoto" class="file-input-label">
                            <i class="fas fa-camera"></i>
                            Change Photo
                        </label>
                    </div>
                    <img id="photoPreview" class="photo-preview" style="display: none;">
                </div>

                <form id="basicInfoForm">
                    <input type="hidden" name="pet_id" value="<?php echo $pet['pet_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">Pet Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($pet['name']); ?>" required readonly>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="species">Species *</label>
                            <select id="species" name="species" required disabled>
                                <option value="">Select Species</option>
                                <option value="Dog" <?php echo $pet['species'] === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                                <option value="Cat" <?php echo $pet['species'] === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                                <option value="Bird" <?php echo $pet['species'] === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                                <option value="Rabbit" <?php echo $pet['species'] === 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                                <option value="Fish" <?php echo $pet['species'] === 'Fish' ? 'selected' : ''; ?>>Fish</option>
                                <option value="Hamster" <?php echo $pet['species'] === 'Hamster' ? 'selected' : ''; ?>>Hamster</option>
                                <option value="Guinea Pig" <?php echo $pet['species'] === 'Guinea Pig' ? 'selected' : ''; ?>>Guinea Pig</option>
                                <option value="Turtle" <?php echo $pet['species'] === 'Turtle' ? 'selected' : ''; ?>>Turtle</option>
                                <option value="Other" <?php echo $pet['species'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="breed">Breed</label>
                            <input type="text" id="breed" name="breed" value="<?php echo htmlspecialchars($pet['breed']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" disabled>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $pet['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $pet['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $pet['date_of_birth']; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveBasicBtn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Additional Details Card -->
            <div class="edit-card">
                <h2>
                    <i class="fas fa-info-circle"></i>
                    Additional Details
                </h2>
                <form id="detailsForm">
                    <input type="hidden" name="pet_id" value="<?php echo $pet['pet_id']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="color">Color/Markings</label>
                            <input type="text" id="color" name="color" value="<?php echo htmlspecialchars($pet['color']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" value="<?php echo $pet['weight']; ?>" step="0.1" min="0" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="microchip_id">Microchip ID</label>
                        <input type="text" id="microchip_id" name="microchip_id" value="<?php echo htmlspecialchars($pet['microchip_id']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes & Medical Conditions</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Any special notes, allergies, or medical conditions..." readonly><?php echo htmlspecialchars($pet['notes']); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveDetailsBtn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- Notification Element -->
<div id="notification"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const API_URL = 'ajax/pet_edit_handler.php';

    const basicInfoForm = document.getElementById('basicInfoForm');
    const detailsForm = document.getElementById('detailsForm');
    const saveBasicBtn = document.getElementById('saveBasicBtn');
    const saveDetailsBtn = document.getElementById('saveDetailsBtn');
    const notification = document.getElementById('notification');
    const editToggleBtn = document.getElementById('editToggleBtn');
    const petContainer = document.getElementById('petContainer');
    const petPhotoInput = document.getElementById('petPhoto');
    const photoPreview = document.getElementById('photoPreview');
    const currentPhoto = document.getElementById('currentPhoto');
    
    let isEditMode = false;
    let notificationTimeout;

    // Edit Mode Toggle
    function toggleEditMode() {
        isEditMode = !isEditMode;
        
        if (isEditMode) {
            petContainer.classList.remove('pet-view-mode');
            petContainer.classList.add('pet-edit-mode');
            editToggleBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Edit';
            editToggleBtn.classList.add('cancel');
            
            // Enable form inputs
            document.querySelectorAll('#basicInfoForm input:not([type="hidden"])').forEach(input => {
                input.removeAttribute('readonly');
            });
            document.querySelectorAll('#basicInfoForm select').forEach(select => {
                select.removeAttribute('disabled');
            });
            document.querySelectorAll('#detailsForm input, #detailsForm textarea').forEach(input => {
                input.removeAttribute('readonly');
            });
            
        } else {
            petContainer.classList.remove('pet-edit-mode');
            petContainer.classList.add('pet-view-mode');
            editToggleBtn.innerHTML = '<i class="fas fa-edit"></i> Edit Information';
            editToggleBtn.classList.remove('cancel');
            
            // Disable form inputs
            document.querySelectorAll('#basicInfoForm input:not([type="hidden"])').forEach(input => {
                input.setAttribute('readonly', 'readonly');
            });
            document.querySelectorAll('#basicInfoForm select').forEach(select => {
                select.setAttribute('disabled', 'disabled');
            });
            document.querySelectorAll('#detailsForm input, #detailsForm textarea').forEach(input => {
                input.setAttribute('readonly', 'readonly');
            });
            
            // Reset photo preview
            photoPreview.style.display = 'none';
            petPhotoInput.value = '';
        }
    }

    editToggleBtn.addEventListener('click', toggleEditMode);

    // Photo preview functionality
    petPhotoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                photoPreview.style.display = 'block';
                currentPhoto.style.opacity = '0.5';
            };
            reader.readAsDataURL(file);
        }
    });

    function showNotification(message, type = 'success') {
        clearTimeout(notificationTimeout);
        notification.textContent = message;
        notification.className = type;
        notification.classList.add('show');
        notificationTimeout = setTimeout(() => notification.classList.remove('show'), 4000);
    }

    // Handle basic info form submission
    basicInfoForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!isEditMode) {
            showNotification('Please enable edit mode first.', 'error');
            return;
        }
        
        saveBasicBtn.disabled = true;
        saveBasicBtn.classList.add('loading');
        saveBasicBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        const formData = new FormData(basicInfoForm);
        formData.append('action', 'update_basic');
        
        // Add photo if selected
        if (petPhotoInput.files[0]) {
            formData.append('photo', petPhotoInput.files[0]);
        }

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            
            if (result.success) {
                // Update photo display if new photo was uploaded
                if (result.photo_url) {
                    currentPhoto.style.backgroundImage = `url('${result.photo_url}')`;
                    currentPhoto.innerHTML = '';
                    photoPreview.style.display = 'none';
                    currentPhoto.style.opacity = '1';
                }
                // Exit edit mode on successful save
                toggleEditMode();
            }
        } catch (error) {
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            saveBasicBtn.disabled = false;
            saveBasicBtn.classList.remove('loading');
            saveBasicBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    });

    // Handle details form submission
    detailsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!isEditMode) {
            showNotification('Please enable edit mode first.', 'error');
            return;
        }
        
        saveDetailsBtn.disabled = true;
        saveDetailsBtn.classList.add('loading');
        saveDetailsBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        const formData = new FormData(detailsForm);
        formData.append('action', 'update_details');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            
            if (result.success) {
                // Exit edit mode on successful save
                toggleEditMode();
            }
        } catch (error) {
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            saveDetailsBtn.disabled = false;
            saveDetailsBtn.classList.remove('loading');
            saveDetailsBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    });

    // Mobile sidebar functionality
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

<?php
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