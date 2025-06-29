<?php
require_once '../../config/init.php';

// Enhanced Security: Ensure user is logged in AND is a staff member.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
    // Redirect non-staff/admin users away.
    header('Location: ../../auth/login.php'); 
    exit();
}

$pageTitle = 'My Profile - ' . SITE_NAME;
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
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .edit-toggle-btn:hover {
            background: #17a294;
            transform: translateY(-1px);
        }

        .edit-toggle-btn.cancel {
            background: #6c757d;
        }

        .edit-toggle-btn.cancel:hover {
            background: #5a6268;
        }

        /* Profile Container - Side by Side Layout */
        .profile-container {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            height: fit-content;
            max-height: calc(100vh - 200px);
        }

        .profile-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            height: fit-content;
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .profile-card h2 {
            font-size: 1.375rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 1rem;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-card h2 i {
            color: var(--primary-color);
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

        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #fff;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(29, 186, 168, 0.1);
        }

        .form-group input[readonly] { 
            background-color: #f8f9fa; 
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }

        /* View Mode Styles */
        .profile-view-mode .form-group input {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            cursor: default;
            pointer-events: none;
        }

        .profile-view-mode .form-actions {
            display: none;
        }

        /* Edit Mode Styles */
        .profile-edit-mode .form-group input:not([readonly]) {
            background-color: #fff;
            border-color: #ced4da;
        }

        .profile-edit-mode .form-actions {
            display: block;
        }

        /* Form Actions */
        .form-actions { 
            margin-top: 2rem; 
            text-align: right;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
            display: none; /* Hidden by default */
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
        }

        .btn-primary { 
            background: linear-gradient(135deg, var(--primary-color), #17a294);
            color: white; 
            box-shadow: 0 4px 12px rgba(29, 186, 168, 0.3);
        }

        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(29, 186, 168, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(29, 186, 168, 0.2);
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
            .profile-container {
                gap: 1.5rem;
            }
            
            .profile-card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .profile-container {
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

            .profile-card {
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
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0.75rem;
                padding-top: 85px;
            }
            
            .profile-card {
                padding: 1rem;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .profile-card h2 {
                font-size: 1.125rem;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <!-- Include the correct sidebar for staff/admin -->
    <?php include '../../includes/sidebar-staff.php'; ?>
    <?php include '../../includes/navbar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>My Profile</h1>
            <button type="button" class="edit-toggle-btn" id="editToggleBtn">
                <i class="fas fa-edit"></i>
                Edit Profile
            </button>
        </div>

        <div class="profile-container profile-view-mode" id="profileContainer">
            <!-- Personal Information Card -->
            <div class="profile-card">
                <h2>
                    <i class="fas fa-user"></i>
                    Personal Information
                </h2>
                <form id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" readonly>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" readonly>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Card -->
            <div class="profile-card">
                <h2>
                    <i class="fas fa-lock"></i>
                    Security Settings
                </h2>
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="8" readonly>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required readonly>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="savePasswordBtn">
                            <i class="fas fa-shield-alt"></i>
                            Update Password
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
    const API_URL = 'ajax/staff_profile_handler.php';

    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const savePasswordBtn = document.getElementById('savePasswordBtn');
    const notification = document.getElementById('notification');
    const editToggleBtn = document.getElementById('editToggleBtn');
    const profileContainer = document.getElementById('profileContainer');
    
    let isEditMode = false;
    let notificationTimeout;

    // Edit Mode Toggle
    function toggleEditMode() {
        isEditMode = !isEditMode;
        
        if (isEditMode) {
            profileContainer.classList.remove('profile-view-mode');
            profileContainer.classList.add('profile-edit-mode');
            editToggleBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Edit';
            editToggleBtn.classList.add('cancel');
            
            // Enable form inputs (except email)
            document.querySelectorAll('#profileForm input:not([type="email"])').forEach(input => {
                input.removeAttribute('readonly');
            });
            
            document.querySelectorAll('#passwordForm input').forEach(input => {
                input.removeAttribute('readonly');
            });
            
        } else {
            profileContainer.classList.remove('profile-edit-mode');
            profileContainer.classList.add('profile-view-mode');
            editToggleBtn.innerHTML = '<i class="fas fa-edit"></i> Edit Profile';
            editToggleBtn.classList.remove('cancel');
            
            // Disable form inputs and reset password form
            document.querySelectorAll('#profileForm input:not([type="email"])').forEach(input => {
                input.setAttribute('readonly', 'readonly');
            });
            
            document.querySelectorAll('#passwordForm input').forEach(input => {
                input.setAttribute('readonly', 'readonly');
            });
            
            passwordForm.reset();
        }
    }

    editToggleBtn.addEventListener('click', toggleEditMode);

    function showNotification(message, type = 'success') {
        clearTimeout(notificationTimeout);
        notification.textContent = message;
        notification.className = type;
        notification.classList.add('show');
        notificationTimeout = setTimeout(() => notification.classList.remove('show'), 4000);
    }

    async function fetchProfileData() {
        try {
            const response = await fetch(`${API_URL}?action=fetch`);
            const result = await response.json();
            if (result.success) {
                populateForm(result.data);
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Could not load your profile.', 'error');
        }
    }

    function populateForm(data) {
        document.getElementById('first_name').value = data.first_name || '';
        document.getElementById('last_name').value = data.last_name || '';
        document.getElementById('email').value = data.email || '';
    }

    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!isEditMode) {
            showNotification('Please enable edit mode first.', 'error');
            return;
        }
        
        saveProfileBtn.disabled = true;
        saveProfileBtn.classList.add('loading');
        saveProfileBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        const formData = new FormData(profileForm);
        formData.append('action', 'update');

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
            saveProfileBtn.disabled = false;
            saveProfileBtn.classList.remove('loading');
            saveProfileBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    });

    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!isEditMode) {
            showNotification('Please enable edit mode first.', 'error');
            return;
        }
        
        if (document.getElementById('new_password').value !== document.getElementById('confirm_password').value) {
            showNotification('New passwords do not match.', 'error');
            return;
        }

        savePasswordBtn.disabled = true;
        savePasswordBtn.classList.add('loading');
        savePasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        const formData = new FormData(passwordForm);
        formData.append('action', 'change_password');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                passwordForm.reset();
                // Exit edit mode on successful password change
                toggleEditMode();
            }
            showNotification(result.message, result.success ? 'success' : 'error');
        } catch (error) {
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            savePasswordBtn.disabled = false;
            savePasswordBtn.classList.remove('loading');
            savePasswordBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Update Password';
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

    // Initialize profile data
    fetchProfileData();
});
</script>

</body>
</html>