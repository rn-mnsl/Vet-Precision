<?php
// includes/navbar.php - Top navigation bar
<<<<<<< HEAD
=======

if (isLoggedIn()) {
    checkAndSendAppointmentReminders(getCurrentUserId());
    $notification_count = getUnreadNotificationCount(getCurrentUserId());
    $recent_notifications = getRecentNotifications(getCurrentUserId());
}

>>>>>>> ad8f7097450c78a93915cbb3b75003ba7f431c9d
?>
<style>
* { 
    font: 'Inter', system-ui, sans-serif;
}
    /* Top Navigation Bar Styles */
.top-navbar {
    background: #ffffff;
    border-bottom: 1px solid #e9ecef;
    padding: 0.75rem 2rem;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1001;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
}

/* Logo Section */
.navbar-brand .brand-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.navbar-brand .brand-link:hover {
    color: #1DBAA8;
    text-decoration: none;
}

.brand-logo {
    width: 100px;
    height: 70px;
    object-fit: cover;
    margin-left: 40px; 
}

.brand-text {
    font-size: 1.5rem;
    font-weight: 600;
    color: inherit;
    padding-top: 15px;
    margin-left: -10px;
}

/* Right Side Actions */
.navbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.navbar-icon {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8f9fa;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.navbar-icon:hover {
    background: #e9ecef;
    color: #333;
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

<<<<<<< HEAD
=======
/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    right: 0;
    top: 48px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    width: 280px;
    padding: 0.5rem 0;
    display: none;
    z-index: 1002;
}

.notification-dropdown.show {
    display: block;
}

.notification-item {
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    border-bottom: 1px solid #f1f1f1;
}
.notification-item:last-child {
    border-bottom: none;
}
.no-notifications {
    padding: 0.75rem 1rem;
    text-align: center;
    font-size: 0.85rem;
    color: #6c757d;
}

>>>>>>> ad8f7097450c78a93915cbb3b75003ba7f431c9d
/* User Dropdown */
.user-dropdown {
    position: relative;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-profile:hover {
    background: #f8f9fa;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e90ff, #0066cc);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 1rem;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
    line-height: 1.2;
}

.user-email {
    font-size: 0.75rem;
    color: #6c757d;
    line-height: 1.2;
}

.dropdown-arrow {
    font-size: 0.7rem;
    color: #6c757d;
    margin-left: 0.5rem;
    transition: transform 0.3s ease;
}

.user-profile:hover .dropdown-arrow {
    transform: rotate(180deg);
}

/* Dropdown Menu */
.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 200px;
    padding: 0.5rem 0;
    margin-top: 0.5rem;
    display: none;
    z-index: 1002;
    animation: fadeIn 0.2s ease;
}

.dropdown-menu.show {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: #f8f9fa;
    color: #1DBAA8;
    text-decoration: none;
}

.dropdown-item .icon {
    font-size: 1rem;
    width: 1.2rem;
    text-align: center;
}

.dropdown-divider {
    height: 1px;
    background: #e9ecef;
    margin: 0.5rem 0;
}

/* Adjust body and layout for navbar */
body {
    padding-top: 70px; /* Account for fixed navbar */
}

/* Adjust sidebar positioning if it's also fixed */
.sidebar {
    top: 70px;
    height: calc(100vh - 70px);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .top-navbar {
        padding: 0.75rem 1rem;
    }
    
    .brand-text {
        font-size: 1.25rem;
    }
    
    .user-info {
        display: none;
    }
    
    .navbar-right {
        gap: 0.5rem;
    }
    
    .navbar-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .dropdown-menu {
        right: -1rem;
        min-width: 180px;
    }
}

@media (max-width: 480px) {
    .brand-logo {
        width: 32px;
        height: 32px;
    }
    
    .brand-text {
        font-size: 1.1rem;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        font-size: 0.8rem;
    }
}
</style>
<nav class="top-navbar">
    <!-- Logo Section -->
    <div class="navbar-brand">
        <a href="<?php echo SITE_URL; ?>/client/index.php" class="brand-link">
            <img src="<?php echo SITE_URL; ?>/assets/images/vet-precision-logo-full.png" alt="Vet Precision Logo" class="brand-logo">
        </a>
    </div>

    <!-- Right Side Actions -->
    <div class="navbar-right">
        <!-- Notification Bell -->
        <button class="navbar-icon" id="notificationBtn" title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><g><path stroke-dasharray="4" stroke-dashoffset="4" d="M12 3v2"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.2s" values="4;0"/></path><path stroke-dasharray="28" stroke-dashoffset="28" d="M12 5c-3.31 0 -6 2.69 -6 6l0 6c-1 0 -2 1 -2 2h8M12 5c3.31 0 6 2.69 6 6l0 6c1 0 2 1 2 2h-8"><animate fill="freeze" attributeName="stroke-dashoffset" begin="0.2s" dur="0.4s" values="28;0"/></path><animateTransform fill="freeze" attributeName="transform" begin="0.9s" dur="6s" keyTimes="0;0.05;0.15;0.2;1" type="rotate" values="0 12 3;3 12 3;-3 12 3;0 12 3;0 12 3"/></g><path stroke-dasharray="8" stroke-dashoffset="8" d="M10 20c0 1.1 0.9 2 2 2c1.1 0 2 -0.9 2 -2"><animate fill="freeze" attributeName="stroke-dashoffset" begin="0.6s" dur="0.2s" values="8;0"/><animateTransform fill="freeze" attributeName="transform" begin="1.1s" dur="6s" keyTimes="0;0.05;0.15;0.2;1" type="rotate" values="0 12 8;6 12 8;-6 12 8;0 12 8;0 12 8"/></path></g></svg>
            <?php if (isset($notification_count) && $notification_count > 0): ?>
                <span class="notification-badge"><?php echo $notification_count; ?></span>
            <?php endif; ?>
        </button>
<<<<<<< HEAD
=======

        <div class="notification-dropdown" id="notificationDropdown">
            <?php if (!empty($recent_notifications)): ?>
                <?php foreach ($recent_notifications as $note): ?>
                    <div class="notification-item"><?php echo sanitize($note['message']); ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">No notifications</div>
            <?php endif; ?>
        </div>
>>>>>>> ad8f7097450c78a93915cbb3b75003ba7f431c9d
 
        <!-- Settings -->
        <button class="navbar-icon" onclick="window.location.href='<?php echo SITE_URL; ?>/client/profile/index.php'" title="Settings">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon"><path fill="currentColor" d="M10.825 22q-.675 0-1.162-.45t-.588-1.1L8.85 18.8q-.325-.125-.612-.3t-.563-.375l-1.55.65q-.625.275-1.25.05t-.975-.8l-1.175-2.05q-.35-.575-.2-1.225t.675-1.075l1.325-1Q4.5 12.5 4.5 12.337v-.675q0-.162.025-.337l-1.325-1Q2.675 9.9 2.525 9.25t.2-1.225L3.9 5.975q.35-.575.975-.8t1.25.05l1.55.65q.275-.2.575-.375t.6-.3l.225-1.65q.1-.65.588-1.1T10.825 2h2.35q.675 0 1.163.45t.587 1.1l.225 1.65q.325.125.613.3t.562.375l1.55-.65q.625-.275 1.25-.05t.975.8l1.175 2.05q.35.575.2 1.225t-.675 1.075l-1.325 1q.025.175.025.338v.674q0 .163-.05.338l1.325 1q.525.425.675 1.075t-.2 1.225l-1.2 2.05q-.35.575-.975.8t-1.25-.05l-1.5-.65q-.275.2-.575.375t-.6.3l-.225 1.65q-.1.65-.587 1.1t-1.163.45zM11 20h1.975l.35-2.65q.775-.2 1.438-.587t1.212-.938l2.475 1.025l.975-1.7l-2.15-1.625q.125-.35.175-.737T17.5 12t-.05-.787t-.175-.738l2.15-1.625l-.975-1.7l-2.475 1.05q-.55-.575-1.212-.962t-1.438-.588L13 4h-1.975l-.35 2.65q-.775.2-1.437.588t-1.213.937L5.55 7.15l-.975 1.7l2.15 1.6q-.125.375-.175.75t-.05.8q0 .4.05.775t.175.75l-2.15 1.625l.975 1.7l2.475-1.05q.55.575 1.213.963t1.437.587zm1.05-4.5q1.45 0 2.475-1.025T15.55 12t-1.025-2.475T12.05 8.5q-1.475 0-2.487 1.025T8.55 12t1.013 2.475T12.05 15.5M12 12"/></svg>
        </button>

        <!-- User Profile Dropdown -->
        <div class="user-dropdown">
            <div class="user-profile" onclick="toggleUserDropdown()">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                </div>
                <span class="dropdown-arrow">▼</span>
            </div>
            
            <!-- Dropdown Menu -->
            <div class="dropdown-menu" id="userDropdown">
                <div class="user-info">
                    <span class="user-name"><?php echo sanitize($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                    <span class="user-email"><?php echo sanitize($_SESSION['email']); ?></span>
                </div>
                <a href="<?php echo SITE_URL; ?>/client/index.php" class="dropdown-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon"><path fill="currentColor" d="M6 19h3v-5q0-.425.288-.712T10 13h4q.425 0 .713.288T15 14v5h3v-9l-6-4.5L6 10zm-2 0v-9q0-.475.213-.9t.587-.7l6-4.5q.525-.4 1.2-.4t1.2.4l6 4.5q.375.275.588.7T20 10v9q0 .825-.588 1.413T18 21h-4q-.425 0-.712-.288T13 20v-5h-2v5q0 .425-.288.713T10 21H6q-.825 0-1.412-.587T4 19m8-6.75"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/client/profile/index.php" class="dropdown-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon"><path fill="currentColor" fill-rule="evenodd" d="M8 7a4 4 0 1 1 8 0a4 4 0 0 1-8 0m0 6a5 5 0 0 0-5 5a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3a5 5 0 0 0-5-5z" clip-rule="evenodd"/></svg>
                    <span>Profile</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="dropdown-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon"><g fill="none"><path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M12 2.5a1.5 1.5 0 0 1 0 3H7a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h4.5a1.5 1.5 0 0 1 0 3H7A3.5 3.5 0 0 1 3.5 18V6A3.5 3.5 0 0 1 7 2.5Zm6.06 5.61l2.829 2.83a1.5 1.5 0 0 1 0 2.12l-2.828 2.83a1.5 1.5 0 1 1-2.122-2.122l.268-.268H12a1.5 1.5 0 0 1 0-3h4.207l-.268-.268a1.5 1.5 0 1 1 2.122-2.121Z"/></g></svg>
                    <span>Log out</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// Toggle user dropdown
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userDropdown = document.querySelector('.user-dropdown');
    const dropdown = document.getElementById('userDropdown');
    
    if (!userDropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

<<<<<<< HEAD
// Notification button functionality
document.getElementById('notificationBtn').addEventListener('click', function() {
    // Add your notification logic here
    alert('Notifications feature coming soon!');
=======
const notifBtn = document.getElementById('notificationBtn');
const notifDropdown = document.getElementById('notificationDropdown');
notifBtn.addEventListener('click', function(event) {
    event.stopPropagation();
    notifDropdown.classList.toggle('show');

    if (notifDropdown.classList.contains('show')) {
        fetch('<?php echo SITE_URL; ?>/client/notifications/mark_read.php')
            .then(() => {
                const badge = document.querySelector('.notification-badge');
                if (badge) badge.remove();
            });
    }
});

document.addEventListener('click', function(e) {
    if (!notifDropdown.contains(e.target) && e.target !== notifBtn) {
        notifDropdown.classList.remove('show');
    }
>>>>>>> ad8f7097450c78a93915cbb3b75003ba7f431c9d
});
</script>