<?php
$pageTitle = 'Privacy Policy - ';
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
            --gradient-primary: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-light) 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --spacing-3xl: 4rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--gray-50);
            color: var(--gray-700);
            line-height: 1.7;
        }

        /* Navigation */
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-900);
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
        }

        .back-link {
            color: var(--primary-teal);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--primary-teal-dark);
        }

        /* Main Content */
        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: var(--spacing-3xl) var(--spacing-lg);
        }

        .page-header {
            text-align: center;
            margin-bottom: var(--spacing-3xl);
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: var(--spacing-md);
        }

        .page-subtitle {
            font-size: 1.25rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }

        .content-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            padding: var(--spacing-3xl);
            margin-bottom: var(--spacing-xl);
        }

        .last-updated {
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
            text-align: center;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-top: var(--spacing-2xl);
            margin-bottom: var(--spacing-lg);
            border-bottom: 2px solid var(--primary-teal);
            padding-bottom: var(--spacing-sm);
        }

        h2:first-child {
            margin-top: 0;
        }

        h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-top: var(--spacing-xl);
            margin-bottom: var(--spacing-md);
        }

        p {
            margin-bottom: var(--spacing-lg);
            color: var(--gray-700);
        }

        ul, ol {
            margin-bottom: var(--spacing-lg);
            padding-left: var(--spacing-xl);
        }

        li {
            margin-bottom: var(--spacing-sm);
            color: var(--gray-700);
        }

        .contact-info {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            margin: var(--spacing-2xl) 0;
        }

        .contact-info h3 {
            color: var(--primary-teal);
            margin-top: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            text-decoration: none;
            color: var(--white);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: var(--spacing-xl) var(--spacing-md);
            }

            .content-card {
                padding: var(--spacing-xl);
            }

            .page-title {
                font-size: 2rem;
            }

            .navbar-container {
                padding: 0 var(--spacing-md);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">🐾</div>
                Vet Precision
            </a>
            <a href="login.php" class="back-link">
                ← Back to Login
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Privacy Policy</h1>
            <p class="page-subtitle">Your privacy is important to us. Learn how we collect, use, and protect your information.</p>
        </div>

        <div class="content-card">
            <div class="last-updated">
                <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
            </div>

            <h2>Introduction</h2>
            <p>At Vet Precision, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our veterinary management platform and services.</p>

            <h2>Information We Collect</h2>
            
            <h3>Personal Information</h3>
            <p>We collect information that you provide directly to us, including:</p>
            <ul>
                <li><strong>Account Information:</strong> Name, email address, phone number, and password</li>
                <li><strong>Contact Details:</strong> Mailing address, billing address, and emergency contact information</li>
                <li><strong>Pet Information:</strong> Pet names, species, breed, age, medical history, and health records</li>
                <li><strong>Appointment Data:</strong> Appointment schedules, visit notes, and treatment records</li>
                <li><strong>Payment Information:</strong> Billing details and payment method information (processed securely through third-party providers)</li>
            </ul>

            <h3>Automatically Collected Information</h3>
            <p>When you use our services, we may automatically collect:</p>
            <ul>
                <li>Device information (IP address, browser type, operating system)</li>
                <li>Usage data (pages visited, features used, time spent on platform)</li>
                <li>Location information (if you enable location services)</li>
                <li>Cookies and similar tracking technologies</li>
            </ul>

            <h2>How We Use Your Information</h2>
            <p>We use the collected information for the following purposes:</p>
            <ul>
                <li><strong>Veterinary Services:</strong> Providing medical care, maintaining health records, and managing appointments</li>
                <li><strong>Communication:</strong> Sending appointment reminders, health updates, and important notifications</li>
                <li><strong>Platform Operation:</strong> Managing your account, processing payments, and providing customer support</li>
                <li><strong>Improvement:</strong> Analyzing usage patterns to improve our services and user experience</li>
                <li><strong>Legal Compliance:</strong> Meeting regulatory requirements and legal obligations</li>
            </ul>

            <h2>Information Sharing and Disclosure</h2>
            <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following circumstances:</p>
            <ul>
                <li><strong>Veterinary Staff:</strong> Authorized veterinarians and clinic staff for providing medical care</li>
                <li><strong>Service Providers:</strong> Third-party vendors who assist in platform operation (payment processors, cloud storage providers)</li>
                <li><strong>Legal Requirements:</strong> When required by law, court order, or government request</li>
                <li><strong>Emergency Situations:</strong> To protect the health and safety of pets or individuals</li>
                <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of business assets</li>
            </ul>

            <h2>Data Security</h2>
            <p>We implement comprehensive security measures to protect your information:</p>
            <ul>
                <li>Encryption of data in transit and at rest</li>
                <li>Secure access controls and authentication procedures</li>
                <li>Regular security audits and vulnerability assessments</li>
                <li>Staff training on data protection and privacy practices</li>
                <li>Compliance with healthcare data security standards</li>
            </ul>

            <h2>Your Rights and Choices</h2>
            <p>You have the following rights regarding your personal information:</p>
            <ul>
                <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
                <li><strong>Correction:</strong> Update or correct inaccurate personal information</li>
                <li><strong>Deletion:</strong> Request deletion of your personal information (subject to legal requirements)</li>
                <li><strong>Portability:</strong> Request a copy of your data in a portable format</li>
                <li><strong>Opt-out:</strong> Unsubscribe from marketing communications</li>
                <li><strong>Withdraw Consent:</strong> Withdraw consent for data processing where applicable</li>
            </ul>

            <h2>Data Retention</h2>
            <p>We retain your personal information for as long as necessary to:</p>
            <ul>
                <li>Provide veterinary services and maintain medical records</li>
                <li>Comply with legal and regulatory requirements</li>
                <li>Resolve disputes and enforce our agreements</li>
                <li>Improve our services and platform functionality</li>
            </ul>
            <p>Medical records are typically retained for 7 years or as required by applicable laws and regulations.</p>

            <h2>Children's Privacy</h2>
            <p>Our services are not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately.</p>

            <h2>Changes to This Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time to reflect changes in our practices or applicable laws. We will notify you of any material changes by:</p>
            <ul>
                <li>Posting the updated policy on our website</li>
                <li>Sending email notifications to registered users</li>
                <li>Displaying prominent notices on our platform</li>
            </ul>

            <div class="contact-info">
                <h3>Contact Us About Privacy</h3>
                <p>If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
                <p><strong>Email:</strong> privacy@vetprecision.com<br>
                <strong>Phone:</strong> (045) 123-4567<br>
                <strong>Address:</strong> Angeles City, Pampanga, Philippines</p>
                
                <a href="contact-support.php" class="btn btn-primary">Contact Support</a>
            </div>
        </div>
    </main>
</body>
</html>