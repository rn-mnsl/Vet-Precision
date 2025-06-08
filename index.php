<?php
require_once 'config/init.php';

// Redirect based on login status
if (isLoggedIn()) {
    // Redirect to appropriate dashboard
    if ($_SESSION['role'] === 'staff') {
        redirect('/staff/index.php');
    } else {
        redirect('/client/index.php');
    }
} else {
    // Redirect to login
    redirect('/login.php');
}
?>
