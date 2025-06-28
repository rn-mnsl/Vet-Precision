<?php
require_once 'config/init.php';

// --- VALIDATION LOGIC AT THE TOP ---

$token = sanitize($_GET['token'] ?? '');
$errors = [];
$reset_request = null;
$is_token_valid = false;
$error_message = '';

// 1. Check if the token is valid before doing anything else
if (empty($token)) {
    $error_message = 'Invalid password reset link. No token was provided.';
} else {
    $reset_request = findValidResetToken($token);
    if ($reset_request) {
        $is_token_valid = true;
    } else {
        $error_message = 'This password reset link is invalid or has expired. Please try again.';
    }
}


// 2. If the token is valid AND the form was submitted, process the new password
if ($is_token_valid && isPost()) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Basic validation
    if (empty($password) || empty($password_confirm)) {
        $errors['password'] = 'Both password fields are required.';
    } elseif ($password !== $password_confirm) {
        $errors['password'] = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if (empty($errors)) {
        // Update the user's password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if (updateUserPassword($reset_request['user_id'], $hashed_password)) {
            // Mark the token as used
            markTokenAsUsed($token);

            // Set success message and redirect to login
            $flash_message = [
                'title'   => 'Password Reset Successful!',
                'message' => 'You can now log in using your new password.'
            ];
            setFlash($flash_message, 'success');
            redirect('/login.php'); // Ensure leading slash
        } else {
            $errors['general'] = 'An unexpected error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <?php include 'includes/favicon.php'; ?>
    <style>
        /* Copy the <style> block from your login.php here */
        <?php
            $login_css = file_get_contents('login.php');
            preg_match('/<style>(.*?)<\/style>/s', $login_css, $matches);
            if (isset($matches[1])) { echo $matches[1]; }
        ?>
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-left-content">
                <div class="welcome-text">
                    <h1>Create a New Password</h1>
                    <p>Your new password must be at least 8 characters long.</p>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-form-container animate-fadeInUp">
                
                <!-- 3. Show either the form OR the error message -->
                <?php if ($is_token_valid): ?>
                    <!-- If token is valid, show the form -->
                    <div class="login-header">
                        <h1 class="login-title">Set Your New Password</h1>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?php echo sanitize($errors['general']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="reset-password.php?token=<?php echo sanitize($token); ?>">
                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" id="password" name="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" placeholder="Enter new password" required>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm" class="form-label">Confirm New Password</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" placeholder="Confirm new password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo sanitize($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </form>

                <?php else: ?>
                    <!-- If token is NOT valid, show an error message -->
                    <div class="login-header">
                        <h1 class="login-title">Invalid Link</h1>
                    </div>
                    <div class="alert alert-danger">
                        <?php echo sanitize($error_message); ?>
                    </div>
                    <div class="login-footer" style="border-top: none; padding-top: 0;">
                        <a href="forgot-password.php" class="btn btn-outline">Request a New Link</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>