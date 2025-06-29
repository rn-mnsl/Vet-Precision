<?php
require_once '../../config/init.php';
requireStaff();

// Pagination setup
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get total count for pagination - FIXED: Only show records for completed appointments
$count_sql = "
    SELECT COUNT(mr.record_id) 
    FROM medical_records mr
    JOIN pets p ON mr.pet_id = p.pet_id
    JOIN appointments a ON mr.appointment_id = a.appointment_id
    JOIN users u ON mr.created_by = u.user_id
    WHERE a.status = 'completed'
";

if (!empty($search)) {
    $count_sql .= " AND (p.name LIKE ? OR mr.symptoms LIKE ? OR mr.diagnosis LIKE ?)";
}

try {
    $count_stmt = $pdo->prepare($count_sql);
    if (!empty($search)) {
        $search_param = "%$search%";
        $count_stmt->execute([$search_param, $search_param, $search_param]);
    } else {
        $count_stmt->execute();
    }
    $total_items = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
} catch (PDOException $e) {
    $total_items = 0;
    $total_pages = 1;
}

// Calculate offset
$offset = ($current_page - 1) * $items_per_page;

// Fetch medical records with related data - FIXED: Only show records for completed appointments
$sql = "
    SELECT 
        mr.record_id,
        mr.pet_id,
        p.name as pet_name,
        p.species,
        mr.appointment_id,
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
        mr.created_by,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
        mr.created_at,
        a.status as appointment_status
    FROM medical_records mr
    JOIN pets p ON mr.pet_id = p.pet_id
    JOIN appointments a ON mr.appointment_id = a.appointment_id
    JOIN users u ON mr.created_by = u.user_id
    WHERE a.status = 'completed'
";

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR mr.symptoms LIKE ? OR mr.diagnosis LIKE ?)";
}

$sql .= " ORDER BY mr.visit_date DESC, mr.created_at DESC LIMIT ? OFFSET ?";

try {
    $stmt = $pdo->prepare($sql);
    $params = [];
    
    if (!empty($search)) {
        $search_param = "%$search%";
        $params = [$search_param, $search_param, $search_param];
    }
    
    $params[] = $items_per_page;
    $params[] = $offset;
    
    $stmt->execute($params);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $medical_records = [];
    $error_message = "Error fetching medical records: " . $e->getMessage();
}

$pageTitle = 'Medical Records - ' . SITE_NAME;
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
        /* CSS Variables */
        :root {
            --primary-color: #ff6b6b;
            --primary-hover: #ff5252;
            --secondary-color: #4ecdc4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gray-light: #e9ecef;
            --text-dark: #2d3748;
            --text-light: #6c757d;
            --border-color: #dee2e6;
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition-base: all 0.2s ease-in-out;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
        }

        /* Dashboard Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar Styles */
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
            transition: transform var(--transition-base);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
            background: var(--light-color);
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0 0 0.5rem 0;
        }

        .page-header p {
            color: var(--text-light);
            margin: 0;
            font-size: 1rem;
        }

        /* Controls Section */
        .controls-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
        }

        .search-container {
            flex: 1;
            max-width: 400px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: var(--transition-base);
            background: var(--light-color);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            background: white;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 0.875rem;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-base);
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: white;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .card-body {
            padding: 0;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table th {
            background: var(--light-color);
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            font-size: 0.875rem;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: var(--light-color);
        }

        /* Pet Info Styles */
        .pet-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .pet-avatar {
            width: 2.5rem;
            height: 2.5rem;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .pet-details h4 {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .pet-species {
            font-size: 0.75rem;
            color: var(--text-light);
            margin: 0;
        }

        /* Vital Signs */
        .vital-signs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            max-width: 200px;
        }

        .vital-item {
            background: var(--light-color);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .vital-item strong {
            color: var(--text-dark);
        }

        /* Description Styles */
        .description-content {
            max-width: 250px;
        }

        .description-item {
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .description-item:last-child {
            margin-bottom: 0;
        }

        .description-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .description-text {
            color: var(--text-light);
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 200px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .follow-up-yes {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .follow-up-no {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        /* Created By Info */
        .created-info {
            display: flex;
            flex-direction: column;
        }

        .creator-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.875rem;
        }

        .created-date {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        /* Action Links */
        .action-links {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition-base);
        }

        .action-view {
            color: var(--primary-color);
            background: rgba(255, 107, 107, 0.1);
        }

        .action-view:hover {
            background: rgba(255, 107, 107, 0.2);
            transform: translateY(-1px);
        }

        .action-delete {
            color: var(--danger-color);
            background: rgba(239, 68, 68, 0.1);
        }

        .action-delete:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }

        /* Pagination */
        .pagination-container {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination-info {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.25rem;
        }

        .pagination-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition-base);
        }

        .pagination-link:hover:not(.disabled) {
            background: var(--light-color);
            border-color: var(--text-light);
        }

        .pagination-link.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .pagination-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-light);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-color: rgba(239, 68, 68, 0.2);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1100;
            }

            body.sidebar-open .sidebar {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .controls-row {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .search-container {
                max-width: none;
            }

            .table-responsive {
                font-size: 0.75rem;
            }

            .vital-signs {
                grid-template-columns: 1fr;
            }

            .action-links {
                flex-direction: column;
                gap: 0.5rem;
            }

            .pagination-container {
                flex-direction: column;
                gap: 1rem;
            }

            .pagination-controls {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../../includes/sidebar-staff.php'; ?>
        <?php include '../../includes/navbar.php'; ?>

        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-file-medical"></i> Medical Records</h1>
                <p>Manage and track all medical records for completed appointments</p>
            </div>

            <!-- Controls Section -->
            <div class="controls-section">
                <div class="controls-row">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search medical records..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               id="searchInput">
                    </div>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create New Record
                    </a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Data Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table"></i> Medical Records List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar"></i> Date</th>
                                    <th><i class="fas fa-paw"></i> Pet</th>
                                    <th><i class="fas fa-heartbeat"></i> Vitals</th>
                                    <th><i class="fas fa-notes-medical"></i> Description</th>
                                    <th><i class="fas fa-user-md"></i> Created By</th>
                                    <th><i class="fas fa-clipboard-check"></i> Status</th>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($medical_records)): ?>
                                    <tr>
                                        <td colspan="7" class="empty-state">
                                            <i class="fas fa-file-medical"></i>
                                            <br>No medical records found
                                            <br><small>Medical records will appear here for completed appointments</small>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($medical_records as $record): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></strong>
                                                </div>
                                                <div class="created-date"><?php echo date('g:i A', strtotime($record['created_at'])); ?></div>
                                            </td>
                                            <td>
                                                <div class="pet-info">
                                                    <div class="pet-avatar">
                                                        <?php echo strtoupper(substr($record['pet_name'], 0, 2)); ?>
                                                    </div>
                                                    <div class="pet-details">
                                                        <h4><?php echo htmlspecialchars($record['pet_name']); ?></h4>
                                                        <p class="pet-species"><?php echo htmlspecialchars($record['species']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="vital-signs">
                                                    <?php if ($record['weight']): ?>
                                                        <div class="vital-item">
                                                            <strong><?php echo $record['weight']; ?></strong>kg
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($record['temperature']): ?>
                                                        <div class="vital-item">
                                                            <strong><?php echo $record['temperature']; ?></strong>°C
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($record['heart_rate']): ?>
                                                        <div class="vital-item">
                                                            HR: <strong><?php echo $record['heart_rate']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($record['respiratory_rate']): ?>
                                                        <div class="vital-item">
                                                            RR: <strong><?php echo $record['respiratory_rate']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="description-content">
                                                    <?php if ($record['symptoms']): ?>
                                                        <div class="description-item">
                                                            <span class="description-label">Symptoms:</span>
                                                            <span class="description-text" title="<?php echo htmlspecialchars($record['symptoms']); ?>">
                                                                <?php echo htmlspecialchars($record['symptoms']); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($record['diagnosis']): ?>
                                                        <div class="description-item">
                                                            <span class="description-label">Diagnosis:</span>
                                                            <span class="description-text" title="<?php echo htmlspecialchars($record['diagnosis']); ?>">
                                                                <?php echo htmlspecialchars($record['diagnosis']); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="created-info">
                                                    <span class="creator-name"><?php echo htmlspecialchars($record['created_by_name']); ?></span>
                                                    <span class="created-date"><?php echo date('M j, Y', strtotime($record['created_at'])); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $record['follow_up_required'] ? 'follow-up-yes' : 'follow-up-no'; ?>">
                                                    <?php echo $record['follow_up_required'] ? 'Follow-up Required' : 'Complete'; ?>
                                                </span>
                                            </td>
                                            <td class="action-links">
                                                <a href="view.php?id=<?php echo $record['record_id']; ?>" class="action-link action-view">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="delete.php?id=<?php echo $record['record_id']; ?>" 
                                                   class="action-link action-delete" 
                                                   onclick="return confirm('Are you sure you want to delete this medical record?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            <?php 
                            $start = ($current_page - 1) * $items_per_page + 1;
                            $end = min($current_page * $items_per_page, $total_items);
                            echo "Showing $start to $end of $total_items entries";
                            ?>
                        </div>
                        <div class="pagination-controls">
                            <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                               class="pagination-link <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php 
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                               class="pagination-link <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        function searchRecords(query) {
            const url = new URL(window.location);
            if (query.trim()) {
                url.searchParams.set('search', query);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page'); // Reset to page 1 on search
            window.location.href = url.toString();
        }

        // Auto-search on input with debounce
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        searchRecords(e.target.value);
                    }, 500);
                });
            }

            // Mobile sidebar toggle
            const hamburgerBtn = document.querySelector('.hamburger-menu');
            const overlay = document.querySelector('.sidebar-overlay');
            const body = document.body;

            if (hamburgerBtn && body) {
                hamburgerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    body.classList.toggle('sidebar-open');
                });
            }
            
            if (overlay && body) {
                overlay.addEventListener('click', function() {
                    body.classList.remove('sidebar-open');
                });
            }
        });
    </script>
</body>
</html>