<?php
require_once '../../config/init.php';
requireClient();

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
        /* Reset and Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* Dashboard Layout */
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

        /* Page Header */
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
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-icon {
            font-size: 2.5rem;
        }

        .edit-toggle-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }

        .edit-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 107, 0.4);
            color: white;
            text-decoration: none;
        }

        .edit-toggle-btn.cancel {
            background: #6c757d;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .edit-toggle-btn.cancel:hover {
            background: #5a6268;
            box-shadow: 0 6px 16px rgba(108, 117, 125, 0.4);
        }

        /* Profile Container */
        .profile-container {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            height: fit-content;
        }

        /* Profile Cards */
        .profile-card {
            background: white;
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
            border-bottom: 2px solid #ff6b6b;
            padding-bottom: 1rem;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-card h2 i {
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
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
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

        /* Account Info Styles */
        .account-info {
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .account-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .account-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.875rem;
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
            }
            
            .page-title {
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
            
            .page-title h1 {
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
    <?php include '../../includes/sidebar-client.php'; ?>
    <?php include '../../includes/navbar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <nav class="breadcrumb">
                <a href="../index.php">Dashboard</a>
                <span>›</span>
                <span>My Profile</span>
            </nav>
            <div class="page-title">
                <h1>
                    <span class="page-icon">👤</span>
                    My Profile
                </h1>
                <button type="button" class="edit-toggle-btn" id="editToggleBtn">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
            </div>
        </div>

        <div class="profile-container profile-view-mode" id="profileContainer">
            <!-- Personal Information Card -->
            <div class="profile-card">
                <h2>
                    <i class="fas fa-user"></i>
                    Personal Information
                </h2>
                
                <div class="account-info">
                    <h3 id="fullNameDisplay">Loading...</h3>
                    <p id="emailDisplay">Loading...</p>
                </div>

                <form id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" readonly required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" readonly required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" readonly>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="e.g., 09123456789" readonly>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" placeholder="e.g., Angeles City" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Complete Address</label>
                        <input type="text" id="address" name="address" placeholder="e.g., 123 Main St, Brgy. Sample" readonly>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Settings Card -->
            <div class="profile-card">
                <h2>
                    <i class="fas fa-lock"></i>
                    Security Settings
                </h2>
                
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password <span class="required">*</span></label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter your current password" readonly required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" readonly required minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" readonly required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="savePasswordBtn">
                            <i class="fas fa-key"></i>
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
    const API_URL = 'ajax/profile_handler.php';

    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const savePasswordBtn = document.getElementById('savePasswordBtn');
    const notification = document.getElementById('notification');
    const editToggleBtn = document.getElementById('editToggleBtn');
    const profileContainer = document.getElementById('profileContainer');
    const fullNameDisplay = document.getElementById('fullNameDisplay');
    const emailDisplay = document.getElementById('emailDisplay');
    
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
            
            // Enable form inputs
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
            
            // Disable form inputs
            document.querySelectorAll('#profileForm input').forEach(input => {
                input.setAttribute('readonly', 'readonly');
            });
            document.querySelectorAll('#passwordForm input').forEach(input => {
                input.setAttribute('readonly', 'readonly');
            });
            
            // Reset password form
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

    // Fetch and populate profile data
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
            console.error('Fetch error:', error);
            showNotification('Could not load your profile. Please try again.', 'error');
        }
    }

    function populateForm(data) {
        document.getElementById('first_name').value = data.first_name || '';
        document.getElementById('last_name').value = data.last_name || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('phone').value = data.phone || '';
        document.getElementById('address').value = data.address || '';
        document.getElementById('city').value = data.city || '';
        
        // Update header display
        const fullName = `${data.first_name || ''} ${data.last_name || ''}`.trim() || 'User';
        fullNameDisplay.textContent = fullName;
        emailDisplay.textContent = data.email || 'No email provided';
    }

    // Handle profile form submission
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
                // Update display and exit edit mode
                populateForm(result.data);
                toggleEditMode();
            }
        } catch (error) {
            console.error('Update error:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            saveProfileBtn.disabled = false;
            saveProfileBtn.classList.remove('loading');
            saveProfileBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    });

    // Handle password form submission
    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!isEditMode) {
            showNotification('Please enable edit mode first.', 'error');
            return;
        }
        
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
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
                toggleEditMode();
            }
            showNotification(result.message, result.success ? 'success' : 'error');
        } catch (error) {
            console.error('Password change error:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            savePasswordBtn.disabled = false;
            savePasswordBtn.classList.remove('loading');
            savePasswordBtn.innerHTML = '<i class="fas fa-key"></i> Update Password';
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

    // Initial data load
    fetchProfileData();
});
</script>

</body>
</html>