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
        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
            margin: 0;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-container {
            position: relative;
            margin-right: 1rem;
        }

        .search-input {
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 300px;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .status-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-container {
            overflow-x: auto;
        }

        .medical-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .medical-table th {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }

        .medical-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        .medical-table tr:hover {
            background-color: #f8f9fa;
        }

        .record-id {
            font-family: monospace;
            font-weight: 600;
            color: var(--primary-color);
        }

        .pet-info {
            display: flex;
            flex-direction: column;
        }

        .pet-name {
            font-weight: 600;
            color: #343a40;
        }

        .pet-species {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .vital-signs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.25rem;
            font-size: 0.8rem;
        }

        .vital-item {
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .text-content {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .follow-up-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .follow-up-yes {
            background: #fff3cd;
            color: #856404;
        }

        .follow-up-no {
            background: #d1ecf1;
            color: #0c5460;
        }

        .date-info {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .no-records {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .pagination-link {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: var(--primary-color);
            border: 1px solid #dee2e6;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .pagination-link:hover {
            background-color: #f1f3f5;
            border-color: #ced4da;
        }

        .pagination-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-link.disabled {
            color: #6c757d;
            pointer-events: none;
            background-color: #f8f9fa;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .alert-success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }

        .alert-danger {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-input {
                width: 100%;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; z-index: 1100; position: fixed; top: 0; height: 100vh; margin-top: 0; }
            .main-content { margin-left: 0; }
            body.sidebar-is-open .sidebar { transform: translateX(0); box-shadow: 0 0 20px rgba(0,0,0,0.25); }
            body.sidebar-is-open .sidebar-overlay { opacity: 1; visibility: visible; }
            .main-content { padding-top: 85px; } /* Space for fixed navbar */
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar-staff.php'; ?>
    <?php include '../../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Medical Records</h1>
                <p class="page-subtitle">Manage and track all medical records</p>
            </div>
            <div class="header-actions">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search records..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           onchange="searchRecords(this.value)">
                </div>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Create New
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="status-filters">
            <button class="filter-btn active">Status: Completed Appointments</button>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <table class="medical-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Pet</th>
                            <th>Record Type</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($medical_records)): ?>
                            <tr>
                                <td colspan="7" class="no-records">
                                    <i class="fas fa-file-medical fa-3x" style="color: #dee2e6; margin-bottom: 1rem;"></i>
                                    <br>No medical records found for completed appointments
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($medical_records as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></strong>
                                        <div class="date-info"><?php echo date('g:i A', strtotime($record['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="pet-info">
                                            <span class="pet-name"><?php echo htmlspecialchars($record['pet_name']); ?></span>
                                            <span class="pet-species"><?php echo htmlspecialchars($record['species']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vital-signs">
                                            <?php if ($record['weight']): ?>
                                                <div class="vital-item">Weight: <?php echo $record['weight']; ?>kg</div>
                                            <?php endif; ?>
                                            <?php if ($record['temperature']): ?>
                                                <div class="vital-item">Temp: <?php echo $record['temperature']; ?>°C</div>
                                            <?php endif; ?>
                                            <?php if ($record['heart_rate']): ?>
                                                <div class="vital-item">HR: <?php echo $record['heart_rate']; ?></div>
                                            <?php endif; ?>
                                            <?php if ($record['respiratory_rate']): ?>
                                                <div class="vital-item">RR: <?php echo $record['respiratory_rate']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($record['symptoms']): ?>
                                            <div class="text-content" title="<?php echo htmlspecialchars($record['symptoms']); ?>">
                                                <strong>Symptoms:</strong> <?php echo htmlspecialchars($record['symptoms']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($record['diagnosis']): ?>
                                            <div class="text-content" title="<?php echo htmlspecialchars($record['diagnosis']); ?>">
                                                <strong>Diagnosis:</strong> <?php echo htmlspecialchars($record['diagnosis']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($record['created_by_name']); ?></div>
                                        <div class="date-info"><?php echo date('M j, Y', strtotime($record['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="follow-up-badge <?php echo $record['follow_up_required'] ? 'follow-up-yes' : 'follow-up-no'; ?>">
                                            <?php echo $record['follow_up_required'] ? 'Follow-up Required' : 'Complete'; ?>
                                        </span>
                                        <div class="date-info">Appt: <?php echo ucfirst($record['appointment_status']); ?></div>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $record['record_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="delete.php?id=<?php echo $record['record_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-controls">
                    <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="pagination-link <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        « Previous
                    </a>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="pagination-link <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        Next »
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        // Auto-search on input
        document.querySelector('.search-input').addEventListener('input', function(e) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                searchRecords(e.target.value);
            }, 500);
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
