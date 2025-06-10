<?php
// includes/navbar.php - Top navigation bar for non-dashboard pages
?>
<nav class="navbar">
    <div class="navbar-container">
        <a href="<?php echo SITE_URL; ?>" class="logo">
            <span class="logo-icon">🐾</span>
            Vet Precision
        </a>
        
        <?php if (isLoggedIn()): ?>
            <ul class="nav-links">
                <?php if (isStaff()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/staff/index.php">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/staff/appointments/index.php">Appointments</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/staff/pets/index.php">Pets</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/staff/owners/index.php">Owners</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/staff/medical/index.php">Medical Records</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/client/index.php">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/client/pets/index.php">My Pets</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/client/appointments/index.php">Appointments</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/client/profile/index.php">My Profile</a></li>
                <?php endif; ?>
                
                <li class="nav-user">
                    <span><?php echo sanitize($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-sm btn-outline">Logout</a>
                </li>
            </ul>
        <?php else: ?>
            <ul class="nav-links">
                <li><a href="<?php echo SITE_URL; ?>/#services">Services</a></li>
                <li><a href="<?php echo SITE_URL; ?>/#about">About</a></li>
                <li><a href="<?php echo SITE_URL; ?>/#contact">Contact</a></li>
                <li><a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-secondary btn-sm">Login</a></li>
                <li><a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary btn-sm">Book Now</a></li>
            </ul>
        <?php endif; ?>
        
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('active');
}
</script>
