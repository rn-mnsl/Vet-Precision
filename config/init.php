<?php
// Initialize application
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/validation.php';

// Check if user is logged in for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('Please login to continue', 'warning');
        redirect('/login.php');
    }
}

// Check if user is staff
function requireStaff() {
    requireLogin();
    if ($_SESSION['role'] !== 'staff') {
        setFlash('Access denied. Staff only area.', 'danger');
        redirect('/client/index.php');
    }
}

// Check if user is client
function requireClient() {
    requireLogin();
    if ($_SESSION['role'] !== 'client') {
        setFlash('Access denied. Client only area.', 'danger');
        redirect('/staff/index.php');
    }
}

// Auto logout after inactivity
if (isLoggedIn()) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        logout();
        setFlash('Session expired. Please login again.', 'info');
        redirect('/login.php');
    }
    $_SESSION['last_activity'] = time();
}
?>