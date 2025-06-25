<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vet Precision - Your Pet's Health Partner</title>
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
            --warning: --orange-accent;
            --error: #EF4444;
            --info: #3B82F6;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-light) 100%);
            --gradient-hero: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
            
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
            font-size: 16px;
            line-height: 1.6;
            color: var(--gray-700);
            background-color: var(--white);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-display);
            font-weight: 700;
            line-height: 1.2;
            color: var(--gray-900);
            margin-bottom: var(--spacing-md);
        }

        h1 { font-size: 3.5rem; }
        h2 { font-size: 2.5rem; }
        h3 { font-size: 2rem; }
        h4 { font-size: 1.5rem; }
        h5 { font-size: 1.25rem; }
        h6 { font-size: 1.125rem; }

        p {
            margin-bottom: var(--spacing-md);
            color: var(--gray-600);
            line-height: 1.7;
        }

        a {
            color: var(--primary-teal);
            text-decoration: none;
            transition: all 0.2s ease-in-out;
        }

        a:hover {
            color: var(--primary-teal-dark);
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===== Navigation ===== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-200);
            z-index: 1000;
            transition: all 0.3s ease;
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
            height: 50px;
            width: 100px;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-900);
            text-decoration: none;
        }

        .logo:hover {
            color: var(--gray-900);
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
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: var(--spacing-xl);
            align-items: center;
            margin: 0;
        }

        .nav-links a {
            color: var(--gray-700);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary-teal);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-teal);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary-teal);
            border: 2px solid var(--primary-teal);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary:hover {
            background: var(--primary-teal);
            color: var(--white);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* ===== Hero Section ===== */
        .hero {
            background: var(--gradient-hero);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f3f4f6' fill-opacity='0.3'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.3;
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
            padding-top: 80px;
            display: grid;
            grid-template-columns: 4fr 6fr;
            gap: var(--spacing-2xl);
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.1;
            margin-bottom: var(--spacing-lg);
        }

        .hero-content .highlight {
            background: linear-gradient(135deg, var(--primary-teal) 0%, var(--primary-teal-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: 1rem;
            color: var(--gray-600);
            margin-bottom: var(--spacing-xl);
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-2xl);
            flex-wrap: wrap;
        }

        .hero-stats {
            display: flex;
            gap: var(--spacing-2xl);
            flex-wrap: wrap;
        }

        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-image img {
            width: 100%;
            max-width: 600px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            transition: transform 0.3s ease;
        }

        .hero-image:hover img {
            transform: scale(1.02);
        }

        /* ===== About Section ===== */
        .about-section {
            padding: var(--spacing-3xl) 0;
            background: var(--white);
            margin-top: 40px;z
        }

        .about-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
        }

        .about-header {
            text-align: left;
            max-width: 1280px;
            margin-bottom: -15px;;
        }

        .about-header h2 {
            color: var(--gray-900);
            margin-bottom: var(--spacing-md);
        }

        .about-header p {
            font-size: 1.125rem;
            color: var(--gray-600);
        }

        .about-grid {
            display: grid;
            grid-template-columns: 5fr 6fr;
            gap: var(--spacing-3xl);
            align-items: center;
        }

        .about-image {
            position: relative;
        }

        .about-image img {
            width: 100%;
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
        }

        .about-content h3 {
            color: var(--gray-900);
            margin-bottom: var(--spacing-lg);
            font-size: 2rem;
        }

        .about-content p {
            font-size: 1.1rem;
            margin-bottom: var(--spacing-lg);
            line-height: 1.7;
        }

        .about-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
            margin: var(--spacing-2xl) 0;
        }

        .feature-item {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            justify-items: center;
            text-align: center;x
        }

        .feature-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .feature-item:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 64px; /* Slightly larger for card layout */
            height: 64px;
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
            margin-bottom: var(--spacing-md);
        }

        .feature-content {
            width: 100%;
        }

        .feature-content h4 {
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .feature-content p {
            color: var(--gray-600);
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.6;
        }


        /* ===== Testimonials Section ==== */
        .testimonials-section {
            position: relative;
            height: 100vh; /* Full viewport height */
            min-height: 600px; /* Minimum height for smaller screens */
            background-image: url('https://images.unsplash.com/photo-1601758228041-f3b2795255f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed; /* Creates parallax effect */
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 50px;
        }

        .testimonials-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4); /* Dark overlay for text readability */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .testimonials-content {
            text-align: center;
            color: blue;
            max-width: 800px;
            padding: var(--spacing-2xl);
        }

        .testimonials-content h2 {
            font-size: 45px;
            font-weight: 700;
            margin-bottom: var(--spacing-lg);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            line-height: 1.2;
            color: white; 
        }

        .testimonials-content p {
            font-size: 1.5rem;
            margin: 0;
            opacity: 0.95;
            line-height: 1.6;
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .testimonials-section {
                height: 70vh;
                min-height: 500px;
                background-attachment: scroll; /* Better performance on mobile */
            }
            
            .testimonials-content {
                padding: var(--spacing-xl);
            }
            
            .testimonials-content h2 {
                font-size: 2.5rem;
            }
            
            .testimonials-content p {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .testimonials-content h2 {
                font-size: 2rem;
            }
            
            .testimonials-content p {
                font-size: 1rem;
            }
        }

        /* ===== Services Section ===== */
        .services-section {
            padding: var(--spacing-3xl) 0;
            background: var(--gray-50);
        }

        .services-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto var(--spacing-3xl);
        }

        .section-header h2 {
            color: var(--gray-900);
            margin-bottom: var(--spacing-md);
        }

        .section-header p {
            font-size: 1.125rem;
            color: var(--gray-600);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-xl);
        }

        .service-card {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-image {
            width: 100%;
            height: 250px; /* Adjust height as needed */
            object-fit: cover;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0; /* Rounded top corners only */
            display: block;
        }

        .service-card h3 {
            color: var(--gray-900);
            margin-bottom: var(--spacing-md);
            margin-top: var(--spacing-lg);
            font-size: 1.5rem;
        }

        .service-card p {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: var(--spacing-lg);
        }

        .service-link {
            color: var(--primary-teal);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            transition: gap 0.2s ease;
        }

        .service-link:hover {
            gap: var(--spacing-sm);
        }

        /* ===== CTA Section ===== */
        .cta-section {
            padding: var(--spacing-3xl) 0;
            background: var(--gradient-primary);
            color: var(--white);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            position: relative;
            z-index: 1;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            color: var(--white);
            margin-bottom: var(--spacing-md);
        }

        .cta-section p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: var(--spacing-xl);
        }

        .cta-button {
            background: var(--white);
            color: var(--primary-teal);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: var(--radius-full);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            color: var(--primary-teal-dark);
        }

        /* ==== REVIEW ===== */

.reviews-carousel {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
}

.carousel-wrapper {
    position: relative;
    background: white;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.carousel-slides {
    position: relative;
    width: 100%;
    height: 700px;
}

.review-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.review-slide.active {
    opacity: 1;
}

.review-image {
    width: 100%;
    height: 100%;
    overflow: hidden;
    align-items: center;
}

.review-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.review-content {
    padding: var(--spacing-xl);
    text-align: center;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.review-stars {
    color: var(--orange-accent);
    font-size: 1.5rem;
    margin-bottom: var(--spacing-md);
}

.review-text {
    font-style: italic;
    font-size: 1.1rem;
    line-height: 1.6;
    color: var(--gray-700);
    margin-bottom: var(--spacing-lg);
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reviewer-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-md);
}

.reviewer-avatar {
    width: 50px;
    height: 50px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.reviewer-details {
    text-align: left;
}

.reviewer-name {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 1rem;
    margin-bottom: 0.2rem;
}

.reviewer-title {
    font-size: 0.9rem;
    color: var(--gray-500);
}

/* Enhanced Navigation Buttons */
.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid var(--primary-color);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--primary-color);
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    user-select: none;
}

.carousel-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.carousel-btn:active {
    transform: translateY(-50%) scale(0.95);
}

.prev-btn {
    left: -35px;
}

.next-btn {
    right: -35px;
}

/* Alternative: If buttons are still not visible, use these positions */
.prev-btn {
    left: 15px; /* Move inside the carousel */
}

.next-btn {
    right: 15px; /* Move inside the carousel */
}

/* Enhanced Dots */
.carousel-dots {
    position: absolute;
    bottom: -50px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 12px;
    background: rgba(0, 0, 0, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    backdrop-filter: blur(5px);
}

.dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.dot:hover {
    background: rgba(255, 255, 255, 0.8);
    transform: scale(1.1);
}

.dot.active {
    background: white;
    border-color: var(--primary-color);
    transform: scale(1.2);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .reviews-carousel {
        max-width: 350px;
    }
    
    .carousel-slides {
        height: 550px;
    }
    
    .review-image {
        height: 200px;
    }
    
    .review-content {
        padding: var(--spacing-lg);
    }
    
    .review-text {
        font-size: 1rem;
    }
    
    .reviewer-info {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .reviewer-details {
        text-align: center;
    }
    
    .prev-btn {
        left: 10px;
    }
    
    .next-btn {
        right: 10px;
    }
}

        /* ===== Footer ===== */
        .footer {
            background: var(--gray-900);
            color: var(--gray-300);
            padding: var(--spacing-3xl) 0 var(--spacing-xl);
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        .footer-section h3 {
            color: var(--white);
            margin-bottom: var(--spacing-md);
            font-size: 1.25rem;
        }

        .footer-section a {
            color: var(--gray-400);
            text-decoration: none;
            display: block;
            margin-bottom: var(--spacing-sm);
            transition: color 0.2s ease;
        }

        .footer-section a:hover {
            color: var(--primary-teal-light);
        }

        .footer-section p {
            color: var(--gray-400);
            margin-bottom: var(--spacing-sm);
        }

        .footer-bottom {
            text-align: center;
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--gray-700);
            color: var(--gray-500);
        }

        .social-links {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: var(--gray-800);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            transition: all 0.2s ease;
        }

        .social-link:hover {
            background: var(--primary-teal);
            color: var(--white);
            transform: translateY(-2px);
        }

        /* ===== Mobile Menu ===== */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray-700);
            cursor: pointer;
            padding: var(--spacing-sm);
        }

        /* ===== Responsive Design ===== */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: var(--spacing-2xl);
                text-align: center;
            }

            .about-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-2xl);
            }

            .about-features {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--white);
                flex-direction: column;
                padding: var(--spacing-lg);
                box-shadow: var(--shadow-lg);
                border-top: 1px solid var(--gray-200);
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .hero-stats {
                justify-content: center;
                gap: var(--spacing-lg);
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .cta-section h2 {
                font-size: 2rem;
            }

            h1 { font-size: 2.5rem; }
            h2 { font-size: 2rem; }
            h3 { font-size: 1.5rem; }
        }

        @media (max-width: 480px) {
            .navbar-container {
                padding: 0 var(--spacing-md);
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .stat-value {
                font-size: 2rem;
            }

            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
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

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
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

        .animate-fadeInRight {
            animation: fadeInRight 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="#" class="logo">
                <img src="assets/images/vet-precision-logo-full.png">
            </a>
            <ul class="nav-links">
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#reviews">Reviews</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="btn btn-outline btn-sm">Login</a></li>
                <li><a href="register.php" class="btn btn-primary btn-sm">Book Now</a></li>
            </ul>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content animate-fadeInLeft">
                <h1>Your Pet's Safety is our <span class="highlight">Top Priority</span></h1>
                <p>The first and only Canine Distemper Facility in Pampanga dedicated to providing quality health care for your pets in need.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary btn-lg">Book Now</a>
                    <a href="#services" class="btn btn-secondary btn-lg">Our Services</a>
                </div>
            </div>
            <div class="hero-image animate-fadeInRight">
                <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Happy pets at Vet Precision">
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="about-container">
            <div class="about-header">
                <h2>About us</h2>
                <p>At Vet Precision, we understand that your pets are more than just animals – they're beloved family members. Our team of experienced veterinarians and caring staff are committed to providing the highest quality medical care in a warm, welcoming environment.</p>
            </div>
            
            <div class="about-grid">
                <div class="about-image animate-fadeInLeft">
                    <img src="https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Veterinarian with pet">
                </div>
                
                <div class="about-content animate-fadeInRight">
                    <div class="about-features">
                        <div class="feature-item">
                            <div class="feature-icon">🏥</div>
                            <div class="feature-content">
                                <h4>Expert Care</h4>
                                <p>Highly qualified veterinarians with years of experience</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🔬</div>
                            <div class="feature-content">
                                <h4>Advanced Technology</h4>
                                <p>State-of-the-art equipment for accurate diagnosis</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">❤️</div>
                            <div class="feature-content">
                                <h4>Compassionate Care</h4>
                                <p>We treat every pet with love and understanding</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🚑</div>
                            <div class="feature-content">
                                <h4>Emergency Services</h4>
                                <p>24/7 emergency care when your pet needs it most</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="services-container">
            <div class="section-header">
                <h2>Services</h2>
                <p>We offer different services that suits both you and your pet</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card">
                    <img src="assets/images/service-consultation.png" alt="Service Name" class="service-image">
                    <h3>General Consultation</h3>
                    <p>Comprehensive health examinations and preventive care to keep your pets healthy and happy throughout their lives.</p>
                    <a href="#" class="service-link">Learn More →</a>
                </div>
                
                <div class="service-card">
                    <img src="assets/images/service-grooming.png" alt="Service Name" class="service-image">
                    <h3>Vaccination & Immunization</h3>
                    <p>Complete vaccination programs to protect your pets from common diseases and ensure their long-term health.</p>
                    <a href="#" class="service-link">Learn More →</a>
                </div>
                
                <div class="service-card">
                    <img src="assets/images/service-supplies.png" alt="Service Name" class="service-image">
                    <h3>Laboratory Services</h3>
                    <p>Advanced diagnostic testing including blood work, urinalysis, and imaging for accurate health assessments.</p>
                    <a href="#" class="service-link">Learn More →</a>
                </div>
                
                <div class="service-card">
                    <img src="assets/images/service-surgery.png" alt="Service Name" class="service-image">
                    <h3>Grooming</h3>
                    <p>Professional grooming services to keep your pets looking and feeling their absolute best with expert care.</p>
                    <a href="#" class="service-link">Learn More →</a>
                </div>
                
                <div class="service-card">
                    <img src="assets/images/service-distemper.png" alt="Service Name" class="service-image">
                    <h3>Dental Care</h3>
                    <p>Comprehensive dental services including cleanings, extractions, and oral health maintenance programs.</p>
                    <a href="#" class="service-link">Learn More →</a>
                </div>
                
                <div class="service-card">
                    <img src="assets/images/service-emergency.png" alt="Service Name" class="service-image">
                    <h3>Emergency Care</h3>
                    <p>24/7 emergency services for urgent pet health situations with immediate response and expert treatment.</p>
                    <a href="#" class="service-link">Learn More →</a>
                </div>
            </div>
        </div>
    </section>

<!-- Testimonials Section -->
<section class="testimonials-section">
    <div class="testimonials-overlay">
        <div class="testimonials-content">
            <h2>Giving hearts to animals</h2>
            <p>See what our satisfied pet parents have to say about our care</p>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section class="reviews-section" id="reviews" style="padding: var(--spacing-3xl) 0; background: var(--gray-50);">
    <div class="services-container">
        <div class="section-header">
            <h2>Reviews</h2>
            <p>Trusted by pet owners across Pampanga</p>
        </div>
        
        <div class="reviews-carousel">
            <div class="carousel-wrapper">
                <div class="carousel-slides" id="carouselSlides">
                    <!-- Slide 1 -->
                    <div class="review-slide active">
                        <div class="review-image">
                            <img src="assets/images/review-snowball.jpg" alt="Happy dog" />
                        </div>
                        <div class="review-content">
                            <div class="review-stars">★★★★★</div>
                            <p class="review-text">Sobrang galing ng serbisyo! Si Dr. Martinez ang nag-alaga kay Dennis nung surgery niya, grabe ang care nila."</p>
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">M</div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name">Maria Santos</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div class="review-slide">
                        <div class="review-image">
                            <img src="assets/images/review-sky.jpg" alt="Cat patient" />
                        </div>
                        <div class="review-content">
                            <div class="review-stars">★★★★★</div>
                            <p class="review-text">"Ang ganda ng clinic, super modern! Komportable si Sky dito, at napaka-propesyonal ng staff."</p>
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">J</div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name">Bong-Bong Dela Cruz</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 3 -->
                    <div class="review-slide">
                        <div class="review-image">
                            <img src="assets/images/review-snow.jpg" alt="Emergency care" />
                        </div>
                        <div class="review-content">
                            <div class="review-stars">★★★★★</div>
                            <p class="review-text">"Naligtasan ni Snow ang buhay dahil sa mabilis na emergency service. Salamat talaga sa inyong lahat!"</p>
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">A</div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name">Alexies Dabu </div>
                                    <div class="reviewer-title">Pet Owner</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 4 -->
                    <div class="review-slide">
                        <div class="review-image">
                            <img src="assets/images/review-mojito&kenzo.jpg" alt="Rabbit care" />
                        </div>
                        <div class="review-content">
                            <div class="review-stars">★★★★★</div>
                            <p class="review-text">"Napakabuti ng team dito, tunay na mahal nila ang mga hayop. Si Butete mabilis gumaling dahil sa kanila!"</p>
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">L</div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name">Anna Manansala</div>
                                    <div class="reviewer-title">Rabbit Owner</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <button class="carousel-btn prev-btn" onclick="changeSlide(-1)">‹</button>
                <button class="carousel-btn next-btn" onclick="changeSlide(1)">›</button>

                <!-- Dots -->
                <div class="carousel-dots">
                    <span class="dot active" onclick="currentSlide(0)"></span>
                    <span class="dot" onclick="currentSlide(1)"></span>
                    <span class="dot" onclick="currentSlide(2)"></span>
                    <span class="dot" onclick="currentSlide(3)"></span>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <h2>Book an Appointment with us</h2>
            <p>Ready to give your pet the best care? Schedule an appointment today and experience the Vet Precision difference.</p>
            <a href="register.php" class="cta-button">Schedule Appointment</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Vet Precision</h3>
                <p>Your trusted partner in pet healthcare since 2014. Dedicated to providing exceptional veterinary care with compassion and expertise.</p>
                <div class="social-links">
                    <a href="#" class="social-link">📱</a>
                    <a href="#" class="social-link">📧</a>
                    <a href="#" class="social-link">📍</a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="#about">About Us</a>
                <a href="#services">Our Services</a>
                <a href="login.php">Client Portal</a>
                <a href="register.php">Book Appointment</a>
                <a href="#reviews">Reviews</a>
            </div>
            
            <div class="footer-section">
                <h3>Our Services</h3>
                <a href="#">General Consultation</a>
                <a href="#">Emergency Care</a>
                <a href="#">Pet Grooming</a>
                <a href="#">Vaccination</a>
                <a href="#">Dental Care</a>
            </div>
            
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p>📍 Angeles City, Pampanga</p>
                <p>📞 (045) 123-4567</p>
                <p>📧 info@vetprecision.com</p>
                <p>🕒 Mon-Sat: 9AM-8PM</p>
                <p>🚑 24/7 Emergency Services</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 Vet Precision. All rights reserved. Made with ❤️ for pets and their families.</p>
        </div>
    </footer>

    <script>
        // Mobile menu functionality
        function toggleMobileMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('active');
        }

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
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.backdropFilter = 'blur(20px)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
            }
        });

        // Intersection Observer for animations
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

        // Observe elements with animation classes
        document.querySelectorAll('.animate-fadeInUp, .animate-fadeInLeft, .animate-fadeInRight').forEach(element => {
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

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navLinks = document.querySelector('.nav-links');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            
            if (!navLinks.contains(event.target) && !mobileToggle.contains(event.target)) {
                navLinks.classList.remove('active');
            }
        });

        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.review-slide');
        const dots = document.querySelectorAll('.dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current slide
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function changeSlide(direction) {
            currentSlideIndex += direction;
            
            // Loop around
            if (currentSlideIndex >= totalSlides) {
                currentSlideIndex = 0;
            }
            if (currentSlideIndex < 0) {
                currentSlideIndex = totalSlides - 1;
            }
            
            showSlide(currentSlideIndex);
        }

        function currentSlide(index) {
            currentSlideIndex = index;
            showSlide(currentSlideIndex);
        }
    </script>
</body>
</html>