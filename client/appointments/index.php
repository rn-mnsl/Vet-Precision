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
    $stmt_pets = $pdo->prepare("SELECT pet_id, name FROM pets WHERE owner_id = ?");
    $stmt_pets->execute([$owner_id]);
    $pets = $stmt_pets->fetchAll(PDO::FETCH_ASSOC);

    if ($action === 'list') {
        $stmt_appts = $pdo->prepare("
            SELECT a.*, p.name AS pet_name 
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            WHERE p.owner_id = ? 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt_appts->execute([$owner_id]);
        $appointments = $stmt_appts->fetchAll(PDO::FETCH_ASSOC);
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
    <style>

        /* ----- 1. BASE AND LAYOUT STYLES ----- */
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
            margin-left: 250px; /* IMPORTANT: This makes space for the sidebar */
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* ----- 2.  SIDEBAR STYLES ----- */
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
        
        /* ----- 3. PAGE-SPECIFIC STYLES (FOR APPOINTMENTS CONTENT) ----- */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #343a40;
        }
        .header-actions { display: flex; gap: 1rem; }
        .btn {
            padding: 0.7rem 1.4rem; border: none; border-radius: 8px; font-size: 0.9rem;
            font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex;
            align-items: center; gap: 0.5rem; transition: all 0.2s ease-in-out;
        }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #f1f3f5; color: #495057; border: 1px solid #dee2e6; }
        .btn-secondary:hover { background-color: #e9ecef; }

        .card {
            background-color: white; border-radius: 12px; padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 2rem;
        }

        .table-container { overflow-x: auto; }
        .appointments-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .appointments-table th, .appointments-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
        .appointments-table th { font-weight: 600; color: #6c757d; background-color: #f8f9fa; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .status-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .status-confirmed { background-color: #d1fae5; color: #065f46; }
        .status-requested { background-color: #feefc3; color: #92400e; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .no-data { text-align: center; padding: 3rem; color: #6c757d; }
        .action-link { color: #007bff; text-decoration: none; font-weight: 600; }
        .action-link:hover { text-decoration: underline; }

        .bottom-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .card-header h3 { font-size: 1.2rem; font-weight: 600; color: #343a40; }
        .card-header .icon { color: #adb5bd; }
        .guidelines-list, .pets-list { list-style: none; padding-left: 0; }
        .guidelines-list li { padding-left: 1.5rem; position: relative; margin-bottom: 0.75rem; color: #495057; }
        .guidelines-list li::before { content: '•'; position: absolute; left: 0; color: #007bff; font-weight: bold; }
        .view-all-link { display: block; text-align: center; padding-top: 1rem; margin-top: 1rem; border-top: 1px solid #e9ecef; color: #007bff; font-weight: 600; text-decoration: none; }

        .form-container { max-width: 950px; }
        .form-grid { display: grid; grid-template-columns: 400px 1fr; gap: 2.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; color: #495057; }
        .form-group label span { color: #dc3545; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid #ced4da;
            border-radius: 8px; font-size: 1rem; background-color: #fff;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        
        .time-slots-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
        .time-slot input[type="radio"] { display: none; }
        .time-slot label {
            display: block; text-align: center; padding: 0.75rem; border: 1px solid #dee2e6;
            border-radius: 8px; cursor: pointer; transition: all 0.2s;
            background-color: #fff; font-weight: 500; color: #495057;
        }
        .time-slot input[type="radio"]:checked + label {
            background-color: #007bff; color: white; border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,.25);
        }
        .time-slot label:hover { border-color: #007bff; }


        /* === NEW CSS FOR DISABLED TIME SLOTS === */
        .time-slot.disabled label {
            background-color: #e9ecef;
            color: #adb5bd;
            cursor: not-allowed;
            border-color: #dee2e6;
        }
        .time-slot.disabled label:hover {
            border-color: #dee2e6; /* Prevents hover effect */
        }
        /* ======================================= */

        
        .form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef; }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; border: 1px solid transparent; }
        .alert-danger { color: #842029; background-color: #f8d7da; border-color: #f5c2c7; }
        .alert-success { color: #0f5132; background-color: #d1e7dd; border-color: #badbcc; }
        .alert ul { margin: 0; padding-left: 1.2rem; }

        @media (max-width: 992px) { .form-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .bottom-cards { grid-template-columns: 1fr; }
            .sidebar { position: relative; width: 100%; height: auto; }
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar-client.php'; ?>
    <div class="main-content">

        <?php if ($action === 'create'): ?>
            
            <!-- CREATE VIEW -->
            <div class="page-header"><h1>Book an Appointment</h1></div>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="card form-container">
                <form action="index.php?action=create" method="POST">
                    <div class="form-grid">
                        <div>
                            <div class="form-group">
                                <label for="pet_id">Pet <span>*</span></label>
                                <select id="pet_id" name="pet_id" required>
                                    <option value="">Select a pet</option>
                                    <?php foreach ($pets as $pet): ?><option value="<?php echo htmlspecialchars($pet['pet_id']); ?>"><?php echo htmlspecialchars($pet['name']); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <br>
                            <div class="form-group"><label for="appointment_date">Select Date <span>*</span></label><input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                            <br>
                             <div class="form-group">
                                <label>Available Time Slots <span>*</span></label>
                                <div class="time-slots-grid" id="time-slots-container">
                                    <?php $time_slots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '14:30', '15:00', '15:30', '16:00', '16:30'];
                                    foreach ($time_slots as $slot): $time_24h = date("H:i:s", strtotime($slot)); ?>
                                        <div class="time-slot"><input type="radio" id="time_<?php echo str_replace(':', '', $slot); ?>" name="appointment_time" value="<?php echo $time_24h; ?>" required><label for="time_<?php echo str_replace(':', '', $slot); ?>"><?php echo $slot; ?></label></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div>
                             <div class="form-group"><label for="reason">Reason for Visit <span>*</span></label><textarea id="reason" name="reason" placeholder="e.g., Annual vaccination, skin irritation..." required></textarea></div><br>
                            <div class="form-group"><label for="notes">Additional Notes</label><textarea id="notes" name="notes" placeholder="Enter any additional information, like symptoms or concerns."></textarea></div>
                        </div>
                    </div>
                    <div class="form-actions"><a href="index.php" class="btn btn-secondary">Cancel</a><button type="submit" name="create_appointment" class="btn btn-primary">Create Appointment</button></div>
                </form>
            </div>

        <?php else: ?>

            <!-- LIST VIEW -->
            <div class="page-header"><h1>Appointments</h1><div class="header-actions"><a href="index.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Create New</a></div></div>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
            <div class="card">
                <div class="table-container">
                    <table class="appointments-table">
                        <thead><tr><th>Date & Time</th><th>Pet</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr><td colspan="5" class="no-data">You have no upcoming appointments.</td></tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $appt): ?>
                                    <tr>
                                        <td><strong><?php echo date("F j, Y", strtotime($appt['appointment_date'])); ?></strong><br><small><?php echo date("g:i A", strtotime($appt['appointment_time'])); ?></small></td>
                                        <td><?php echo htmlspecialchars($appt['pet_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                                        <td><span class="status-badge status-<?php echo htmlspecialchars(strtolower($appt['status'])); ?>"><?php echo htmlspecialchars($appt['status']); ?></span></td>
                                        <td><a href="#" class="action-link">Cancel</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bottom-cards">
                <div class="card">
                    <div class="card-header"><h3>Appointment Guidelines</h3><i class="fas fa-info-circle icon"></i></div>
                    <ul class="guidelines-list">
                        <li>Appointments can be scheduled up to 2 months in advance.</li><li>Please arrive 15 minutes before your scheduled time.</li><li>Cancellations should be made at least 24 hours in advance.</li><li>Bring your pet's medical history if this is your first visit.</li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header"><h3>Your Pets</h3><i class="fas fa-paw icon"></i></div>
                    <?php if (empty($pets)): ?><p>No pets found. You can add a pet in your profile.</p>
                    <?php else: ?>
                        <ul class="pets-list"><?php foreach($pets as $pet): ?><li><?php echo htmlspecialchars($pet['name']); ?></li><?php endforeach; ?></ul>
                        <a href="../my-pets/" class="view-all-link">Manage My Pets</a>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
    
    <script>
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
    </script>

</body>
</html>