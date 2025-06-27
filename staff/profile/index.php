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
    <style>
        /* Reusing the exact same styles from the client profile for consistency */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8f9fa; color: #333; }
        .dashboard-layout { display: flex; min-height: 100vh; }

        /* Main Content Centering */
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-left: 250px; 
            flex: 1;
            padding: 2rem;
        }

        .page-header {
            width: 100%;
            max-width: 800px;
            margin-bottom: 2rem;
        }
        .page-header h1 { font-size: 2rem; font-weight: 600; }
        .profile-container {
            width: 100%;
            max-width: 800px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        .profile-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .profile-card h2 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1rem;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-row { display: flex; gap: 1.5rem; }
        .form-row .form-group { flex: 1; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: #495057; }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.25);
        }
        .form-group input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        .form-actions { margin-top: 2rem; text-align: right; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background-color 0.2s; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { opacity: 0.9; }
        #notification { position: fixed; top: 80px; right: 20px; padding: 1rem 1.5rem; border-radius: 6px; color: white; z-index: 9999; opacity: 0; transform: translateY(-20px); transition: opacity 0.3s, transform 0.3s; }
        #notification.show { opacity: 1; transform: translateY(0); }
        #notification.success { background-color: #28a745; }
        #notification.error { background-color: #dc3545; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <!-- Make sure you include the correct sidebar for staff/admin -->
    <?php include '../../includes/sidebar-staff.php'; ?>
    <?php include '../../includes/navbar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>My Profile</h1>
        </div>

        <div class="profile-container">
            <!-- Card for Personal Information (Simplified) -->
            <div class="profile-card">
                <h2>Personal Information</h2>
                <form id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="Enter your first name">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Enter your last name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" readonly>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Card for Password Change (Identical to client's) -->
            <div class="profile-card">
                <h2>Change Password</h2>
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="savePasswordBtn">Update Password</button>
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
    // Point this to your new staff-specific API handler
    const API_URL = 'ajax/staff_profile_handler.php';

    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const savePasswordBtn = document.getElementById('savePasswordBtn');
    const notification = document.getElementById('notification');
    let notificationTimeout;

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

    // Simplified form population for staff
    function populateForm(data) {
        document.getElementById('first_name').value = data.first_name || '';
        document.getElementById('last_name').value = data.last_name || '';
        document.getElementById('email').value = data.email || '';
    }

    // This handler works without changes because FormData only grabs existing fields
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        saveProfileBtn.disabled = true;
        saveProfileBtn.textContent = 'Saving...';
        const formData = new FormData(profileForm);
        formData.append('action', 'update');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            showNotification(result.message, result.success ? 'success' : 'error');
        } catch (error) {
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            saveProfileBtn.disabled = false;
            saveProfileBtn.textContent = 'Save Changes';
        }
    });

    // This handler is identical and works without changes
    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (document.getElementById('new_password').value !== document.getElementById('confirm_password').value) {
            showNotification('New passwords do not match.', 'error');
            return;
        }

        savePasswordBtn.disabled = true;
        savePasswordBtn.textContent = 'Updating...';
        const formData = new FormData(passwordForm);
        formData.append('action', 'change_password');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) passwordForm.reset();
            showNotification(result.message, result.success ? 'success' : 'error');
        } catch (error) {
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            savePasswordBtn.disabled = false;
            savePasswordBtn.textContent = 'Update Password';
        }
    });

    fetchProfileData();
});
</script>

</body>
</html>