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
        'phone' => '',
        'address' => '',
        'city' => ''
    ];
    
    $result = register($data);
    
    if ($result['success']) {
        setFlash('Registration successful! Please login with your new account.', 'success');
        redirect('/login.php');
    } else {
        $errors = $result['errors'] ?? [];
        if (isset($result['error'])) {
            $errors['general'] = $result['error'];
        }
    }
}

$pageTitle = 'Sign Up - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        /* ===== CSS Variables ===== */
        :root {
            --primary-teal: #1DBAA8;
            --primary-teal-dark: #189A8A;
            --primary-teal-light: #2DD4C4;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --error: #EF4444;
            --success: #10B981;
            --gradient-primary: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-light) 100%);
            --gradient-overlay: linear-gradient(135deg, rgba(29, 186, 168, 0.9) 0%, rgba(45, 212, 196, 0.8) 100%);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--gray-100);
            color: var(--gray-700);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ===== Register Container ===== */
        .register-container {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ===== Left Side - Image & Branding ===== */
        .register-left {
            position: relative;
            background: var(--gradient-overlay);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-2xl);
            overflow: hidden;
        }

        .register-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://images.unsplash.com/photo-1601758228041-f3b2795255f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -1;
        }

        .register-left-content {
            text-align: center;
            color: var(--white);
            position: relative;
            z-index: 1;
            max-width: 400px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: var(--white);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-teal);
            box-shadow: var(--shadow-lg);
        }

        .brand-text {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
        }

        .welcome-text h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: var(--spacing-md);
            color: var(--white);
            line-height: 1.2;
        }

        .welcome-text p {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: var(--spacing-xl);
            line-height: 1.6;
        }

        /* ===== Right Side - Registration Form ===== */
        .register-right {
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-2xl);
            position: relative;
        }

        .register-form-container {
            width: 100%;
            max-width: 400px;
        }

        .register-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }

        .register-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: var(--spacing-sm);
        }

        .register-subtitle {
            color: var(--gray-600);
            font-size: 1rem;
            margin: 0;
        }

        /* ===== Form Styles ===== */
        .form-section {
            margin-bottom: var(--spacing-2xl);
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-lg);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            color: var(--gray-700);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .required {
            color: var(--error);
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            transition: all 0.2s ease;
            font-family: inherit;
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 3px rgba(29, 186, 168, 0.1);
        }

        .form-control.is-invalid {
            border-color: var(--error);
        }

        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .invalid-feedback {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: var(--spacing-sm);
            display: block;
        }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            width: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ===== Alerts ===== */
        .alert {
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            border: 1px solid transparent;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.2);
        }

        /* ===== Footer Links ===== */
        .register-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
        }

        .register-footer p {
            color: var(--gray-600);
            margin-bottom: var(--spacing-sm);
            font-size: 0.875rem;
        }

        .register-footer a {
            color: var(--primary-teal);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .register-footer a:hover {
            color: var(--primary-teal-dark);
            text-decoration: underline;
        }

        /* ===== Responsive Design ===== */
        @media (max-width: 1024px) {
            .register-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }

            .register-left {
                min-height: 40vh;
                padding: var(--spacing-xl);
            }

            .welcome-text h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .register-left {
                min-height: 30vh;
                padding: var(--spacing-lg);
            }

            .register-right {
                padding: var(--spacing-lg);
            }

            .welcome-text h1 {
                font-size: 1.75rem;
            }

            .brand-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .brand-text {
                font-size: 1.5rem;
            }

            .register-title {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .register-left,
            .register-right {
                padding: var(--spacing-md);
            }

            .register-form-container {
                max-width: 100%;
            }
        }

        /* ===== Loading State ===== */
        .loading {
            position: relative;
            color: transparent;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== Animations ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out;
        }

        .animate-fadeInLeft {
            animation: fadeInLeft 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Left Side - Branding & Image -->
        <div class="register-left">
            <div class="register-left-content animate-fadeInLeft">
                <div class="brand-logo">
                    <div class="brand-icon">🐾</div>
                    <div class="brand-text">Vet Precision</div>
                </div>
                
                <div class="welcome-text">
                    <h1>Join Our Community</h1>
                    <p>Start your journey with professional veterinary care for your beloved pets.</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Registration Form -->
        <div class="register-right">
            <div class="register-form-container animate-fadeInUp">
                <div class="register-header">
                    <h1 class="register-title">Join us!</h1>
                    <p class="register-subtitle">Create your account to start caring for your pets</p>
                </div>

                <!-- Flash Messages -->
                <?php if ($flash = getFlash()): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo sanitize($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- General Error -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?php echo sanitize($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" action="" id="registerForm">
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <div class="section-title">Personal Information</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="first_name" 
                                    name="first_name" 
                                    class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>"
                                    value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>"
                                    placeholder=""
                                    required
                                    autofocus
                                >
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?php echo sanitize($errors['first_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="last_name" 
                                    name="last_name" 
                                    class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>"
                                    value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>"
                                    placeholder=""
                                    required
                                >
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?php echo sanitize($errors['last_name']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                                placeholder=""
                                required
                            >
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo sanitize($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password <span class="required">*</span></label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                placeholder=""
                                required
                                minlength="6"
                            >
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo sanitize($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                placeholder=""
                                required
                            >
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?php echo sanitize($errors['confirm_password']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        Sign Up
                    </button>
                </form>

                <!-- Footer -->
                <div class="register-footer">
                    <p>Already have an account? <a href="login.php">Log In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            // Password match validation
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length > 0 && password !== confirmPassword) {
                    confirmPasswordInput.classList.add('is-invalid');
                } else {
                    confirmPasswordInput.classList.remove('is-invalid');
                }
            }

            // Event listeners
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            passwordInput.addEventListener('input', checkPasswordMatch);

            // Form submission with loading state
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Check if passwords match
                if (password !== confirmPassword) {
                    e.preventDefault();
                    confirmPasswordInput.focus();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                submitBtn.textContent = '';
                
                // Re-enable button after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    submitBtn.textContent = 'Sign Up';
                }, 10000);
            });

            // Real-time form validation
            const requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];
            
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('blur', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('is-invalid');
                        } else {
                            this.classList.remove('is-invalid');
                        }
                    });

                    field.addEventListener('input', function() {
                        if (this.classList.contains('is-invalid') && this.value.trim() !== '') {
                            this.classList.remove('is-invalid');
                        }
                    });
                }
            });

            // Email validation
            const emailField = document.getElementById('email');
            emailField.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.classList.add('is-invalid');
                } else if (this.value) {
                    this.classList.remove('is-invalid');
                }
            });

            // Auto-capitalize names
            const nameFields = ['first_name', 'last_name'];
            nameFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('input', function() {
                        this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                    });
                }
            });
        });

        // Smooth animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0) translateX(0)';
                }
            });
        }, observerOptions);

        // Observe animated elements
        document.querySelectorAll('.animate-fadeInUp, .animate-fadeInLeft').forEach(element => {
            element.style.opacity = '0';
            element.style.transition = 'all 0.6s ease-out';
            
            if (element.classList.contains('animate-fadeInLeft')) {
                element.style.transform = 'translateX(-30px)';
            } else {
                element.style.transform = 'translateY(30px)';
            }
            
            observer.observe(element);
        });
    </script>
</body>
</html>