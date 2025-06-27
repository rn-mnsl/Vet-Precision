<?php
require_once '../../config/init.php';
requireClient();

$pageTitle = 'My Pets - ' . SITE_NAME;

// Get all pets for the logged-in client
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM appointments 
         WHERE pet_id = p.pet_id 
         AND appointment_date >= CURDATE() 
         AND status != 'cancelled') as upcoming_appointments,
        (SELECT COUNT(*) FROM medical_records 
         WHERE pet_id = p.pet_id) as total_visits,
        (SELECT MAX(visit_date) FROM medical_records 
         WHERE pet_id = p.pet_id) as last_visit_date,
        (SELECT COUNT(*) FROM medical_records 
         WHERE pet_id = p.pet_id 
         AND follow_up_required = 1 
         AND follow_up_date >= CURDATE()) as pending_followups
    FROM pets p
    WHERE p.owner_id = :owner_id
    ORDER BY p.is_active DESC, p.name ASC
");
$stmt->execute(['owner_id' => $_SESSION['owner_id']]);
$pets = $stmt->fetchAll();

// Calculate age function
function calculateAge($birthDate) {
    if (!$birthDate) return 'Unknown';
    
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

// Get pet emoji based on species
function getPetEmoji($species) {
    $species = strtolower($species);
    if (strpos($species, 'dog') !== false) return '🐕';
    if (strpos($species, 'cat') !== false) return '🐈';
    if (strpos($species, 'bird') !== false) return '🦜';
    if (strpos($species, 'rabbit') !== false) return '🐰';
    if (strpos($species, 'hamster') !== false) return '🐹';
    if (strpos($species, 'fish') !== false) return '🐠';
    if (strpos($species, 'turtle') !== false) return '🐢';
    return '🐾';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
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
            padding: 1rem 0;
            margin: 0;
        }

        .sidebar-menu li {
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
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .sidebar-menu .icon {
            font-size: 1.25rem;
            width: 1.5rem;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            color: #333;
            font-size: 2rem;
        }

        .page-header p {
            margin: 0.5rem 0 0 0;
            color: #666;
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

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label {
            color: #666;
            font-size: 0.875rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #FF6B6B;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 0.875rem;
            transition: border-color 0.3s ease;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cpath d='m21 21-4.35-4.35'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 10px center;
        }

        .search-input:focus {
            outline: none;
            border-color: #FF6B6B;
        }

        /* Pet Grid */
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Pet Card */
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

        .pet-card.inactive {
            opacity: 0.7;
        }

        .pet-card.inactive .inactive-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 10;
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
            margin: 0 0 0.5rem 0;
            color: white;
            font-size: 1.5rem;
        }

        .pet-species {
            color: rgba(255,255,255,0.9);
            font-size: 0.875rem;
        }

        .pet-card-body {
            padding: 1.5rem;
        }

        .pet-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pet-info-item {
            display: flex;
            flex-direction: column;
        }

        .pet-info-label {
            color: #666;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .pet-info-value {
            color: #333;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .pet-stats {
            display: flex;
            justify-content: space-around;
            padding: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .pet-stat {
            text-align: center;
        }

        .pet-stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #FF6B6B;
        }

        .pet-stat-label {
            display: block;
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            margin-top: 0.25rem;
        }

        .pet-card-actions {
            padding: 1.5rem;
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .empty-state-icon {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
            display: block;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 2rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-icon {
            font-size: 1.5rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        /* Mobile Responsive */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 101;
            background: #FF6B6B;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.5rem;
        }

        /* Modal Styles */
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

        /* Modal Content Specific Styles */
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

            .page-header {
                flex-direction: column;
                align-items: start;
                gap: 1rem;
            }

            .filter-section {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

            .pets-grid {
                grid-template-columns: 1fr;
            }

            .pet-info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f0f0f0;
            border-top-color: #FF6B6B;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../../includes/sidebar-client.php'; ?>
        <?php include '../../includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Flash Messages -->
            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <span class="alert-icon">
                        <?php echo $flash['type'] == 'success' ? '✓' : 'ℹ️'; ?>
                    </span>
                    <div class="alert-content">
                        <?php echo sanitize($flash['message']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>My Pets</h1>
                    <p>Manage your pets' information and health records</p>
                </div>
                <?php if (count($pets) < 10): ?>
                    <a href="add.php" class="btn btn-primary">
                        <span style="margin-right: 0.5rem;">➕</span>
                        Add New Pet
                    </a>
                <?php endif; ?>
            </div>

            <!-- Pet Count Limit Warning -->
            <?php if (count($pets) >= 10): ?>
                <div class="alert alert-warning">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-content">
                        <strong>Pet Limit Reached:</strong> You have reached the maximum limit of 10 pets. Please contact us if you need to register more pets.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <?php if (!empty($pets)): ?>
                <div class="filter-section">
                    <div class="search-box">
                        <input 
                            type="text" 
                            class="search-input" 
                            placeholder="Search pets by name..."
                            id="searchPets"
                            onkeyup="filterPets()"
                        >
                    </div>
                    <div class="filter-group">
                        <label for="filterSpecies">Species:</label>
                        <select class="filter-select" id="filterSpecies" onchange="filterPets()">
                            <option value="">All Species</option>
                            <option value="dog">Dogs</option>
                            <option value="cat">Cats</option>
                            <option value="bird">Birds</option>
                            <option value="other">Others</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterStatus">Status:</label>
                        <select class="filter-select" id="filterStatus" onchange="filterPets()">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pets Grid -->
            <?php if (empty($pets)): ?>
                <div class="empty-state">
                    <span class="empty-state-icon">🐾</span>
                    <h3>No Pets Added Yet</h3>
                    <p>Start by adding your first pet to manage their health records and appointments</p>
                    <a href="add.php" class="btn btn-primary btn-lg">Add Your First Pet</a>
                </div>
            <?php else: ?>
                <div class="pets-grid" id="petsGrid">
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
                                $user_photo_path = '../../' . $cleaned_path;

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

                        <div class="pet-card <?php echo !$pet['is_active'] ? 'inactive' : ''; ?>" 
                             data-name="<?php echo strtolower($pet['name']); ?>"
                             data-species="<?php echo strtolower($pet['species']); ?>"
                             data-status="<?php echo $pet['is_active'] ? 'active' : 'inactive'; ?>">
                            
                            <?php if (!$pet['is_active']): ?>
                                <div class="inactive-badge">Inactive</div>
                            <?php endif; ?>
                            

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
                                <div class="pet-info-grid">
                                    <div class="pet-info-item">
                                        <span class="pet-info-label">Age</span>
                                        <span class="pet-info-value"><?php echo calculateAge($pet['date_of_birth']); ?></span>
                                    </div>
                                    <div class="pet-info-item">
                                        <span class="pet-info-label">Gender</span>
                                        <span class="pet-info-value"><?php echo $pet['gender'] ? ucfirst($pet['gender']) : 'Not specified'; ?></span>
                                    </div>
                                    <?php if ($pet['weight']): ?>
                                        <div class="pet-info-item">
                                            <span class="pet-info-label">Weight</span>
                                            <span class="pet-info-value"><?php echo $pet['weight']; ?> kg</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($pet['microchip_id']): ?>
                                        <div class="pet-info-item">
                                            <span class="pet-info-label">Microchip</span>
                                            <span class="pet-info-value"><?php echo sanitize($pet['microchip_id']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($pet['last_visit_date']): ?>
                                    <div class="pet-info-item" style="margin-top: 1rem;">
                                        <span class="pet-info-label">Last Visit</span>
                                        <span class="pet-info-value"><?php echo formatDate($pet['last_visit_date']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pet-stats">
                                <div class="pet-stat">
                                    <span class="pet-stat-value"><?php echo $pet['upcoming_appointments']; ?></span>
                                    <span class="pet-stat-label">Upcoming</span>
                                </div>
                                <div class="pet-stat">
                                    <span class="pet-stat-value"><?php echo $pet['total_visits']; ?></span>
                                    <span class="pet-stat-label">Total Visits</span>
                                </div>
                                <?php if ($pet['pending_followups'] > 0): ?>
                                    <div class="pet-stat">
                                        <span class="pet-stat-value" style="color: #f39c12;"><?php echo $pet['pending_followups']; ?></span>
                                        <span class="pet-stat-label">Follow-ups</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pet-card-actions">
                                <!-- THIS BUTTON IS NOW UPDATED -->
                                <button onclick="viewPetDetails(<?php echo $pet['pet_id']; ?>)" class="btn btn-primary btn-sm">
                                    View Details
                                </button>
                                <a href="edit.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-secondary btn-sm">
                                    Edit Info
                                </a>
                                <?php if ($pet['is_active']): ?>
                                    <!-- This link now needs the correct path from /client/pets/ to /client/appointments/ -->
                                    <a href="../appointments/index.php?action=create&pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-secondary btn-sm">
                                        Book Appointment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Info Section -->
            <?php if (!empty($pets)): ?>
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <div class="alert-content">
                        <strong>Pet Management Tips:</strong>
                        <ul style="margin: 0.5rem 0 0 1rem; padding-left: 1rem;">
                            <li>Keep your pets' information up to date for accurate medical records</li>
                            <li>Upload a recent photo to help our staff identify your pet</li>
                            <li>Regular check-ups are recommended every 6-12 months</li>
                            <li>You can manage up to 10 pets per account</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Pet Detail Modal -->
    <div id="petDetailModal" class="modal-container hidden">
        <div class="modal-overlay" onclick="closePetModal()"></div>
        <div class="modal-content">
            <button class="modal-close-btn" onclick="closePetModal()">×</button>
            <div id="petDetailModalBody">
                <div class="modal-loader">Loading...</div>
            </div>
        </div>
    </div>

    <script>
        // Filter pets function
        function filterPets() {
            const searchTerm = document.getElementById('searchPets').value.toLowerCase();
            const speciesFilter = document.getElementById('filterSpecies').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;
            const petCards = document.querySelectorAll('.pet-card');
            
            petCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const species = card.getAttribute('data-species');
                const status = card.getAttribute('data-status');
                
                let showCard = true;
                
                // Search filter
                if (searchTerm && !name.includes(searchTerm)) {
                    showCard = false;
                }
                
                // Species filter
                if (speciesFilter) {
                    if (speciesFilter === 'other') {
                        if (species === 'dog' || species === 'cat' || species === 'bird') {
                            showCard = false;
                        }
                    } else if (!species.includes(speciesFilter)) {
                        showCard = false;
                    }
                }
                
                // Status filter
                if (statusFilter && status !== statusFilter) {
                    showCard = false;
                }
                
                card.style.display = showCard ? 'block' : 'none';
            });
            
            // Check if no results
            const visibleCards = document.querySelectorAll('.pet-card[style="display: block;"], .pet-card:not([style])');
            const petsGrid = document.getElementById('petsGrid');
            
            if (visibleCards.length === 0 && petsGrid) {
                // Show no results message
                const existingNoResults = document.getElementById('noResults');
                if (!existingNoResults) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.id = 'noResults';
                    noResultsDiv.className = 'empty-state';
                    noResultsDiv.innerHTML = `
                        <span class="empty-state-icon">🔍</span>
                        <h3>No pets found</h3>
                        <p>Try adjusting your search or filters</p>
                    `;
                    petsGrid.parentNode.insertBefore(noResultsDiv, petsGrid.nextSibling);
                }
                petsGrid.style.display = 'none';
            } else {
                // Remove no results message if it exists
                const noResults = document.getElementById('noResults');
                if (noResults) {
                    noResults.remove();
                }
                petsGrid.style.display = 'grid';
            }
        }


        const petDetailModal = document.getElementById('petDetailModal');
        const petDetailModalBody = document.getElementById('petDetailModalBody');
        // This relative path is correct for /client/pets/index.php
        const API_URL = '../ajax/pet_details_handler.php';

        function openPetModal() {
            petDetailModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePetModal() {
            petDetailModal.classList.add('hidden');
            document.body.style.overflow = '';
            petDetailModalBody.innerHTML = '<div class="modal-loader">Loading...</div>';
        }

        async function viewPetDetails(petId) {
            openPetModal();

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
            const sanitize = (str) => str || 'N/A';
            const formatDate = (dateStr) => dateStr ? new Date(dateStr).toLocaleDateString() : 'N/A';
            const formatTime = (timeStr) => timeStr ? new Date(`1970-01-01T${timeStr}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';

            let avatarHtml = '';

            // This simple check now works perfectly!
            if (pet.photo_url) {
                // This block only runs if the server confirmed the file exists.
                const cleanedPath = pet.photo_url.replace('../../', '');
                const avatarPath = `../../${cleanedPath}`;
                avatarHtml = `<div class="modal-pet-avatar" style="background-image: url('${avatarPath}')"></div>`;
            } else {
                // This block will now correctly run for pets with no photo or a broken photo link.
                const emoji = getPetEmojiJS(pet.species); // Remember to have getPetEmojiJS() in your script
                avatarHtml = `<div class="modal-pet-avatar" style="display: flex; align-items: center; justify-content: center; font-size: 4rem; background-color: white;">
                                ${emoji}
                            </div>`;
            }
            
            const html = `
                <div class="modal-header">
                    ${avatarHtml}
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
                    <div id="tab-info" class="tab-content active">
                        <div class="info-grid">
                            <div class="info-item"><label>Gender</label> <span>${sanitize(pet.gender)}</span></div>
                            <div class="info-item"><label>Date of Birth</label> <span>${formatDate(pet.date_of_birth)}</span></div>
                            <div class="info-item"><label>Color</label> <span>${sanitize(pet.color)}</span></div>
                            <div class="info-item"><label>Weight</label> <span>${pet.weight ? pet.weight + ' kg' : 'N/A'}</span></div>
                            <div class="info-item"><label>Microchip ID</label> <span>${sanitize(pet.microchip_id)}</span></div>
                        </div>
                        <div class="info-item" style="margin-top: 1rem;"><label>Notes / Allergies</label><span>${sanitize(pet.notes)}</span></div>
                    </div>
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
            switchTab(document.querySelector('.tab-link'), 'info'); 
        }

        function switchTab(btn, tabName) {
            document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }

        function getPetEmojiJS(species) {
            if (!species) return '🐾'; // Handle null/undefined cases
            const s = species.toLowerCase(); // Use .toLowerCase() in JS

            if (s.includes('dog')) return '🐕';   // Use .includes() in JS
            if (s.includes('cat')) return '🐈';
            if (s.includes('bird')) return '🦜';
            if (s.includes('rabbit')) return '🐰';
            if (s.includes('hamster')) return '🐹';
            if (s.includes('fish')) return '🐠';
            if (s.includes('turtle')) return '🐢';

            return '🐾'; // Fallback
        }
    </script>
</body>
</html>