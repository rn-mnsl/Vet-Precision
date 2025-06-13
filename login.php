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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #FFF5F5 0%, #F0FFFF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-logo-icon {
            font-size: 60px;
            display: block;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .auth-logo h1 {
            color: #FF6B6B;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .auth-logo p {
            color: #666;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #FF6B6B;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .form-control.is-invalid {
            border-color: #e74c3c;
        }

        .invalid-feedback {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            display: block;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            color: #666;
            font-size: 14px;
            cursor: pointer;
            user-select: none;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            width: 100%;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #FF6B6B;
            border: 2px solid #FF6B6B;
            padding: 10px 20px;
            font-size: 14px;
        }

        .btn-secondary:hover {
            background: #FF6B6B;
            color: white;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
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

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #6c757d;
        }

        .mt-3 {
            margin-top: 20px;
        }

        .mt-4 {
            margin-top: 30px;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }

        a {
            color: #FF6B6B;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .back-link {
            color: #6c757d;
            font-size: 14px;
        }

        /* Paw pattern decorations */
        .paw-pattern {
            position: fixed;
            opacity: 0.05;
            font-size: 40px;
            animation: float 10s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-container {
                padding: 30px 20px;
            }
            
            .auth-logo h1 {
                font-size: 24px;
            }
            
            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }
    </style>
</head>
<body>
    <!-- Decorative paw prints -->
    <div class="paw-pattern" style="top: 10%; left: 10%; animation-delay: 0s;">🐾</div>
    <div class="paw-pattern" style="top: 20%; right: 15%; animation-delay: 2s;">🐾</div>
    <div class="paw-pattern" style="bottom: 20%; left: 20%; animation-delay: 4s;">🐾</div>
    <div class="paw-pattern" style="bottom: 10%; right: 10%; animation-delay: 6s;">🐾</div>
    
    <div class="auth-container">
        <div class="auth-logo">
            <span class="auth-logo-icon">🐾</span>
            <h1>Vet Precision</h1>
            <p>Welcome back! Please login to continue.</p>
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
                    placeholder="Enter your email"
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
                    placeholder="Enter your password"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?php echo sanitize($errors['password']); ?></div>
                <?php endif; ?>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary">Login to Your Account</button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>

        <div class="text-center">
            <p class="text-muted">Don't have an account?</p>
            <a href="register.php" class="btn btn-secondary">Create Account</a>
        </div>

        <div class="text-center mt-3">
            <a href="index.php" class="back-link">← Back to Home</a>
        </div>

        <!-- Test credentials for development -->
        <div class="alert alert-info mt-4">
            <strong>Test Accounts:</strong><br>
staff - manansalarin@gmail.com - password123 <br>
client - ryan.percival@gmail.com - password1234 
        </div>
    </div>
</body>
</html>