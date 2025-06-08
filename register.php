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
</head>
<body class="auth-page">
    <!-- Decorative paw prints -->
    <div class="paw-pattern" style="top: 5%; left: 5%; animation-delay: 0s;">🐾</div>
    <div class="paw-pattern" style="top: 15%; right: 10%; animation-delay: 1s;">🐾</div>
    <div class="paw-pattern" style="bottom: 15%; left: 10%; animation-delay: 2s;">🐾</div>
    <div class="paw-pattern" style="bottom: 5%; right: 5%; animation-delay: 3s;">🐾</div>
    
    <div class="auth-container register animate-fadeIn">
        <div class="auth-logo">
            <span class="auth-logo-icon">🐾</span>
            <h1>Join Vet Precision</h1>
            <p>Create your account to start caring for your pets</p>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger animate-fadeIn">
                <?php echo sanitize($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <h3 class="mb-3">Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">
                        First Name <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>"
                        value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>"
                        required
                        placeholder="John"
                    >
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="invalid-feedback"><?php echo sanitize($errors['first_name']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">
                        Last Name <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>"
                        value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>"
                        required
                        placeholder="Doe"
                    >
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="invalid-feedback"><?php echo sanitize($errors['last_name']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <h3 class="mb-3 mt-4">Account Details</h3>
            <div class="form-group">
                <label for="email" class="form-label">
                    Email Address <span class="text-danger">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                    value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                    required
                    placeholder="john.doe@example.com"
                >
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo sanitize($errors['email']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                        required
                        placeholder="Min. 6 characters"
                    >
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?php echo sanitize($errors['password']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        Confirm Password <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                        required
                        placeholder="Re-enter password"
                    >
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?php echo sanitize($errors['confirm_password']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <h3 class="mb-3 mt-4">Contact Information</h3>
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
                    placeholder="123 Main Street, Barangay"
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

            <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">Create Account</button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted">Already have an account?</p>
            <a href="login.php" class="btn btn-secondary">Login Instead</a>
        </div>

        <div class="text-center mt-3">
            <a href="index.php" class="text-muted">← Back to Home</a>
        </div>
    </div>
</body>
</html>