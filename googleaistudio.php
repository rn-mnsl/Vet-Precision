<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vet Precision - Your Pet's Safety is our Top Priority</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* 
        DEVELOPER NOTES:
        - This CSS is a complete rewrite to match the new design.
        - It uses modern CSS (Flexbox, Grid) for layout.
        - Colors and fonts are extracted from the provided design mockups.
        - Responsive styles are included at the bottom for mobile devices.
        */

        /* ===== VARIABLES & BASE STYLES ===== */
        :root {
            --teal: #00A99D;
            --orange: #FBAA33;
            --dark-text: #1F2937;
            --body-text: #374151;
            --background-light: #F9FAFB;
            --white: #FFFFFF;
            --placeholder-gray: #D1D5DB;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
            --radius-md: 16px;
            --radius-full: 9999px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            color: var(--dark-text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        section {
            padding: 80px 0;
        }

        h1, h2, h3 {
            font-weight: 700;
            line-height: 1.2;
        }

        h1 {
            font-size: 3.5rem; /* 56px */
            color: var(--dark-text);
        }

        h2 {
            font-size: 2.5rem; /* 40px */
            text-align: center;
        }

        p {
            color: var(--body-text);
            line-height: 1.6;
            font-size: 1.125rem; /* 18px */
        }
        
        img {
            max-width: 100%;
            display: block;
        }

        /* ===== NAVIGATION ===== */
        header {
            padding: 20px 0;
            display: flex;
            justify-content: center;
            position: sticky;
            top: 20px;
            z-index: 100;
        }

        .navbar {
            display: flex;
            align-items: center;
            background-color: var(--white);
            padding: 8px;
            border-radius: var(--radius-full);
            box-shadow: var(--shadow);
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 8px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark-text);
            font-weight: 600;
            font-size: 1rem;
            padding: 12px 24px;
            border-radius: var(--radius-full);
            transition: all 0.3s ease;
        }
        
        .nav-links a.active,
        .nav-links a:hover {
            background-color: var(--teal);
            color: var(--white);
        }

        /* ===== HERO SECTION ===== */
        .hero {
            padding-top: 40px;
        }

        .hero .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 60px;
        }

        .hero-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 24px;
        }

        .btn-primary {
            background-color: var(--teal);
            color: var(--white);
            padding: 16px 32px;
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }
        
        .hero-image-placeholder {
            width: 100%;
            height: 380px;
            background-color: var(--placeholder-gray);
            border-radius: var(--radius-md);
        }

        /* ===== ABOUT US SECTION ===== */
        .about-us-section {
            background-color: var(--teal);
        }

        .about-us-section h2 {
            color: var(--white);
            margin-bottom: 40px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .about-card {
            background-color: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            aspect-ratio: 4 / 3;
            overflow: hidden;
        }
        
        .about-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ===== SERVICES SECTION ===== */
        .services-section {
            background-color: var(--background-light);
        }

        .services-header {
            text-align: center;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .services-header p {
            font-size: 1rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .service-card {
            background-color: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            overflow: hidden;
            text-align: center;
        }
        
        .service-card-content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .service-card h3 {
            font-size: 1.5rem;
        }
        
        .service-card p {
            font-size: 1rem;
        }

        .service-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        /* ===== IMAGE BANNER SECTION ===== */
        .image-banner-section {
            height: 400px;
            background-image: url('https://i.imgur.com/kK3bEAB.png'); /* Using a stable link for the image */
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .image-banner-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: var(--radius-md);
        }

        .image-banner-section .container {
            position: relative;
            z-index: 2;
        }
        
        .image-banner-section h2 {
            color: var(--white);
        }
        
        /* The design uses a rounded look for this section, this can be achieved inside the container */
        .image-banner-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===== REVIEWS SECTION ===== */
        .reviews-section {
            padding-bottom: 40px;
        }

        /* ===== CTA SECTION ===== */
        .cta-section {
            background-color: var(--orange);
            padding: 60px 0;
        }

        /* ===== FOOTER SECTION ===== */
        .footer-section {
            background-color: var(--teal);
            padding: 60px 0;
            color: var(--white);
        }

        .footer-section .container {
            text-align: center;
        }
        
        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 992px) {
            h1 { font-size: 2.75rem; }
            h2 { font-size: 2rem; }

            .hero .container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content {
                align-items: center;
            }

            .about-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            header {
                top: 0;
                padding: 10px;
                width: 100%;
            }
            .navbar {
                width: 100%;
                justify-content: center;
            }
            .nav-links a {
                padding: 10px 16px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            header {
                /* On very small screens, let the nav bar scroll away */
                position: static;
            }
            .navbar {
                flex-direction: column;
                gap: 10px;
                border-radius: var(--radius-md);
            }

            h1 { font-size: 2.25rem; }

            .about-grid {
                grid-template-columns: 1fr;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>
</head>
<body>

    <!-- Header & Navigation -->
    <header>
        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="#about" class="active">About us</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#reviews">Reviews</a></li>
                <li><a href="#contact">Contacts</a></li>
                <li><a href="#">Book Now</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero" id="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Your Pet's Safety is our Top Priority</h1>
                    <p>The first and only Canine Distemper Facility in Pampanga dedicated to providing quality health care for patients in need.</p>
                    <a href="#" class="btn-primary">Book Now</a>
                </div>
                <div class="hero-image-placeholder"></div>
            </div>
        </section>

        <!-- About Us Section -->
        <section class="about-us-section" id="about">
            <div class="container">
                <h2>About us</h2>
                <div class="about-grid">
                    <div class="about-card">
                        <!-- Image provided in the design -->
                        <img src="https://i.imgur.com/k6lP0W3.png" alt="V&I Precision Animal Hospital Building">
                    </div>
                    <div class="about-card"></div>
                    <div class="about-card"></div>
                    <div class="about-card"></div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section" id="services">
            <div class="container">
                <div class="services-header">
                    <h2>Services</h2>
                    <p>We offer different services blah blah blah</p>
                </div>
                <div class="services-grid">
                    <!-- Card 1 -->
                    <div class="service-card">
                        <img src="https://i.imgur.com/83HS449.png" alt="Happy dog getting groomed">
                        <div class="service-card-content">
                            <h3>Grooming</h3>
                            <p>We offer different services blah blah blah blah</p>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div class="service-card">
                        <img src="https://i.imgur.com/83HS449.png" alt="Happy dog getting groomed">
                        <div class="service-card-content">
                            <h3>Grooming</h3>
                            <p>We offer different services blah blah blah blah</p>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div class="service-card">
                        <img src="https://i.imgur.com/83HS449.png" alt="Happy dog getting groomed">
                        <div class="service-card-content">
                            <h3>Grooming</h3>
                            <p>We offer different services blah blah blah blah</p>
                        </div>
                    </div>
                    <!-- Card 4 -->
                    <div class="service-card">
                        <img src="https://i.imgur.com/83HS449.png" alt="Happy dog getting groomed">
                        <div class="service-card-content">
                            <h3>Grooming</h3>
                            <p>We offer different services blah blah blah blah</p>
                        </div>
                    </div>
                    <!-- Card 5 -->
                    <div class="service-card">
                        <img src="https://i.imgur.com/83HS449.png" alt="Happy dog getting groomed">
                        <div class="service-card-content">
                            <h3>Grooming</h3>
                            <p>We offer different services blah blah blah blah</p>
                        </div>
                    </div>
                    <!-- Card 6 -->
                    <div class="service-card">
                        <img src="https://i.imgur.com/83HS449.png" alt="Happy dog getting groomed">
                        <div class="service-card-content">
                            <h3>Grooming</h3>
                            <p>We offer different services blah blah blah blah</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Image Banner Section -->
        <div class="image-banner-wrapper">
             <section class="image-banner-section">
                <div class="container">
                    <h2>Giving hearts to animals</h2>
                </div>
            </section>
        </div>

        <!-- Reviews Section -->
        <section class="reviews-section" id="reviews">
            <div class="container">
                <h2>Reviews</h2>
                <!-- Review content would go here -->
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                 <h2>Book an Appointment with us</h2>
            </div>
        </section>

    </main>

    <!-- Footer Section -->
    <footer class="footer-section" id="contact">
        <div class="container">
            <!-- Simplified footer as per design. More content can be added here. -->
            <p>© 2024 Vet Precision. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>