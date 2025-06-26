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

// Get upcoming appointments
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
    LIMIT 10
");
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

        .btn-primary:hover {
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
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
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Pet</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="text-dark"><?php echo formatDate($appointment['appointment_date']); ?></div>
                                                <div class="text-muted small"><?php echo formatTime($appointment['appointment_time']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="pet-info">
                                                <span class="pet-name"><?php echo sanitize($appointment['pet_name']); ?></span>
                                                <span class="pet-species"><?php echo sanitize($appointment['species']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div><?php echo sanitize($appointment['owner_name']); ?></div>
                                                <div class="text-muted small"><?php echo sanitize($appointment['owner_phone']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="appointments/view.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                               class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
</body>
</html>