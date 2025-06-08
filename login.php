<?php
require_once 'config/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'staff' ? '/staff/index.php' : '/client/index.php');
}

$errors = [];

if (isPost()) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    $errors = validateLogin($_POST);
    
    if (empty($errors)) {
        $result = login($email, $password);
        
        if ($result['success']) {
            // Redirect based on role
            redirect($_SESSION['role'] === 'staff' ? '/staff/index.php' : '/client/index.php');
        } else {
            $errors['general'] = $result['error'];
        }
    }
}

$pageTitle = 'Login - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
    <style>
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .logo h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .logo p {
            color: #7f8c8d;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        .form-control.is-invalid {
            border-color: #e74c3c;
        }
        .invalid-feedback {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-warning {
            background-color: #fffbf0;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .text-center {
            text-align: center;
        }
        .mt-3 {
            margin-top: 20px;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🐾</div>
            <h1>Vet Precision</h1>
            <p>Pet Health Management System</p>
        </div>

        <?php if ($flash = getFlash()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo sanitize($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?php echo sanitize($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                    value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                    required
                    autofocus
                >
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo sanitize($errors['email']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?php echo sanitize($errors['password']); ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="text-center mt-3">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>

        <!-- Test credentials for development -->
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 6px; font-size: 14px;">
            <strong>Test Accounts:</strong><br>
            Staff: admin@vetprecision.com / password123<br>
            Client: client1@example.com / password123
        </div>
    </div>
</body>
</html>