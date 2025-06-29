<?php
// Function to get the active page for staff/admin
function getActivePage() {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Debug - you can remove this later
    // echo "<!-- Debug: Current page: $currentPage, URI: $requestUri -->";
    
    // Dashboard
    if ($currentPage === 'index.php' && strpos($requestUri, '/staff/') !== false && 
        !strpos($requestUri, '/staff/appointments/') && 
        !strpos($requestUri, '/staff/pets/') && 
        !strpos($requestUri, '/staff/medical/') && 
        !strpos($requestUri, '/staff/owners/') && 
        !strpos($requestUri, '/staff/reports/') && 
        !strpos($requestUri, '/staff/profile/')) {
        return 'dashboard';
    }
    // Appointments
    elseif (strpos($requestUri, '/staff/appointments/') !== false) {
        return 'appointments';
    }
    // Patients/Pets
    elseif (strpos($requestUri, '/staff/pets/') !== false) {
        return 'pets';
    }
    // Medical Records
    elseif (strpos($requestUri, '/staff/medical/') !== false) {
        return 'medical';
    }
    // Owners
    elseif (strpos($requestUri, '/staff/owners/') !== false) {
        return 'owners';
    }
    // Reports
    elseif (strpos($requestUri, '/staff/reports/') !== false) {
        return 'reports';
    }
    // Profile
    elseif (strpos($requestUri, '/staff/profile/') !== false) {
        return 'profile';
    }
    
    return '';
}

$activePage = getActivePage();
?>

<style>
:root {
    --primary-color: #1DBAA8;
    --dark-color: #2c3e50;
    --light-color: #f8f9fa;
    --border-color: #e9ecef;
    --text-color: #333;
    --text-muted: #6c757d;
    --white: #ffffff;
    --shadow: 0 2px 4px rgba(0,0,0,0.1);
    --transition-base: 0.3s ease;
}

* { 
    font-family: 'Inter', system-ui, sans-serif;
}

/* Sidebar */
.sidebar {
    background: var(--white);
    border-right: 1px solid var(--border-color);
    padding: 2rem 0;
    width: 250px;
    min-width: 250px;
    height: calc(100vh - 70px);
    position: fixed;
    left: 0;
    top: 70px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: -10px;
}

.sidebar-menu {
    list-style: none;
    padding: 0.5rem 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 0.75rem;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 1.5rem;
    color: var(--dark-color);
    text-decoration: none;
    transition: all var(--transition-base);
    font-weight: 600;
    border-radius: 0;
    margin-right: 0;
}

.sidebar-menu a:hover {
    background: rgba(29, 186, 168, 0.1);
    color: var(--primary-color);
}

.sidebar-menu a.active {
    background: var(--primary-color);
    color: white !important;
    border-radius: 0 25px 25px 0;
    margin-right: 0.5rem;
}

.sidebar-menu .icon {
    font-size: 1.25rem;
    width: 1.5rem;
    text-align: center;
}


</style>

<aside class="sidebar">    
    <ul class="sidebar-menu">
        <!-- Dashboard -->
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/index.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon">
                    <path fill="currentColor" d="M6 19h3v-5q0-.425.288-.712T10 13h4q.425 0 .713.288T15 14v5h3v-9l-6-4.5L6 10zm-2 0v-9q0-.475.213-.9t.587-.7l6-4.5q.525-.4 1.2-.4t1.2.4l6 4.5q.375.275.588.7T20 10v9q0 .825-.588 1.413T18 21h-4q-.425 0-.712-.288T13 20v-5h-2v5q0 .425-.288.713T10 21H6q-.825 0-1.412-.587T4 19m8-6.75"/>
                </svg>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Appointments -->
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/appointments/index.php" class="<?php echo $activePage === 'appointments' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon">
                    <path fill="currentColor" d="M19 19H5V8h14m-3-7v2H8V1H6v2H5c-1.11 0-2 .89-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-1V1m-1 11h-5v5h5z"/>
                </svg>
                <span>Appointments</span>
            </a>
        </li>

        <!-- Pets-->
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/pets/index.php" class="<?php echo $activePage === 'pets' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" class="icon">
                    <path fill="currentColor" d="M11.9 8.4c1.3 0 2.1-1.9 2.1-3.1c0-1-.5-2.2-1.5-2.2c-1.3 0-2.1 1.9-2.1 3.1c0 1 .5 2.2 1.5 2.2m-3.8 0c1 0 1.5-1.2 1.5-2.2C9.6 4.9 8.8 3 7.5 3C6.5 3 6 4.2 6 5.2c-.1 1.3.7 3.2 2.1 3.2m7.4-1c-1.3 0-2.2 1.8-2.2 3.1c0 .9.4 1.8 1.3 1.8c1.3 0 2.2-1.8 2.2-3.1c0-.9-.5-1.8-1.3-1.8m-8.7 3.1c0-1.3-1-3.1-2.2-3.1c-.9 0-1.3.9-1.3 1.8c0 1.3 1 3.1 2.2 3.1c.9 0 1.3-.9 1.3-1.8m3.2-.2c-2 0-4.7 3.2-4.7 5.4c0 1 .7 1.3 1.5 1.3c1.2 0 2.1-.8 3.2-.8c1 0 1.9.8 3 .8c.8 0 1.7-.2 1.7-1.3c0-2.2-2.7-5.4-4.7-5.4"/>
                </svg>
                <span>Patients</span>
            </a>
        </li>

        <!-- Medical Records -->
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/medical/index.php" class="<?php echo $activePage === 'medical' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon">
                    <path fill="currentColor" d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2m-7 0a1 1 0 0 1 1 1a1 1 0 0 1-1 1a1 1 0 0 1-1-1a1 1 0 0 1 1-1M7 7h10V5h2v14H5V5h2z"/>
                </svg>
                <span>Medical Records</span>
            </a>
        </li>

        <!-- Owners -->
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/owners/index.php" class="<?php echo $activePage === 'owners' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon">
                    <path fill="currentColor" d="M16 4c0-1.11.89-2 2-2s2 .89 2 2s-.89 2-2 2s-2-.89-2-2M4 18v-4.5c0-1.1.9-2 2-2s2 .9 2 2V18h2v-5.5c0-1.1.9-2 2-2s2 .9 2 2V18h2v-6h-3l1.5-1.5C13.6 9.4 14.6 9 15.7 9H18c1.1 0 2 .9 2 2v7h2v2H2v-2z"/>
                </svg>
                <span>Owners</span>
            </a>
        </li>

        <!-- Reports -->
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/reports/index.php" class="<?php echo $activePage === 'reports' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon">
                    <path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm4 18H6V4h7v5h5zm-6.5-1H17v-2h-5.5zm0-4H17v-2h-5.5zM7 13h10v-2H7zm0-4h7V7H7z"/>
                </svg>
                <span>Reports</span>
            </a>
        </li>

        <!-- Profile -->
        <li>
            <a href="<?php echo SITE_URL; ?>/staff/profile/index.php" class="<?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon">
                    <path fill="currentColor" fill-rule="evenodd" d="M8 7a4 4 0 1 1 8 0a4 4 0 0 1-8 0m0 6a5 5 0 0 0-5 5a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3a5 5 0 0 0-5-5z" clip-rule="evenodd"/>
                </svg>
                <span>My Profile</span>
            </a>
        </li>

        <!-- Logout -->
        <li>
            <a href="<?php echo SITE_URL; ?>/logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon">
                    <g fill="none">
                        <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/>
                        <path fill="currentColor" d="M12 2.5a1.5 1.5 0 0 1 0 3H7a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h4.5a1.5 1.5 0 0 1 0 3H7A3.5 3.5 0 0 1 3.5 18V6A3.5 3.5 0 0 1 7 2.5Zm6.06 5.61l2.829 2.83a1.5 1.5 0 0 1 0 2.12l-2.828 2.83a1.5 1.5 0 1 1-2.122-2.122l.268-.268H12a1.5 1.5 0 0 1 0-3h4.207l-.268-.268a1.5 1.5 0 1 1 2.122-2.121Z"/>
                    </g>
                </svg>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>
