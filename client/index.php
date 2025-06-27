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
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        /* Dashboard Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        /* Welcome Section */
        .welcome-section {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
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
            color: #FF6B6B;
            font-size: 2rem;
        }

        .welcome-content p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #FF6B6B;
            border: 2px solid #FF6B6B;
        }

        .btn-secondary:hover {
            background: #FF6B6B;
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
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
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .quick-action-card:hover {
            border-color: #FF6B6B;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-decoration: none;
        }

        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .quick-action-card h4 {
            margin: 0.5rem 0;
            color: #333;
        }

        .quick-action-card p {
            margin: 0;
            font-size: 0.875rem;
            color: #666;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
        }

        /* Pet Cards */
        .pet-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .pet-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .pet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .pet-card-header {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .pet-avatar {
            width: 100px;
            height: 100px;
            background-color: white; /* Use background-color for fallback */
            background-size: cover;    /* This is for the uploaded photo */
            background-position: center; /* This is for the uploaded photo */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 4.5rem; /* Increased size for a better look */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            line-height: 1; /* Ensures perfect vertical centering for emojis */
        }

        .pet-card-header h3 {
            margin: 0;
            color: white;
        }

        .pet-card-body {
            padding: 1.5rem;
        }

        .pet-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #666;
            font-size: 0.875rem;
        }

        .pet-info-row strong {
            color: #333;
        }

        .pet-card-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            display: flex;
            gap: 0.5rem;
        }

        /* Appointments Section */
        .appointments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .appointment-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.25rem;
        }

        .appointment-list {
            padding: 0;
        }

        .appointment-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }

        .appointment-item:hover {
            background: #f8f9fa;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            border-radius: 8px;
            min-width: 60px;
            margin-right: 1rem;
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
        }

        .appointment-pet {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .appointment-time {
            font-size: 0.875rem;
            color: #666;
        }

        .appointment-status {
            margin-left: auto;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-requested {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #cce5ff;
            color: #004085;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .empty-state-icon {
            font-size: 3rem;
            opacity: 0.5;
            margin-bottom: 1rem;
            display: block;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }


        /* --- Modal Styles --- */
        .modal-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .modal-container.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .modal-overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
        }

        .modal-content {
            position: relative;
            background: #f8f9fa;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: scale(1);
            transition: transform 0.3s;
        }

        .modal-container.hidden .modal-content {
            transform: scale(0.9);
        }

        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 2rem;
            color: #999;
            cursor: pointer;
            line-height: 1;
        }

        .modal-loader {
            text-align: center;
            padding: 5rem;
            font-size: 1.2rem;
            color: #666;
        }

        /* --- Modal Content Specific Styles --- */
        .modal-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 2rem;
            background: white;
            border-bottom: 1px solid #e0e0e0;
        }
        .modal-pet-avatar {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            border: 5px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .modal-pet-title h2 { font-size: 2rem; margin: 0 0 0.25rem 0; }
        .modal-pet-title p { color: #666; margin: 0; }

        .modal-body { padding: 0; }

        .modal-tabs {
            display: flex;
            background: #e9ecef;
            padding: 0 2rem;
        }
        .tab-link {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1rem;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab-link.active {
            color: var(--primary-color, #FF6B6B);
            border-bottom-color: var(--primary-color, #FF6B6B);
        }
        .tab-content { display: none; padding: 2rem; background: white; }
        .tab-content.active { display: block; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .info-item { background: #f8f9fa; padding: 1rem; border-radius: 8px; }
        .info-item label { display: block; font-size: 0.8rem; color: #666; margin-bottom: 0.25rem; }
        .info-item span { font-size: 1rem; font-weight: 500; }

        .history-item { border-bottom: 1px solid #e9ecef; padding: 1rem 0; }
        .history-item:last-child { border-bottom: none; }
        .history-date { font-weight: 600; margin-bottom: 0.5rem; }
        .history-details p { margin: 0.25rem 0; font-size: 0.9rem; }
        .history-details strong { color: #333; }

        .no-records { text-align: center; padding: 2rem; color: #666; }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .pet-cards {
                grid-template-columns: 1fr;
            }

            .appointments-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .welcome-content h1 {
                font-size: 1.5rem;
            }
        }

        /* Utility Classes */
        .mb-4 { margin-bottom: 2rem; }
        .text-primary { color: #FF6B6B; }
        .text-muted { color: #6c757d; }
        .small { font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php include '../includes/sidebar-client.php'; ?>
        <?php include '../includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Welcome back, <?php echo sanitize($_SESSION['first_name']); ?>!</h1>
                    <p>Manage your pets' health and appointments all in one place</p>
                    <a href="appointments/book.php" class="btn btn-primary">Book New Appointment</a>
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
                    <div class="empty-state">
                        <span class="empty-state-icon">🐾</span>
                        <h3>No Pets Added Yet</h3>
                        <p>Add your first pet to start managing their health records</p>
                        <a href="pets/add.php" class="btn btn-primary">Add Your First Pet</a>
                    </div>
                <?php else: ?>
                    <div class="pet-cards">
                        <?php foreach ($pets as $pet): ?>
                            <?php
                            // --- START OF NEW LOGIC ---

                            // 1. Initialize variables for the avatar's content and style
                            $avatar_style = '';   // Will hold the 'background-image' style if a photo exists
                            $avatar_content = ''; // Will hold the emoji character if no photo exists

                            // 2. CHECK FOR A USER-UPLOADED PHOTO (HIGHEST PRIORITY)
                            if (!empty($pet['photo_url'])) {
                                // Clean the incorrect relative path from the database
                                $cleaned_path = str_replace('../../', '', $pet['photo_url']);
                                // Build the correct path relative to this dashboard file
                                $user_photo_path = '../' . $cleaned_path;

                                // If the custom photo file actually exists...
                                if (file_exists($user_photo_path)) {
                                    // ...set the inline style for the background image.
                                    $avatar_style = "background-image: url('{$user_photo_path}');";
                                    // The content remains empty because the background will be used.
                                }
                            }
                            
                            // 3. IF NO PHOTO WAS SET, GET THE FALLBACK EMOJI
                            // This runs if $avatar_style is still empty, meaning no valid photo was found.
                            if (empty($avatar_style)) {
                                // Get the correct emoji using our new helper function
                                $avatar_content = getPetEmoji($pet['species']);
                                $avatar_style = "width: 100px;
                                                height: 100px;
                                                background: white;
                                                border-radius: 50%;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                margin: 0 auto 1rem;
                                                font-size: 3rem;
                                                box-shadow: 0 4px 8px rgba(0,0,0,0.1);";
                            }

                            // --- END OF NEW LOGIC ---
                            ?>
                            <div class="pet-card">
                                <div class="pet-card-header">
                                    <!-- 
                                        This div now handles both cases perfectly:
                                        - If a photo exists: $avatar_style is set, $avatar_content is empty.
                                        - If no photo: $avatar_style is empty, $avatar_content is the emoji.
                                    -->

                                    <!-- No photo --> 
                                    <?php if (empty($avatar_style)): ?>
                                        <div style="<?php echo $avatar_style; ?>"><?php echo $avatar_content; ?></div>
                                    <!-- With photo --> 
                                    <?php else: ?>
                                        <div class="pet-avatar" style="<?php echo $avatar_style; ?>"><?php echo $avatar_content; ?></div>
                                    <?php endif; ?>

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
                                    <button onclick="viewPetDetails(<?php echo $pet['pet_id']; ?>)" class="btn btn-sm btn-primary">View Details</button>
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
                                    <span class="empty-state-icon">📅</span>
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
                                    <span class="empty-state-icon">📋</span>
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

    <!-- Pet Detail Modal -->
    <div id="petDetailModal" class="modal-container hidden">
        <div class="modal-overlay" onclick="closePetModal()"></div>
        <div class="modal-content">
            <button class="modal-close-btn" onclick="closePetModal()">×</button>
            <div id="petDetailModalBody">
                <!-- Content will be loaded here by JavaScript -->
                <div class="modal-loader">Loading...</div>
            </div>
        </div>
    </div>

    <?php

    function getPetEmoji($species) {
        $species = strtolower($species); // Make comparison case-insensitive

        if (strpos($species, 'dog') !== false) return '🐕';
        if (strpos($species, 'cat') !== false) return '🐈';
        if (strpos($species, 'bird') !== false) return '🦜';
        if (strpos($species, 'rabbit') !== false) return '🐰';
        if (strpos($species, 'hamster') !== false) return '🐹';
        if (strpos($species, 'fish') !== false) return '🐠';
        if (strpos($species, 'turtle') !== false) return '🐢';

        return '🐾'; // A generic fallback emoji
    }

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

<script>
// --- MODAL SCRIPT ---
const petDetailModal = document.getElementById('petDetailModal');
const petDetailModalBody = document.getElementById('petDetailModalBody');
const API_URL = 'ajax/pet_details_handler.php';

function openPetModal() {
    petDetailModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closePetModal() {
    petDetailModal.classList.add('hidden');
    document.body.style.overflow = ''; // Restore scrolling
    petDetailModalBody.innerHTML = '<div class="modal-loader">Loading...</div>'; // Reset for next time
}

async function viewPetDetails(petId) {
    openPetModal(); // Show modal with loader immediately

    try {
        const response = await fetch(`${API_URL}?id=${petId}`);
        const result = await response.json();

        if (result.success) {
            renderModalContent(result.data);
        } else {
            petDetailModalBody.innerHTML = `<div class="no-records">${result.message}</div>`;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        petDetailModalBody.innerHTML = '<div class="no-records">Failed to load details. Please try again.</div>';
    }
}

function renderModalContent(data) {
    const pet = data.details;

    // Helper for cleaning and formatting
    const sanitize = (str) => str || 'N/A';
    const formatDate = (dateStr) => dateStr ? new Date(dateStr).toLocaleDateString() : 'N/A';
    const formatTime = (timeStr) => timeStr ? new Date(`1970-01-01T${timeStr}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';

    // Determine avatar path
    let avatarPath = '../assets/images/default-generic.png'; // Fallback
    if (pet.photo_url) {
        const cleanedPath = pet.photo_url.replace('../../', '');
        avatarPath = `../${cleanedPath}`;
    }
    
    // Build the HTML using template literals
    const html = `
        <div class="modal-header">
            <div class="modal-pet-avatar" style="background-image: url('${avatarPath}')"></div>
            <div class="modal-pet-title">
                <h2>${sanitize(pet.name)}</h2>
                <p>${sanitize(pet.species)} - ${sanitize(pet.breed)}</p>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-tabs">
                <button class="tab-link active" onclick="switchTab(this, 'info')">Details</button>
                <button class="tab-link" onclick="switchTab(this, 'appointments')">Appointments</button>
                <button class="tab-link" onclick="switchTab(this, 'history')">Medical History</button>
            </div>

            <!-- Details Tab -->
            <div id="tab-info" class="tab-content active">
                <div class="info-grid">
                    <div class="info-item"><label>Gender</label> <span>${sanitize(pet.gender)}</span></div>
                    <div class="info-item"><label>Date of Birth</label> <span>${formatDate(pet.date_of_birth)}</span></div>
                    <div class="info-item"><label>Color</label> <span>${sanitize(pet.color)}</span></div>
                    <div class="info-item"><label>Weight</label> <span>${pet.weight ? pet.weight + ' kg' : 'N/A'}</span></div>
                    <div class="info-item"><label>Microchip ID</label> <span>${sanitize(pet.microchip_id)}</span></div>
                </div>
                <div class="info-item" style="margin-top: 1rem;">
                    <label>Notes / Allergies</label>
                    <span>${sanitize(pet.notes)}</span>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div id="tab-appointments" class="tab-content">
                ${data.upcoming_appointments.length > 0 ? data.upcoming_appointments.map(app => `
                    <div class="history-item">
                        <div class="history-date">${formatDate(app.appointment_date)} at ${formatTime(app.appointment_time)}</div>
                        <div class="history-details">
                            <p><strong>Type:</strong> ${sanitize(app.type)}</p>
                            <p><strong>Reason:</strong> ${sanitize(app.reason)}</p>
                            <p><strong>Status:</strong> <span class="status-badge status-${app.status}">${app.status}</span></p>
                        </div>
                    </div>
                `).join('') : '<div class="no-records">No upcoming appointments scheduled.</div>'}
            </div>

            <!-- History Tab -->
            <div id="tab-history" class="tab-content">
                ${data.medical_history.length > 0 ? data.medical_history.map(rec => `
                    <div class="history-item">
                        <div class="history-date">${formatDate(rec.visit_date)}</div>
                        <div class="history-details">
                            <p><strong>Visit Type:</strong> ${sanitize(rec.visit_type)}</p>
                            <p><strong>Symptoms:</strong> ${sanitize(rec.symptoms)}</p>
                            <p><strong>Diagnosis:</strong> ${sanitize(rec.diagnosis)}</p>
                            <p><strong>Treatment:</strong> ${sanitize(rec.treatment)}</p>
                        </div>
                    </div>
                `).join('') : '<div class="no-records">No medical history found.</div>'}
            </div>
        </div>
    `;

    petDetailModalBody.innerHTML = html;
}

function switchTab(btn, tabName) {
    // Deactivate all tabs and content
    document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

    // Activate the clicked tab and corresponding content
    btn.classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');
}
</script>
</html>