<?php
require_once 'config/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'staff' ? '/staff/index.php' : '/client/index.php');
}

$errors = [];
$success = false;

if (isPost()) {
    $data = [
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'city' => sanitize($_POST['city'] ?? '')
    ];
    
    $result = register($data);
    
    if ($result['success']) {
        setFlash('Registration successful! Please login.', 'success');
        redirect('/login.php');
    } else {
        $errors = $result['errors'] ?? [];
        if (isset($result['error'])) {
            $errors['general'] = $result['error'];
        }
    }
}

$pageTitle = 'Register - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        .required {
            color: #e74c3c;
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
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
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <div class="logo-icon">🐾</div>
            <h1>Vet Precision</h1>
            <p>Create Your Account</p>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?php echo sanitize($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">
                        First Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>"
                        value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>"
                        required
                    >
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="invalid-feedback"><?php echo sanitize($errors['first_name']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">
                        Last Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>"
                        value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>"
                        required
                    >
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="invalid-feedback"><?php echo sanitize($errors['last_name']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">
                    Email Address <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                    value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo sanitize($errors['email']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="required">*</span>
                    </label>
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

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                        required
                    >
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?php echo sanitize($errors['confirm_password']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Phone Number</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                    value="<?php echo sanitize($_POST['phone'] ?? ''); ?>"
                    placeholder="09171234567"
                >
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?php echo sanitize($errors['phone']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address" class="form-label">Address</label>
                <input 
                    type="text" 
                    id="address" 
                    name="address" 
                    class="form-control"
                    value="<?php echo sanitize($_POST['address'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="city" class="form-label">City</label>
                <input 
                    type="text" 
                    id="city" 
                    name="city" 
                    class="form-control"
                    value="<?php echo sanitize($_POST['city'] ?? ''); ?>"
                    placeholder="Angeles City"
                >
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <div class="text-center mt-3">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html> 
