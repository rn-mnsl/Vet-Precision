<?php
require_once '../../config/init.php';
requireClient();

$pageTitle = 'View Pet - ' . SITE_NAME;

// Get Pet ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlash('Invalid pet ID provided.', 'danger');
    redirect('/client/pets/index.php');
}

$petId = (int)$_GET['id'];
$ownerId = $_SESSION['owner_id'];

// Fetch Pet Data with additional information
try {
    $stmt = $pdo->prepare("
        SELECT p.*,
               (SELECT COUNT(*) FROM appointments 
                WHERE pet_id = p.pet_id 
                AND appointment_date >= CURDATE() 
                AND status != 'cancelled') as upcoming_appointments,
               (SELECT COUNT(*) FROM appointments 
                WHERE pet_id = p.pet_id 
                AND status = 'completed') as total_visits,
               (SELECT MAX(appointment_date) FROM appointments 
                WHERE pet_id = p.pet_id 
                AND status = 'completed') as last_visit_date,
               (SELECT COUNT(*) FROM medical_records 
                WHERE pet_id = p.pet_id 
                AND follow_up_required = 1 
                AND follow_up_date >= CURDATE()) as pending_followups,
               (SELECT appointment_date FROM appointments 
                WHERE pet_id = p.pet_id 
                AND appointment_date >= CURDATE() 
                AND status != 'cancelled' 
                ORDER BY appointment_date ASC LIMIT 1) as next_appointment_date,
               (SELECT COUNT(*) FROM vaccinations 
                WHERE pet_id = p.pet_id) as vaccination_records
        FROM pets p
        WHERE p.pet_id = :id AND p.owner_id = :owner_id AND p.is_active = 1
    ");
    $stmt->execute(['id' => $petId, 'owner_id' => $ownerId]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        setFlash('Pet not found or you do not have permission to view it.', 'danger');
        redirect('/client/pets/index.php');
    }

    // Update page title with pet's name
    $pageTitle = $pet['name'] . ' - View Pet - ' . SITE_NAME;

} catch (PDOException $e) {
    error_log("Failed to fetch pet details: " . $e->getMessage());
    setFlash('An error occurred while fetching pet details. Please try again.', 'danger');
    redirect('/client/pets/index.php');
}

// Get recent appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               CASE 
                   WHEN a.appointment_date >= CURDATE() THEN 'upcoming'
                   ELSE 'past'
               END as appointment_type
        FROM appointments a
        WHERE a.pet_id = :pet_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 5
    ");
    $stmt->execute(['pet_id' => $petId]);
    $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentAppointments = [];
}

// Get recent vaccinations
try {
    $stmt = $pdo->prepare("
        SELECT v.*, vt.name as vaccine_name
        FROM vaccinations v
        LEFT JOIN vaccine_types vt ON v.vaccine_type_id = vt.vaccine_type_id
        WHERE v.pet_id = :pet_id
        ORDER BY v.vaccination_date DESC
        LIMIT 5
    ");
    $stmt->execute(['pet_id' => $petId]);
    $recentVaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentVaccinations = [];
}

// Calculate age function
function calculateAge($birthDate) {
    if (!$birthDate) return 'Unknown';
    
    $birthDate = new DateTime($birthDate);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    
    if ($age->y > 0) {
        return $age->y . ' year' . ($age->y > 1 ? 's' : '') . 
               ($age->m > 0 ? ', ' . $age->m . ' month' . ($age->m > 1 ? 's' : '') : '');
    } elseif ($age->m > 0) {
        return $age->m . ' month' . ($age->m > 1 ? 's' : '') . 
               ($age->d > 0 && $age->m < 6 ? ', ' . $age->d . ' day' . ($age->d > 1 ? 's' : '') : '');
    } else {
        return $age->d . ' day' . ($age->d > 1 ? 's' : '');
    }
}

// Get vaccination status
function getVaccinationStatusBadge($status) {
    switch ($status) {
        case 'up_to_date':
            return '<span class="status-badge status-success">Up to Date</span>';
        case 'overdue':
            return '<span class="status-badge status-warning">Overdue</span>';
        case 'not_vaccinated':
            return '<span class="status-badge status-danger">Not Vaccinated</span>';
        default:
            return '<span class="status-badge status-info">Unknown</span>';
    }
}

// Get pet species icon
function getPetSpeciesIcon($species) {
    $icons = [
        'Dog' => '🐶',
        'Cat' => '🐱', 
        'Bird' => '🐦',
        'Rabbit' => '🐇',
        'Hamster' => '🐹',
        'Guinea Pig' => '🐹',
        'Fish' => '🐠',
        'Reptile' => '🦎',
        'Other' => '🐾'
    ];
    return $icons[$species] ?? '🐾';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
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
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }

        .sidebar-logo:hover {
            transform: scale(1.05);
        }

        .sidebar-user {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
        }

        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #4ECDC4;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
            animation: fadeInDown 0.5s ease;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: #5a67d8;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title h1 {
            margin: 0;
            color: #2d3748;
            font-size: 2.5rem;
            font-weight: 600;
        }

        .page-icon {
            font-size: 3rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        /* Pet Profile Header */
        .pet-profile-header {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pet-header-background {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .pet-header-background::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .pet-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 4rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .pet-avatar:hover {
            transform: scale(1.1);
        }

        .pet-name-display {
            font-size: 3rem;
            margin: 0 0 1rem 0;
            position: relative;
            z-index: 1;
        }

        .pet-species-breed {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Stats Section */
        .pet-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 2rem;
            background: #f7fafc;
            border-top: 1px solid #e2e8f0;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Details Cards */
        .details-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeInLeft 0.5s ease;
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .sidebar-card {
            animation: fadeInRight 0.5s ease;
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        }

        .card-header h3 {
            margin: 0;
            color: #2d3748;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 2rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-label {
            font-weight: 600;
            color: #718096;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #2d3748;
            word-wrap: break-word;
        }

        .detail-value-notes {
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .detail-item.full-width {
            grid-column: 1 / -1;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: #2f855a;
        }

        .status-warning {
            background: linear-gradient(135deg, #feebc8 0%, #fbd38d 100%);
            color: #c05621;
        }

        .status-danger {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #c53030;
        }

        .status-info {
            background: linear-gradient(135deg, #bee3f8 0%, #90cdf4 100%);
            color: #2c5282;
        }

        /* Appointment List */
        .appointment-list {
            list-style: none;
            padding: 0;
        }

        .appointment-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s ease;
        }

        .appointment-item:hover {
            background: #f7fafc;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            min-width: 70px;
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
            margin-top: 0.25rem;
        }

        .appointment-details {
            flex: 1;
        }

        .appointment-type {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .appointment-time {
            font-size: 0.875rem;
            color: #718096;
        }

        .appointment-status {
            margin-left: auto;
        }

        /* Action Buttons */
        .pet-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeInUp 0.5s ease 0.3s both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(229, 62, 62, 0.4);
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.6);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #718096;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #c53030;
            border: 1px solid #fc8181;
        }

        .alert-success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: #2f855a;
            border: 1px solid #68d391;
        }

        .alert-warning {
            background: linear-gradient(135deg, #feebc8 0%, #fbd38d 100%);
            color: #c05621;
            border: 1px solid #f6ad55;
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 101;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.25rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .pet-header-background {
                padding: 2rem 1rem;
            }

            .pet-name-display {
                font-size: 2rem;
            }

            .pet-stats {
                grid-template-columns: repeat(2, 1fr);
                padding: 1rem;
            }

            .pet-actions {
                flex-direction: column;
                padding: 1.5rem 1rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Health Status Indicators */
        .health-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .health-good {
            background: #c6f6d5;
            color: #2f855a;
        }

        .health-warning {
            background: #feebc8;
            color: #c05621;
        }

        .health-unknown {
            background: #e2e8f0;
            color: #4a5568;
        }

        /* Vaccination Timeline */
        .vaccination-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s ease;
        }

        .vaccination-item:hover {
            background: #f7fafc;
        }

        .vaccination-item:last-child {
            border-bottom: none;
        }

        .vaccination-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        .vaccination-details {
            flex: 1;
        }

        .vaccination-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .vaccination-date {
            font-size: 0.875rem;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../../includes/sidebar-client.php'; ?>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" aria-label="Toggle Navigation">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <nav class="breadcrumb">
                    <a href="../index.php">Dashboard</a>
                    <span>›</span>
                    <a href="index.php">My Pets</a>
                    <span>›</span>
                    <span><?php echo sanitize($pet['name']); ?></span>
                </nav>
                <div class="page-title">
                    <span class="page-icon"><?php echo getPetSpeciesIcon($pet['species']); ?></span>
                    <h1><?php echo sanitize($pet['name']); ?>'s Profile</h1>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php $flashMessages = getFlash(); ?>
            <?php if (!empty($flashMessages)): ?>
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?php echo sanitize($type); ?>">
                            <span>
                                <?php 
                                    if ($type == 'success') echo '✅';
                                    else if ($type == 'warning') echo '⚠️';
                                    else if ($type == 'danger') echo '❌';
                                    else echo 'ℹ️';
                                ?>
                            </span>
                            <?php echo sanitize($message); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Pet Profile Header -->
            <div class="pet-profile-header">
                <div class="pet-header-background">
                    <div class="pet-avatar">
                        <?php echo getPetSpeciesIcon($pet['species']); ?>
                    </div>
                    <h2 class="pet-name-display"><?php echo sanitize($pet['name']); ?></h2>
                    <p class="pet-species-breed">
                        <?php echo sanitize($pet['species']); ?>
                        <?php echo !empty($pet['breed']) ? ' • ' . sanitize($pet['breed']) : ''; ?>
                        <?php if ($pet['date_of_birth']): ?>
                            • <?php echo calculateAge($pet['date_of_birth']); ?> old
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Pet Stats -->
                <div class="pet-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $pet['upcoming_appointments']; ?></div>
                        <div class="stat-label">Upcoming Appointments</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $pet['total_visits']; ?></div>
                        <div class="stat-label">Total Visits</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $pet['vaccination_records']; ?></div>
                        <div class="stat-label">Vaccinations</div>
                    </div>
                    <?php if ($pet['pending_followups'] > 0): ?>
                        <div class="stat-item">
                            <div class="stat-value" style="color: #ed8936;"><?php echo $pet['pending_followups']; ?></div>
                            <div class="stat-label">Pending Follow-ups</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Pet Details -->
                <div class="details-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-info-circle"></i>
                            Pet Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Name</span>
                                <span class="detail-value"><?php echo sanitize($pet['name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Species</span>
                                <span class="detail-value"><?php echo sanitize($pet['species']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Breed</span>
                                <span class="detail-value"><?php echo !empty($pet['breed']) ? sanitize($pet['breed']) : 'Not specified'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date of Birth</span>
                                <span class="detail-value">
                                    <?php echo !empty($pet['date_of_birth']) ? date('F j, Y', strtotime($pet['date_of_birth'])) : 'Not specified'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Age</span>
                                <span class="detail-value"><?php echo calculateAge($pet['date_of_birth']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Gender</span>
                                <span class="detail-value">
                                    <?php echo !empty($pet['gender']) ? ucfirst(sanitize($pet['gender'])) : 'Not specified'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Color/Markings</span>
                                <span class="detail-value">
                                    <?php echo !empty($pet['color']) ? sanitize($pet['color']) : 'Not specified'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Weight</span>
                                <span class="detail-value">
                                    <?php echo !empty($pet['weight']) ? sanitize($pet['weight']) . ' kg' : 'Not specified'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Microchip ID</span>
                                <span class="detail-value">
                                    <?php echo !empty($pet['microchip_id']) ? sanitize($pet['microchip_id']) : 'Not microchipped'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Vaccination Status</span>
                                <span class="detail-value">
                                    <?php echo getVaccinationStatusBadge($pet['vaccination_status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Medical Information -->
                        <?php if (!empty($pet['allergies']) || !empty($pet['medical_conditions']) || !empty($pet['notes'])): ?>
                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                                <h4 style="color: #2d3748; margin-bottom: 1rem;">Medical Information</h4>
                                <div class="details-grid">
                                    <?php if (!empty($pet['allergies'])): ?>
                                    <div class="detail-item full-width">
                                        <span class="detail-label">Known Allergies</span>
                                        <span class="detail-value detail-value-notes"><?php echo sanitize($pet['allergies']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pet['medical_conditions'])): ?>
                                    <div class="detail-item full-width">
                                        <span class="detail-label">Medical Conditions</span>
                                        <span class="detail-value detail-value-notes"><?php echo sanitize($pet['medical_conditions']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pet['notes'])): ?>
                                    <div class="detail-item full-width">
                                        <span class="detail-label">Additional Notes</span>
                                        <span class="detail-value detail-value-notes"><?php echo sanitize($pet['notes']); ?></span>
                                    </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar-card">
                    <!-- Quick Actions -->
                    <div class="details-card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-bolt"></i>
                                Quick Actions
                            </h3>
                        </div>
                        <div class="card-body" style="padding: 1rem;">
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <a href="../appointments/book.php?pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-calendar-plus"></i> Book Appointment
                                </a>
                                <a href="edit.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </a>
                                <a href="../vaccinations/add.php?pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-syringe"></i> Add Vaccination
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Health Summary -->
                    <div class="details-card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-heartbeat"></i>
                                Health Summary
                            </h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 500;">Vaccination Status:</span>
                                    <?php echo getVaccinationStatusBadge($pet['vaccination_status']); ?>
                                </div>
                                
                                <?php if ($pet['next_appointment_date']): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 500;">Next Appointment:</span>
                                        <span class="health-indicator health-good">
                                            <?php echo date('M j', strtotime($pet['next_appointment_date'])); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 500;">Next Appointment:</span>
                                        <span class="health-indicator health-warning">Not scheduled</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 500;">Microchipped:</span>
                                    <?php if (!empty($pet['microchip_id'])): ?>
                                        <span class="health-indicator health-good">Yes</span>
                                    <?php else: ?>
                                        <span class="health-indicator health-warning">No</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($pet['allergies'])): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 500;">Allergies:</span>
                                        <span class="health-indicator health-warning">Has allergies</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($pet['medical_conditions'])): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 500;">Medical Conditions:</span>
                                        <span class="health-indicator health-warning">Has conditions</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Vaccinations -->
                    <?php if (!empty($recentVaccinations)): ?>
                        <div class="details-card">
                            <div class="card-header">
                                <h3>
                                    <i class="fas fa-syringe"></i>
                                    Recent Vaccinations
                                </h3>
                            </div>
                            <div class="card-body" style="padding: 0;">
                                <?php foreach ($recentVaccinations as $vaccination): ?>
                                    <div class="vaccination-item">
                                        <div class="vaccination-icon">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div class="vaccination-details">
                                            <div class="vaccination-name">
                                                <?php echo sanitize($vaccination['vaccine_name'] ?? 'Vaccination'); ?>
                                            </div>
                                            <div class="vaccination-date">
                                                <?php echo date('F j, Y', strtotime($vaccination['vaccination_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div style="padding: 1rem; text-align: center; border-top: 1px solid #e2e8f0;">
                                    <a href="../vaccinations/index.php?pet_id=<?php echo $pet['pet_id']; ?>" style="color: #667eea; text-decoration: none; font-size: 0.875rem;">
                                        View all vaccinations →
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Appointments -->
            <?php if (!empty($recentAppointments)): ?>
                <div class="details-card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-calendar-alt"></i>
                            Recent Appointments
                        </h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php foreach ($recentAppointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="appointment-date">
                                    <div class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></div>
                                    <div class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></div>
                                </div>
                                <div class="appointment-details">
                                    <div class="appointment-type">
                                        <?php echo sanitize($appointment['type'] ?? 'General Checkup'); ?>
                                        <?php if ($appointment['appointment_type'] === 'upcoming'): ?>
                                            <span style="color: #48bb78; font-size: 0.75rem; margin-left: 0.5rem;">UPCOMING</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-time">
                                        <?php echo formatTime($appointment['appointment_time']); ?>
                                        <?php if (!empty($appointment['reason'])): ?>
                                            • <?php echo sanitize($appointment['reason']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="appointment-status">
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="padding: 1rem; text-align: center; border-top: 1px solid #e2e8f0;">
                            <a href="../appointments/index.php?pet_id=<?php echo $pet['pet_id']; ?>" style="color: #667eea; text-decoration: none; font-size: 0.875rem;">
                                View all appointments →
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="details-card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-calendar-alt"></i>
                            Appointments
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-state-icon">📅</div>
                            <h4>No appointments yet</h4>
                            <p>Schedule your pet's first appointment to get started</p>
                            <a href="../appointments/book.php?pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-calendar-plus"></i> Book First Appointment
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="pet-actions">
                <a href="edit.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Pet Profile
                </a>
                <a href="../appointments/book.php?pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-calendar-plus"></i> Schedule Appointment
                </a>
                <a href="../medical/history.php?pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-clipboard-list"></i> Medical History
                </a>
                <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $pet['pet_id']; ?>, '<?php echo sanitize($pet['name']); ?>')">
                    <i class="fas fa-trash-alt"></i> Delete Pet
                </button>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle logic
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        if (mobileMenuToggle && sidebar) {
            mobileMenuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Enhanced delete confirmation
        function confirmDelete(petId, petName) {
            const modal = document.createElement('div');
            modal.className = 'delete-modal';
            modal.innerHTML = `
                <div class="delete-modal-overlay" onclick="closeDeleteModal()"></div>
                <div class="delete-modal-content">
                    <div class="delete-modal-header">
                        <h3>⚠️ Delete Pet Profile</h3>
                    </div>
                    <div class="delete-modal-body">
                        <p>Are you sure you want to delete <strong>${petName}'s</strong> profile?</p>
                        <p style="color: #e53e3e; font-size: 0.9rem; margin-top: 1rem;">
                            This action cannot be undone. All medical records, appointments, and vaccination history will be permanently deleted.
                        </p>
                    </div>
                    <div class="delete-modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deletePet(${petId})">
                            Yes, Delete Pet
                        </button>
                    </div>
                </div>
            `;

            // Add modal styles
            const modalStyles = document.createElement('style');
            modalStyles.textContent = `
                .delete-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    z-index: 1000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.3s ease;
                }
                
                .delete-modal-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(5px);
                }
                
                .delete-modal-content {
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                    max-width: 400px;
                    width: 90%;
                    position: relative;
                    z-index: 1;
                    animation: slideUp 0.3s ease;
                }
                
                .delete-modal-header {
                    padding: 2rem 2rem 1rem;
                    border-bottom: 1px solid #e2e8f0;
                }
                
                .delete-modal-header h3 {
                    margin: 0;
                    color: #2d3748;
                    font-size: 1.25rem;
                }
                
                .delete-modal-body {
                    padding: 1.5rem 2rem;
                }
                
                .delete-modal-actions {
                    padding: 1rem 2rem 2rem;
                    display: flex;
                    gap: 1rem;
                    justify-content: flex-end;
                }
                
                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            
            document.head.appendChild(modalStyles);
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            const modal = document.querySelector('.delete-modal');
            if (modal) {
                modal.remove();
                document.body.style.overflow = '';
            }
        }

        function deletePet(petId) {
            closeDeleteModal();
            
            // Show loading state
            const loadingModal = document.createElement('div');
            loadingModal.className = 'delete-modal';
            loadingModal.innerHTML = `
                <div class="delete-modal-overlay"></div>
                <div class="delete-modal-content" style="text-align: center; padding: 3rem 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">
                        <i class="fas fa-spinner fa-spin" style="color: #667eea;"></i>
                    </div>
                    <h3 style="margin: 0; color: #2d3748;">Deleting pet profile...</h3>
                    <p style="color: #718096; margin-top: 0.5rem;">Please wait</p>
                </div>
            `;
            document.body.appendChild(loadingModal);
            
            // Redirect to delete script
            window.location.href = `delete.php?id=${petId}`;
        }

        // Enhanced animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.addEventListener('DOMContentLoaded', () => {
            const animateElements = document.querySelectorAll('.details-card, .pet-actions');
            animateElements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = `all 0.6s ease ${index * 0.1}s`;
                observer.observe(el);
            });

            // Add hover effects to action buttons
            const actionButtons = document.querySelectorAll('.btn');
            actionButtons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px) scale(1.02)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add ripple effect to buttons
            actionButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.5);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add ripple animation
            const rippleStyle = document.createElement('style');
            rippleStyle.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(rippleStyle);
        });

        // Auto-refresh appointment status
        setInterval(() => {
            // Only refresh if there are upcoming appointments
            const upcomingAppointments = document.querySelectorAll('[data-appointment-status="upcoming"]');
            if (upcomingAppointments.length > 0) {
                // You could implement AJAX refresh here
                console.log('Checking for appointment updates...');
            }
        }, 300000); // Check every 5 minutes
    </script>
</body>
</html>