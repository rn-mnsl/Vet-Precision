<?php
require_once 'config/init.php';

// Logout the user
logout();

// Set success message
setFlash('You have been logged out successfully.', 'success');

// Redirect to login page
redirect('/login.php');
?>
