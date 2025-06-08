<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vet Precision - Your Pet's Health Partner</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Additional styles specific to landing page */
        .hero {
            margin-top: 80px;
            padding: 4rem 2rem;
            background: var(--gradient-light);
            position: relative;
            overflow: hidden;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--dark-color);
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }

        .hero-content .highlight {
            color: var(--primary-color);
            position: relative;
        }

        .hero-content p {
            font-size: 1.25rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .hero-stats {
            display: flex;
            gap: 3rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary-color);
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-image {
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

        .hero-image img {
            width: 100%;
            height: auto;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
        }

        .services-section {
            padding: 5rem 2rem;
            background: white;
        }

        .services-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: var(--light-color);
            padding: 2rem;
            border-radius: var(--radius-lg);
            text-align: center;
            transition: all var(--transition-base);
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform var(--transition-base);
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .service-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: inline-block;
            animation: bounce 2s infinite;
        }

        .service-card:nth-child(2) .service-icon {
            animation-delay: 0.2s;
        }

        .service-card:nth-child(3) .service-icon {
            animation-delay: 0.4s;
        }

        .about-section {
            padding: 5rem 2rem;
            background: var(--gradient-light);
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-image {
            position: relative;
        }

        .about-image img {
            width: 100%;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
        }

        .about-badge {
            position: absolute;
            bottom: -20px;
            right: -20px;
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            text-align: center;
        }

        .about-badge-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .cta-section {
            padding: 5rem 2rem;
            background: var(--gradient-primary);
            color: white;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .cta-section p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            color: white;
        }

        .cta-section .btn-secondary {
            background: white;
            color: var(--primary-color);
            border: none;
        }

        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-stats {
                justify-content: center;
            }

            .about-container {
                grid-template-columns: 1fr;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="#" class="logo">
                <span class="logo-icon">🐾</span>
                Vet Precision
            </a>
            <ul class="nav-links">
                <li><a href="#services">Services</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="btn btn-secondary btn-sm">Login</a></li>
                <li><a href="register.php" class="btn btn-primary btn-sm">Book Now</a></li>
            </ul>
            <button class="mobile-menu-toggle">☰</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="paw-pattern" style="top: 10%; left: 10%; animation-delay: 0s;">🐾</div>
        <div class="paw-pattern" style="top: 20%; right: 15%; animation-delay: 2s;">🐾</div>
        <div class="paw-pattern" style="bottom: 20%; left: 20%; animation-delay: 4s;">🐾</div>
        <div class="paw-pattern" style="bottom: 10%; right: 10%; animation-delay: 6s;">🐾</div>
        
        <div class="hero-container">
            <div class="hero-content animate-fadeIn">
                <h1>Your Pet's Health is Our <span class="highlight">Top Priority</span></h1>
                <p>Experience compassionate care and state-of-the-art veterinary services for your beloved companions</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary btn-lg">Book Appointment</a>
                    <a href="#services" class="btn btn-secondary btn-lg">Our Services</a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value">2,500+</div>
                        <div class="stat-label">Happy Pets</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">10+</div>
                        <div class="stat-label">Years Experience</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">24/7</div>
                        <div class="stat-label">Emergency Care</div>
                    </div>
                </div>
            </div>
            <div class="hero-image animate-fadeInRight">
                <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2069&q=80" alt="Happy pets">
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="section-header">
            <h2>Our Comprehensive Pet Care Services</h2>
            <p>From routine check-ups to emergency care, we're here for every step of your pet's journey</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">🏥</div>
                <h3>General Consultation</h3>
                <p>Regular health check-ups and preventive care to keep your pets healthy and happy</p>
            </div>
            <div class="service-card">
                <div class="service-icon">💉</div>
                <h3>Vaccination & Immunization</h3>
                <p>Complete vaccination programs to protect your pets from common diseases</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🔬</div>
                <h3>Laboratory Services</h3>
                <p>Advanced diagnostic testing for accurate and timely health assessments</p>
            </div>
            <div class="service-card">
                <div class="service-icon">✂️</div>
                <h3>Grooming & Spa</h3>
                <p>Professional grooming services to keep your pets looking and feeling their best</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🦷</div>
                <h3>Dental Care</h3>
                <p>Comprehensive dental services for optimal oral health</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🚑</div>
                <h3>Emergency Care</h3>
                <p>24/7 emergency services for urgent pet health situations</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="about-container">
            <div class="about-image animate-fadeInLeft">
                <img src="https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Veterinarian with pet">
                <div class="about-badge">
                    <div class="about-badge-number">10+</div>
                    <div>Years of Excellence</div>
                </div>
            </div>
            <div class="about-content animate-fadeInRight">
                <h2>Dedicated to Your Pet's Wellbeing</h2>
                <p>At Vet Precision, we understand that your pets are more than just animals – they're beloved family members. Our team of experienced veterinarians and caring staff are committed to providing the highest quality medical care in a warm, welcoming environment.</p>
                <p>We combine cutting-edge veterinary technology with compassionate care to ensure your pets receive the best possible treatment. From routine wellness exams to complex surgical procedures, we're here to support your pet's health journey every step of the way.</p>
                <a href="#" class="btn btn-primary">Learn More About Us</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Give Your Pet the Best Care?</h2>
            <p>Book an appointment today and experience the Vet Precision difference</p>
            <a href="register.php" class="btn btn-secondary btn-lg">Schedule Appointment</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Vet Precision</h3>
                <p>Your trusted partner in pet healthcare since 2014</p>
                <div class="mt-3">
                    <span style="font-size: 1.5rem; margin-right: 1rem;">📱</span>
                    <span style="font-size: 1.5rem; margin-right: 1rem;">📧</span>
                    <span style="font-size: 1.5rem;">📍</span>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="#services">Our Services</a>
                <a href="#about">About Us</a>
                <a href="login.php">Client Portal</a>
                <a href="register.php">Book Appointment</a>
            </div>
            <div class="footer-section">
                <h3>Our Services</h3>
                <a href="#">General Consultation</a>
                <a href="#">Emergency Care</a>
                <a href="#">Pet Grooming</a>
                <a href="#">Vaccination</a>
            </div>
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p>📍 Angeles City, Pampanga</p>
                <p>📞 (045) 123-4567</p>
                <p>📧 info@vetprecision.com</p>
                <p>🕒 Mon-Sat: 9AM-8PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Vet Precision. All rights reserved. Made with ❤️ for pets</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.08)';
            }
        });

        // Mobile menu toggle
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const navLinks = document.querySelector('.nav-links');
        
        mobileMenuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });

        // Intersection Observer for animations
        const animateElements = document.querySelectorAll('.animate-fadeIn, .animate-fadeInLeft, .animate-fadeInRight');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0) translateX(0)';
                }
            });
        }, { threshold: 0.1 });

        animateElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transition = 'all 0.6s ease-out';
            
            if (element.classList.contains('animate-fadeInLeft')) {
                element.style.transform = 'translateX(-30px)';
            } else if (element.classList.contains('animate-fadeInRight')) {
                element.style.transform = 'translateX(30px)';
            } else {
                element.style.transform = 'translateY(30px)';
            }
            
            observer.observe(element);
        });
    </script>
</body>
</html>