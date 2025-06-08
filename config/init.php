 <?php
// Initialize application
session_start();
date_default_timezone_set('Asia/Manila');

// Include required files
require_once 'database.php';
require_once 'constants.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in for protected pages
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/login.php');
    }
}

// Check if user is staff
function requireStaff() {
    requireLogin();
    if ($_SESSION['role'] !== 'staff') {
        redirect('/client/index.php');
    }
}

// Check if user is client
function requireClient() {
    requireLogin();
    if ($_SESSION['role'] !== 'client') {
        redirect('/staff/index.php');
    }
}
?>
