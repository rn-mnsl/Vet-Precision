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
        /* Base Styles from your template */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8f9fa; color: #333; }
        .dashboard-layout { display: flex; min-height: 100vh; }
        .main-content { /* These three lines are the magic */
            display: flex;
            flex-direction: column;
            align-items: center;

            /* These are your existing styles, keep them */
            margin-left: 250px; 
            flex: 1;
            padding: 2rem;
        }

        /* --- Profile Page Specific CSS --- */
        .page-header {
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 2rem;
            font-weight: 600;
        }
        .profile-container {
            /* We need to set a width on the container */
            /* so flexbox knows how to center it.     */
            width: 100%;
            max-width: 800px; /* Adjust this value as needed */

            /* These are your existing styles */
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
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-row {
            display: flex;
            gap: 1.5rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.25); /* Assuming --primary-rgb is defined */
        }
        .form-group input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .form-actions {
            margin-top: 2rem;
            text-align: right;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        /* Notification Styling */
        #notification {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            color: white;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s, transform 0.3s;
        }
        #notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        #notification.success { background-color: #28a745; }
        #notification.error { background-color: #dc3545; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; z-index: 1100; position: fixed; top: 0; height: 100vh; margin-top: 0; }
            .main-content { margin-left: 0; }
            body.sidebar-is-open .sidebar { transform: translateX(0); box-shadow: 0 0 20px rgba(0,0,0,0.25); }
            body.sidebar-is-open .sidebar-overlay { opacity: 1; visibility: visible; }
            .main-content { padding-top: 85px; } /* Space for fixed navbar */
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include '../../includes/sidebar-client.php'; ?>
    <?php include '../../includes/navbar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>My Profile</h1>
        </div>

        <div class="profile-container">
            <!-- Card for Personal Information -->
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
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g., 09123456789">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" placeholder="e.g., 123 Main St, Brgy. Sample">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" placeholder="e.g., Angeles City">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Card for Password Change -->
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
    const API_URL = 'ajax/profile_handler.php';

    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const savePasswordBtn = document.getElementById('savePasswordBtn');

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
    }

    // --- FORM SUBMISSION HANDLERS ---
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
            console.error('Update error:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            saveProfileBtn.disabled = false;
            saveProfileBtn.textContent = 'Save Changes';
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
        savePasswordBtn.textContent = 'Updating...';

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
            savePasswordBtn.textContent = 'Update Password';
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