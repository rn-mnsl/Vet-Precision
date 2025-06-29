<?php
require_once '../../config/init.php';

// --- SESSION & AUTHENTICATION ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For demonstration purposes, let's assume a user is logged in.
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4; // Assuming user_id 4 is a client
}
$user_id = $_SESSION['user_id'];

// --- HELPER FUNCTION ---
function getOwnerId($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT owner_id FROM owners WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

// --- PAGE LOGIC ---
$owner_id = getOwnerId($pdo, $user_id);
$action = $_GET['action'] ?? 'list';
$view = $_GET['view'] ?? 'list'; // Default to list view as it's more common

// ADD THIS LINE
$preselected_pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : null;

$errors = [];
$success_message = '';

// --- HANDLE FORM SUBMISSION (CREATE APPOINTMENT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appointment'])) {
    $pet_id = $_POST['pet_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($pet_id)) $errors[] = "Please select a pet.";
    if (empty($appointment_date)) $errors[] = "Please select a date.";
    if (empty($appointment_time)) $errors[] = "Please select a time slot.";
    if (empty($reason)) $errors[] = "Please provide a reason for the visit.";

    if (empty($errors) && $owner_id) {
        try {
            $sql = "INSERT INTO appointments (pet_id, appointment_date, appointment_time, duration_minutes, status, type, reason, notes, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pet_id, $appointment_date, $appointment_time, 30, 'requested', 'Checkup', $reason, $notes, $user_id]);
            
            $_SESSION['success_message'] = "Appointment requested successfully!";
            header('Location: index.php');
            exit();

        } catch (PDOException $e) {
            $errors[] = "We couldn't save your appointment. Please try again later. Error: " . $e->getMessage();
        }
    }
}

// --- FETCH DATA FOR VIEWS ---
$pets = [];
$appointments = [];

if ($owner_id) {
    // Fetch pets for the create form
    $stmt_pets = $pdo->prepare("SELECT pet_id, name FROM pets WHERE owner_id = ?");
    $stmt_pets->execute([$owner_id]);
    $pets = $stmt_pets->fetchAll(PDO::FETCH_ASSOC);

    if ($view === 'list') {
        // ... Pagination logic is the same ...
        $items_per_page = 5;
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $count_stmt = $pdo->prepare("SELECT COUNT(a.appointment_id) FROM appointments a JOIN pets p ON a.pet_id = p.pet_id WHERE p.owner_id = ?");
        $count_stmt->execute([$owner_id]);
        $total_items = (int)$count_stmt->fetchColumn();
        $total_pages = ceil($total_items / $items_per_page);
        $offset = ($current_page - 1) * $items_per_page;
        
        // REMINDER: SQL query is updated to fetch more details to match staff page needs
        $stmt_appts = $pdo->prepare("
            SELECT a.*, p.name AS pet_name 
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            WHERE p.owner_id = :owner_id 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT :limit OFFSET :offset");
        $stmt_appts->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
        $stmt_appts->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt_appts->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt_appts->execute();
        $appointments = $stmt_appts->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($view === 'week') {
        date_default_timezone_set('Asia/Manila');
        $current_date_str = $_GET['date'] ?? 'now';
        $target_date = new DateTime($current_date_str);
        
        $week_start_dt = (clone $target_date)->modify('monday this week');
        $week_end_dt = (clone $week_start_dt)->modify('+6 days');

        // REMINDER: SQL query fetches all necessary data for the interactive calendar
        $stmt_week = $pdo->prepare("
            SELECT a.*, p.name AS pet_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            WHERE p.owner_id = ? AND a.appointment_date BETWEEN ? AND ? AND a.status != 'cancelled'
            ORDER BY a.appointment_date, a.appointment_time");
        $stmt_week->execute([$owner_id, $week_start_dt->format('Y-m-d'), $week_end_dt->format('Y-m-d')]);
        $week_appointments = $stmt_week->fetchAll(PDO::FETCH_ASSOC);
        
        $appointments_grid = [];
        foreach ($week_appointments as $appt) {
            $date_key = $appt['appointment_date'];
            $hour_key = date('H', strtotime($appt['appointment_time']));
            if (!isset($appointments_grid[$date_key][$hour_key])) {
                $appointments_grid[$date_key][$hour_key] = [];
            }
            $appointments_grid[$date_key][$hour_key][] = $appt;
        }

        // <!-- NEW --> Logic to create array for mobile agenda view
        $appointments_by_day = [];
        foreach ($week_appointments as $appt) {
            $date_key = $appt['appointment_date'];
            if (!isset($appointments_by_day[$date_key])) {
                $appointments_by_day[$date_key] = [];
            }
            $appointments_by_day[$date_key][] = $appt;
        }

        $week_dates = [];
        $day_looper = clone $week_start_dt;
        for ($i = 0; $i < 7; $i++) {
            $week_dates[] = ['date' => $day_looper->format('Y-m-d'), 'day_short' => $day_looper->format('D'), 'day_number' => $day_looper->format('j')];
            $day_looper->modify('+1 day');
        }
        
        $time_slots = [];
        for ($h = 8; $h <= 19; $h++) {
            $time_slots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }
    }
} else {
    $errors[] = "Could not find your owner profile. Please contact support.";
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}


$pageTitle = 'Appointments - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../../includes/favicon.php'; ?>
    <style>
        /* This entire style block is replaced with the more comprehensive one from sidebar-staff.php */
        /* --- 1. BASE AND LAYOUT STYLES --- */
        body { background-color: var(--light-color); margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .dashboard-layout { display: flex; min-height: 100vh; }
        .main-content { margin-left: 250px; padding: 2rem; flex: 1; }

        /* --- 2. RESPONSIVE SIDEBAR & OVERLAY STYLES --- */
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1099; opacity: 0; visibility: hidden; transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out; }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; z-index: 1100; position: fixed; top: 0; height: 100vh; margin-top: 0; }
            .main-content { margin-left: 0; }
            body.sidebar-is-open .sidebar { transform: translateX(0); box-shadow: 0 0 20px rgba(0,0,0,0.25); }
            body.sidebar-is-open .sidebar-overlay { opacity: 1; visibility: visible; }
            .main-content { padding-top: 85px; } /* Space for fixed navbar */
        }
        
        /* --- 3. PAGE CONTENT STYLES (HEADER, CARD, BUTTONS) --- */
        .page-header { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { margin: 0; font-size: 2rem; }
        .page-header p { margin: 0.5rem 0 0; color: var(--text-light); }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; }

        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary { background: var(--gray-light); color: var(--text-dark); }
        .btn-close { background: var(--gray-light); color: var(--text-dark); }
        .btn-close:hover { background: #dc3545; color: white; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; border-radius: 6px; }

        /* --- 4. TABLE & LIST VIEW STYLES --- */
        .table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--gray-light); vertical-align: middle; }
        .table th { color: var(--text-light); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        
        .status-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .status-requested { background-color: #fef3c7; color: #92400e; }
        .status-confirmed { background-color: #d1fae5; color: #065f46; }
        .status-completed { background-color: #dbeafe; color: #1e40af; }
        .status-cancelled { background-color: #fee2e2; color: #dc2626; }
        
        .pagination-controls { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-light); }
        .pagination-link { padding: 0.5rem 1rem; text-decoration: none; color: var(--primary-color); border: 1px solid var(--gray-light); border-radius: 6px; transition: all 0.2s ease; font-weight: 500; }
        .pagination-link:hover { background-color: var(--light-color); border-color: #ccc; }
        .pagination-link.active { background-color: var(--primary-color); color: white; border-color: var(--primary-color); }
        .pagination-link.disabled { color: var(--text-light); pointer-events: none; background-color: #f8f9fa; }
        
        @media (max-width: 768px) {
            .table thead { display: none; }
            .table, .table tbody, .table tr, .table td { display: block; width: 100%; }
            .table tr { margin-bottom: 1rem; border: 1px solid var(--gray-light); border-radius: 8px; padding: 0.5rem; }
            .table td { display: flex; justify-content: space-between; padding: 0.75rem 0.5rem; text-align: right; border-bottom: 1px solid #eee; }
            .table td:last-child { border-bottom: none; }
            .table td::before { content: attr(data-label); font-weight: 600; text-align: left; margin-right: 1rem; color: var(--text-dark); }
        }

        /* --- 5. VIEW TOGGLE & WEEK VIEW CALENDAR --- */
        .view-controls { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .view-toggle { display: flex; background: white; border-radius: 8px; padding: 0.25rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .view-toggle-btn { padding: 0.5rem 1rem; border: none; background: transparent; color: var(--text-light); font-weight: 500; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; text-decoration: none; }
        .view-toggle-btn.active { background: var(--primary-color); color: white; }
        .view-toggle-btn:hover:not(.active) { background: var(--light-color); color: var(--text-dark); }

        .week-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .week-navigation { display: flex; align-items: center; gap: 1rem; }
        .week-nav-btn { background: white; border: 1px solid var(--gray-light); padding: 0.5rem; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; }
        .week-nav-btn:hover { background: var(--light-color); }
        .week-title { font-size: 1.1rem; font-weight: 600; color: var(--text-dark); }
        
        .calendar-grid-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 1px solid var(--gray-light); border-radius: 8px; }
        .calendar-grid { display: grid; grid-template-columns: 80px repeat(7, 1fr); gap: 1px; background: var(--gray-light); border-radius: 8px; overflow: hidden; min-width: 900px; }
        .calendar-header { background: white; padding: 1rem 0.5rem; text-align: center; font-weight: 600; color: var(--text-dark); }
        .calendar-day-header { display: flex; flex-direction: column; align-items: center; gap: 0.25rem; }
        .day-name { font-size: 0.85rem; color: var(--text-light); }
        .day-number { font-size: 1.1rem; font-weight: 700; }
        .time-slot-row { background: var(--light-color); padding: 0.75rem 0.5rem; text-align: center; font-size: 0.85rem; color: var(--text-light); }
        .calendar-cell { background: white; min-height: 60px; padding: 0.25rem; display: flex; flex-direction: column; gap: 0.25rem; }

        .appointment-block { background: var(--primary-color); color: white; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem; margin-bottom: 0.25rem; cursor: pointer; transition: all 0.2s ease; overflow: hidden; }
        .appointment-block:hover { opacity: 0.9; transform: translateY(-1px); }
        .appointment-block.status-requested { background-color: #f59e0b; }
        .appointment-block.status-confirmed { background-color: #10b981; }
        .appointment-block.status-completed { background-color: #3b82f6; }
        .appointment-summary { display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
        .appointment-summary i { transition: transform 0.2s ease; }
        .appointment-details { display: none; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.3); font-size: 0.75rem; line-height: 1.4; }
        .appointment-block.is-expanded .appointment-details { display: block; }
        .appointment-block.is-expanded .appointment-summary i { transform: rotate(180deg); }

        /* --- 6. MOBILE AGENDA VIEW STYLES --- */
        .agenda-view { display: none; }
        .agenda-day { margin-bottom: 1.5rem; border: 1px solid var(--gray-light); border-radius: 8px; overflow: hidden; }
        .agenda-day-header { background-color: var(--light-color); padding: 0.75rem 1rem; font-weight: 700; font-size: 1.1rem; }
        .agenda-appointments { list-style: none; padding: 0; margin: 0; }
        .agenda-appointment-item { display: flex; padding: 1rem; gap: 1rem; align-items: flex-start; cursor: pointer; transition: background-color 0.2s ease; color: white; border-bottom: 1px solid rgba(0,0,0,0.1); }
        .agenda-appointment-item.status-requested { background-color: #f59e0b; }
        .agenda-appointment-item.status-confirmed { background-color: #10b981; }
        .agenda-appointment-item.status-completed { background-color: #3b82f6; }
        .agenda-appointment-item:hover { filter: brightness(95%); }
        .agenda-appointment-item .time { flex-shrink: 0; width: 80px; font-weight: 600; color: rgba(255,255,255,0.85); }
        .agenda-appointment-item .details .pet-name { font-weight: 600; font-size: 1rem; color: white; }
        .agenda-appointment-item .details .reason-text { font-size: 0.9rem; color: rgba(255,255,255,0.85); margin-top: 0.25rem; }
        .no-appointments { padding: 1.5rem 1rem; text-align: center; color: var(--text-light); }

        @media (max-width: 768px) {
            .calendar-grid-wrapper { display: none; }
            .agenda-view { display: block; }
        }

        /* --- 7. FORM & MODAL STYLES --- */
        .form-container { max-width: 950px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem 2.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 0.5rem; color: #495057; }
        .form-group label span { color: #dc3545; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid #ced4da; border-radius: 8px; font-size: 1rem; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .time-slots-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem; }
        .time-slot input[type="radio"] { display: none; }
        .time-slot label { display: block; text-align: center; padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
        .time-slot input[type="radio"]:checked + label { background-color: var(--primary-color); color: white; border-color: var(--primary-color); }
        .time-slot.disabled label { background-color: #e9ecef; color: #adb5bd; cursor: not-allowed; border-color: #dee2e6; }
        .form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--gray-light); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; border: 1px solid transparent; }
        .alert-danger { color: #842029; background-color: #f8d7da; border-color: #f5c2c7; }
        
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1050; display: none; justify-content: center; align-items: center; padding: 1rem; }
        .modal-content { background: #fff; border-radius: 12px; width: 90%; max-width: 500px; padding: 0; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-header { padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gray-light); }
        .modal-header h3 { margin: 0; font-size: 1.25rem; }
        .modal-header .close-btn { background: none; border: none; font-size: 1.75rem; cursor: pointer; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1rem 1.5rem; text-align: right; border-top: 1px solid var(--gray-light); background: #f8f9fa; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;}

        @media (max-width: 768px) {
            .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .form-grid { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column-reverse; }
            .form-actions .btn { width: 100%; text-align: center; }
            .view-controls { flex-direction: column; align-items: flex-start; }
        }
    </style>
    
</head>
<body>    
    <div class="dashboard-layout">
        <?php include '../../includes/sidebar-client.php'; ?>
        
        <div class="main-content">
            <?php include '../../includes/navbar.php'; ?>

            <?php if ($action === 'create'): ?>
                <!-- CREATE VIEW -->
                <div class="page-header"><div><h1>Book an Appointment</h1></div></div>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <div class="card form-container">
                    <form action="index.php?action=create" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="pet_id">Pet <span>*</span></label>
                                <select id="pet_id" name="pet_id" required>
                                    <option value="">Select a pet</option>
                                    <?php foreach ($pets as $pet): ?>
                                        <option value="<?php echo htmlspecialchars($pet['pet_id']); ?>" <?php echo ($preselected_pet_id && $pet['pet_id'] == $preselected_pet_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pet['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="appointment_date">Select Date <span>*</span></label><input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                        </div>
                        <div class="form-grid" style="margin-top: 1.5rem;">
                             <div class="form-group">
                                <label>Available Time Slots <span>*</span></label>
                                <div class="time-slots-grid" id="time-slots-container">
                                    Select a date to see times
                                </div>
                            </div>
                            <div>
                                 <div class="form-group"><label for="reason">Reason for Visit <span>*</span></label><textarea id="reason" name="reason" placeholder="e.g., Annual vaccination, skin irritation..." required></textarea></div><br>
                                <div class="form-group"><label for="notes">Additional Notes</label><textarea id="notes" name="notes" placeholder="Enter any additional information, like symptoms or concerns."></textarea></div>
                            </div>
                        </div>
                        <div class="form-actions"><a href="index.php" class="btn btn-close">Cancel</a><button type="submit" name="create_appointment" class="btn btn-primary">Request Appointment</button></div>
                    </form>
                </div>

            <?php else: ?>
                <!-- MAIN LIST/WEEK VIEW -->
                <div class="page-header">
                    <div><h1>My Appointments</h1><p>View your scheduled appointments in a list or weekly calendar.</p></div>
                    <a href="index.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Book New</a>
                </div>

                <div class="view-controls">
                    <div class="view-toggle">
                        <a href="index.php?view=list" class="view-toggle-btn <?php echo $view === 'list' ? 'active' : ''; ?>">List</a>
                        <a href="index.php?view=week" class="view-toggle-btn <?php echo $view === 'week' ? 'active' : ''; ?>">Week</a>
                    </div>
                </div>

                <?php if ($view === 'week'): ?>
                    <div class="card">
                        <div class="week-header">
                            <div class="week-navigation">
                                <?php
                                $prev_week_date = (clone $target_date)->modify('-1 week')->format('Y-m-d');
                                $next_week_date = (clone $target_date)->modify('+1 week')->format('Y-m-d');
                                ?>
                                <a href="index.php?view=week&date=<?php echo $prev_week_date; ?>" class="week-nav-btn"><i class="fas fa-chevron-left"></i></a>
                                <span class="week-title"><?php echo $week_start_dt->format('M j') . ' - ' . $week_end_dt->format('M j, Y'); ?></span>
                                <a href="index.php?view=week&date=<?php echo $next_week_date; ?>" class="week-nav-btn"><i class="fas fa-chevron-right"></i></a>
                            </div>
                            <button class="btn btn-secondary" onclick="goToToday()">Today</button>
                        </div>
                        
                        <!-- NEW: Desktop Grid View Wrapper -->
                        <div class="calendar-grid-wrapper">
                            <div class="calendar-grid">
                                <div class="calendar-header"></div> <!-- Time column header -->
                                <?php foreach ($week_dates as $date_info): ?>
                                    <div class="calendar-header"><div class="calendar-day-header"><span class="day-name"><?php echo $date_info['day_short']; ?></span><span class="day-number"><?php echo $date_info['day_number']; ?></span></div></div>
                                <?php endforeach; ?>
                                <?php foreach ($time_slots as $time): ?>
                                    <div class="time-slot-row"><?php echo date('g A', strtotime($time)); ?></div>
                                    <?php foreach ($week_dates as $date_info): ?>
                                        <div class="calendar-cell">
                                            <?php
                                            $current_date = $date_info['date']; $current_hour = substr($time, 0, 2);
                                            if (isset($appointments_grid[$current_date][$current_hour])):
                                                foreach ($appointments_grid[$current_date][$current_hour] as $appt): ?>
                                                    <div class="appointment-block status-<?php echo htmlspecialchars(strtolower($appt['status'])); ?>" 
                                                       data-datetime="<?php echo htmlspecialchars(date("D, M j, Y, g:i A", strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']))); ?>"
                                                       data-pet="<?php echo htmlspecialchars($appt['pet_name']); ?>"
                                                       data-reason="<?php echo htmlspecialchars($appt['reason']); ?>"
                                                       data-notes="<?php echo htmlspecialchars($appt['notes']); ?>"
                                                       data-status="<?php echo htmlspecialchars($appt['status']); ?>">
                                                        <div class="appointment-summary">
                                                            <span><?php echo htmlspecialchars(date('g:iA', strtotime($appt['appointment_time']))) . ' ' . htmlspecialchars($appt['pet_name']); ?></span>
                                                            <i class="fas fa-chevron-down"></i>
                                                        </div>
                                                        <div class="appointment-details"><strong>Reason:</strong> <?php echo htmlspecialchars($appt['reason']); ?></div>
                                                    </div>
                                            <?php endforeach; endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- NEW: Mobile Agenda View -->
                        <div class="agenda-view">
                            <?php foreach ($week_dates as $date_info): 
                                $current_date = $date_info['date'];
                                $day_has_appointments = isset($appointments_by_day[$current_date]);
                            ?>
                                <div class="agenda-day">
                                    <div class="agenda-day-header"><?php echo date('l, F j', strtotime($current_date)); ?></div>
                                    <?php if ($day_has_appointments): ?>
                                        <ul class="agenda-appointments">
                                            <?php foreach ($appointments_by_day[$current_date] as $appt): ?>
                                                <li class="agenda-appointment-item appointment-block status-<?php echo htmlspecialchars($appt['status']); ?>" 
                                                    data-datetime="<?php echo htmlspecialchars(date("D, M j, Y, g:i A", strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']))); ?>"
                                                    data-pet="<?php echo htmlspecialchars($appt['pet_name']); ?>"
                                                    data-reason="<?php echo htmlspecialchars($appt['reason']); ?>"
                                                    data-notes="<?php echo htmlspecialchars($appt['notes']); ?>"
                                                    data-status="<?php echo htmlspecialchars($appt['status']); ?>">
                                                    <div class="time"><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></div>
                                                    <div class="details">
                                                        <div class="pet-name"><?php echo htmlspecialchars($appt['pet_name']); ?></div>
                                                        <div class="reason-text"><?php echo htmlspecialchars($appt['reason']); ?></div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="no-appointments">No appointments scheduled.</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- LIST VIEW -->
                    <div class="card">
                        <!-- MODIFIED: Changed class to 'table' and added data-label attributes -->
                        <table class="table">
                            <thead><tr><th>Date & Time</th><th>Pet</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($appointments)): ?>
                                    <tr><td colspan="5" style="text-align:center; padding: 3rem;">No appointments found.</td></tr>
                                <?php else: foreach ($appointments as $appt): ?>
                                    <tr data-appointment-id="<?php echo $appt['appointment_id']; ?>">
                                        <td data-label="Date & Time"><strong><?php echo date("M d, Y", strtotime($appt['appointment_date'])); ?></strong><br><small><?php echo date("g:i A", strtotime($appt['appointment_time'])); ?></small></td>
                                        <td data-label="Pet"><?php echo htmlspecialchars($appt['pet_name']); ?></td>
                                        <td data-label="Reason"><?php echo htmlspecialchars($appt['reason']); ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge status-<?php echo htmlspecialchars(strtolower($appt['status'])); ?>"><?php echo htmlspecialchars($appt['status']); ?></span>
                                        </td>
                                        <td data-label="Actions">
                                            <?php if (!in_array($appt['status'], ['cancelled', 'completed'])): ?>
                                                <button class="btn btn-sm btn-close cancel-appointment-btn">Cancel</button>
                                            <?php else: echo '<span>-</span>'; endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                        
                        <div class="pagination-controls">
                            <?php if (isset($total_pages) && $total_pages > 1): ?>
                                <a href="?view=list&page=<?php echo $current_page - 1; ?>" class="pagination-link <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">« Previous</a>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?view=list&page=<?php echo $i; ?>" class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <a href="?view=list&page=<?php echo $current_page + 1; ?>" class="pagination-link <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">Next »</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODAL for Appointment Details -->
    <div class="modal-overlay" id="appointment-details-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Appointment Details</h3>
                <button type="button" class="close-btn" onclick="closeModal('appointment-details-modal')">×</button>
            </div>
            <div class="modal-body">
                <p><strong>Date & Time:</strong> <span id="modal-datetime"></span></p>
                <p><strong>Pet:</strong> <span id="modal-pet"></span></p>
                <p><strong>Status:</strong> <span id="modal-status" class="status-badge"></span></p>
                <p><strong>Reason for Visit:</strong><br><span id="modal-reason"></span></p>
                <p><strong>Notes:</strong><br><span id="modal-notes"></span></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-close" onclick="closeModal('appointment-details-modal')">Close</button>
            </div>
        </div>
    </div>

    <script>

        function goToToday() {
            window.location.href = 'index.php?view=week';
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'none';
        }
        
        document.addEventListener('click', function (e) {
            const appointmentBlock = e.target.closest('.appointment-block');
            if (appointmentBlock) {
                e.preventDefault();
                appointmentBlock.classList.toggle('is-expanded');
                const details = appointmentBlock.dataset;
                document.getElementById('modal-datetime').textContent = details.datetime;
                document.getElementById('modal-pet').textContent = details.pet;
                document.getElementById('modal-reason').textContent = details.reason || 'N/A';
                document.getElementById('modal-notes').textContent = details.notes || 'N/A';
                const statusBadge = document.getElementById('modal-status');
                const statusText = details.status || 'unknown';
                statusBadge.textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
                statusBadge.className = 'status-badge status-' + statusText.toLowerCase();
                openModal('appointment-details-modal');
            }

            if (e.target.classList.contains('modal-overlay')) {
                closeModal(e.target.id);
            }

            if (e.target.classList.contains('cancel-appointment-btn')) {
                // Your existing cancel button logic here...
            }
        });

    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('appointment_date');
        if (!dateInput) { return; }

        const timeSlotsContainer = document.getElementById('time-slots-container');
        // This check is important for debugging. If it fails, the ID is missing.
        if (!timeSlotsContainer) {
            console.error('Error: Could not find the time slots container with ID "time-slots-container".');
            return;
        }
        
        const timeSlotRadios = timeSlotsContainer.querySelectorAll('input[type="radio"]');

        async function updateAvailableSlots() {
            const selectedDate = dateInput.value;
            if (!selectedDate) {
                timeSlotRadios.forEach(radio => {
                    radio.disabled = false;
                    radio.closest('.time-slot').classList.remove('disabled');
                });
                return;
            }

            try {
                const response = await fetch(`ajax/get_booked_slots.php?date=${selectedDate}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const bookedSlots = await response.json();

                timeSlotRadios.forEach(radio => {
                    const slotTime = radio.value;
                    const timeSlotDiv = radio.closest('.time-slot');

                    if (bookedSlots.includes(slotTime)) {
                        radio.disabled = true;
                        radio.checked = false; 
                        timeSlotDiv.classList.add('disabled');
                    } else {
                        radio.disabled = false;
                        timeSlotDiv.classList.remove('disabled');
                    }
                });
            } catch (error) {
                console.error('Error fetching available slots:', error);
            }
        }
        dateInput.addEventListener('change', updateAvailableSlots);
        updateAvailableSlots();
    });

        // This function can be reused from your staff panel for a consistent look
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
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            `;
            
            if (type === 'success') {
                notification.style.backgroundColor = '#28a745'; // Green for success
            } else if (type === 'error') {
                notification.style.backgroundColor = '#dc3545'; // Red for error
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
            }, 4000);
        }


        // New event listener for the cancel button
        document.addEventListener('click', async function(e) {
            // Target only our new cancel button
            if (e.target.classList.contains('cancel-appointment-btn')) {
                const button = e.target;
                const row = button.closest('tr');
                const appointmentId = row.dataset.appointmentId;
                
                if (!confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
                    return;
                }

                // Disable the button to prevent multiple clicks
                button.disabled = true;
                button.textContent = 'Cancelling...';

                try {
                    const formData = new FormData();
                    formData.append('appointment_id', appointmentId);
                    
                    // Call our new, secure AJAX endpoint
                    const response = await fetch('ajax/cancel_appointment.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok && result.success) {
                        
                        // Find the status badge directly. It's the only one in the row.
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) { // Always a good idea to check if it was found
                            statusBadge.textContent = 'Cancelled';
                            statusBadge.className = 'status-badge status-cancelled';
                        }

                        // Find the parent TD of the button and replace its content.
                        const actionCell = button.closest('td');
                        if (actionCell) { // Check again
                            actionCell.innerHTML = '<span>-</span>';
                        }
                        
                        showNotification('Appointment cancelled successfully!', 'success');
                    } else {
                        // Throw an error to be caught by the catch block
                        throw new Error(result.error || 'An unknown error occurred.');
                    }

                } catch (error) {
                    showNotification('Error: ' + error.message, 'error');
                    // Re-enable the button if something went wrong
                    button.disabled = false;
                    button.textContent = 'Cancel';
                }
            }
        });

        // <!-- NEW --> Hamburger Menu & Sidebar Toggle Logic
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