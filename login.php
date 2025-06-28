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
    <?php include 'includes/favicon.php'; ?>
    <style>
        /* ===== CSS Variables - Updated Color Palette ===== */
        :root {
            /* Primary Colors */
            --primary-teal: #1DBAA8;
            --primary-teal-dark: #189A8A;
            --primary-teal-light: #2DD4C4;
            
            /* Secondary Colors */
            --orange-accent: #F6A144;
            --orange-light: #F8B366;
            --orange-dark: #E8923A;
            
            /* Neutral Colors */
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            
            /* Semantic Colors */
            --success: #10B981;
            --warning: var(--orange-accent);
            --error: #EF4444;
            --info: #3B82F6;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-light) 100%);
            --gradient-overlay: linear-gradient(135deg, rgba(29, 186, 168, 0.2) 0%, rgba(45, 212, 196, 0.2) 50%);
            
            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --spacing-3xl: 4rem;
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --radius-full: 9999px;
            
            /* Typography */
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-display: 'Inter', system-ui, sans-serif;
        }

        /* ===== Reset & Base Styles ===== */
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

        /* ===== Login Container ===== */
        .login-container {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ===== Left Side - Image & Branding ===== */
        .login-left {
            position: relative;
            background: var(--gradient-overlay);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
            overflow: hidden;
            text-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -1;
        }

        .login-left-content {
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
            font-family: var(--font-display);
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

        .features-list {
            list-style: none;
            text-align: left;
            margin: var(--spacing-xl) 0;
        }

        .features-list li {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            color: rgba(255, 255, 255, 0.95);
            font-size: 1rem;
        }

        .features-list li::before {
            content: '✓';
            width: 24px;
            height: 24px;
            background: var(--white);
            color: var(--primary-teal);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        /* ===== Right Side - Login Form ===== */
        .login-right {
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-2xl);
            position: relative;
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }

        .user-type-toggle {
            display: flex;
            background: var(--gray-100);
            border-radius: var(--radius-full);
            padding: 4px;
            margin-bottom: var(--spacing-xl);
            position: relative;
        }

        .toggle-option {
            flex: 1;
            padding: var(--spacing-sm) var(--spacing-lg);
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            border-radius: var(--radius-full);
            transition: all 0.2s ease;
            position: relative;
            z-index: 2;
        }

        .toggle-option.active {
            background: var(--white);
            color: var(--primary-teal);
            box-shadow: var(--shadow-sm);
        }

        .toggle-option:not(.active) {
            color: var(--gray-500);
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
        }

        .login-subtitle {
            color: var(--gray-600);
            font-size: 1rem;
            margin: 0;
        }

        /* ===== Form Styles ===== */
        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            color: var(--gray-700);
            font-weight: 600;
            font-size: 0.875rem;
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
            margin-top: var(--spacing-xs);
            display: block;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xl);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-teal);
        }

        .checkbox-group label {
            color: var(--gray-600);
            font-size: 0.875rem;
            cursor: pointer;
            user-select: none;
        }

        .forgot-password {
            color: var(--primary-teal);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .forgot-password:hover {
            color: var(--primary-teal-dark);
            text-decoration: underline;
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

        .btn-outline {
            background: transparent;
            color: var(--primary-teal);
            border: 2px solid var(--primary-teal);
        }

        .btn-outline:hover {
            background: var(--primary-teal);
            color: var(--white);
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

        .alert-warning {
            background-color: rgba(246, 161, 68, 0.1);
            color: var(--warning);
            border-color: rgba(246, 161, 68, 0.2);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-info {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .alert-enhanced {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-md);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            position: relative;
            border-left-width: 5px;
            border-left-style: solid;
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            animation: fadeInUp 0.5s ease-out;
        }

        .alert-enhanced .alert-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
        }

        .alert-enhanced .alert-icon svg {
            width: 24px;
            height: 24px;
        }

        .alert-enhanced .alert-content {
            flex-grow: 1;
            padding-right: var(--spacing-lg); /* Space for the close button */
        }
        
        .alert-enhanced .alert-content strong {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
        }

        .alert-enhanced .alert-content p {
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .alert-enhanced .alert-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s ease;
            padding: 0;
        }
        .alert-enhanced .alert-close:hover {
            opacity: 1;
        }

        /* Success Variant */
        .alert-enhanced.alert-success {
            background-color: #F0FDF4; /* Lighter green */
            border-left-color: var(--success);
        }
        .alert-enhanced.alert-success .alert-icon {
            background-color: var(--success);
        }
        .alert-enhanced.alert-success .alert-content strong {
            color: #059669; /* Darker green */
        }
        .alert-enhanced.alert-success .alert-content p,
        .alert-enhanced.alert-success .alert-close {
            color: #047857; /* Mid green */
        }
        
        /* Danger Variant (for future use) */
        .alert-enhanced.alert-danger {
            background-color: #FEF2F2; /* Lighter red */
            border-left-color: var(--error);
        }
        .alert-enhanced.alert-danger .alert-icon {
            background-color: var(--error);
        }
        .alert-enhanced.alert-danger .alert-content strong {
            color: #B91C1C; /* Darker red */
        }
        .alert-enhanced.alert-danger .alert-content p,
        .alert-enhanced.alert-danger .alert-close {
            color: #991B1B; /* Mid red */
        }


        /* ===== Footer Links ===== */
        .login-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--gray-200);
        }

        .login-footer p {
            color: var(--gray-600);
            margin-bottom: var(--spacing-md);
        }

        .footer-links {
            display: flex;
            gap: var(--spacing-lg);
            justify-content: center;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--gray-500);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--primary-teal);
        }

        /* ===== Test Credentials ===== */
        .test-credentials {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-top: var(--spacing-xl);
        }

        .test-credentials h4 {
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .test-credentials p {
            color: var(--gray-600);
            font-size: 0.8rem;
            margin: var(--spacing-xs) 0;
            font-family: monospace;
        }

        /* ===== Responsive Design ===== */
/* ===== Enhanced Mobile Responsive Design ===== */
@media (max-width: 1024px) {
    .login-container {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr;
    }

    .login-left {
        min-height: 50vh; /* Increased from 40vh */
        padding: var(--spacing-xl) var(--spacing-lg);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-left-content {
        max-width: 600px; /* Wider on tablet */
        width: 100%;
    }

    .welcome-text h1 {
        font-size: 2.25rem; /* Slightly larger */
        margin-bottom: var(--spacing-lg);
    }

    .welcome-text p {
        font-size: 1.125rem;
        margin-bottom: var(--spacing-lg);
    }

    .features-list {
        display: flex; /* Show as horizontal list on tablet */
        flex-wrap: wrap;
        gap: var(--spacing-lg);
        justify-content: center;
        margin: var(--spacing-lg) 0;
    }

    .features-list li {
        flex: 1 1 250px; /* Flexible items */
        min-width: 200px;
        justify-content: center;
        text-align: center;
        margin-bottom: var(--spacing-sm);
    }

    .login-right {
        padding: var(--spacing-xl) var(--spacing-lg);
    }

    .login-form-container {
        max-width: 500px; /* Wider form on tablet */
    }
}

@media (max-width: 768px) {
    .login-left {
        min-height: 45vh; /* Adequate height for content */
        padding: var(--spacing-lg);
        text-align: center;
    }

    .login-left-content {
        max-width: 100%;
        padding: var(--spacing-md);
    }

    .welcome-text h1 {
        font-size: 1.875rem; /* Responsive font size */
        line-height: 1.3;
        margin-bottom: var(--spacing-md);
    }

    .welcome-text p {
        font-size: 1rem;
        line-height: 1.5;
        margin-bottom: var(--spacing-lg);
        padding: 0 var(--spacing-sm);
    }

    .features-list {
        display: grid; /* Grid layout for mobile */
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
        margin: var(--spacing-lg) 0;
        padding: 0 var(--spacing-md);
    }

    .features-list li {
        font-size: 0.9rem;
        justify-content: flex-start;
        text-align: left;
        margin-bottom: 0;
        padding: var(--spacing-sm);
        background: rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-lg);
        backdrop-filter: blur(10px);
    }

    .features-list li::before {
        width: 20px;
        height: 20px;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .brand-logo {
        margin-bottom: var(--spacing-lg);
        gap: var(--spacing-sm);
    }

    .brand-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }

    .brand-text {
        font-size: 1.5rem;
    }

    .login-right {
        padding: var(--spacing-lg) var(--spacing-md);
    }

    .login-form-container {
        max-width: 100%;
    }

    .login-title {
        font-size: 1.75rem;
    }

    .login-subtitle {
        font-size: 0.95rem;
    }

    .user-type-toggle {
        margin-bottom: var(--spacing-lg);
    }

    .form-options {
        flex-direction: column;
        gap: var(--spacing-md);
        align-items: stretch;
    }

    .checkbox-group {
        justify-content: center;
    }

    .forgot-password {
        text-align: center;
    }

    .footer-links {
        flex-direction: column;
        gap: var(--spacing-sm);
    }

    .test-credentials {
        margin-top: var(--spacing-lg);
        padding: var(--spacing-md);
    }

    .test-credentials h4 {
        font-size: 0.8rem;
    }

    .test-credentials p {
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .login-left {
        min-height: 40vh;
        padding: var(--spacing-md) var(--spacing-sm);
    }

    .login-left-content {
        padding: var(--spacing-sm);
    }

    .welcome-text h1 {
        font-size: 1.5rem;
        margin-bottom: var(--spacing-sm);
    }

    .welcome-text p {
        font-size: 0.9rem;
        margin-bottom: var(--spacing-md);
        padding: 0;
    }

    .features-list {
        gap: var(--spacing-sm);
        margin: var(--spacing-md) 0;
        padding: 0;
    }

    .features-list li {
        font-size: 0.85rem;
        padding: var(--spacing-xs) var(--spacing-sm);
    }

    .features-list li::before {
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
    }

    .brand-logo {
        margin-bottom: var(--spacing-md);
        gap: var(--spacing-xs);
    }

    .brand-icon {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }

    .brand-text {
        font-size: 1.25rem;
    }

    .login-left,
    .login-right {
        padding: var(--spacing-md) var(--spacing-sm);
    }

    .login-form-container {
        max-width: 100%;
        padding: 0;
    }

    .login-title {
        font-size: 1.5rem;
    }

    .login-subtitle {
        font-size: 0.9rem;
    }

    .user-type-toggle {
        flex-direction: column;
        gap: 4px;
        margin-bottom: var(--spacing-md);
    }

    .toggle-option {
        padding: var(--spacing-md);
        font-size: 0.8rem;
    }

    .form-group {
        margin-bottom: var(--spacing-md);
    }

    .form-control {
        padding: 0.75rem;
        font-size: 0.9rem;
    }

    .btn {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }

    .login-header {
        margin-bottom: var(--spacing-lg);
    }

    .login-footer {
        margin-top: var(--spacing-lg);
        padding-top: var(--spacing-lg);
    }

    .test-credentials {
        padding: var(--spacing-sm);
        margin-top: var(--spacing-md);
    }

    .test-credentials p {
        font-size: 0.7rem;
        word-break: break-all;
    }
}

/* ===== Very Small Screens ===== */
@media (max-width: 360px) {
    .login-left {
        min-height: 35vh;
        padding: var(--spacing-sm);
    }

    .welcome-text h1 {
        font-size: 1.25rem;
    }

    .welcome-text p {
        font-size: 0.85rem;
    }

    .features-list li {
        font-size: 0.8rem;
    }

    .brand-text {
        font-size: 1rem;
    }

    .login-title {
        font-size: 1.25rem;
    }

    .form-control {
        padding: 0.625rem;
    }
}

/* ===== Enhanced Text Shadow for Better Readability ===== */
.login-left-content {
    text-shadow: 
        0 2px 4px rgba(0, 0, 0, 0.6),
        0 0 20px rgba(0, 0, 0, 0.4);
}

.welcome-text h1 {
    text-shadow: 
        2px 2px 4px rgba(0, 0, 0, 0.8),
        0 0 20px rgba(0, 0, 0, 0.6);
}

.welcome-text p {
    text-shadow: 
        1px 1px 3px rgba(0, 0, 0, 0.8),
        0 0 15px rgba(0, 0, 0, 0.5);
}

.features-list li {
    text-shadow: 
        1px 1px 2px rgba(0, 0, 0, 0.7),
        0 0 10px rgba(0, 0, 0, 0.4);
}

/* ===== Better Overlay for Mobile ===== */
@media (max-width: 768px) {
    .login-left::before {
    }
}

/* ===== Landscape Mobile Fix ===== */
@media (max-width: 768px) and (orientation: landscape) {
    .login-left {
        min-height: 60vh;
        padding: var(--spacing-sm);
    }
    
    .welcome-text h1 {
        font-size: 1.5rem;
        margin-bottom: var(--spacing-xs);
    }
    
    .welcome-text p {
        font-size: 0.9rem;
        margin-bottom: var(--spacing-sm);
    }
    
    .features-list {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: var(--spacing-sm);
        margin: var(--spacing-sm) 0;
    }
    
    .features-list li {
        flex: 1 1 auto;
        font-size: 0.75rem;
        padding: var(--spacing-xs);
    }
}   
        /* ===== Loading State ===== */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

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

        /* ===== Password Input Container ===== */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container .form-control {
            padding-right: 3rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-400);
            transition: color 0.2s ease;
            padding: 0.25rem;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--gray-600);
        }

        .password-toggle:focus {
            outline: none;
            color: var(--primary-teal);
        }

        .eye-icon {
            width: 20px;
            height: 20px;
            transition: all 0.2s ease;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding & Image -->
        <div class="login-left">
            <div class="login-left-content animate-fadeInLeft">
                <div class="welcome-text">
                    <h1>Welcome Veterinary Professionals</h1>
                    <p>Manage your practice, access patient records, and provide excellent care.</p>
                </div>
                
                <ul class="features-list">
                    <li>Manage appointments and schedules</li>
                    <li>Access complete patient records</li>
                    <li>Streamline clinic operations</li>
                </ul>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-form-container animate-fadeInUp">
                <div class="login-header">
                    <!-- User Type Toggle -->
                    <div class="user-type-toggle">
                        <div class="toggle-option active" data-type="staff">Hospital Staff</div>
                        <div class="toggle-option" data-type="owner">Pet Owner</div>
                    </div>
                    
                    <h1 class="login-title">Welcome to Vet Precision</h1>
                    <p class="login-subtitle">Prioritize the Health of your pets</p>
                </div>

                <!-- Flash Messages from other pages (like password reset) -->
                <?php if ($flash = getFlash()):
    
                    // --- Determine the message format ---
                    $flash_content = $flash['message'] ?? '';
                    $is_enhanced = is_array($flash_content);
                    
                    // --- Set variables safely based on the format ---
                    if ($is_enhanced) {
                        // It's our new, structured message
                        $title = $flash_content['title'] ?? 'Success!';
                        $message = $flash_content['message'] ?? '';
                    } else {
                        // It's an old, simple string message
                        $title = ucfirst($flash['type']); // e.g., "Success" or "Error"
                        $message = (string) $flash_content;
                    }

                ?>
                    <!-- --- Render the correct alert --- -->
                    <?php if ($is_enhanced): ?>
                        <!-- RENDER THE NEW, PRETTY ALERT -->
                        <div class="alert-enhanced alert-<?php echo sanitize($flash['type']); ?>" role="alert">
                            <div class="alert-icon">
                                <!-- Checkmark Icon for Success -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="alert-content">
                                <strong><?php echo sanitize($title); ?></strong>
                                <p><?php echo sanitize($message); ?></p>
                            </div>
                            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'" aria-label="Close">×</button>
                        </div>

                    <?php else: ?>
                        <!-- FALLBACK: Render the old, simple alert for other messages -->
                        <div class="alert alert-<?php echo sanitize($flash['type']); ?>">
                            <strong><?php echo sanitize($title); ?>:</strong> <?php echo sanitize($message); ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

                <!-- General Error -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?php echo sanitize($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
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
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                                <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle class="eye-open" cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path class="eye-closed" d="m1 1 22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"/>
                                    <path class="eye-closed" d="M6.71 6.71a9.5 9.5 0 0 0-4.71 5.29s4 8 11 8a9.5 9.5 0 0 0 5.29-1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"/>
                                    <path class="eye-closed" d="M12 7a5 5 0 0 1 5 5 4.64 4.64 0 0 1-.39 1.61" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"/>
                                    <path class="eye-closed" d="M12 17a5 5 0 0 1-5-5 4.64 4.64 0 0 1 .39-1.61" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?php echo sanitize($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-options">
                        <div class="checkbox-group">
                            <input type="checkbox" id="remember" name="remember" value="1">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary" id="loginBtn">
                        Login
                    </button>
                </form>

                <!-- Footer -->
                <div class="login-footer">
                    <p>Don't have an account?</p>
                    <a href="register.php" class="btn btn-outline">Create Account</a>
                    
                    <div class="footer-links">
                        <a href="index.php">← Back to Home</a>
                        <a href="contact-support.php">Contact Support</a>
                        <a href="privacy-policy.php">Privacy Policy</a>
                    </div>
                </div>

                <!-- Test Credentials for Development -->
                <div class="test-credentials">
                    <h4>Test Accounts:</h4>
                    <p><strong>Staff:</strong> manansalarin@gmail.com - password123</p>
                    <p><strong>Client:</strong> ryan.percival@gmail.com - password1234</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User type toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleOptions = document.querySelectorAll('.toggle-option');
            const welcomeText = document.querySelector('.welcome-text h1');
            const welcomeDesc = document.querySelector('.welcome-text p');
            const featuresList = document.querySelector('.features-list');
            const loginTitle = document.querySelector('.login-title');
            const loginSubtitle = document.querySelector('.login-subtitle');

            toggleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove active class from all options
                    toggleOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to clicked option
                    this.classList.add('active');
                    
                    const userType = this.getAttribute('data-type');
                    
                    if (userType === 'staff') {
                        // Update left side content for staff
                        welcomeText.textContent = 'Welcome Veterinary Professionals';
                        welcomeDesc.textContent = 'Manage your practice, access patient records, and provide excellent care.';
                        featuresList.innerHTML = `
                            <li>Manage appointments and schedules</li>
                            <li>Access complete patient records</li>
                            <li>Streamline clinic operations</li>
                        `;
                        
                        // Update right side content for staff
                        loginTitle.textContent = 'Welcome to Vet Precision';
                        loginSubtitle.textContent = 'Prioritize the Health of your pets';
                    } else {
                        // Update left side content for pet owners
                        welcomeText.textContent = 'Welcome Pet Owners';
                        welcomeDesc.textContent = 'Book appointments, track your pets health, and connect with our veterinary team.';
                        featuresList.innerHTML = `
                            <li>Book and manage appointments</li>
                            <li>Track vaccination schedules</li>
                            <li>Access medical history</li>
                        `;
                        
                        // Update right side content for pet owners
                        loginTitle.textContent = 'Welcome Back';
                        loginSubtitle.textContent = 'Access your pet\'s health dashboard';
                    }
                });
            });

            // Form submission with loading state
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');

            loginForm.addEventListener('submit', function(e) {
                loginBtn.disabled = true;
                loginBtn.classList.add('loading');
                loginBtn.textContent = '';
                
                // Re-enable button after 5 seconds as fallback
                setTimeout(() => {
                    loginBtn.disabled = false;
                    loginBtn.classList.remove('loading');
                    loginBtn.textContent = 'Login';
                }, 5000);
            });

            // Auto-fill test credentials (for development)
            const testCredentials = document.querySelectorAll('.test-credentials p');
            testCredentials.forEach(credential => {
                credential.addEventListener('click', function() {
                    const text = this.textContent;
                    const emailMatch = text.match(/(\S+@\S+\.\S+)/);
                    const passwordMatch = text.match(/- (\S+)$/);
                    
                    if (emailMatch && passwordMatch) {
                        document.getElementById('email').value = emailMatch[1];
                        document.getElementById('password').value = passwordMatch[1];
                    }
                });
            });

            // Password visibility toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeOpenElements = document.querySelectorAll('.eye-open');
            const eyeClosedElements = document.querySelectorAll('.eye-closed');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle eye icon
                if (type === 'text') {
                    // Show "eye closed" icon
                    eyeOpenElements.forEach(el => el.style.display = 'none');
                    eyeClosedElements.forEach(el => el.style.display = 'block');
                } else {
                    // Show "eye open" icon
                    eyeOpenElements.forEach(el => el.style.display = 'block');
                    eyeClosedElements.forEach(el => el.style.display = 'none');
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