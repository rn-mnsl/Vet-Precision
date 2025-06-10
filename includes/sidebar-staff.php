<?php
// includes/sidebar-staff.php - Staff sidebar navigation
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo SITE_URL; ?>/staff/index.php" class="sidebar-logo">
            <span>🐾</span>
            <span>Vet Precision</span>
        </a>
        <div class="sidebar-user">
            Staff Portal
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/staff/') !== false ? 'active' : ''; ?>">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/appointments/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/staff/appointments/') !== false ? 'active' : ''; ?>">
                <span class="icon">📅</span>
                <span>Appointments</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/pets/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/staff/pets/') !== false ? 'active' : ''; ?>">
                <span class="icon">🐕</span>
                <span>Pets</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/owners/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/staff/owners/') !== false ? 'active' : ''; ?>">
                <span class="icon">👥</span>
                <span>Owners</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/medical/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/staff/medical/') !== false ? 'active' : ''; ?>">
                <span class="icon">📋</span>
                <span>Medical Records</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/reports/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/staff/reports/') !== false ? 'active' : ''; ?>">
                <span class="icon">📈</span>
                <span>Reports</span>
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