<?php
require_once '../../config/init.php';

// This is a client-facing page, ensure they are logged in.
// If not, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    // You should replace 'login.php' with your actual login page URL
    header('Location: ../../auth/login.php'); 
    exit();
}

$pageTitle = 'Profile - ' . SITE_NAME;
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
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .breadcrumb a:hover {
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
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        .form-group input[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }

        .form-hint {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.813rem;
        }

        /* Form Actions */
        .form-actions {
            margin-top: 2rem;
            text-align: right;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
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
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.4);
            opacity: 0.9;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.2);
        }

        /* User Info Section */
        .user-info-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color, #28a745) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .user-info-section h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .user-info-section p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }

        /* Notification Styling */
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

        /* Security Info Box */
        .security-info {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0 8px 8px 0;
        }

        .security-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--dark-color);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .security-info p {
            margin: 0;
            color: #666;
            font-size: 0.813rem;
            line-height: 1.5;
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
                <span class="page-icon">👤</span>
                <h1>My Profile</h1>
            </div>
        </div>

        <div class="profile-container">
            <!-- Personal Information Card -->
            <div class="profile-card">
                <h2>
                    <i class="fas fa-user"></i>
                    Personal Information
                </h2>
                
                <div class="user-info-section" id="userInfoDisplay">
                    <h3 id="fullNameDisplay">Loading...</h3>
                    <p id="emailDisplay">Loading...</p>
                </div>

                <form id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
                            <span class="form-hint">Your legal first name</span>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required>
                            <span class="form-hint">Your legal last name</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" readonly>
                        <span class="form-hint">Contact support to change your email address</span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="e.g., 09123456789">
                            <span class="form-hint">For appointment reminders and updates</span>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" placeholder="e.g., Angeles City">
                            <span class="form-hint">Your current city of residence</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Complete Address</label>
                        <input type="text" id="address" name="address" placeholder="e.g., 123 Main St, Brgy. Sample">
                        <span class="form-hint">Full address for home visits if needed</span>
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
                
                <div class="security-info">
                    <h4><i class="fas fa-shield-alt"></i> Password Security</h4>
                    <p>Keep your account secure by using a strong password that you don't use elsewhere. We recommend at least 8 characters with a mix of letters, numbers, and symbols.</p>
                </div>

                <form id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password <span class="required">*</span></label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter your current password" required>
                        <span class="form-hint">Verify your identity with your current password</span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="8">
                            <span class="form-hint">Minimum 8 characters</span>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                            <span class="form-hint">Must match your new password</span>
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
    const fullNameDisplay = document.getElementById('fullNameDisplay');
    const emailDisplay = document.getElementById('emailDisplay');

    // --- NOTIFICATION FUNCTION ---
    const notification = document.getElementById('notification');
    let notificationTimeout;

    function showNotification(message, type = 'success') {
        clearTimeout(notificationTimeout);
        notification.textContent = message;
        notification.className = type; // 'success' or 'error'
        notification.classList.add('show');
        notificationTimeout = setTimeout(() => {
            notification.classList.remove('show');
        }, 4000);
    }

    // --- FETCH AND POPULATE PROFILE DATA ---
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

    // --- FORM SUBMISSION HANDLERS ---
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        saveProfileBtn.disabled = true;
        saveProfileBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const formData = new FormData(profileForm);
        formData.append('action', 'update');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
            
            if (result.success && result.data) {
                populateForm(result.data);
            }
        } catch (error) {
            console.error('Update error:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            saveProfileBtn.disabled = false;
            saveProfileBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    });

    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
            showNotification('New passwords do not match.', 'error');
            return;
        }

        savePasswordBtn.disabled = true;
        savePasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

        const formData = new FormData(passwordForm);
        formData.append('action', 'change_password');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                passwordForm.reset();
            }
            showNotification(result.message, result.success ? 'success' : 'error');
        } catch (error) {
            console.error('Password change error:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            savePasswordBtn.disabled = false;
            savePasswordBtn.innerHTML = '<i class="fas fa-key"></i> Update Password';
        }
    });

    // Initial data load
    fetchProfileData();
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