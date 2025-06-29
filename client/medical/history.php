<?php
require_once '../../config/init.php';
requireClient(); // Ensure only logged-in clients can access

$pageTitle = 'Medical History - ' . SITE_NAME;

// Get client's owner_id
$owner_id = null;
try {
    $stmt = $pdo->prepare("SELECT owner_id FROM owners WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $owner_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    $error_message = "Error fetching owner information.";
}

// Get pet filter from URL
$selected_pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;

// Pagination setup
$items_per_page = 6; // Show 6 cards per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Get client's pets for filter dropdown
$pets = [];
if ($owner_id) {
    try {
        $stmt = $pdo->prepare("SELECT pet_id, name, species FROM pets WHERE owner_id = ? AND is_active = 1 ORDER BY name");
        $stmt->execute([$owner_id]);
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $pets = [];
    }
}

// Build the medical records query
$medical_records = [];
$total_items = 0;
$total_pages = 1;

if ($owner_id) {
    // Count total records for pagination
    $count_sql = "
        SELECT COUNT(mr.record_id)
        FROM medical_records mr
        JOIN pets p ON mr.pet_id = p.pet_id
        JOIN appointments a ON mr.appointment_id = a.appointment_id
        WHERE p.owner_id = ? AND a.status = 'completed'
    ";
    
    $count_params = [$owner_id];
    
    if ($selected_pet_id) {
        $count_sql .= " AND p.pet_id = ?";
        $count_params[] = $selected_pet_id;
    }
    
    try {
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($count_params);
        $total_items = (int)$count_stmt->fetchColumn();
        $total_pages = ceil($total_items / $items_per_page);
    } catch (PDOException $e) {
        $total_items = 0;
        $total_pages = 1;
    }
    
    // Calculate offset
    $offset = ($current_page - 1) * $items_per_page;
    
    // Fetch medical records with related data
    $sql = "
        SELECT 
            mr.record_id,
            mr.visit_date,
            mr.weight,
            mr.temperature,
            mr.heart_rate,
            mr.respiratory_rate,
            mr.symptoms,
            mr.diagnosis,
            mr.treatment,
            mr.prescription,
            mr.follow_up_required,
            mr.follow_up_date,
            mr.created_at,
            p.pet_id,
            p.name as pet_name,
            p.species,
            p.breed,
            p.photo_url as pet_photo,
            a.appointment_date,
            a.appointment_time,
            CONCAT(staff.first_name, ' ', staff.last_name) as veterinarian_name
        FROM medical_records mr
        JOIN pets p ON mr.pet_id = p.pet_id
        JOIN appointments a ON mr.appointment_id = a.appointment_id
        JOIN users staff ON mr.created_by = staff.user_id
        WHERE p.owner_id = ? AND a.status = 'completed'
    ";
    
    $params = [$owner_id];
    
    if ($selected_pet_id) {
        $sql .= " AND p.pet_id = ?";
        $params[] = $selected_pet_id;
    }
    
    $sql .= " ORDER BY mr.visit_date DESC, mr.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $items_per_page;
    $params[] = $offset;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $medical_records = [];
        $error_message = "Error fetching medical records: " . $e->getMessage();
    }
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
    <?php include '../../includes/favicon.php'; ?>
    <style>
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

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

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
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-header p {
            color: #6c757d;
            margin: 0.5rem 0 0;
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
            color: #FF6B6B;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Filters */
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
            min-width: 150px;
        }

        .clear-filter {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            color: #495057;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .clear-filter:hover {
            background: #e9ecef;
        }

        /* Medical Records Grid */
        .records-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Medical Record Card */
        .medical-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .medical-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        /* Card Header */
        .card-header {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 0 0 50% 50% / 0 0 20px 20px;
        }

        .card-header-content {
            position: relative;
            z-index: 1;
        }

        .visit-date {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .pet-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pet-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #4ECDC4;
            overflow: hidden;
        }

        .pet-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pet-details h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .pet-species {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .appointment-info {
            background: rgba(255,255,255,0.15);
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        /* Card Body */
        .card-body {
            padding: 1.5rem;
        }

        .medical-section {
            margin-bottom: 1.5rem;
        }

        .medical-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #4ECDC4;
        }

        .vital-signs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .vital-item {
            background: white;
            padding: 0.75rem;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .vital-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .vital-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #343a40;
        }

        .text-content {
            color: #495057;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        /* Prescription Photo */
        .prescription-section {
            margin-top: 1rem;
        }

        .prescription-photo {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 0.75rem;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .prescription-photo:hover {
            transform: scale(1.02);
        }

        .prescription-photo img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        .prescription-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
            color: white;
            font-size: 1.5rem;
        }

        .prescription-photo:hover .prescription-overlay {
            opacity: 1;
        }

        .no-prescription {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 0.75rem;
        }

        /* Follow-up Badge */
        .follow-up-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff3cd;
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 1rem;
        }

        .follow-up-badge.complete {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Veterinarian Info */
        .vet-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Modal for Prescription Photo - Enhanced Version */
        .prescription-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0,0,0,0.9);
            justify-content: center;
            align-items: center;
            padding: 60px 20px 20px 20px; /* Extra top padding for close button */
            box-sizing: border-box;
        }

        .prescription-modal.show {
            display: flex !important;
        }

        .prescription-modal-content {
            position: relative;
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 80px); /* Account for close button space */
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
        }

        .prescription-modal img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.8);
            background: white;
            padding: 4px;
            /* Ensure image never exceeds viewport */
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 120px);
            cursor: zoom-in;
        }

        .prescription-modal-close {
            position: fixed;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2.5rem;
            cursor: pointer;
            background: rgba(0,0,0,0.8);
            border: 2px solid rgba(255,255,255,0.3);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
            z-index: 10001;
            line-height: 1;
        }

        .prescription-modal-close:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.6);
            transform: scale(1.1);
        }

        .prescription-modal-close:focus {
            outline: 3px solid white;
            outline-offset: 2px;
        }

        /* Add zoom info indicator */
        .zoom-info {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
            z-index: 10001;
        }

        /* Mobile responsive adjustments for modal */
        @media (max-width: 768px) {
            .prescription-modal {
                padding: 70px 10px 10px 10px;
            }
            
            .prescription-modal-close {
                top: 15px;
                right: 15px;
                font-size: 2rem;
                width: 50px;
                height: 50px;
            }
            
            .prescription-modal-content {
                max-width: calc(100vw - 20px);
                max-height: calc(100vh - 100px);
            }

            .prescription-modal img {
                max-width: calc(100vw - 20px);
                max-height: calc(100vh - 130px);
            }

            .zoom-info {
                font-size: 0.8rem;
                padding: 8px 16px;
                bottom: 10px;
            }
        }

        /* Mobile Responsive Styles - Updated to match existing pattern */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .dashboard-layout {
                display: block;
            }
        }

        @media (max-width: 768px) {
            /* Sidebar Mobile Styles */
            .sidebar { 
                transform: translateX(-100%); 
                transition: transform 0.3s ease-in-out; 
                z-index: 1100; 
                position: fixed; 
                top: 0; 
                height: 100vh; 
                margin-top: 0; 
            }
            
            .main-content { 
                margin-left: 0; 
                padding-top: 85px; /* Space for fixed navbar */
            }
            
            body.sidebar-is-open .sidebar { 
                transform: translateX(0); 
                box-shadow: 0 0 20px rgba(0,0,0,0.25); 
            }
            
            body.sidebar-is-open .sidebar-overlay { 
                opacity: 1; 
                visibility: visible; 
            }

            /* Dashboard Layout */
            .dashboard-layout {
                display: block;
            }

            /* Mobile Layout Adjustments */
            .main-content {
                padding: 1rem;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            /* Page Header Mobile */
            .page-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
                text-align: center;
            }

            .page-header h1 {
                font-size: 1.5rem;
                justify-content: center;
            }

            .breadcrumb {
                justify-content: center;
                margin-bottom: 1rem;
            }

            /* Filters Section Mobile */
            .filters-section {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
                align-items: stretch;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .filter-group label {
                text-align: center;
                font-size: 1rem;
            }

            .filter-select {
                min-width: 100%;
                padding: 0.75rem;
                font-size: 1rem;
                text-align: center;
            }

            .clear-filter {
                align-self: center;
                padding: 0.75rem 1.5rem;
            }

            /* Records Count Mobile */
            .filters-section div[style*="margin-left: auto"] {
                margin-left: 0 !important;
                text-align: center;
                margin-top: 0.5rem;
            }

            /* Medical Records Grid Mobile */
            .records-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-bottom: 1.5rem;
                padding: 0;
            }

            /* Medical Card Mobile */
            .medical-card {
                margin: 0 auto;
                max-width: 100%;
                width: 100%;
            }

            /* Card Header Mobile */
            .card-header {
                padding: 1.25rem;
            }

            .visit-date {
                font-size: 1.25rem;
                text-align: center;
                margin-bottom: 1rem;
            }

            .pet-info {
                justify-content: center;
                text-align: center;
                margin-bottom: 1rem;
            }

            .pet-details h3 {
                font-size: 1.1rem;
            }

            .appointment-info {
                text-align: center;
                font-size: 0.85rem;
            }

            /* Card Body Mobile */
            .card-body {
                padding: 1.25rem;
            }

            .section-title {
                font-size: 0.95rem;
                justify-content: center;
                text-align: center;
                margin-bottom: 1rem;
            }

            .section-content {
                padding: 1rem;
                text-align: center;
            }

            .text-content {
                font-size: 0.9rem;
                line-height: 1.6;
            }

            /* Vital Signs Mobile */
            .vital-signs-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }

            .vital-item {
                padding: 0.5rem;
            }

            .vital-label {
                font-size: 0.7rem;
            }

            .vital-value {
                font-size: 1rem;
            }

            /* Prescription Photo Mobile */
            .prescription-photo {
                margin: 0 auto;
                max-width: 100%;
            }

            .prescription-photo img {
                height: 150px;
            }

            .no-prescription {
                padding: 1.5rem;
                font-size: 0.9rem;
            }

            /* Follow-up Badge Mobile */
            .follow-up-badge {
                justify-content: center;
                margin: 1rem auto 0;
                display: flex;
                max-width: fit-content;
            }

            /* Veterinarian Info Mobile */
            .vet-info {
                justify-content: center;
                text-align: center;
                flex-wrap: wrap;
                font-size: 0.8rem;
            }

            /* Empty State Mobile */
            .empty-state {
                text-align: center;
                padding: 2rem 1rem;
            }

            .empty-state h3 {
                font-size: 1.25rem;
                margin: 1rem 0;
            }

            .empty-state p {
                font-size: 0.9rem;
                line-height: 1.5;
                margin-bottom: 1.5rem;
            }

            /* Alert Messages Mobile */
            .alert {
                margin: 0 1rem 1.5rem;
                padding: 1rem;
                text-align: center;
                border-radius: 8px;
            }

            /* Pagination Mobile Styles */
            .pagination-controls {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
                margin: 2rem 0;
                padding: 0 1rem;
            }

            .pagination-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
                min-width: 40px;
                text-align: center;
            }

            .pagination-link.active {
                background: #4ECDC4;
                color: white;
                border-radius: 6px;
            }

            .pagination-link.disabled {
                opacity: 0.5;
                pointer-events: none;
            }
        }

        @media (max-width: 480px) {
            /* Extra Small Mobile Devices */
            .main-content {
                padding: 0.5rem;
                padding-top: 85px; /* Maintain navbar space */
            }

            .page-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .page-header h1 {
                font-size: 1.25rem;
            }

            .filters-section {
                padding: 0.75rem;
                margin-bottom: 1rem;
            }

            .records-grid {
                gap: 1rem;
            }

            .card-header {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .vital-signs-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .pet-info {
                flex-direction: column;
                gap: 0.5rem;
            }

            .pet-avatar {
                align-self: center;
            }

            .appointment-info {
                font-size: 0.8rem;
                padding: 0.5rem;
            }

            .section-title {
                font-size: 0.9rem;
                gap: 0.25rem;
            }

            .prescription-photo img {
                height: 120px;
            }

            .follow-up-badge {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }

            .vet-info {
                font-size: 0.75rem;
            }
        }

        /* Center content for all screen sizes */
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 100%;
        }

        .page-header,
        .filters-section,
        .records-grid,
        .pagination-controls,
        .empty-state,
        .alert {
            width: 100%;
            max-width: 1200px;
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
                align-items: stretch;
                padding-top: 2rem; /* Reset padding for desktop */
            }

            .page-header,
            .filters-section,
            .records-grid,
            .pagination-controls,
            .empty-state,
            .alert {
                max-width: none;
            }
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
            <!-- Page Header -->
            <div class="page-header">
                <nav class="breadcrumb">
                    <a href="../index.php">Dashboard</a>
                    <span>›</span>
                    <span>Medical History</span>
                </nav>
                <h1>
                    <i class="fas fa-clipboard-list"></i>
                    Medical History
                </h1>
                <p>View your pets' complete medical records and treatment history</p>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filter-group">
                    <label for="pet_filter">Filter by Pet:</label>
                    <select id="pet_filter" class="filter-select" onchange="filterByPet()">
                        <option value="">All Pets</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?php echo $pet['pet_id']; ?>" 
                                    <?php echo ($selected_pet_id == $pet['pet_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selected_pet_id): ?>
                    <a href="history.php" class="clear-filter">
                        <i class="fas fa-times"></i> Clear Filter
                    </a>
                <?php endif; ?>
                <div style="margin-left: auto; color: #6c757d; font-size: 0.875rem;">
                    <strong><?php echo $total_items; ?></strong> record<?php echo $total_items !== 1 ? 's' : ''; ?> found
                </div>
            </div>

            <!-- Error Messages -->
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Medical Records -->
            <?php if (empty($medical_records)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>No Medical Records Found</h3>
                    <?php if ($selected_pet_id): ?>
                        <p>No medical records found for the selected pet. Try selecting a different pet or clearing the filter.</p>
                    <?php else: ?>
                        <p>Your pets don't have any medical records yet. Medical records are created after completed veterinary appointments.</p>
                        <a href="../appointments/index.php?action=create" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Book an Appointment
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="records-grid">
                    <?php foreach ($medical_records as $record): ?>
                        <div class="medical-card">
                            <!-- Card Header -->
                            <div class="card-header">
                                <div class="card-header-content">
                                    <div class="visit-date">
                                        <?php echo date('M j, Y', strtotime($record['visit_date'])); ?>
                                    </div>
                                    
                                    <div class="pet-info">
                                        <div class="pet-avatar">
                                            <?php if (!empty($record['pet_photo']) && file_exists($record['pet_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($record['pet_photo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($record['pet_name']); ?>">
                                            <?php else: ?>
                                                <?php 
                                                $species_icons = [
                                                    'Dog' => '🐶', 'Cat' => '🐱', 'Bird' => '🐦', 
                                                    'Rabbit' => '🐇', 'Hamster' => '🐹', 'Fish' => '🐠'
                                                ];
                                                echo $species_icons[$record['species']] ?? '🐾';
                                                ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pet-details">
                                            <h3><?php echo htmlspecialchars($record['pet_name']); ?></h3>
                                            <div class="pet-species">
                                                <?php echo htmlspecialchars($record['species']); ?>
                                                <?php if ($record['breed']): ?>
                                                    • <?php echo htmlspecialchars($record['breed']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="appointment-info">
                                        <i class="fas fa-calendar"></i>
                                        Appointment: <?php echo date('M j, Y g:i A', strtotime($record['appointment_date'] . ' ' . $record['appointment_time'])); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body">
                                <!-- Vital Signs -->
                                <?php if ($record['weight'] || $record['temperature'] || $record['heart_rate'] || $record['respiratory_rate']): ?>
                                    <div class="medical-section">
                                        <div class="section-title">
                                            <i class="fas fa-heartbeat"></i>
                                            Vital Signs
                                        </div>
                                        <div class="vital-signs-grid">
                                            <?php if ($record['weight']): ?>
                                                <div class="vital-item">
                                                    <div class="vital-label">Weight</div>
                                                    <div class="vital-value"><?php echo $record['weight']; ?> kg</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($record['temperature']): ?>
                                                <div class="vital-item">
                                                    <div class="vital-label">Temperature</div>
                                                    <div class="vital-value"><?php echo $record['temperature']; ?>°C</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($record['heart_rate']): ?>
                                                <div class="vital-item">
                                                    <div class="vital-label">Heart Rate</div>
                                                    <div class="vital-value"><?php echo $record['heart_rate']; ?> bpm</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($record['respiratory_rate']): ?>
                                                <div class="vital-item">
                                                    <div class="vital-label">Respiratory</div>
                                                    <div class="vital-value"><?php echo $record['respiratory_rate']; ?> rpm</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Symptoms -->
                                <div class="medical-section">
                                    <div class="section-title">
                                        <i class="fas fa-exclamation-circle"></i>
                                        Symptoms
                                    </div>
                                    <div class="section-content">
                                        <div class="text-content"><?php echo nl2br(htmlspecialchars($record['symptoms'])); ?></div>
                                    </div>
                                </div>

                                <!-- Diagnosis -->
                                <div class="medical-section">
                                    <div class="section-title">
                                        <i class="fas fa-stethoscope"></i>
                                        Diagnosis
                                    </div>
                                    <div class="section-content">
                                        <div class="text-content"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></div>
                                    </div>
                                </div>

                                <!-- Treatment -->
                                <div class="medical-section">
                                    <div class="section-title">
                                        <i class="fas fa-pills"></i>
                                        Treatment
                                    </div>
                                    <div class="section-content">
                                        <div class="text-content"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></div>
                                    </div>
                                </div>

                                <!-- Prescription Photo -->
                                <div class="medical-section">
                                    <div class="section-title">
                                        <i class="fas fa-prescription"></i>
                                        Prescription
                                    </div>
                                    <?php if (!empty($record['prescription']) && file_exists('../../staff/medical/' . $record['prescription'])): ?>
                                        <div class="prescription-photo" onclick="openPrescriptionModal('<?php echo htmlspecialchars('../../staff/medical/' . $record['prescription']); ?>')">
                                            <img src="<?php echo htmlspecialchars('../../staff/medical/' . $record['prescription']); ?>" 
                                                 alt="Prescription Photo">
                                            <div class="prescription-overlay">
                                                <i class="fas fa-search-plus"></i>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-prescription">
                                            <i class="fas fa-prescription"></i>
                                            <div>No prescription photo available</div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Follow-up Status -->
                                <div class="follow-up-badge <?php echo $record['follow_up_required'] ? '' : 'complete'; ?>">
                                    <i class="fas fa-<?php echo $record['follow_up_required'] ? 'calendar-plus' : 'check-circle'; ?>"></i>
                                    <?php if ($record['follow_up_required']): ?>
                                        Follow-up Required
                                        <?php if ($record['follow_up_date']): ?>
                                            • <?php echo date('M j, Y', strtotime($record['follow_up_date'])); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Treatment Complete
                                    <?php endif; ?>
                                </div>

                                <!-- Veterinarian Info -->
                                <div class="vet-info">
                                    <i class="fas fa-user-md"></i>
                                    Treated by Dr. <?php echo htmlspecialchars($record['veterinarian_name']); ?>
                                    • <?php echo date('M j, Y', strtotime($record['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-controls">
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $selected_pet_id ? '&pet_id=' . $selected_pet_id : ''; ?>" 
                           class="pagination-link <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $selected_pet_id ? '&pet_id=' . $selected_pet_id : ''; ?>" 
                               class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $selected_pet_id ? '&pet_id=' . $selected_pet_id : ''; ?>" 
                           class="pagination-link <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Prescription Photo Modal -->
    <div id="prescriptionModal" class="prescription-modal">
        <div class="prescription-modal-content">
            <img id="prescriptionModalImg" src="" alt="Prescription Photo Full Size" style="cursor: zoom-in;">
        </div>
        <button class="prescription-modal-close" onclick="closePrescriptionModal()" aria-label="Close modal">&times;</button>
    </div>

    <script>
        // Filter functionality
        function filterByPet() {
            const petId = document.getElementById('pet_filter').value;
            const url = new URL(window.location);
            
            if (petId) {
                url.searchParams.set('pet_id', petId);
            } else {
                url.searchParams.delete('pet_id');
            }
            
            url.searchParams.delete('page'); // Reset to page 1 when filtering
            window.location.href = url.toString();
        }

        // Prescription modal functionality
        function openPrescriptionModal(imageSrc) {
            console.log('Opening prescription modal...'); // Debug log
            
            const modal = document.getElementById('prescriptionModal');
            const modalImg = document.getElementById('prescriptionModalImg');
            
            if (!modal || !modalImg) {
                console.error('Modal elements not found');
                return;
            }
            
            if (imageSrc) {
                console.log('Setting modal image source:', imageSrc); // Debug log
                modalImg.src = imageSrc;
                modal.classList.add('show');
                modal.style.display = 'flex';
                
                // Prevent body scroll when modal is open
                document.body.style.overflow = 'hidden';
                
                // Add zoom info
                addZoomInfo();
                
                // Focus on close button for accessibility
                setTimeout(() => {
                    document.querySelector('.prescription-modal-close')?.focus();
                }, 100);
                
                // Add click-to-zoom functionality
                modalImg.addEventListener('click', toggleImageZoom);
                
            } else {
                console.error('No valid image source provided');
            }
        }

        function closePrescriptionModal() {
            console.log('Closing prescription modal...'); // Debug log
            
            const modal = document.getElementById('prescriptionModal');
            const modalImg = document.getElementById('prescriptionModalImg');
            
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                
                // Restore body scroll
                document.body.style.overflow = 'auto';
                
                // Remove zoom info
                removeZoomInfo();
                
                // Reset image zoom
                if (modalImg) {
                    modalImg.style.transform = '';
                    modalImg.style.cursor = 'zoom-in';
                    modalImg.removeEventListener('click', toggleImageZoom);
                    isZoomed = false;
                }
            }
        }

        function addZoomInfo() {
            // Remove existing zoom info if present
            removeZoomInfo();
            
            const zoomInfo = document.createElement('div');
            zoomInfo.className = 'zoom-info';
            zoomInfo.id = 'zoomInfo';
            zoomInfo.innerHTML = 'Click image to zoom • Press ESC to close';
            document.body.appendChild(zoomInfo);
        }

        function removeZoomInfo() {
            const existing = document.getElementById('zoomInfo');
            if (existing) {
                existing.remove();
            }
        }

        let isZoomed = false;
        function toggleImageZoom(e) {
            e.stopPropagation();
            const img = e.target;
            
            if (!isZoomed) {
                // Zoom in
                img.style.transform = 'scale(1.5)';
                img.style.cursor = 'zoom-out';
                isZoomed = true;
                
                // Update zoom info
                const zoomInfo = document.getElementById('zoomInfo');
                if (zoomInfo) {
                    zoomInfo.innerHTML = 'Click image to zoom out • Press ESC to close';
                }
            } else {
                // Zoom out
                img.style.transform = '';
                img.style.cursor = 'zoom-in';
                isZoomed = false;
                
                // Update zoom info
                const zoomInfo = document.getElementById('zoomInfo');
                if (zoomInfo) {
                    zoomInfo.innerHTML = 'Click image to zoom • Press ESC to close';
                }
            }
        }

        // Enhanced modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Hamburger menu functionality - Updated to match existing pattern
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

            // Close sidebar on window resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    body.classList.remove('sidebar-is-open');
                }
            });

            // Modal functionality
            const modal = document.getElementById('prescriptionModal');
            const closeBtn = document.querySelector('.prescription-modal-close');
            
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closePrescriptionModal();
                    }
                });
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closePrescriptionModal();
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePrescriptionModal();
                    // Also close sidebar if open
                    if (body.classList.contains('sidebar-is-open')) {
                        body.classList.remove('sidebar-is-open');
                    }
                }
            });
            
            console.log('Medical history page loaded successfully');
        });
    </script>
</body>
</html>