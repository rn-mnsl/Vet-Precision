<?php
require_once 'config/init.php';

$pageTitle = 'Contact Support - ' . SITE_NAME;
$success = false;
$errors = [];

if (isPost()) {
    $data = [
        'name' => sanitize($_POST['name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'subject' => sanitize($_POST['subject'] ?? ''),
        'category' => sanitize($_POST['category'] ?? ''),
        'message' => sanitize($_POST['message'] ?? ''),
        'priority' => sanitize($_POST['priority'] ?? 'normal')
    ];
    
    // Basic validation
    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($data['subject'])) {
        $errors['subject'] = 'Subject is required';
    }
    
    if (empty($data['message'])) {
        $errors['message'] = 'Message is required';
    } elseif (strlen($data['message']) < 10) {
        $errors['message'] = 'Message must be at least 10 characters long';
    }
    
    if (empty($errors)) {
        // In a real application, you would save this to database and/or send email
        // For now, we'll just show a success message
        try {
            // Here you could save to database:
            // $stmt = $pdo->prepare("INSERT INTO support_tickets (name, email, subject, category, message, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            // $stmt->execute([$data['name'], $data['email'], $data['subject'], $data['category'], $data['message'], $data['priority']]);
            
            $success = true;
            setFlash('Your support request has been submitted successfully! We will get back to you within 24 hours.', 'success');
            
            // Clear form data on success
            $data = [
                'name' => '',
                'email' => '',
                'subject' => '',
                'category' => '',
                'message' => '',
                'priority' => 'normal'
            ];
        } catch (Exception $e) {
            $errors['general'] = 'There was an error submitting your request. Please try again.';
        }
    }
}
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
            --orange-accent: #F6A144;
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
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
            --gradient-primary: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-light) 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --spacing-3xl: 4rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
            color: var(--gray-700);
            line-height: 1.6;
            min-height: 100vh;
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
            max-width: 1200px;
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

        .support-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-3xl);
            align-items: start;
        }

        /* Contact Form */
        .contact-form-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-3xl);
        }

        .form-header {
            margin-bottom: var(--spacing-2xl);
        }

        .form-header h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
        }

        .form-header p {
            color: var(--gray-600);
            font-size: 1rem;
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

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .priority-options {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-sm);
        }

        .priority-option {
            flex: 1;
            position: relative;
        }

        .priority-option input[type="radio"] {
            display: none;
        }

        .priority-label {
            display: block;
            padding: var(--spacing-md);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .priority-option input[type="radio"]:checked + .priority-label {
            border-color: var(--primary-teal);
            background: rgba(29, 186, 168, 0.05);
            color: var(--primary-teal);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: 0.875rem 2rem;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
            width: 100%;
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

        /* Contact Info Sidebar */
        .contact-info-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            padding: var(--spacing-2xl);
            height: fit-content;
        }

        .contact-info-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-lg);
        }

        .contact-method {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            transition: background 0.2s ease;
        }

        .contact-method:hover {
            background: var(--gray-50);
        }

        .contact-icon {
            width: 48px;
            height: 48px;
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
            flex-shrink: 0;
        }

        .contact-details h4 {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
        }

        .contact-details p {
            color: var(--gray-600);
            margin: 0;
            font-size: 0.875rem;
        }

        .faq-section {
            margin-top: var(--spacing-2xl);
            padding-top: var(--spacing-2xl);
            border-top: 1px solid var(--gray-200);
        }

        .faq-item {
            margin-bottom: var(--spacing-lg);
        }

        .faq-question {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
            font-size: 0.9rem;
        }

        .faq-answer {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin: 0;
        }

        /* Alerts */
        .alert {
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border-color: rgba(239, 68, 68, 0.2);
        }

        /* Response Time Badge */
        .response-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: rgba(29, 186, 168, 0.1);
            color: var(--primary-teal);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: var(--spacing-lg);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .support-container {
                grid-template-columns: 1fr;
                gap: var(--spacing-2xl);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: var(--spacing-xl) var(--spacing-md);
            }

            .contact-form-card,
            .contact-info-card {
                padding: var(--spacing-xl);
            }

            .page-title {
                font-size: 2rem;
            }

            .navbar-container {
                padding: 0 var(--spacing-md);
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .priority-options {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .contact-form-card,
            .contact-info-card {
                padding: var(--spacing-lg);
            }

            .contact-method {
                padding: var(--spacing-md);
            }

            .contact-icon {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
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
            <h1 class="page-title">Contact Support</h1>
            <p class="page-subtitle">Need help? We're here to assist you with any questions or technical issues.</p>
        </div>

        <div class="support-container">
            <!-- Contact Form -->
            <div class="contact-form-card">
                <div class="form-header">
                    <h2>Send us a Message</h2>
                    <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                    <div class="response-badge">
                        ⚡ Average response time: 2-4 hours
                    </div>
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

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo sanitize($data['name'] ?? ''); ?>"
                                placeholder="Enter your full name"
                                required
                            >
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo sanitize($errors['name']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo sanitize($data['email'] ?? ''); ?>"
                                placeholder="Enter your email address"
                                required
                            >
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo sanitize($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category" class="form-label">Category</label>
                            <select 
                                id="category" 
                                name="category" 
                                class="form-control"
                            >
                                <option value="">Select a category</option>
                                <option value="technical" <?php echo ($data['category'] ?? '') === 'technical' ? 'selected' : ''; ?>>Technical Issue</option>
                                <option value="account" <?php echo ($data['category'] ?? '') === 'account' ? 'selected' : ''; ?>>Account Problem</option>
                                <option value="billing" <?php echo ($data['category'] ?? '') === 'billing' ? 'selected' : ''; ?>>Billing Question</option>
                                <option value="appointment" <?php echo ($data['category'] ?? '') === 'appointment' ? 'selected' : ''; ?>>Appointment Help</option>
                                <option value="medical" <?php echo ($data['category'] ?? '') === 'medical' ? 'selected' : ''; ?>>Medical Records</option>
                                <option value="feedback" <?php echo ($data['category'] ?? '') === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                                <option value="other" <?php echo ($data['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Priority Level</label>
                            <div class="priority-options">
                                <div class="priority-option">
                                    <input type="radio" id="priority_low" name="priority" value="low" <?php echo ($data['priority'] ?? 'normal') === 'low' ? 'checked' : ''; ?>>
                                    <label for="priority_low" class="priority-label">Low</label>
                                </div>
                                <div class="priority-option">
                                    <input type="radio" id="priority_normal" name="priority" value="normal" <?php echo ($data['priority'] ?? 'normal') === 'normal' ? 'checked' : ''; ?>>
                                    <label for="priority_normal" class="priority-label">Normal</label>
                                </div>
                                <div class="priority-option">
                                    <input type="radio" id="priority_high" name="priority" value="high" <?php echo ($data['priority'] ?? 'normal') === 'high' ? 'checked' : ''; ?>>
                                    <label for="priority_high" class="priority-label">High</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label">Subject <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="subject" 
                            name="subject" 
                            class="form-control <?php echo isset($errors['subject']) ? 'is-invalid' : ''; ?>"
                            value="<?php echo sanitize($data['subject'] ?? ''); ?>"
                            placeholder="Brief description of your issue"
                            required
                        >
                        <?php if (isset($errors['subject'])): ?>
                            <div class="invalid-feedback"><?php echo sanitize($errors['subject']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">Message <span class="required">*</span></label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-control <?php echo isset($errors['message']) ? 'is-invalid' : ''; ?>"
                            placeholder="Please provide detailed information about your issue or question..."
                            rows="6"
                            required
                        ><?php echo sanitize($data['message'] ?? ''); ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <div class="invalid-feedback"><?php echo sanitize($errors['message']); ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Information Sidebar -->
            <div class="contact-info-card">
                <h3>Get in Touch</h3>

                <div class="contact-method">
                    <div class="contact-icon">📞</div>
                    <div class="contact-details">
                        <h4>Phone Support</h4>
                        <p>(045) 123-4567</p>
                        <p>Mon-Sat: 9:00 AM - 8:00 PM</p>
                        <p>Emergency: 24/7</p>
                    </div>
                </div>

                <div class="contact-method">
                    <div class="contact-icon">📧</div>
                    <div class="contact-details">
                        <h4>Email Support</h4>
                        <p>support@vetprecision.com</p>
                        <p>Response within 2-4 hours</p>
                    </div>
                </div>

                <div class="contact-method">
                    <div class="contact-icon">📍</div>
                    <div class="contact-details">
                        <h4>Visit Us</h4>
                        <p>Angeles City, Pampanga</p>
                        <p>Philippines</p>
                        <p>By appointment only</p>
                    </div>
                </div>

                <div class="contact-method">
                    <div class="contact-icon">💬</div>
                    <div class="contact-details">
                        <h4>Live Chat</h4>
                        <p>Available during business hours</p>
                        <p>Instant responses</p>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="faq-section">
                    <h3>Common Questions</h3>

                    <div class="faq-item">
                        <div class="faq-question">How do I reset my password?</div>
                        <div class="faq-answer">Use the "Forgot Password" link on the login page to receive reset instructions.</div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">How do I book an appointment?</div>
                        <div class="faq-answer">Log into your account and navigate to the "Appointments" section to schedule a visit.</div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">Can I access medical records online?</div>
                        <div class="faq-answer">Yes, all medical records are available in your client dashboard after each visit.</div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">What are your emergency hours?</div>
                        <div class="faq-answer">We provide 24/7 emergency services. Call our main number for urgent situations.</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Form enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('.btn-primary');
            
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Send Message';
                }, 10000);
            });

            // Auto-resize textarea
            const textarea = document.getElementById('message');
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });

            // Category-based subject suggestions
            const categorySelect = document.getElementById('category');
            const subjectInput = document.getElementById('subject');
            
            const subjectSuggestions = {
                'technical': 'Website/App not working properly',
                'account': 'Unable to access my account',
                'billing': 'Question about my bill',
                'appointment': 'Need help with appointment scheduling',
                'medical': 'Request for medical records',
                'feedback': 'Feedback about service',
                'other': 'General inquiry'
            };

            categorySelect.addEventListener('change', function() {
                if (this.value && subjectInput.value === '') {
                    subjectInput.value = subjectSuggestions[this.value] || '';
                }
            });

            // Priority level styling
            const priorityInputs = document.querySelectorAll('input[name="priority"]');
            priorityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const label = this.nextElementSibling;
                    
                    // Reset all labels
                    priorityInputs.forEach(inp => {
                        inp.nextElementSibling.style.borderColor = 'var(--gray-200)';
                        inp.nextElementSibling.style.background = 'var(--white)';
                        inp.nextElementSibling.style.color = 'var(--gray-700)';
                    });
                    
                    // Style selected label based on priority
                    if (this.value === 'high') {
                        label.style.borderColor = 'var(--error)';
                        label.style.background = 'rgba(239, 68, 68, 0.05)';
                        label.style.color = 'var(--error)';
                    } else if (this.value === 'low') {
                        label.style.borderColor = 'var(--success)';
                        label.style.background = 'rgba(16, 185, 129, 0.05)';
                        label.style.color = 'var(--success)';
                    } else {
                        label.style.borderColor = 'var(--primary-teal)';
                        label.style.background = 'rgba(29, 186, 168, 0.05)';
                        label.style.color = 'var(--primary-teal)';
                    }
                });
            });

            // Initialize priority styling
            const checkedPriority = document.querySelector('input[name="priority"]:checked');
            if (checkedPriority) {
                checkedPriority.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>