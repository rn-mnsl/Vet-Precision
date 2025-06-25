<?php
require_once '../../config/init.php';

// --- AUTHENTICATION & PERMISSIONS ---
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: ../../login.php');
    exit();
}
$logged_in_staff_id = $_SESSION['user_id'];

// --- PAGE LOGIC ---
$action = $_GET['action'] ?? 'list';
$view = $_GET['view'] ?? 'list'; // New view parameter
$errors = [];
$success_message = '';

// Get current week dates for week view
$current_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($current_date)));

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appointment'])) {
    $owner_id = $_POST['owner_id'] ?? '';
    $pet_id = $_POST['pet_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($owner_id)) $errors[] = "A client must be selected.";
    if (empty($pet_id)) $errors[] = "A pet must be selected.";
    if (empty($appointment_date)) $errors[] = "Please select a date.";
    if (empty($appointment_time)) $errors[] = "Please select a time slot.";
    if (empty($reason)) $errors[] = "Please provide a reason for the visit.";

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO appointments (pet_id, appointment_date, appointment_time, duration_minutes, status, type, reason, notes, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pet_id, $appointment_date, $appointment_time, 30, 'confirmed', 'Checkup', $reason, $notes, $logged_in_staff_id]);
            
            $_SESSION['success_message'] = "Appointment created successfully!";
            header('Location: index.php?view=' . $view);
            exit();

        } catch (PDOException $e) { $errors[] = "Database error: " . $e->getMessage(); }
    }
}

// --- FETCH DATA ---
$appointments = [];
if ($view === 'list') {
    // --- PAGINATION LOGIC START ---
    $items_per_page = 5; // Define how many appointments to show per page
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;

    // 1. Get the total number of appointments for calculating total pages
    try {
        $count_stmt = $pdo->query("SELECT COUNT(appointment_id) FROM appointments");
        $total_items = (int)$count_stmt->fetchColumn();
        $total_pages = ceil($total_items / $items_per_page);
    } catch (PDOException $e) {
        $errors[] = "Could not count appointments.";
        $total_items = 0;
        $total_pages = 1;
    }
    
    // 2. Calculate the offset for the SQL query
    $offset = ($current_page - 1) * $items_per_page;
    // --- PAGINATION LOGIC END ---

    try {
        // 3. Modify the main query to use LIMIT and OFFSET
        $stmt_appts = $pdo->prepare("
            SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason, p.name AS pet_name,
                   u_owner.first_name AS owner_first_name, u_owner.last_name AS owner_last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN owners o ON p.owner_id = o.owner_id
            JOIN users u_owner ON o.user_id = u_owner.user_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT :limit OFFSET :offset"); // <-- Added LIMIT and OFFSET

        // 4. Bind the new parameters
        $stmt_appts->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt_appts->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt_appts->execute();
        
        $appointments = $stmt_appts->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { 
        $errors[] = "Could not fetch appointments."; 
    }
} elseif ($view === 'week') {
    // --- FIX 1: Use a reliable date calculation method ---
    date_default_timezone_set('UTC'); // Or your local timezone
    $current_date_str = $_GET['date'] ?? 'now';
    $target_date = new DateTime($current_date_str);

    // Calculate start and end of the week (e.g., Sunday to Saturday)
    // This is more reliable than strtotime('... this week')
    $day_of_week = (int)$target_date->format('w'); // 0 (Sun) to 6 (Sat)
    $week_start_dt = (clone $target_date)->modify('-' . $day_of_week . ' days');
    $week_end_dt = (clone $week_start_dt)->modify('+6 days');
    
    // Fetch appointments for the calculated week
    try {
        $stmt_week = $pdo->prepare("
            SELECT a.*, p.name AS pet_name, u_owner.first_name AS owner_first_name, u_owner.last_name AS owner_last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN owners o ON p.owner_id = o.owner_id
            JOIN users u_owner ON o.user_id = u_owner.user_id
            WHERE a.appointment_date BETWEEN ? AND ? AND a.status != 'cancelled'
            ORDER BY a.appointment_date, a.appointment_time");
        $stmt_week->execute([$week_start_dt->format('Y-m-d'), $week_end_dt->format('Y-m-d')]);
        $week_appointments = $stmt_week->fetchAll(PDO::FETCH_ASSOC);
        
        // --- FIX 2: Use a more flexible data structure (group by hour) ---
        // This allows appointments at any time (e.g., 9:15) to show up in the 9:00 hour slot.
        $appointments_grid = [];
        foreach ($week_appointments as $appt) {
            $date_key = $appt['appointment_date'];
            $hour_key = date('H', strtotime($appt['appointment_time'])); // '08', '09', etc.
            if (!isset($appointments_grid[$date_key][$hour_key])) {
                 $appointments_grid[$date_key][$hour_key] = [];
            }
            $appointments_grid[$date_key][$hour_key][] = $appt;
        }
    } catch (PDOException $e) { 
        $errors[] = "Could not fetch week appointments."; 
    }

    // Generate helper arrays needed for the CSS Grid HTML structure
    $week_dates = [];
    if (isset($week_start_dt)) { // Ensure this only runs for week view
        $day_looper = clone $week_start_dt;
        for ($i = 0; $i < 7; $i++) {
            $week_dates[] = [
                'date' => $day_looper->format('Y-m-d'),
                'day_short' => $day_looper->format('D'),
                'day_number' => $day_looper->format('j')
            ];
            $day_looper->modify('+1 day');
        }
    }

    // Time slots for the grid rows, as defined in your preferred layout
    $time_slots = [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
        '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00'
    ];
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
$pageTitle = 'Manage Appointments - ' . SITE_NAME;

// Generate week dates array
$week_dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime($week_start . " +$i days"));
    $week_dates[] = [
        'date' => $date,
        'day_name' => date('l', strtotime($date)),
        'day_short' => date('D', strtotime($date)),
        'day_number' => date('j', strtotime($date))
    ];
}

// Time slots for week view
$time_slots = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
    '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* === EXISTING STYLES === */
        body {
            background-color: var(--light-color);
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-layout { display: flex; min-height: 100vh; }

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

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            flex: 1;
        }

        /* Action buttons container */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Status action buttons */
        .btn-status {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 6px;
            border: 1px solid;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-status:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-requested {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fbbf24;
        }

        .btn-requested:hover:not(:disabled) {
            background-color: #fde68a;
        }

        .btn-confirmed {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }

        .btn-confirmed:hover:not(:disabled) {
            background-color: #a7f3d0;
        }

        .btn-completed {
            background-color: #dbeafe;
            color: #1e40af;
            border-color: #3b82f6;
        }

        .btn-completed:hover:not(:disabled) {
            background-color: #bfdbfe;
        }

        .btn-cancelled {
            background-color: #fee2e2;
            color: #dc2626;
            border-color: #ef4444;
        }

        .btn-cancelled:hover:not(:disabled) {
            background-color: #fecaca;
        }

        /* Status badges */
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-requested {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #dc2626;
        }

        /* Page Header and Card */
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .page-header p { margin: 0.5rem 0 0; color: var(--text-light); }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Button Styles */
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary { background: var(--gray-light); color: var(--text-dark); }

        /* Table Styles */
        .table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--gray-light); }
        .table th { color: var(--text-light); }

        /* === NEW VIEW TOGGLE STYLES === */
        .view-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .view-toggle {
            display: flex;
            background: white;
            border-radius: 8px;
            padding: 0.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .view-toggle-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: var(--text-light);
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .view-toggle-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .view-toggle-btn:hover:not(.active) {
            background: var(--light-color);
            color: var(--text-dark);
        }

        /* === WEEK VIEW STYLES === */
        .week-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .week-navigation {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .week-nav-btn {
            background: white;
            border: 1px solid var(--gray-light);
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .week-nav-btn:hover {
            background: var(--light-color);
        }

        .week-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            gap: 1px;
            background: var(--gray-light);
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            overflow: hidden;
        }

        .calendar-header {
            background: white;
            padding: 1rem 0.5rem;
            text-align: center;
            font-weight: 600;
            color: var(--text-dark);
        }

        .calendar-header.time-header {
            background: var(--light-color);
        }

        .calendar-day-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .day-name {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .day-number {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .time-slot-row {
            background: var(--light-color);
            padding: 0.75rem 0.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-light);
            border-right: 1px solid var(--gray-light);
        }

        .calendar-cell {
            background: white;
            min-height: 60px;
            padding: 0.25rem;
            position: relative;
            border-right: 1px solid var(--gray-light);
        }

        .appointment-block {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.99rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .appointment-block:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .appointment-block.status-requested {
            background: #f59e0b;
        }

        .appointment-block.status-confirmed {
            background: #10b981;
        }

        .appointment-block.status-completed {
            background: #3b82f6;
        }

        .appointment-block.status-cancelled {
            background: #ef4444;
        }

        .appointment-client {
            font-weight: 600;
            display: block;
        }

        .appointment-pet {
            opacity: 0.9;
            font-size: 0.7rem;
        }

        .appointment-time {
            display: block;
            font-weight: 700; /* Makes the time bold */
            margin-bottom: 3px;
        }

        .appointment-client {
            font-weight: 600; /* Make client name slightly bold */
            display: block;
        }

        .appointment-pet {
            opacity: 0.9;
            font-size: 0.9em; /* Makes pet name slightly smaller relative to parent */
            display: block;
        }


        /* === PAGINATION STYLES === */
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-light);
        }

        .pagination-link {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: var(--primary-color);
            border: 1px solid var(--gray-light);
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .pagination-link:hover {
            background-color: var(--light-color);
            border-color: #ccc;
        }

        .pagination-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            cursor: default;
        }

        .pagination-link.disabled {
            color: var(--text-light);
            pointer-events: none;
            background-color: #f8f9fa;
        }

        /* === FORM STYLES === */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem 2.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 0.5rem; font-weight: 600; color: var(--text-dark); }
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 8px; }
        .form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--gray-light); }
        .time-slots-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem; }
        .time-slot input[type="radio"] { display: none; }
        .time-slot label { display: block; text-align: center; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 8px; cursor: pointer; }
        .time-slot input[type="radio"]:checked + label { background-color: var(--primary-color); color: white; border-color: var(--primary-color); }
        .time-slot.disabled label { background-color: var(--gray-light); color: var(--text-light); cursor: not-allowed; }
        
        /* Modal Styles */
        .selector-button { text-align: left; background-color: #fff; border: 1px solid #ced4da; padding: 0.75rem; border-radius: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .selector-button:disabled { background-color: var(--gray-light); cursor: not-allowed; }
        .selector-placeholder { color: var(--text-light); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1050; display: none; justify-content: center; align-items: center; }
        .modal-content { background: #fff; border-radius: 12px; width: 90%; max-width: 800px; padding: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-light); }
        .modal-header h3 { margin: 0; font-size: 1.25rem; }
        .modal-header .close-btn { background: none; border: none; font-size: 1.75rem; cursor: pointer; }
        .modal-body .search-bar { width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 8px; margin-bottom: 1rem; }
        .modal-body .table-wrapper { max-height: 50vh; overflow-y: auto; }
        .modal-body .table tbody tr { cursor: pointer; }
        .modal-body .table tbody tr:hover { background-color: var(--light-color); }

        /* Responsive */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-status {
                width: 100%;
                margin-bottom: 0.25rem;
            }

            .calendar-grid {
                font-size: 0.8rem;
            }

            .calendar-cell {
                min-height: 50px;
            }

            .view-controls {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../../includes/sidebar-staff.php'; ?>
        <main class="main-content">
            <?php if ($action === 'create'): ?>
                <!-- CREATE VIEW -->
                <div class="page-header"><div><h1>Create Appointment</h1><p>Fill in the details below to schedule a new appointment.</p></div></div>
                <div class="card">
                    <form action="index.php?action=create&view=<?php echo $view; ?>" method="POST">
                        <div class="form-grid">
                            <div class="form-group"><label>Client*</label><button type="button" class="selector-button" id="select-client-btn"><span class="selector-placeholder" id="client-name-display">Select a client</span><i class="fas fa-chevron-down"></i></button></div>
                            <div class="form-group"><label>Pet*</label><button type="button" class="selector-button" id="select-pet-btn" disabled><span class="selector-placeholder" id="pet-name-display">Select a client first</span><i class="fas fa-chevron-down"></i></button></div>
                        </div>
                        <div class="form-grid" style="margin-top: 1.5rem;">
                            <div class="form-group"><label for="appointment_date">Select Date*</label><input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                            <div class="form-group"><label>Available Time Slots*</label><div class="time-slots-grid" id="time-slots-container">Select a date to see times</div></div>
                            <div class="form-group"><label for="reason">Reason for Visit*</label><textarea id="reason" name="reason" required></textarea></div>
                            <div class="form-group"><label for="notes">Additional Notes</label><textarea id="notes" name="notes"></textarea></div>
                        </div>
                        <div class="form-actions"><a href="index.php?view=<?php echo $view; ?>" class="btn btn-secondary">Cancel</a><button type="submit" name="create_appointment" class="btn btn-primary">Create Appointment</button></div>
                        <input type="hidden" id="selected_owner_id" name="owner_id"><input type="hidden" id="selected_pet_id" name="pet_id">
                    </form>
                </div>
            <?php else: ?>
                <!-- MAIN VIEW -->
                <div class="page-header">
                    <div><h1>Appointments</h1><p>Schedule and manage appointments with multiple calendar views</p></div>
                    <a href="index.php?action=create&view=<?php echo $view; ?>" class="btn btn-primary"><i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Create New</a>
                </div>

                <!-- VIEW CONTROLS -->
                <div class="view-controls">
                    <div class="view-toggle">
                        <a href="index.php?view=list" class="view-toggle-btn <?php echo $view === 'list' ? 'active' : ''; ?>">List</a>
                        <a href="index.php?view=week" class="view-toggle-btn <?php echo $view === 'week' ? 'active' : ''; ?>">Week</a>
                    </div>
                </div>

                <?php if ($view === 'week'): ?>
                    <!-- WEEK VIEW -->
                    <div class="card">
                        <div class="week-header">
                            <div class="week-navigation">
                                <?php
                                // Get navigation dates from the reliable DateTime objects
                                $prev_week_date = (clone $target_date)->modify('-1 week')->format('Y-m-d');
                                $next_week_date = (clone $target_date)->modify('+1 week')->format('Y-m-d');
                                ?>
                                <a href="index.php?view=week&date=<?php echo $prev_week_date; ?>" class="week-nav-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <span class="week-title">
                                    <?php echo $week_start_dt->format('M j') . ' - ' . $week_end_dt->format('M j, Y'); ?>
                                </span>
                                <a href="index.php?view=week&date=<?php echo $next_week_date; ?>" class="week-nav-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                            <button class="btn btn-secondary" onclick="goToToday()">Today</button>
                        </div>

                        <div class="calendar-grid">
                            <!-- Header row -->
                            <div class="calendar-header time-header"></div>
                            <?php foreach ($week_dates as $date_info): ?>
                                <div class="calendar-header">
                                    <div class="calendar-day-header">
                                        <span class="day-name"><?php echo $date_info['day_short']; ?></span>
                                        <span class="day-number"><?php echo $date_info['day_number']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Time slots and appointments -->
                            <?php foreach ($time_slots as $time): ?>
                                <div class="time-slot-row"><?php echo date('g:i A', strtotime($time . ':00')); ?></div>
                                <?php foreach ($week_dates as $date_info): ?>
                                    <div class="calendar-cell">
                                        <?php
                                        // NEW LOGIC: This checks our flexible hourly data against your specific 30-min slots
                                        $current_date = $date_info['date'];
                                        $current_hour = substr($time, 0, 2); // Get '08', '09', etc.

                                        // Check if appointments exist for this day and hour
                                        if (isset($appointments_grid[$current_date][$current_hour])) {
                                            // Loop through appointments for that hour
                                            foreach ($appointments_grid[$current_date][$current_hour] as $appt) {
                                                // Check if the appointment's specific time matches the current 30-min slot
                                                if (date('H:i', strtotime($appt['appointment_time'])) === $time) {
                                                    echo '<div class="appointment-block status-' . $appt['status'] . '" onclick="viewAppointment(' . $appt['appointment_id'] . ')">';
                                                    echo '<span class="appointment-time">' . date('g:i A', strtotime($appt['appointment_time'])) . '</span>';
                                                    echo '<span class="appointment-client">' . htmlspecialchars($appt['owner_first_name'] . ' ' . $appt['owner_last_name']) . '</span>';
                                                    echo '<span class="appointment-pet">' . htmlspecialchars($appt['pet_name']) . '</span>';
                                                    echo '</div>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- LIST VIEW -->
                    <div class="card">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Client</th>
                                    <th>Pet</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointments)): ?>
                                    <tr><td colspan="6" style="text-align:center; padding: 3rem;">No appointments found.</td></tr>
                                <?php else: foreach ($appointments as $appt): ?>
                                    <tr data-appointment-id="<?php echo $appt['appointment_id']; ?>">
                                        <td>
                                            <strong><?php echo date("M d, Y", strtotime($appt['appointment_date'])); ?></strong><br>
                                            <small><?php echo date("g:i A", strtotime($appt['appointment_time'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appt['owner_first_name'] . ' ' . $appt['owner_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['pet_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $appt['status']; ?>">
                                                <?php echo ucfirst(htmlspecialchars($appt['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-status btn-requested" data-status="requested" 
                                                        <?php echo $appt['status'] === 'requested' ? 'disabled' : ''; ?>>
                                                    Requested
                                                </button>
                                                <button class="btn btn-status btn-confirmed" data-status="confirmed"
                                                        <?php echo $appt['status'] === 'confirmed' ? 'disabled' : ''; ?>>
                                                    Confirmed
                                                </button>
                                                <button class="btn btn-status btn-completed" data-status="completed"
                                                        <?php echo $appt['status'] === 'completed' ? 'disabled' : ''; ?>>
                                                    Completed
                                                </button>
                                                <button class="btn btn-status btn-cancelled" data-status="cancelled"
                                                        <?php echo $appt['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                                    Cancelled
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                            <!-- PAGINATION CONTROLS START -->
                            <div class="pagination-controls">
                                <?php if ($total_pages > 1): ?>
                                    <!-- Previous Button -->
                                    <a href="?view=list&page=<?php echo $current_page - 1; ?>" 
                                    class="pagination-link <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                        « Previous
                                    </a>

                                    <!-- Page Number Links -->
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?view=list&page=<?php echo $i; ?>" 
                                        class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <!-- Next Button -->
                                    <a href="?view=list&page=<?php echo $current_page + 1; ?>" 
                                    class="pagination-link <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                        Next »
                                    </a>
                                <?php endif; ?>
                            </div>
                            <!-- PAGINATION CONTROLS END -->
                    </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODALS -->
    <div class="modal-overlay" id="client-modal"><div class="modal-content"><div class="modal-header"><h3>Select Client</h3><button type="button" class="close-btn">×</button></div><div class="modal-body"><input type="search" id="client-search-input" class="search-bar" placeholder="Search by name, email, or phone..."><div class="table-wrapper"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody id="client-list-tbody"></tbody></table></div></div></div></div>
    <div class="modal-overlay" id="pet-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Pet</h3>
                <button type="button" class="close-btn">×</button>
            </div>
            <div class="modal-body">
                <input type="search" id="pet-search-input" class="search-bar" placeholder="Search by pet name, species, or breed...">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Species</th>
                                <th>Breed</th>
                            </tr>
                        </thead>
                        <tbody id="pet-list-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Week navigation functions
        function navigateWeek(direction) {
            const currentDate = new URLSearchParams(window.location.search).get('date') || '<?php echo date('Y-m-d'); ?>';
            const newDate = new Date(currentDate);
            newDate.setDate(newDate.getDate() + (direction * 7));
            const formattedDate = newDate.toISOString().split('T')[0];
            window.location.href = `index.php?view=week&date=${formattedDate}`;
        }

        function goToToday() {
            window.location.href = 'index.php?view=week';
        }

        function viewAppointment(appointmentId) {
            // You can implement appointment details modal or redirect to edit page
            console.log('View appointment:', appointmentId);
            // For now, just alert
            alert('Appointment details: ' + appointmentId);
        }

        // Form functionality (existing code)
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.querySelector('form')) return;
            
            const clientModal = document.getElementById('client-modal');
            const petModal = document.getElementById('pet-modal');
            const selectClientBtn = document.getElementById('select-client-btn');
            const selectPetBtn = document.getElementById('select-pet-btn');
            const clientNameDisplay = document.getElementById('client-name-display');
            const petNameDisplay = document.getElementById('pet-name-display');
            const ownerIdInput = document.getElementById('selected_owner_id');
            const petIdInput = document.getElementById('selected_pet_id');
            const clientSearchInput = document.getElementById('client-search-input');
            const petSearchInput = document.getElementById('pet-search-input');
            const clientListTbody = document.getElementById('client-list-tbody');
            const petListTbody = document.getElementById('pet-list-tbody');
            const dateInput = document.getElementById('appointment_date');
            const timeSlotsContainer = document.getElementById('time-slots-container');
            
            let currentOwnerId = null;
            
            const openModal = (modal) => modal.style.display = 'flex';
            const closeModal = (modal) => modal.style.display = 'none';
            
            selectClientBtn.addEventListener('click', () => { 
                fetchClients(); 
                openModal(clientModal); 
            });
            
            selectPetBtn.addEventListener('click', () => {
                petSearchInput.value = '';
                fetchPets(currentOwnerId);
                openModal(petModal);
            });
            
            document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', (e) => { 
                if (e.target === m) closeModal(m); 
            }));
            
            document.querySelectorAll('.close-btn').forEach(btn => btn.addEventListener('click', () => 
                closeModal(btn.closest('.modal-overlay'))
            ));
            
            async function fetchClients(searchTerm = '') {
                searchTerm = searchTerm.trim();
                clientListTbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Loading...</td></tr>';
                
                try {
                    const response = await fetch(`ajax/get_clients.php?search=${encodeURIComponent(searchTerm)}`);
                    const clients = await response.json();
                    clientListTbody.innerHTML = '';
                    
                    if (clients.length > 0) {
                        clients.forEach(c => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `<td>${c.first_name} ${c.last_name}</td><td>${c.email}</td><td>${c.phone}</td>`;
                            tr.dataset.ownerId = c.owner_id;
                            tr.dataset.clientName = `${c.first_name} ${c.last_name}`;
                            
                            tr.addEventListener('click', () => {
                                ownerIdInput.value = tr.dataset.ownerId;
                                clientNameDisplay.textContent = tr.dataset.clientName;
                                clientNameDisplay.classList.remove('selector-placeholder');
                                selectPetBtn.disabled = false;
                                petNameDisplay.textContent = 'Select a pet';
                                petNameDisplay.classList.add('selector-placeholder');
                                petIdInput.value = '';
                                currentOwnerId = tr.dataset.ownerId;
                                closeModal(clientModal);
                            });
                            
                            clientListTbody.appendChild(tr);
                        });
                    } else {
                        clientListTbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No clients found.</td></tr>';
                    }
                } catch (error) {
                    clientListTbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: red;">Error loading clients.</td></tr>';
                }
            }
            
            clientSearchInput.addEventListener('input', () => fetchClients(clientSearchInput.value.trim()));
            
            async function fetchPets(ownerId, searchTerm = '') {
                if (!ownerId) return;
                
                searchTerm = searchTerm.trim();
                petListTbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Loading...</td></tr>';
                
                try {
                    const response = await fetch(`ajax/get_pets.php?owner_id=${ownerId}&search=${encodeURIComponent(searchTerm)}`);
                    const pets = await response.json();
                    petListTbody.innerHTML = '';
                    
                    if (pets.length > 0) {
                        pets.forEach(p => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `<td>${p.name}</td><td>${p.species}</td><td>${p.breed}</td>`;
                            tr.dataset.petId = p.pet_id;
                            tr.dataset.petName = p.name;
                            
                            tr.addEventListener('click', () => {
                                petIdInput.value = tr.dataset.petId;
                                petNameDisplay.textContent = tr.dataset.petName;
                                petNameDisplay.classList.remove('selector-placeholder');
                                closeModal(petModal);
                            });
                            
                            petListTbody.appendChild(tr);
                        });
                    } else {
                        const message = searchTerm ? 'No pets found matching your search.' : 'No pets found for this client.';
                        petListTbody.innerHTML = `<tr><td colspan="3" style="text-align:center;">${message}</td></tr>`;
                    }
                } catch (error) {
                    petListTbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: red;">Error loading pets.</td></tr>';
                }
            }
            
            petSearchInput.addEventListener('input', () => {
                if (currentOwnerId) {
                    fetchPets(currentOwnerId, petSearchInput.value.trim());
                }
            });
            
            async function updateAvailableSlots() {
                const selectedDate = dateInput.value;
                if (!selectedDate) {
                    timeSlotsContainer.innerHTML = 'Select a date to see times';
                    return;
                }
                
                timeSlotsContainer.innerHTML = 'Loading times...';
                
                try {
                    const response = await fetch(`ajax/get_booked_slots.php?date=${selectedDate}`);
                    const bookedSlots = await response.json();
                    timeSlotsContainer.innerHTML = '';
                    
                    ['09:00','09:30','10:00','10:30','11:00','11:30','12:00','14:30','15:00','15:30','16:00','16:30'].forEach(slot => {
                        const time24h = slot + ':00';
                        const isBooked = bookedSlots.includes(time24h);
                        const slotId = `time_${slot.replace(':', '')}`;
                        
                        const slotDiv = document.createElement('div');
                        slotDiv.className = 'time-slot' + (isBooked ? ' disabled' : '');
                        slotDiv.innerHTML = `<input type="radio" id="${slotId}" name="appointment_time" value="${time24h}" ${isBooked ? 'disabled' : ''} required><label for="${slotId}">${slot}</label>`;
                        timeSlotsContainer.appendChild(slotDiv);
                    });
                } catch (error) {
                    timeSlotsContainer.innerHTML = 'Error loading time slots';
                }
            }
            
            dateInput.addEventListener('change', updateAvailableSlots);
        });

        // Status update functionality
        document.addEventListener('click', async function(e) {
            if (e.target.classList.contains('btn-status') && !e.target.disabled) {
                const button = e.target;
                const row = button.closest('tr');
                const appointmentId = row.dataset.appointmentId;
                const newStatus = button.dataset.status;
                
                const statusNames = {
                    'requested': 'Requested',
                    'confirmed': 'Confirmed', 
                    'completed': 'Completed',
                    'cancelled': 'Cancelled'
                };
                
                if (!confirm(`Are you sure you want to mark this appointment as ${statusNames[newStatus]}?`)) {
                    return;
                }
                
                const allButtons = row.querySelectorAll('.btn-status');
                allButtons.forEach(btn => btn.disabled = true);
                
                try {
                    const formData = new FormData();
                    formData.append('appointment_id', appointmentId);
                    formData.append('status', newStatus);
                    
                    const response = await fetch('ajax/update_appointment_status.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok && result.success) {
                        const statusBadge = row.querySelector('.status-badge');
                        statusBadge.textContent = statusNames[newStatus];
                        statusBadge.className = `status-badge status-${newStatus}`;
                        
                        allButtons.forEach(btn => {
                            btn.disabled = (btn.dataset.status === newStatus);
                        });
                        
                        showNotification('Status updated successfully!', 'success');
                        
                    } else {
                        throw new Error(result.error || 'Failed to update status');
                    }
                    
                } catch (error) {
                    console.error('Error updating status:', error);
                    alert('Failed to update appointment status. Please try again.');
                    
                    allButtons.forEach(btn => {
                        btn.disabled = false;
                    });
                }
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            
            if (type === 'success') {
                notification.style.backgroundColor = '#10b981';
            } else if (type === 'error') {
                notification.style.backgroundColor = '#ef4444';
            }
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>