<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
require_once __DIR__ . '/../vendor/autoload.php'; // generated from `composer` to download phpmailer

/**
 * =================================================================
 * PASSWORD RESET HELPER FUNCTIONS (Corrected to use $pdo)
 * =================================================================
 * NOTE: For better code organization, you could move this entire
 * block of functions into your 'includes/auth.php' file.
 */

/**
 * Finds a user by their email address.
 *
 * @param string $email
 * @return array|false The user data or false if not found.
 */
function findUserByEmail(string $email) {
    global $pdo; // Changed from $db
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?"); // Changed from $db
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Creates a secure password reset token and stores it in the database.
 *
 * @param int $user_id
 * @param string $email
 * @return string|false The generated token or false on failure.
 */
function createPasswordResetToken(int $user_id, string $email) {
    global $pdo; // Changed from $db
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    try {
        $stmt_invalidate = $pdo->prepare("UPDATE password_resets SET is_used = 1, used_at = NOW() WHERE user_id = ? AND is_used = 0");
        $stmt_invalidate->execute([$user_id]);

        $stmt = $pdo->prepare(
            "INSERT INTO password_resets (user_id, email, token, expires_at, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$user_id, $email, $token, $expires_at]);
        return $token;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Finds a valid, non-expired, non-used password reset token.
 *
 * @param string $token
 * @return array|false The reset request data or false if not valid.
 */
function findValidResetToken(string $token) {
    global $pdo; // Changed from $db
    $stmt = $pdo->prepare(
        "SELECT * FROM password_resets WHERE token = ? AND is_used = 0 AND expires_at > NOW()"
    );
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Updates a user's password in the database.
 *
 * @param int $user_id
 * @param string $hashed_password
 * @return bool True on success, false on failure.
 */
function updateUserPassword(int $user_id, string $hashed_password) {
    global $pdo; // Changed from $db
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
        return $stmt->execute([$hashed_password, $user_id]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Marks a password reset token as used.
 *
 * @param string $token
 * @return bool True on success, false on failure.
 */
function markTokenAsUsed(string $token) {
    global $pdo; // Changed from $db
    try {
        $stmt = $pdo->prepare("UPDATE password_resets SET is_used = 1, used_at = NOW() WHERE token = ?");
        return $stmt->execute([$token]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

// sendPasswordResetEmail function does not use the database, so it remains unchanged.

/**
 * Sends a REAL password reset email using PHPMailer.
 *
 * @param string $email The recipient's email address.
 * @param string $token The password reset token.
 */
function sendPasswordResetEmail(string $email, string $token) {
    $mail = new PHPMailer(true);

    try {
        // --- Server settings ---
        // $mail->SMTPDebug = 2;                      // Enable verbose debug output for troubleshooting
        $mail->isSMTP();                               // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';          // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                      // Enable SMTP authentication
        $mail->Username   = 'roljohn.frilles87@gmail.com';  //  Gmail address ()
        $mail->Password   = 'yecs lggr egaf kiej'; //  Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port       = 465;                       // TCP port to connect to

        // --- Recipients ---
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Vet Precision';
        $mail->setFrom('no-reply@vetprecision.com', $site_name);
        $mail->addAddress($email);                     // Add a recipient

        // --- Content ---
        $reset_link = SITE_URL . '/reset-password.php?token=' . $token;
        
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Password Reset Request for ' . $site_name;
        $mail->Body    = "
            <html>
            <body>
              <h2>Password Reset Request</h2>
              <p>Hello,</p>
              <p>We received a request to reset your password. Please click the button below to proceed.</p>
              <p style='text-align:center; margin: 25px 0;'>
                <a href='{$reset_link}' style='background-color:#1DBAA8; color:white; padding:12px 25px; text-decoration:none; border-radius:5px;'>Reset Your Password</a>
              </p>
              <p>If that doesn't work, copy and paste this link into your browser: <br><a href='{$reset_link}'>{$reset_link}</a></p>
              <p>This link is valid for 1 hour.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "To reset your password, please visit this link: {$reset_link}";

        $mail->send();
        // The email was sent successfully.
        
    } catch (Exception $e) {
        // Email failed to send. Log the error, but don't show it to the user.
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
    }
}
// ===== END OF PASSWORD RESET FUNCTIONS =====

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





// ADD THIS NEW FUNCTION TO config/init.php

/**
 * Sends a general notification email to the site administrator.
 *
 * @param string $subject The subject of the email.
 * @param string $body    The HTML body of the email.
 * @param string $altBody The plain-text alternative body.
 * @return bool           True on success, false on failure.
 */
function sendAdminNotification(string $subject, string $body, string $altBody) {
    $mail = new PHPMailer(true);

    try {
        // --- Server settings (Copied from your existing function) ---
        // $mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'roljohn.frilles87@gmail.com'; // Your sending email
        $mail->Password   = 'yecs lggr egaf kiej';         // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- Recipients ---
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Vet Precision';
        $mail->setFrom('no-reply@vetprecision.com', $site_name);
        
        // ** THE IMPORTANT PART: Hardcode the admin's email address **
        $mail->addAddress('manansalarin@gmail.com'); // Add the admin recipient

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = $subject; // Use the provided subject
        $mail->Body    = $body;    // Use the provided HTML body
        $mail->AltBody = $altBody; // Use the provided plain-text body

        $mail->send();
        return true; // Email sent successfully
        
    } catch (Exception $e) {
        // Log the error for debugging, but don't expose it to the client.
        error_log("sendAdminNotification PHPMailer Error: {$mail->ErrorInfo}");
        return false; // Email failed to send
    }
}
?>