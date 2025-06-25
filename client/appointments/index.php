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
        // --- PAGINATION LOGIC START ---
        $items_per_page = 5; // Define how many appointments to show per page
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($current_page < 1) $current_page = 1;

        // 1. Get the total number of appointments for this specific owner
        $total_items = 0;
        $total_pages = 1;
        try {
            $count_stmt = $pdo->prepare("
                SELECT COUNT(a.appointment_id) 
                FROM appointments a
                JOIN pets p ON a.pet_id = p.pet_id
                WHERE p.owner_id = ?
            ");
            $count_stmt->execute([$owner_id]);
            $total_items = (int)$count_stmt->fetchColumn();
            if ($total_items > 0) {
                 $total_pages = ceil($total_items / $items_per_page);
            }
           
        } catch (PDOException $e) {
            $errors[] = "Could not retrieve appointment count.";
        }
        
        // 2. Calculate the offset for the SQL query
        $offset = ($current_page - 1) * $items_per_page;
        // --- PAGINATION LOGIC END ---

        // 3. Fetch the paginated results for the current page
        try {
            $stmt_appts = $pdo->prepare("
                SELECT a.*, p.name AS pet_name 
                FROM appointments a
                JOIN pets p ON a.pet_id = p.pet_id
                WHERE p.owner_id = :owner_id 
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                LIMIT :limit OFFSET :offset
            ");
            
            // Bind parameters using ONLY named placeholders
            $stmt_appts->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
            $stmt_appts->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
            $stmt_appts->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt_appts->execute();
            
            $appointments = $stmt_appts->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $errors[] = "Could not retrieve appointments.";
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
        .btn-primary { background-color: var(--primary-color);; color: white; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #f1f3f5; color: #495057; border: 1px solid #dee2e6; }
        .btn-secondary:hover { background-color: var(--primary-color) }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }
        .cancel-appointment-btn {
            background-color: #dc3545; /* A standard 'danger' red */
            color: white;
            border-color: #dc3545;
        }

        .cancel-appointment-btn:hover {
            background-color: #c82333; /* A slightly darker red for hover */
            border-color: #c82333;
        }

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
            background-color: var(--primary-color); color: white; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(0,123,255,.25);
        }
        .time-slot label:hover { border-color: var(--primary-color); }


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
    <?php include '../../includes/navbar.php'; ?>
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
                                    <tr data-appointment-id="<?php echo $appt['appointment_id']; ?>">
                                        <td>
                                            <strong><?php echo date("F j, Y", strtotime($appt['appointment_date'])); ?></strong><br>
                                            <small><?php echo date("g:i A", strtotime($appt['appointment_time'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appt['pet_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                                        <td class="status-cell">
                                            <span class="status-badge status-<?php echo htmlspecialchars(strtolower($appt['status'])); ?>">
                                                <?php echo htmlspecialchars($appt['status']); ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <?php if (!in_array($appt['status'], ['cancelled', 'completed'])): ?>
                                                <button class="btn btn-secondary btn-sm cancel-appointment-btn">Cancel</button>
                                            <?php else: ?>
                                                <span>-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                    <!-- PAGINATION CONTROLS START -->
                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                        <div class="pagination-controls">
                            <!-- Previous Button -->
                            <a href="?page=<?php echo $current_page - 1; ?>" 
                            class="pagination-link <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                « Previous
                            </a>

                            <!-- Page Number Links -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" 
                                class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <a href="?page=<?php echo $current_page + 1; ?>" 
                            class="pagination-link <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                Next »
                            </a>
                        </div>
                    <?php endif; ?> 
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
                        // Update the UI without a page reload
                        const statusCell = row.querySelector('.status-cell .status-badge');
                        statusCell.textContent = 'cancelled';
                        statusCell.className = 'status-badge status-cancelled';

                        // Remove the button
                        const actionCell = row.querySelector('.action-cell');
                        actionCell.innerHTML = '<span>-</span>';
                        
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

    </script>

</body>
</html>