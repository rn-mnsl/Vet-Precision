<?php
require_once 'config/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'staff' ? '/staff/index.php' : '/client/index.php');
}

$errors = [];
$success_message = '';

if (isPost()) {
    $email = sanitize($_POST['email'] ?? '');

    // 1. Validate the email
    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        // 2. Find the user by email
        $user = findUserByEmail($email);

        if ($user) {
            // 3. Generate a reset token and save it
            $token = createPasswordResetToken($user['user_id'], $email);

            if ($token) {
                // 4. Send the password reset email
                // IMPORTANT: You need a real email sending library like PHPMailer for production
                sendPasswordResetEmail($email, $token);
            }
        }
        
        // IMPORTANT: Always show a generic success message to prevent email enumeration attacks.
        // This way, an attacker can't figure out which emails are registered.
        $success_message = 'If an account with that email exists, a password reset link has been sent. Please check your inbox (and spam folder).';
    }
}

$pageTitle = 'Forgot Password - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <?php include 'includes/favicon.php'; ?>
    <!-- Re-use the same styles from login.php -->
    <link rel="stylesheet" href="path/to/your/login-styles.css"> <!-- Or copy the <style> block from login.php -->
    <style>
        /* For demonstration, copy the <style> block from your login.php here */
        <?php
            // A simple way to include the CSS without creating a new file
            $login_css = file_get_contents('login.php');
            preg_match('/<style>(.*?)<\/style>/s', $login_css, $matches);
            if (isset($matches[1])) {
                echo $matches[1];
            }
        ?>
    </style>
</head>
<body>
    <div class="login-container">
        <!-- You can keep the left side for branding consistency -->
        <div class="login-left">
            <div class="login-left-content">
                <div class="welcome-text">
                    <h1>Forgot Your Password?</h1>
                    <p>No problem. Enter your email address below and we'll send you a link to reset it.</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="login-right">
            <div class="login-form-container animate-fadeInUp">
                <div class="login-header">
                    <h1 class="login-title">Reset Your Password</h1>
                    <p class="login-subtitle">Enter your registered email address.</p>
                </div>

                <!-- Flash/Success Message -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo sanitize($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- General Error -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?php echo sanitize($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($success_message)): // Hide form after submission ?>
                <form method="POST" action="forgot-password.php">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                            value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                            placeholder="you@example.com"
                            required
                            autofocus
                        >
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo sanitize($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Send Reset Link
                    </button>
                </form>
                <?php endif; ?>

                <!-- Footer -->
                <div class="login-footer">
                    <div class="footer-links">
                        <a href="login.php">← Back to Login</a>
                        <a href="index.php">← Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>