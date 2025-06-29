<?php
require_once '../config/init.php';
requireStaff();

$pageTitle = 'Staff Dashboard - ' . SITE_NAME;

// Get dashboard statistics
$stats = [];

// Total pets
$stmt = $pdo->query("SELECT COUNT(*) as count FROM pets WHERE is_active = 1");
$stats['total_pets'] = $stmt->fetch()['count'];

// Today's appointments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM appointments 
    WHERE appointment_date = CURDATE() 
    AND status != 'cancelled'
");
$stmt->execute();
$stats['today_appointments'] = $stmt->fetch()['count'];

// Pending appointments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'requested'");
$stats['pending_appointments'] = $stmt->fetch()['count'];

// Total clients
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'client' AND is_active = 1");
$stats['total_clients'] = $stmt->fetch()['count'];

// --- NEW: PAGINATION LOGIC FOR UPCOMING APPOINTMENTS ---
$appointments_per_page = 5; // How many appointments to show per page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $appointments_per_page;

// First, get the total number of upcoming appointments for pagination controls
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date >= CURDATE() AND status != 'cancelled'");
$stmt_count->execute();
$total_appointments = $stmt_count->fetchColumn();
$total_pages = ceil($total_appointments / $appointments_per_page);

// --- MODIFIED: Get paginated upcoming appointments ---
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        p.name as pet_name,
        p.species,
        CONCAT(u.first_name, ' ', u.last_name) as owner_name,
        o.phone as owner_phone
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN owners o ON p.owner_id = o.owner_id
    JOIN users u ON o.user_id = u.user_id
    WHERE a.appointment_date >= CURDATE()
    AND a.status != 'cancelled'
    ORDER BY a.appointment_date, a.appointment_time
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $appointments_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$upcoming_appointments = $stmt->fetchAll();

// Get recent activities
$stmt = $pdo->prepare("
    SELECT 
        'appointment' as type,
        CONCAT('New appointment for ', p.name) as description,
        a.created_at as timestamp
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE DATE(a.created_at) = CURDATE()
    
    UNION ALL
    
    SELECT 
        'pet' as type,
        CONCAT('New pet registered: ', name) as description,
        created_at as timestamp
    FROM pets
    WHERE DATE(created_at) = CURDATE()
    
    ORDER BY timestamp DESC
    LIMIT 5
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Helper function for time ago
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return $minutes == 1 ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return $hours == 1 ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return $days == 1 ? "Yesterday" : "$days days ago";
    } else {
        return date('M j, Y', $time_ago);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php include '../includes/favicon.php'; ?>
   <style>
        /* Dashboard specific styles */
        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar */
        .sidebar {
            background: var(--dark-color);
            color: white;
            padding: 2rem 0;
            width: 250px;
            min-width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .sidebar-logo:hover {
            color: white;
            text-decoration: none;
        }

        .sidebar-user {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 0.25rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all var(--transition-base);
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--primary-color);
        }

        .sidebar-menu .icon {
            font-size: 1.25rem;
            width: 1.5rem;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            flex: 1;
            width: calc(100% - 250px);
            min-height: 100vh;
            background-color: var(--light-color);
        }

        .welcome-header {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .welcome-header h1 {
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 2rem;
        }

        .welcome-header p {
            color: var(--text-light);
            margin: 0;
            font-size: 1rem;
        }

        /* Statistics Grid - FIXED */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all var(--transition-base);
            border: 1px solid var(--gray-light);
            min-height: 120px;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            font-size: 3rem;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-color);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .stat-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Quick Actions - FIXED */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }

        .quick-action-btn {
            background: white;
            border: 2px solid var(--gray-light);
            padding: 2rem 1.5rem;
            border-radius: var(--radius-lg);
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            transition: all var(--transition-base);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            min-height: 140px;
        }

        .quick-action-btn:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
            color: var(--text-dark);
        }

        .quick-action-btn .icon {
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        .quick-action-btn span {
            font-weight: 600;
            font-size: 1rem;
        }

        /* Dashboard Grid - FIXED */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            width: 100%;
            align-items: start;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
        }

        /* Appointments Table */
        .appointments-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            width: 100%;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            color: var(--text-dark);
            font-size: 1.25rem;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table th {
            background: var(--light-color);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .appointments-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: top;
        }

        .appointments-table tr:hover {
            background: var(--light-color);
        }

        /* --- MODIFICATION: Make the table wrapper overflow for large screens if needed --- */
        .appointments-table-wrapper {
            overflow-x: auto;
        }
        /* --- NEW: STYLES FOR THE RESPONSIVE APPOINTMENT LIST --- */
        .appointments-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .appointment-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            gap: 1.5rem;
            flex-wrap: wrap; /* Allows wrapping on small screens */
        }
        .appointment-item:last-child {
            border-bottom: none;
        }
        .appointment-item:hover {
            background: var(--light-color);
        }
        .appointment-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(78, 205, 196, 0.1);
            color: var(--secondary-color);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            min-width: 80px;
            text-align: center;
        }
        .appointment-date .day {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
        }
        .appointment-date .month {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        .appointment-details {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* 2 columns for details */
            gap: 0.5rem 1.5rem;
            min-width: 250px; /* Prevents squishing */
        }
        .appointment-details .detail-label {
            font-size: 0.75rem;
            color: var(--text-light);
            text-transform: uppercase;
        }
        .appointment-details .detail-value {
            font-weight: 600;
            color: var(--text-dark);
        }
        .appointment-actions {
            margin-left: auto;
        }

        /* --- NEW: PAGINATION STYLES --- */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }
        .pagination a, .pagination span {
            padding: 0.6rem 1rem;
            margin: 0 0.25rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-base);
        }
        .pagination a {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--gray-light);
        }
        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .pagination span.current {
            background: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }
        .pagination span.disabled {
            color: #aaa;
            background-color: var(--light-color);
            cursor: not-allowed;
            border: 1px solid var(--gray-light);
        }

        .pet-info {
            display: flex;
            flex-direction: column;
        }

        .pet-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .pet-species {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        /* Activity Feed */
        .activity-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            width: 100%;
        }

        .activity-list {
            padding: 0;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.appointment {
            background: rgba(78, 205, 196, 0.1);
            color: var(--secondary-color);
        }

        .activity-icon.pet {
            background: rgba(255, 107, 107, 0.1);
            color: var(--primary-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-description {
            font-size: 0.875rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        /* Utility classes */
        .mb-4 {
            margin-bottom: 2rem;
        }

        .p-4 {
            padding: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .text-primary {
            color: var(--primary-color) !important;
            text-decoration: none;
        }

        .text-primary:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .text-dark {
            color: var(--text-dark) !important;
        }

        .text-muted {
            color: var(--text-light) !important;
        }

        .small {
            font-size: 0.875rem;
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-requested {
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
        }

        .status-confirmed {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
        }

        .status-completed {
            background: rgba(23, 162, 184, 0.1);
            color: #0c5460;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
        }

        /* Button styles */
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all var(--transition-base);
            border: none;
            cursor: pointer;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        /* .btn-primary:hover {
            background: var(--primary-dark);
            color: black;
            text-decoration: none;
        } */

        /* MODIFICATION START: Added styles for secondary button */
        .btn-secondary {
            background-color: #f8f9fa; /* Light gray background */
            color: #343a40; /* Dark text */
            border: 1px solid #dee2e6; /* Gray border */
        }

        .btn-secondary:hover {
            background-color: #dc3545; /* Red background on hover */
            color: #fff; /* White text on hover */
            border-color: #dc3545; /* Red border on hover */
        }
        /* MODIFICATION END */

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1050; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.6); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 10% from the top and centered */
            border-radius: var(--radius-lg);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 80%;
            max-width: 700px;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-dark);
        }

        .close-modal {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            background-color: var(--light-color);
            border-top: 1px solid var(--gray-light);
            text-align: right;
            border-bottom-left-radius: var(--radius-lg);
            border-bottom-right-radius: var(--radius-lg);
        }

        .modal-footer .btn {
            margin-left: 0.5rem;
        }

        /* --- NEW: Modal Edit Form Styles --- */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* 2 columns by default for desktop */
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group-full {
            grid-column: 1 / -1; /* This class makes an item span both columns */
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 1rem;
        }

        /* --- NEW: Alert message style for the modal form --- */
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border: 1px solid #f5c6cb;
            border-radius: var(--radius-sm);
        }

        /* Detail grid inside modal */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .detail-group {
            margin-bottom: 1rem;
        }
        .detail-group h4 {
            font-size: 1rem;
            color: var(--primary-color);
            border-bottom: 2px solid var(--light-color);
            padding-bottom: 0.5rem;
            margin-bottom: 0.75rem;
        }
        .detail-item {
            margin-bottom: 0.5rem;
        }
        .detail-label {
            font-weight: 600;
            color: var(--text-light);
            display: block;
            font-size: 0.8rem;
            margin-bottom: 2px;
        }
        .detail-value {
            color: var(--text-dark);
        }

        /* Mobile Responsive */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; z-index: 1100; position: fixed; top: 0; height: 100vh; margin-top: 0; }
            .main-content { margin-left: 0; }
            body.sidebar-is-open .sidebar { transform: translateX(0); box-shadow: 0 0 20px rgba(0,0,0,0.25); }
            body.sidebar-is-open .sidebar-overlay { opacity: 1; visibility: visible; }
            .main-content { padding-top: 85px; } /* Space for fixed navbar */
        }

        @media (max-width: 768px) {
            .dashboard-layout {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform var(--transition-base);
                position: fixed;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
                min-height: 100px;
            }

            .stat-icon {
                font-size: 2rem;
                width: 50px;
                height: 50px;
            }

            .stat-number {
                font-size: 1.75rem;
            }

            .quick-actions {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .quick-action-btn {
                padding: 1.5rem 1rem;
                min-height: 120px;
            }

            .welcome-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1.5rem;
            }

            .welcome-header h1 {
                font-size: 1.5rem;
            }

            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                background: var(--primary-color);
                color: white;
                border: none;
                padding: 0.75rem;
                border-radius: var(--radius-sm);
                cursor: pointer;
                font-size: 1.25rem;
            }

            .appointment-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .appointment-details {
                grid-template-columns: 1fr; /* Stack details in a single column */
                width: 100%;
            }
            .appointment-actions {
                margin-left: 0;
                width: 100%;
            }
            .appointment-actions .btn {
                width: 100%;
            }
            .form-grid {
                grid-template-columns: 1fr; /* FORCES a single column layout on mobile */
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../includes/sidebar-staff.php'; ?>
        <?php include '../includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Header -->
            <div class="welcome-header">
                <div>
                    <h1>Welcome back, <?php echo sanitize($_SESSION['first_name']); ?>!</h1>
                    <p>Here's what's happening at the clinic today</p>
                </div>
                <div>
                    <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['today_appointments']; ?></div>
                        <div class="stat-label">Today's Appointments</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['pending_appointments']; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🐾</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_pets']; ?></div>
                        <div class="stat-label">Total Pets</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_clients']; ?></div>
                        <div class="stat-label">Active Clients</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="appointments/create.php" class="quick-action-btn">
                    <span class="icon">➕</span>
                    <span>New Appointment</span>
                </a>
                <a href="pets/create.php" class="quick-action-btn">
                    <span class="icon">🐕</span>
                    <span>Add Pet</span>
                </a>
                <a href="owners/create.php" class="quick-action-btn">
                    <span class="icon">👤</span>
                    <span>Add Owner</span>
                </a>
                <a href="medical/create.php" class="quick-action-btn">
                    <span class="icon">📝</span>
                    <span>Medical Record</span>
                </a>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Upcoming Appointments -->
                <div class="appointments-card">
                    <div class="card-header">
                        <h3>Upcoming Appointments</h3>
                        <a href="appointments/index.php" class="text-primary">View All →</a>
                    </div>
                    <?php if (empty($upcoming_appointments)): ?>
                        <div class="p-4 text-center text-muted">
                            <p>No upcoming appointments</p>
                        </div>
                    <?php else: ?>
                        <!-- MODIFIED: Replaced table with a responsive list -->
                        <div class="appointments-list">
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <div class="appointment-item">
                                    <div class="appointment-date">
                                        <span class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></span>
                                    </div>
                                    <div class="appointment-details">
                                        <div>
                                            <div class="detail-label">Pet</div>
                                            <div class="detail-value"><?php echo sanitize($appointment['pet_name']); ?></div>
                                        </div>
                                        <div>
                                            <div class="detail-label">Owner</div>
                                            <div class="detail-value"><?php echo sanitize($appointment['owner_name']); ?></div>
                                        </div>
                                        <div>
                                            <div class="detail-label">Time</div>
                                            <div class="detail-value"><?php echo formatTime($appointment['appointment_time']); ?></div>
                                        </div>
                                        <div>
                                            <div class="detail-label">Status</div>
                                            <div class="detail-value">
                                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="appointment-actions">
                                        <button 
                                            class="btn btn-sm btn-primary view-appointment-btn" 
                                            data-id="<?php echo $appointment['appointment_id']; ?>">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- NEW: Pagination Controls -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?>">Prev</a>
                            <?php else: ?>
                                <span class="disabled">Prev</span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?>">Next</a>
                            <?php else: ?>
                                <span class="disabled">Next</span>
                            <?php endif; ?>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Activity Feed -->
                <div class="activity-card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="activity-list">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center text-muted p-4">
                                <p>No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php echo $activity['type']; ?>">
                                        <?php echo $activity['type'] == 'appointment' ? '📅' : '🐾'; ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-description">
                                            <?php echo sanitize($activity['description']); ?>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo timeAgo($activity['timestamp']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <!-- Appointment Details Modal -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Appointment Details</h3>
                <span class="close-modal close-modal-btn">×</span>
            </div>
            <form id="editAppointmentForm">
                <div class="modal-body" id="modalBody">
                    <p>Loading details...</p>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="modalAppointmentId" name="appointment_id">
                    <button type="button" id="editModeBtn" class="btn btn-primary">Edit</button>
                    <button type="submit" id="saveChangesBtn" class="btn btn-primary" style="display:none;">Save Changes</button>
                    <button type="button" id="cancelEditBtn" class="btn btn-secondary" style="display:none;">Cancel</button>
                    <button type="button" class="btn btn-secondary close-modal-btn">Close</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const modal = document.getElementById('appointmentModal');
            const modalBody = document.getElementById('modalBody');
            const modalTitle = document.getElementById('modalTitle');
            const modalForm = document.getElementById('editAppointmentForm');
            const modalAppointmentIdInput = document.getElementById('modalAppointmentId');
            
            // Buttons
            const editModeBtn = document.getElementById('editModeBtn');
            const saveChangesBtn = document.getElementById('saveChangesBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const closeButtons = document.querySelectorAll('.close-modal-btn');

            let currentAppointmentDetails = null;

            // --- Helper to render VIEW mode ---
            function renderViewMode(details) {
                const apptDate = new Date(details.appointment_date + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                const apptTime = new Date('1970-01-01T' + details.appointment_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

                modalTitle.textContent = `Appointment for ${details.pet_name || 'N/A'}`;
                modalBody.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-group">
                            <h4>Appointment Info</h4>
                            <div class="detail-item"><span class="detail-label">Date & Time</span> <span class="detail-value">${apptDate} at ${apptTime}</span></div>
                            <div class="detail-item"><span class="detail-label">Status</span> <span class="detail-value status-badge status-${details.status}">${details.status}</span></div>
                            <div class="detail-item"><span class="detail-label">Type</span> <span class="detail-value">${details.type || 'N/A'}</span></div>
                            <div class="detail-item"><span class="detail-label">Reason</span> <span class="detail-value">${details.reason || 'N/A'}</span></div>
                            <div class="detail-item"><span class="detail-label">Staff Notes</span> <span class="detail-value">${details.notes || 'None'}</span></div>
                        </div>
                        <div class="detail-group">
                            <h4>Pet & Owner</h4>
                            <div class="detail-item"><span class="detail-label">Pet Name</span> <span class="detail-value">${details.pet_name || 'N/A'}</span></div>
                            <div class="detail-item"><span class="detail-label">Owner Name</span> <span class="detail-value">${details.owner_name || 'N/A'}</span></div>
                            <div class="detail-item"><span class="detail-label">Owner Phone</span> <span class="detail-value">${details.owner_phone || 'N/A'}</span></div>
                        </div>
                    </div>`;

                editModeBtn.style.display = 'inline-block';
                saveChangesBtn.style.display = 'none';
                cancelEditBtn.style.display = 'none';
            }

            // --- Helper to render EDIT form ---
            function renderEditForm(details) {
                modalTitle.textContent = `Editing Appointment for ${details.pet_name || 'N/A'}`;
                
                const statusOptions = ['requested', 'confirmed', 'completed', 'cancelled'];
                const typeOptions = ['Checkup', 'Vaccination', 'Grooming', 'Surgery', 'Consultation', 'Emergency', 'Testing visit'];
                
                const generateOptions = (options, selectedValue) => 
                    options.map(opt => `<option value="${opt}" ${opt.toLowerCase() === selectedValue.toLowerCase() ? 'selected' : ''}>${opt.charAt(0).toUpperCase() + opt.slice(1)}</option>`).join('');

                modalBody.innerHTML = `
                    <div id="modal-error-message" class="alert-danger" style="display:none; margin-bottom: 1rem;"></div>
                    <div class="form-grid">
                        <div class="form-group"><label for="appointment_date">Date</label><input type="date" id="appointment_date" name="appointment_date" class="form-control" value="${details.appointment_date || ''}" required></div>
                        <div class="form-group"><label for="appointment_time">Time</label><input type="time" id="appointment_time" name="appointment_time" class="form-control" value="${details.appointment_time || ''}" required></div>
                        <div class="form-group"><label for="status">Status</label><select id="status" name="status" class="form-control" required>${generateOptions(statusOptions, details.status)}</select></div>
                        <div class="form-group form-group-full"><label for="type">Type</label><select id="type" name="type" class="form-control" required>${generateOptions(typeOptions, details.type)}</select></div>
                        <div class="form-group form-group-full"><label for="reason">Reason for Visit</label><input type="text" id="reason" name="reason" class="form-control" value="${details.reason || ''}"></div>
                        <div class="form-group form-group-full"><label for="notes">Staff Notes</label><textarea id="notes" name="notes" class="form-control" rows="3">${details.notes || ''}</textarea></div>
                    </div>
                `;

                editModeBtn.style.display = 'none';
                saveChangesBtn.style.display = 'inline-block';
                cancelEditBtn.style.display = 'inline-block';
            }

            // --- Modal Control ---
            function openModal(appointmentId) {
                if (!appointmentId) return;
                modalBody.innerHTML = '<p>Loading details...</p>';
                modal.style.display = 'block';
                fetch(`ajax/get_appointment_details.php?id=${appointmentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentAppointmentDetails = data.details;
                            modalAppointmentIdInput.value = currentAppointmentDetails.appointment_id;
                            renderViewMode(currentAppointmentDetails);
                        } else {
                            modalBody.innerHTML = `<p class="alert-danger">${data.message || 'Could not load details.'}</p>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching details:', error);
                        modalBody.innerHTML = '<p class="alert-danger">An error occurred.</p>';
                    });
            }

            function closeModal() {
                modal.style.display = 'none';
                currentAppointmentDetails = null;
            }

            // --- Event Listeners ---
            document.querySelector('.appointments-list').addEventListener('click', e => {
                if (e.target && e.target.classList.contains('view-appointment-btn')) {
                    openModal(e.target.dataset.id);
                }
            });
            
            editModeBtn.addEventListener('click', () => {
                if (currentAppointmentDetails) renderEditForm(currentAppointmentDetails);
            });

            cancelEditBtn.addEventListener('click', () => {
                if (currentAppointmentDetails) renderViewMode(currentAppointmentDetails);
            });

            modalForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveChangesBtn.disabled = true;
                saveChangesBtn.textContent = 'Saving...';
                const errorMessageDiv = document.getElementById('modal-error-message');
                if(errorMessageDiv) errorMessageDiv.style.display = 'none';

                fetch('ajax/update_appointment.php', { method: 'POST', body: new FormData(modalForm) })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Easiest way to show updated data
                    } else {
                        errorMessageDiv.textContent = data.message || 'An unknown error occurred.';
                        errorMessageDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error updating:', error);
                    errorMessageDiv.textContent = 'A network error occurred.';
                    errorMessageDiv.style.display = 'block';
                })
                .finally(() => {
                    saveChangesBtn.disabled = false;
                    saveChangesBtn.textContent = 'Save Changes';
                });
            });

            closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
            window.addEventListener('click', e => { if (e.target == modal) closeModal(); });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.querySelector('.hamburger-menu');
            const overlay = document.querySelector('.sidebar-overlay');
            const body = document.body;

            if (hamburgerBtn && body) {
                hamburgerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    body.classList.toggle('sidebar-is-open');
                });
            }
            
            if (overlay && body) {
                overlay.addEventListener('click', function() {
                    body.classList.remove('sidebar-is-open');
                });
            }
        });
    </script>
</body>
</html>