<?php
require_once '../config/init.php';
requireClient();

$pageTitle = 'My Dashboard - ' . SITE_NAME;

// Get client's pets
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM appointments 
            WHERE pet_id = p.pet_id 
            AND appointment_date >= CURDATE() 
            AND status != 'cancelled') as upcoming_appointments,
           (SELECT MAX(visit_date) FROM medical_records 
            WHERE pet_id = p.pet_id) as last_visit
    FROM pets p
    WHERE p.owner_id = :owner_id
    AND p.is_active = 1
    ORDER BY p.name
");
$stmt->execute(['owner_id' => $_SESSION['owner_id']]);
$pets = $stmt->fetchAll();

// Get upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, p.name as pet_name, p.species
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE p.owner_id = :owner_id
    AND a.appointment_date >= CURDATE()
    AND a.status != 'cancelled'
    ORDER BY a.appointment_date, a.appointment_time
    LIMIT 5
");
$stmt->execute(['owner_id' => $_SESSION['owner_id']]);
$upcoming_appointments = $stmt->fetchAll();

// Get past appointments
$stmt = $pdo->prepare("
    SELECT a.*, p.name as pet_name, p.species
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE p.owner_id = :owner_id
    AND (a.appointment_date < CURDATE() OR a.status = 'completed')
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->execute(['owner_id' => $_SESSION['owner_id']]);
$past_appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Client Dashboard Specific Styles */
        body {
            background-color: var(--light-color);
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar - Using similar style but with primary color */
        .sidebar {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            width: 250px;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
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
            color: rgba(255,255,255,0.9);
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
        }

        .sidebar-menu li {
            margin-bottom: 0.25rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all var(--transition-base);
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
            border-left: 3px solid white;
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
        }

        /* Welcome Section */
        .welcome-section {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            background-image: linear-gradient(135deg, #FFF5F5 0%, #F0FFFF 100%);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '🐾';
            position: absolute;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 6rem;
            opacity: 0.1;
        }

        .welcome-content h1 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .welcome-content p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        /* Pet Cards */
        .pets-section {
            margin-bottom: 3rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .pet-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .pet-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: all var(--transition-base);
            position: relative;
        }

        .pet-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .pet-card-header {
            background: var(--gradient-secondary);
            padding: 1.5rem;
            text-align: center;
            color: white;
        }

        .pet-avatar {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
        }

        .pet-card-body {
            padding: 1.5rem;
        }

        .pet-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .pet-info-row strong {
            color: var(--text-dark);
        }

        .pet-card-footer {
            padding: 1rem 1.5rem;
            background: var(--light-color);
            display: flex;
            gap: 0.5rem;
        }

        /* Appointments Section */
        .appointments-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .appointment-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .appointment-list {
            padding: 1rem;
        }

        .appointment-item {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            transition: background var(--transition-base);
        }

        .appointment-item:hover {
            background: var(--light-color);
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--radius-sm);
            min-width: 60px;
        }

        .appointment-date .day {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .appointment-date .month {
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .appointment-details {
            flex: 1;
            margin-left: 1rem;
        }

        .appointment-pet {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .appointment-time {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .appointment-status {
            margin-left: 1rem;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 3rem;
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            transition: all var(--transition-base);
            border: 2px solid transparent;
        }

        .quick-action-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }

        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Mobile Responsive */
        @media (max-width: 1200px) {
            .appointments-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform var(--transition-base);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .pet-cards {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr 1fr;
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
                padding: 0.5rem;
                border-radius: var(--radius-sm);
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../includes/sidebar-client.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Welcome back, <?php echo sanitize($_SESSION['first_name']); ?>!</h1>
                    <p>Manage your pets' health and appointments all in one place</p>
                    <a href="appointments/book.php" class="btn btn-primary btn-lg">Book New Appointment</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="appointments/book.php" class="quick-action-card">
                    <span class="quick-action-icon">📅</span>
                    <h4>Book Appointment</h4>
                    <p class="text-muted small">Schedule a visit</p>
                </a>
                <a href="pets/add.php" class="quick-action-card">
                    <span class="quick-action-icon">➕</span>
                    <h4>Add New Pet</h4>
                    <p class="text-muted small">Register a pet</p>
                </a>
                <a href="medical/history.php" class="quick-action-card">
                    <span class="quick-action-icon">📋</span>
                    <h4>Medical Records</h4>
                    <p class="text-muted small">View history</p>
                </a>
                <a href="profile/index.php" class="quick-action-card">
                    <span class="quick-action-icon">👤</span>
                    <h4>My Profile</h4>
                    <p class="text-muted small">Update info</p>
                </a>
            </div>

            <!-- My Pets Section -->
            <div class="pets-section">
                <div class="section-header">
                    <h2>My Pets</h2>
                    <?php if (count($pets) < 10): ?>
                        <a href="pets/add.php" class="btn btn-secondary btn-sm">Add New Pet</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($pets)): ?>
                    <div class="empty-state card">
                        <div class="empty-state-icon">🐾</div>
                        <h3>No Pets Added Yet</h3>
                        <p>Add your first pet to start managing their health records</p>
                        <a href="pets/add.php" class="btn btn-primary">Add Your First Pet</a>
                    </div>
                <?php else: ?>
                    <div class="pet-cards">
                        <?php foreach ($pets as $pet): ?>
                            <div class="pet-card">
                                <div class="pet-card-header">
                                    <div class="pet-avatar">
                                        <?php 
                                        $emoji = '🐾';
                                        if (stripos($pet['species'], 'dog') !== false) $emoji = '🐕';
                                        elseif (stripos($pet['species'], 'cat') !== false) $emoji = '🐈';
                                        elseif (stripos($pet['species'], 'bird') !== false) $emoji = '🦜';
                                        elseif (stripos($pet['species'], 'rabbit') !== false) $emoji = '🐰';
                                        echo $emoji;
                                        ?>
                                    </div>
                                    <h3><?php echo sanitize($pet['name']); ?></h3>
                                </div>
                                <div class="pet-card-body">
                                    <div class="pet-info-row">
                                        <span>Species:</span>
                                        <strong><?php echo sanitize($pet['species']); ?></strong>
                                    </div>
                                    <?php if ($pet['breed']): ?>
                                        <div class="pet-info-row">
                                            <span>Breed:</span>
                                            <strong><?php echo sanitize($pet['breed']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($pet['date_of_birth']): ?>
                                        <div class="pet-info-row">
                                            <span>Age:</span>
                                            <strong><?php echo calculateAge($pet['date_of_birth']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="pet-info-row">
                                        <span>Next Appointments:</span>
                                        <strong><?php echo $pet['upcoming_appointments'] ?: 'None'; ?></strong>
                                    </div>
                                    <?php if ($pet['last_visit']): ?>
                                        <div class="pet-info-row">
                                            <span>Last Visit:</span>
                                            <strong><?php echo formatDate($pet['last_visit']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="pet-card-footer">
                                    <a href="pets/view.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                    <a href="appointments/book.php?pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-sm btn-secondary">Book Appointment</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Appointments Section -->
            <div class="appointments-section">
                <h2 class="mb-4">Appointments</h2>
                
                <div class="appointments-grid">
                    <!-- Upcoming Appointments -->
                    <div class="appointment-card">
                        <div class="card-header">
                            <h3>Upcoming Appointments</h3>
                            <?php if (!empty($upcoming_appointments)): ?>
                                <a href="appointments/index.php" class="text-primary">View All →</a>
                            <?php endif; ?>
                        </div>
                        <div class="appointment-list">
                            <?php if (empty($upcoming_appointments)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📅</div>
                                    <p>No upcoming appointments</p>
                                    <a href="appointments/book.php" class="btn btn-primary btn-sm">Book Appointment</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-date">
                                            <div class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></div>
                                            <div class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></div>
                                        </div>
                                        <div class="appointment-details">
                                            <div class="appointment-pet"><?php echo sanitize($appointment['pet_name']); ?></div>
                                            <div class="appointment-time">
                                                <?php echo formatTime($appointment['appointment_time']); ?> - 
                                                <?php echo sanitize($appointment['type'] ?? 'General Checkup'); ?>
                                            </div>
                                        </div>
                                        <div class="appointment-status">
                                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Past Appointments -->
                    <div class="appointment-card">
                        <div class="card-header">
                            <h3>Recent Visits</h3>
                            <?php if (!empty($past_appointments)): ?>
                                <a href="appointments/history.php" class="text-primary">View History →</a>
                            <?php endif; ?>
                        </div>
                        <div class="appointment-list">
                            <?php if (empty($past_appointments)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📋</div>
                                    <p>No past appointments</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($past_appointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-date">
                                            <div class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></div>
                                            <div class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></div>
                                        </div>
                                        <div class="appointment-details">
                                            <div class="appointment-pet"><?php echo sanitize($appointment['pet_name']); ?></div>
                                            <div class="appointment-time">
                                                <?php echo sanitize($appointment['type'] ?? 'General Checkup'); ?>
                                            </div>
                                        </div>
                                        <div class="appointment-status">
                                            <a href="medical/view.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" 
                                               class="btn btn-sm btn-secondary">View Record</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php
    // Helper function to calculate age
    function calculateAge($birthDate) {
        $birthDate = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birthDate);
        
        if ($age->y > 0) {
            return $age->y . ' year' . ($age->y > 1 ? 's' : '');
        } elseif ($age->m > 0) {
            return $age->m . ' month' . ($age->m > 1 ? 's' : '');
        } else {
            return $age->d . ' day' . ($age->d > 1 ? 's' : '');
        }
    }
    ?>
</body>
</html>