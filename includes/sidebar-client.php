<?php
// includes/sidebar-client.php - Client sidebar navigation
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo SITE_URL; ?>/client/index.php" class="sidebar-logo">
            <span>🐾</span>
            <span>Vet Precision</span>
        </a>
        <div class="sidebar-user">
            Welcome, <?php echo sanitize($_SESSION['first_name']); ?>!
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo SITE_URL; ?>/client/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/client/') !== false ? 'active' : ''; ?>">
                <span class="icon">🏠</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/client/pets/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/client/pets/') !== false ? 'active' : ''; ?>">
                <span class="icon">🐾</span>
                <span>My Pets</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/client/appointments/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/client/appointments/') !== false ? 'active' : ''; ?>">
                <span class="icon">📅</span>
                <span>Appointments</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/client/medical/history.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/client/medical/') !== false ? 'active' : ''; ?>">
                <span class="icon">📋</span>
                <span>Medical History</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/client/profile/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/client/profile/') !== false ? 'active' : ''; ?>">
                <span class="icon">👤</span>
                <span>My Profile</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/logout.php">
                <span class="icon">🚪</span>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">
    ☰
</button>