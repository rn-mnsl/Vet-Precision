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

// --- MODIFICATION: HANDLE PRE-SELECTION FROM URL ---
$preselected_owner_id = null;
$preselected_pet_id = null;
$preselected_owner_name = 'Select a client';
$preselected_pet_name = 'Select a client first';

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
            // 1. This part remains the same: Insert the new appointment
            $sql = "INSERT INTO appointments (pet_id, appointment_date, appointment_time, duration_minutes, status, type, reason, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            // Note: I removed the NOW() placeholders as your table likely handles them automatically. If not, add them back.
            $stmt->execute([$pet_id, $appointment_date, $appointment_time, 30, 'confirmed', 'Checkup', $reason, $notes, $logged_in_staff_id]);
            
            // --- 2. [NEW] ADD THIS BLOCK TO FETCH DETAILS AND SEND THE EMAIL ---
            // We need the client's email and name, and the pet's name for the notification.
            // The $owner_id is from the form post, which we get from the hidden input.
            $stmt_details = $pdo->prepare("
                SELECT u.email, u.first_name, p.name as pet_name
                FROM users u
                JOIN owners o ON u.user_id = o.user_id
                JOIN pets p ON p.owner_id = o.owner_id
                WHERE o.owner_id = :owner_id AND p.pet_id = :pet_id
            ");
            $stmt_details->execute([':owner_id' => $owner_id, ':pet_id' => $pet_id]);
            $details = $stmt_details->fetch(PDO::FETCH_ASSOC);

            // 3. Check if we found the details, then construct and send the email
            if ($details) {
                $site_name = defined('SITE_NAME') ? SITE_NAME : 'Vet Precision';
                $subject = "New Appointment Scheduled: " . htmlspecialchars($details['pet_name']);
                $formatted_date = date("l, F j, Y", strtotime($appointment_date));
                $formatted_time = date("g:i A", strtotime($appointment_time));
                
                $email_body = "<html><body>
                    <p>Dear " . htmlspecialchars($details['first_name']) . ",</p>
                    <p>Our staff has scheduled and confirmed a new appointment for your pet, <strong>" . htmlspecialchars($details['pet_name']) . "</strong>.</p>
                    <p><strong>Date:</strong> " . $formatted_date . "</p>
                    <p><strong>Time:</strong> " . $formatted_time . "</p>
                    <p>If this time does not work for you or if you have any questions, please contact our clinic.</p>
                    <p>Sincerely,<br>The " . $site_name . " Team</p>
                </body></html>";
                $alt_body = "A new appointment has been scheduled for {$details['pet_name']} on {$formatted_date} at {$formatted_time}.";
                
                // Use the function from init.php
                sendClientNotification($details['email'], $subject, $email_body, $alt_body);

                // Update the success message to reflect that the email was sent
                $_SESSION['success_message'] = 'Appointment created successfully and the client has been notified.';

            } else {
                // This is a fallback in case the email details couldn't be found
                $_SESSION['success_message'] = 'Appointment created, but the email notification failed to send. Please check logs.';
                error_log("Staff Create Appointment Email Failed: Could not fetch details for owner_id {$owner_id} or pet_id {$pet_id}.");
            }
            // --- END OF NEW EMAIL BLOCK ---

            // 4. Redirect after everything is done
            header('Location: index.php?view=' . $view);
            exit();

        } catch (PDOException $e) { 
            error_log("Staff Create Appointment DB Error: " . $e->getMessage());
            $errors[] = "A database error occurred. Could not create the appointment."; 
        }
    }
}

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
    date_default_timezone_set('Asia/Manila'); 
    $current_date_str = $_GET['date'] ?? 'now';
    $target_date = new DateTime($current_date_str);

    $day_of_week = (int)$target_date->format('w');
    if ($day_of_week == 0) {
        $week_start_dt = (clone $target_date)->modify('last monday');
    } else {
        $week_start_dt = (clone $target_date)->modify('-' . ($day_of_week - 1) . ' days');
    }
    $week_end_dt = (clone $week_start_dt)->modify('+6 days');
    
    // Fetch appointments for the calculated week
    try {
        $stmt_week = $pdo->prepare("
            SELECT a.*, p.name AS pet_name, u_owner.first_name AS owner_first_name, u_owner.last_name AS owner_last_name,
                   p.species, p.breed, p.date_of_birth
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN owners o ON p.owner_id = o.owner_id
            JOIN users u_owner ON o.user_id = u_owner.user_id
            WHERE a.appointment_date BETWEEN ? AND ? AND a.status != 'cancelled'
            ORDER BY a.appointment_date, a.appointment_time");
        $stmt_week->execute([$week_start_dt->format('Y-m-d'), $week_end_dt->format('Y-m-d')]);
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

        // --- NEW: Add this logic to create a simpler array for the mobile agenda view ---
        $appointments_by_day = [];
        foreach ($week_appointments as $appt) {
            $date_key = $appt['appointment_date'];
            if (!isset($appointments_by_day[$date_key])) {
                $appointments_by_day[$date_key] = [];
            }
            $appointments_by_day[$date_key][] = $appt;
        }

    } catch (PDOException $e) { 
        $errors[] = "Could not fetch week appointments."; 
    }

    $week_dates = [];
    if (isset($week_start_dt)) {
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
    
    // --- MODIFICATION 1: Generate hourly time slots ---
    $time_slots = [];
    for ($h = 8; $h <= 19; $h++) { // 8 AM to 7 PM (19:00)
        $time_slots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
    }
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


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <?php include '../../includes/favicon.php'; ?>
    <style>
        /* === EXISTING STYLES === */
        /* === EXISTING STYLES === */
        body {
            background-color: var(--light-color);
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-layout { display: flex; min-height: 100vh; }
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

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1099; /* Below sidebar, above content */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
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
        .btn-close { background: var(--gray-light); color: var(--text-dark); }
        .btn-close:hover { background: #FF0000; color: white; }

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
            min-height: 60px; /* Increased height to better fit multiple appointments */
            padding: 0.25rem;
            border-right: 1px solid var(--gray-light);
            /* Added for vertical alignment */
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        -block {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.99rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .appointment-block {
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 0.6rem; /* Make it more compact */
            border-radius: 4px;
            font-size: 0.8rem; /* Smaller font for compactness */
            margin-bottom: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
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

        .appointment-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        .appointment-summary i {
            transition: transform 0.2s ease;
        }
        
        /* The details section that is hidden by default */
        .appointment-details {
            display: none; /* Hide details by default */
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255,255,255,0.3);
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .appointment-details strong {
            color: rgba(255,255,255,0.8);
            display: block;
            margin-bottom: 2px;
        }
        
        /* This class will be toggled by JavaScript */
        .appointment-block.is-expanded .appointment-details {
            display: block; /* Show details when expanded */
        }
        
        .appointment-block.is-expanded .appointment-summary i {
            transform: rotate(180deg); /* Flip the chevron icon */
        }


        /* === PAGINATION STYLES === */
        .pagination-controls {
            display: flex;
            flex-wrap: wrap;
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

        /* === NEW: MOBILE RESPONSIVE STYLES === */
        /* --- 1. General Layout: Sidebar & Content --- */
        @media (max-width: 768px) {
            /* Hide the sidebar off-screen by default on mobile */
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                /* OVERRIDE styles from navbar.css to make it a full-height slide-out */
                top: 0; 
                height: 100vh;
                z-index: 1100; /* Ensure sidebar is on top */
            }

            /* --- NEW: Style for when the sidebar is open --- */
            body.sidebar-is-open .sidebar {
                transform: translateX(0);
                box-shadow: 0 0 20px rgba(0,0,0,0.25); /* Add shadow for depth */
            }

            body.sidebar-is-open .sidebar-overlay {
                opacity: 1;
                visibility: visible;
            }
            /* --- END NEW --- */

            /* Adjust main content for mobile */
            .main-content {
                margin-left: 0; /* Remove the space left for the desktop sidebar */
            }
            
            /* Remove padding-top from body since main-content handles it */
            body {
                padding-top: 0;
            }
            /* Add the space back to the main-content area */
            .main-content {
                padding-top: 85px; /* 70px for navbar + 15px top padding */
            }
            
            /* On mobile, remove the left margin from the logo to make space */
            .navbar-brand .brand-logo {
                margin-left: 0;
            }
        }


        /* --- 2. Card Layout for Tables on Mobile --- */
        @media (max-width: 768px) {
            .table thead {
                display: none; /* Hide table headers */
            }
            .table, .table tbody, .table tr, .table td {
                display: block; /* Make table elements behave like blocks */
                width: 100%;
            }
            .table tr {
                margin-bottom: 1rem;
                border: 1px solid var(--gray-light);
                border-radius: 8px;
                padding: 0.5rem;
            }
            .table td {
                display: flex; /* Use flexbox for label-value alignment */
                justify-content: space-between;
                padding: 0.75rem 0.5rem;
                text-align: right; /* Align value to the right */
                border-bottom: 1px solid #eee;
            }
            .table td:last-child {
                border-bottom: none;
            }
            .table td::before {
                content: attr(data-label); /* Use data-label for the "header" */
                font-weight: 600;
                text-align: left;
                margin-right: 1rem;
                color: var(--text-dark);
            }
            /* Style action buttons container in the card view */
            .table td[data-label="Actions"] .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            .table td[data-label="Actions"] .btn-status {
                 width: 100%;
                 text-align: center;
                 margin-bottom: 0.25rem;
            }
        }

        /* --- 3. Week View (Calendar) --- */
        @media (max-width: 768px) {
            .calendar-grid-wrapper {
                overflow-x: auto; /* Enable horizontal scrolling */
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
                border: 1px solid var(--gray-light);
                border-radius: 8px;
            }
            .calendar-grid {
                width: 1200px; /* Force grid to be wide, making it scrollable */
            }
            .week-header {
                 justify-content: center;
            }
        }

        /* --- 4. Forms, Headers, and General Stacking --- */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.5rem;
            }
            .page-header h1 {
                font-size: 1.75rem;
            }
            .card {
                padding: 1.5rem;
            }
            .form-grid {
                grid-template-columns: 1fr; /* Stack form fields vertically */
                gap: 1.5rem;
            }
            .form-actions {
                flex-direction: column-reverse; /* Stack buttons, primary on top */
            }
            .form-actions .btn {
                width: 100%;
                text-align: center;
            }
            .view-controls {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* === NEW: AGENDA VIEW STYLES FOR MOBILE WEEK VIEW === */
        .agenda-view {
            display: none; /* Hidden by default, shown on mobile */
        }

        .agenda-day {
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            overflow: hidden;
        }

        .agenda-day-header {
            background-color: var(--light-color);
            padding: 0.75rem 1rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
            border-bottom: 1px solid var(--gray-light);
        }

        .agenda-appointments {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .agenda-appointment-item {
            display: flex;
            padding: 1rem;
            gap: 1rem;
            align-items: flex-start;
            border-bottom: 1px solid var(--gray-light);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .agenda-appointment-item:hover {
            background-color: #f8f9fa;
        }

        .agenda-appointment-item:last-child {
            border-bottom: none;
        }

        .agenda-appointment-item .time {
            flex-shrink: 0;
            width: 80px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--primary-color);
        }

        .agenda-appointment-item .details {
            flex-grow: 1;
        }

        .agenda-appointment-item .details .pet-name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .agenda-appointment-item .details .client-name {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .agenda-appointment-item .details .status {
            display: inline-block; /* allows padding/margin */
            margin-top: 0.5rem;
        }

        .no-appointments {
            padding: 1.5rem 1rem;
            text-align: center;
            color: var(--text-light);
            font-style: italic;
        }

        /* --- Media Query to SWITCH between Grid and Agenda --- */
        @media (max-width: 768px) {
            /* Hide the desktop grid view */
            .calendar-grid-wrapper {
                display: none;
            }
            /* Show the mobile agenda view */
            .agenda-view {
                display: block;
            }
        }

        /* === NEW: Color the entire agenda item card based on status === */

        /* Base style for all colored cards: white text, no border */
        .agenda-appointment-item.status-confirmed,
        .agenda-appointment-item.status-completed,
        .agenda-appointment-item.status-requested {
            color: white; /* Make all text inside white */
            border-bottom: 1px solid rgba(0,0,0,0.1); /* Use a darker border for separation */
        }

        /* Set specific background colors from your desktop theme */
        .agenda-appointment-item.status-confirmed { background-color: #10b981; } /* Green */
        .agenda-appointment-item.status-completed { background-color: #3b82f6; } /* Blue */
        .agenda-appointment-item.status-requested { background-color: #f59e0b; } /* Orange */

        /* Make the text inside the colored cards more legible */
        .agenda-appointment-item.status-confirmed .time,
        .agenda-appointment-item.status-completed .time,
        .agenda-appointment-item.status-requested .time,
        .agenda-appointment-item.status-confirmed .client-name,
        .agenda-appointment-item.status-completed .client-name,
        .agenda-appointment-item.status-requested .client-name {
            color: rgba(255, 255, 255, 0.85); /* Make secondary text slightly transparent */
        }
        .agenda-appointment-item.status-confirmed .pet-name,
        .agenda-appointment-item.status-completed .pet-name,
        .agenda-appointment-item.status-requested .pet-name {
            color: white; /* Ensure main text is fully opaque */
        }

        /* Update the hover effect for colored cards to darken them slightly */
        .agenda-appointment-item.status-confirmed:hover,
        .agenda-appointment-item.status-completed:hover,
        .agenda-appointment-item.status-requested:hover {
            background-color: initial;  /* Reset the default hover effect */
            filter: brightness(95%);    /* Slightly darken the existing color */
            -webkit-filter: brightness(95%);
        }

        .action-dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-button {
    background: white;
    border: 1px solid var(--border-color, #dee2e6);
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-dark, #2d3748);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    min-width: 120px;
    justify-content: space-between;
}

.dropdown-button:hover {
    background: var(--light-color, #f8f9fa);
    border-color: var(--text-light, #6c757d);
}

.dropdown-button:focus {
    outline: none;
    border-color: var(--primary-color, #ff6b6b);
    box-shadow: 0 0 0 2px rgba(255, 107, 107, 0.1);
}

.dropdown-button.loading {
    opacity: 0.7;
    cursor: not-allowed;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid var(--border-color, #dee2e6);
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s ease;
    max-height: 200px;
    overflow-y: auto;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: var(--text-dark, #2d3748);
    cursor: pointer;
    transition: background-color 0.2s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}

.dropdown-item:hover {
    background: var(--light-color, #f8f9fa);
}

.dropdown-item:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: none;
}

.dropdown-item.current {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color, #10b981);
    font-weight: 600;
}

.dropdown-item.current:hover {
    background: rgba(16, 185, 129, 0.15);
}

.dropdown-item .icon {
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dropdown-item .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-dot.requested { background: #f59e0b; }
.status-dot.confirmed { background: #10b981; }
.status-dot.completed { background: #3b82f6; }
.status-dot.cancelled { background: #ef4444; }

.dropdown-divider {
    height: 1px;
    background: var(--border-color, #dee2e6);
    margin: 0.5rem 0;
}

/* Send Reminder Button */
.send-reminder-btn {
    background: var(--secondary-color, #6c757d);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: 0.5rem;
}

.send-reminder-btn:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.send-reminder-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.send-reminder-btn.success {
    background: var(--success-color, #10b981);
}

/* Mobile responsiveness for dropdowns */
@media (max-width: 768px) {
    .table td[data-label="Actions"] {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-dropdown {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .dropdown-button {
        width: 100%;
        justify-content: space-between;
    }
    
    .send-reminder-btn {
        width: 100%;
        margin-left: 0;
        justify-content: center;
    }
}
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../../includes/sidebar-staff.php'; ?>

        
        <main class="main-content">
            <?php include '../../includes/navbar.php'; ?>


            <?php if ($action === 'create'): ?>
                <!-- CREATE VIEW -->
                <div class="page-header"><div><h1>Create Appointment</h1><p>Fill in the details below to schedule a new appointment.</p></div></div>
                <div class="card">
                    <form action="index.php?action=create&view=<?php echo $view; ?>" method="POST">
                        <div class="form-grid">
                            <!-- --- MODIFICATION: Pre-populate Client Button --- -->
                            <div class="form-group">
                                <label>Client*</label>
                                <button type="button" class="selector-button" id="select-client-btn">
                                    <span class="<?php echo $preselected_owner_id ? '' : 'selector-placeholder'; ?>" id="client-name-display">
                                        <?php echo htmlspecialchars($preselected_owner_name); ?>
                                    </span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <!-- --- MODIFICATION: Pre-populate Pet Button and handle 'disabled' state --- -->
                            <div class="form-group">
                                <label>Pet*</label>
                                <button type="button" class="selector-button" id="select-pet-btn" <?php echo $preselected_owner_id ? '' : 'disabled'; ?>>
                                    <span class="<?php echo $preselected_pet_id ? '' : 'selector-placeholder'; ?>" id="pet-name-display">
                                        <?php echo htmlspecialchars($preselected_pet_name); ?>
                                    </span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-grid" style="margin-top: 1.5rem;">
                            <div class="form-group"><label for="appointment_date">Select Date*</label><input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                            <div class="form-group"><label>Available Time Slots*</label><div class="time-slots-grid" id="time-slots-container">Select a date to see times</div></div>
                            <div class="form-group"><label for="reason">Reason for Visit*</label><textarea id="reason" name="reason" required></textarea></div>
                            <div class="form-group"><label for="notes">Additional Notes</label><textarea id="notes" name="notes"></textarea></div>
                        </div>
                        <div class="form-actions"><a href="index.php?view=<?php echo $view; ?>" class="btn btn-close">Cancel</a><button type="submit" name="create_appointment" class="btn btn-primary">Create Appointment</button></div>
                        <!-- --- MODIFICATION: Pre-populate hidden inputs --- -->
                        <input type="hidden" id="selected_owner_id" name="owner_id" value="<?php echo htmlspecialchars($preselected_owner_id); ?>">
                        <input type="hidden" id="selected_pet_id" name="pet_id" value="<?php echo htmlspecialchars($preselected_pet_id); ?>">
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

                        <!-- 1. DESKTOP GRID VIEW (Hidden on mobile) -->
                        <div class="calendar-grid-wrapper">
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
                                            $current_date = $date_info['date'];
                                            $current_hour = substr($time, 0, 2);
                                            if (isset($appointments_grid[$current_date][$current_hour])):
                                                foreach ($appointments_grid[$current_date][$current_hour] as $appt): ?>
                                                    <div class="appointment-block status-<?php echo htmlspecialchars($appt['status']); ?>" 
                                                        data-id="<?php echo $appt['appointment_id']; ?>"
                                                        data-datetime="<?php echo date("D, M j, Y, g:i A", strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time'])); ?>"
                                                        data-client="<?php echo htmlspecialchars($appt['owner_first_name'] . ' ' . $appt['owner_last_name']); ?>"
                                                        data-pet="<?php echo htmlspecialchars($appt['pet_name']); ?>"
                                                        data-reason="<?php echo htmlspecialchars($appt['reason']); ?>"
                                                        data-notes="<?php echo htmlspecialchars($appt['notes']); ?>"
                                                        data-status="<?php echo htmlspecialchars($appt['status']); ?>">
                                                        <div class="appointment-summary">
                                                            <span><?php echo date('g:iA', strtotime($appt['appointment_time'])) . ' ' . htmlspecialchars($appt['pet_name']); ?></span>
                                                            <i class="fas fa-chevron-down"></i>
                                                        </div>
                                                        <div class="appointment-details">
                                                            <strong>Client:</strong> <?php echo htmlspecialchars($appt['owner_first_name'] . ' ' . $appt['owner_last_name']); ?><br>
                                                            <strong>Reason:</strong> <?php echo htmlspecialchars($appt['reason']); ?>
                                                        </div>
                                                    </div>
                                            <?php endforeach; endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- 2. MOBILE AGENDA VIEW (Hidden on desktop) -->
                        <div class="agenda-view">
                            <?php foreach ($week_dates as $date_info): 
                                $current_date = $date_info['date'];
                                $day_has_appointments = isset($appointments_by_day[$current_date]);
                            ?>
                                <div class="agenda-day">
                                    <div class="agenda-day-header">
                                        <?php echo date('l, F j', strtotime($current_date)); ?>
                                    </div>
                                    <?php if ($day_has_appointments): ?>
                                        <ul class="agenda-appointments">
                                            <?php foreach ($appointments_by_day[$current_date] as $appt): ?>
                                                <!-- MODIFICATION: Added status class to the <li> for background coloring -->
                                                <li class="agenda-appointment-item appointment-block status-<?php echo htmlspecialchars($appt['status']); ?>" 
                                                    data-id="<?php echo $appt['appointment_id']; ?>"
                                                    data-datetime="<?php echo date("D, M j, Y, g:i A", strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time'])); ?>"
                                                    data-client="<?php echo htmlspecialchars($appt['owner_first_name'] . ' ' . $appt['owner_last_name']); ?>"
                                                    data-pet="<?php echo htmlspecialchars($appt['pet_name']); ?>"
                                                    data-reason="<?php echo htmlspecialchars($appt['reason']); ?>"
                                                    data-notes="<?php echo htmlspecialchars($appt['notes']); ?>"
                                                    data-status="<?php echo htmlspecialchars($appt['status']); ?>">
                                                    
                                                    <div class="time">
                                                        <?php echo date('g:i A', strtotime($appt['appointment_time'])); ?>
                                                    </div>
                                                    <div class="details">
                                                        <div class="pet-name"><?php echo htmlspecialchars($appt['pet_name']); ?></div>
                                                        <div class="client-name"><?php echo htmlspecialchars($appt['owner_first_name'] . ' ' . $appt['owner_last_name']); ?></div>
                                                        <!-- The redundant status badge has been removed from here -->
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="no-appointments">
                                            No appointments scheduled.
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                        <td data-label="Date & Time">
                            <strong><?php echo date("M d, Y", strtotime($appt['appointment_date'])); ?></strong><br>
                            <small><?php echo date("g:i A", strtotime($appt['appointment_time'])); ?></small>
                        </td>
                        <td data-label="Client"><?php echo htmlspecialchars($appt['owner_first_name'] . ' ' . $appt['owner_last_name']); ?></td>
                        <td data-label="Pet"><?php echo htmlspecialchars($appt['pet_name']); ?></td>
                        <td data-label="Reason"><?php echo htmlspecialchars($appt['reason']); ?></td>
                        <td data-label="Status">
                            <span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo ucfirst(htmlspecialchars($appt['status'])); ?></span>
                        </td>
                        <td data-label="Actions">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <!-- Status Dropdown -->
                                <div class="action-dropdown">
                                    <button class="dropdown-button action-select" 
                                            data-appointment-id="<?php echo $appt['appointment_id']; ?>"
                                            data-current-status="<?php echo $appt['status']; ?>"
                                            aria-haspopup="true"
                                            aria-expanded="false">
                                        <span class="dropdown-text">
                                            <span class="status-dot <?php echo $appt['status']; ?>"></span>
                                            Change Status
                                        </span>
                                        <i class="fas fa-chevron-down dropdown-icon"></i>
                                    </button>
                                    
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item <?php echo $appt['status'] === 'requested' ? 'current' : ''; ?>" 
                                                data-status="requested"
                                                <?php echo $appt['status'] === 'requested' ? 'disabled' : ''; ?>>
                                            <span class="status-dot requested"></span>
                                            <span>Requested</span>
                                            <?php if ($appt['status'] === 'requested'): ?>
                                                <i class="fas fa-check" style="margin-left: auto; color: var(--success-color);"></i>
                                            <?php endif; ?>
                                        </button>
                                        
                                        <button class="dropdown-item <?php echo $appt['status'] === 'confirmed' ? 'current' : ''; ?>" 
                                                data-status="confirmed"
                                                <?php echo $appt['status'] === 'confirmed' ? 'disabled' : ''; ?>>
                                            <span class="status-dot confirmed"></span>
                                            <span>Confirmed</span>
                                            <?php if ($appt['status'] === 'confirmed'): ?>
                                                <i class="fas fa-check" style="margin-left: auto; color: var(--success-color);"></i>
                                            <?php endif; ?>
                                        </button>
                                        
                                        <button class="dropdown-item <?php echo $appt['status'] === 'completed' ? 'current' : ''; ?>" 
                                                data-status="completed"
                                                <?php echo $appt['status'] === 'completed' ? 'disabled' : ''; ?>>
                                            <span class="status-dot completed"></span>
                                            <span>Completed</span>
                                            <?php if ($appt['status'] === 'completed'): ?>
                                                <i class="fas fa-check" style="margin-left: auto; color: var(--success-color);"></i>
                                            <?php endif; ?>
                                        </button>
                                        
                                        <div class="dropdown-divider"></div>
                                        
                                        <button class="dropdown-item <?php echo $appt['status'] === 'cancelled' ? 'current' : ''; ?>" 
                                                data-status="cancelled"
                                                <?php echo $appt['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                            <span class="status-dot cancelled"></span>
                                            <span>Cancelled</span>
                                            <?php if ($appt['status'] === 'cancelled'): ?>
                                                <i class="fas fa-check" style="margin-left: auto; color: var(--success-color);"></i>
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </div>

                                <!-- Send Reminder Button
                                <button class="send-reminder-btn" 
                                        data-appointment-id="<?php echo $appt['appointment_id']; ?>"
                                        title="Send appointment reminder to client">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Send Reminder</span>
                                </button> -->
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
    <!-- NEW: MODAL for Appointment Details -->                                   
    <div class="modal-overlay" id="appointment-details-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Appointment Details</h3>
                <button type="button" class="close-btn" onclick="closeModal('appointment-details-modal')">×</button>
            </div>
            <div class="modal-body">
                <p><strong>Date & Time:</strong> <span id="modal-datetime"></span></p>
                <p><strong>Client:</strong> <span id="modal-client"></span></p>
                <p><strong>Pet:</strong> <span id="modal-pet"></span></p>
                <p><strong>Status:</strong> <span id="modal-status" class="status-badge"></span></p>
                <p><strong>Reason for Visit:</strong><br><span id="modal-reason"></span></p>
                <p><strong>Notes:</strong><br><span id="modal-notes"></span></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-close" onclick="closeModal('appointment-details-modal')">Close</button>
            </div>
        </div>
    </div>

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

        // Function to open any modal by its ID
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        // Function to close any modal by its ID
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        // Add one event listener to the calendar grid for efficiency
        document.addEventListener('click', function (e) {
            // Find the closest appointment block ancestor from the clicked element
            const appointmentBlock = e.target.closest('.appointment-block');

            if (appointmentBlock) {
                // Prevent default browser action, if any
                e.preventDefault();

                // 1. Toggle the expansion for details in the grid
                appointmentBlock.classList.toggle('is-expanded');

                // 2. Populate and show the details modal
                const details = appointmentBlock.dataset;
                
                document.getElementById('modal-datetime').textContent = details.datetime;
                document.getElementById('modal-client').textContent = details.client;
                document.getElementById('modal-pet').textContent = details.pet;
                document.getElementById('modal-reason').textContent = details.reason || 'N/A';
                document.getElementById('modal-notes').textContent = details.notes || 'N/A';
                
                const statusBadge = document.getElementById('modal-status');
                statusBadge.textContent = details.status.charAt(0).toUpperCase() + details.status.slice(1);
                statusBadge.className = 'status-badge status-' + details.status;

                openModal('appointment-details-modal');
            }

            // Close modal if overlay is clicked
            if (e.target.classList.contains('modal-overlay')) {
                closeModal(e.target.id);
            }
        });

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

        // --- NEW: Hamburger Menu & Sidebar Toggle ---
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

            // Updated dropdown and status management
    document.addEventListener('DOMContentLoaded', function() {
        // Dropdown functionality
        function initializeDropdowns() {
            document.addEventListener('click', function(e) {
                // Handle dropdown button clicks
                if (e.target.closest('.dropdown-button')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const button = e.target.closest('.dropdown-button');
                    const dropdown = button.closest('.action-dropdown');
                    const menu = dropdown.querySelector('.dropdown-menu');
                    const isOpen = menu.classList.contains('show');
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                        if (m !== menu) {
                            m.classList.remove('show');
                            m.closest('.action-dropdown').querySelector('.dropdown-button').setAttribute('aria-expanded', 'false');
                        }
                    });
                    
                    // Toggle current dropdown
                    if (isOpen) {
                        menu.classList.remove('show');
                        button.setAttribute('aria-expanded', 'false');
                    } else {
                        menu.classList.add('show');
                        button.setAttribute('aria-expanded', 'true');
                    }
                }
                
                // Handle dropdown item clicks
                else if (e.target.closest('.dropdown-item') && !e.target.closest('.dropdown-item').disabled) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const item = e.target.closest('.dropdown-item');
                    const dropdown = item.closest('.action-dropdown');
                    const button = dropdown.querySelector('.dropdown-button');
                    const menu = dropdown.querySelector('.dropdown-menu');
                    const appointmentId = button.dataset.appointmentId;
                    const newStatus = item.dataset.status;
                    
                    if (newStatus && appointmentId) {
                        updateAppointmentStatus(appointmentId, newStatus, button, item);
                    }
                    
                    // Close dropdown
                    menu.classList.remove('show');
                    button.setAttribute('aria-expanded', 'false');
                }
                
                // Close dropdowns when clicking outside
                else if (!e.target.closest('.action-dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                        menu.closest('.action-dropdown').querySelector('.dropdown-button').setAttribute('aria-expanded', 'false');
                    });
                }
            });
        }

        // Initialize dropdown functionality
        initializeDropdowns();

        // Status update function
        async function updateAppointmentStatus(appointmentId, newStatus, button, clickedItem) {
            const statusNames = {
                'requested': 'Requested',
                'confirmed': 'Confirmed', 
                'completed': 'Completed',
                'cancelled': 'Cancelled'
            };

            if (!confirm(`Are you sure you want to mark this appointment as ${statusNames[newStatus]}?`)) {
                return;
            }

            // Show loading state
            const originalText = button.querySelector('.dropdown-text').innerHTML;
            const dropdownIcon = button.querySelector('.dropdown-icon');
            
            button.classList.add('loading');
            button.disabled = true;
            button.querySelector('.dropdown-text').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            dropdownIcon.style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('appointment_id', appointmentId);
                formData.append('status', newStatus);

                const response = await fetch('ajax/update_appointment_status.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Update the status badge in the table
                    const row = button.closest('tr');
                    const badge = row.querySelector('.status-badge');
                    if (badge) {
                        badge.textContent = statusNames[newStatus];
                        badge.className = `status-badge status-${newStatus}`;
                    }

                    // Update dropdown button
                    button.querySelector('.dropdown-text').innerHTML = `
                        <span class="status-dot ${newStatus}"></span>
                        Change Status
                    `;
                    button.dataset.currentStatus = newStatus;

                    // Update dropdown menu items
                    const dropdown = button.closest('.action-dropdown');
                    const allItems = dropdown.querySelectorAll('.dropdown-item');
                    
                    allItems.forEach(item => {
                        const itemStatus = item.dataset.status;
                        const checkIcon = item.querySelector('.fas.fa-check');
                        
                        if (itemStatus === newStatus) {
                            item.classList.add('current');
                            item.disabled = true;
                            if (!checkIcon) {
                                item.innerHTML += '<i class="fas fa-check" style="margin-left: auto; color: var(--success-color);"></i>';
                            }
                        } else {
                            item.classList.remove('current');
                            item.disabled = false;
                            if (checkIcon) {
                                checkIcon.remove();
                            }
                        }
                    });

                    showNotification(`Appointment status updated to ${statusNames[newStatus]}!`, 'success');
                } else {
                    throw new Error(result.error || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error updating status:', error);
                showNotification('Failed to update appointment status. Please try again.', 'error');
            } finally {
                // Reset loading state
                button.classList.remove('loading');
                button.disabled = false;
                dropdownIcon.style.display = '';
                
                if (button.querySelector('.dropdown-text').textContent.includes('Updating')) {
                    button.querySelector('.dropdown-text').innerHTML = originalText;
                }
            }
        }

        // Send reminder functionality
document.addEventListener('click', async function(e) {
    if (e.target.closest('.send-reminder-btn')) {
        e.preventDefault();
        const btn = e.target.closest('.send-reminder-btn');
        const appointmentId = btn.dataset.appointmentId;
        
        if (!appointmentId) {
            showNotification('Invalid appointment ID', 'error');
            return;
        }
        
        if (!confirm('Send appointment reminder to the client?')) return;

        // Show loading state
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;
        btn.style.opacity = '0.7';

        try {
            const formData = new FormData();
            formData.append('appointment_id', appointmentId);

            const response = await fetch('ajax/send_reminder.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }

            const result = await response.json();

            if (response.ok && result.success) {
                showNotification('Reminder sent successfully!', 'success');
                
                // Show success state
                btn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                btn.classList.add('success');
                btn.style.opacity = '1';
                
                // Reset after 3 seconds
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('success');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }, 3000);
            } else {
                throw new Error(result.error || 'Failed to send reminder');
            }
        } catch (error) {
            console.error('Error sending reminder:', error);
            
            let errorMessage = 'Failed to send reminder. ';
            if (error.message.includes('non-JSON')) {
                errorMessage += 'Server configuration error.';
            } else if (error.message.includes('Failed to fetch')) {
                errorMessage += 'Network connection error.';
            } else {
                errorMessage += error.message;
            }
            
            showNotification(errorMessage, 'error');
            
            // Reset button state
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }
});

        // Make updateAppointmentStatus available globally
        window.updateAppointmentStatus = updateAppointmentStatus;
    });
        
    </script>
</body>
</html>
