<?php
require_once '../../config/init.php';
requireStaff();

$pageTitle = 'Reports & Analytics - ' . SITE_NAME;

// Get date range from query parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default to first day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Default to last day of current month
$reportType = $_GET['report_type'] ?? 'overview';

// Initialize report data
$reportData = [];

// 1. Overview Statistics
function getOverviewStats($pdo, $startDate, $endDate) {
    $stats = [];
    
    // Total appointments in period
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
            SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) as pending_appointments
        FROM appointments 
        WHERE appointment_date BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $stats['appointments'] = $stmt->fetch();
    
    // New patients registered
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_patients 
        FROM pets 
        WHERE DATE(created_at) BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $stats['new_patients'] = $stmt->fetch()['new_patients'];
    
    // Active clients
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.owner_id) as active_clients
        FROM owners o
        JOIN pets p ON o.owner_id = p.owner_id
        JOIN appointments a ON p.pet_id = a.pet_id
        WHERE a.appointment_date BETWEEN :start_date AND :end_date
        AND a.status != 'cancelled'
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $stats['active_clients'] = $stmt->fetch()['active_clients'];
    
    // Medical records created
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as medical_records
        FROM medical_records
        WHERE visit_date BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $stats['medical_records'] = $stmt->fetch()['medical_records'];
    
    return $stats;
}

// 2. Appointment Analytics
function getAppointmentAnalytics($pdo, $startDate, $endDate) {
    $analytics = [];
    
    // Appointments by type
    $stmt = $pdo->prepare("
        SELECT type, COUNT(*) as count
        FROM appointments
        WHERE appointment_date BETWEEN :start_date AND :end_date
        GROUP BY type
        ORDER BY count DESC
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $analytics['by_type'] = $stmt->fetchAll();
    
    // Appointments by day of week
    $stmt = $pdo->prepare("
        SELECT 
            DAYNAME(appointment_date) as day_name,
            DAYOFWEEK(appointment_date) as day_number,
            COUNT(*) as count
        FROM appointments
        WHERE appointment_date BETWEEN :start_date AND :end_date
        AND status != 'cancelled'
        GROUP BY day_name, day_number
        ORDER BY day_number
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $analytics['by_day'] = $stmt->fetchAll();
    
    // Busiest hours
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(appointment_time) as hour,
            COUNT(*) as count
        FROM appointments
        WHERE appointment_date BETWEEN :start_date AND :end_date
        AND status != 'cancelled'
        GROUP BY hour
        ORDER BY hour
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $analytics['by_hour'] = $stmt->fetchAll();
    
    // Daily appointment trend
    $stmt = $pdo->prepare("
        SELECT 
            appointment_date,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM appointments
        WHERE appointment_date BETWEEN :start_date AND :end_date
        GROUP BY appointment_date
        ORDER BY appointment_date
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $analytics['daily_trend'] = $stmt->fetchAll();
    
    return $analytics;
}

// 3. Patient Analytics
function getPatientAnalytics($pdo, $startDate, $endDate) {
    $analytics = [];
    
    // Patients by species
    $stmt = $pdo->prepare("
        SELECT species, COUNT(*) as count
        FROM pets
        WHERE is_active = 1
        GROUP BY species
        ORDER BY count DESC
    ");
    $stmt->execute();
    $analytics['by_species'] = $stmt->fetchAll();
    
    // Most frequent patients
    $stmt = $pdo->prepare("
        SELECT 
            p.name as pet_name,
            p.species,
            CONCAT(u.first_name, ' ', u.last_name) as owner_name,
            COUNT(a.appointment_id) as visit_count
        FROM pets p
        JOIN owners o ON p.owner_id = o.owner_id
        JOIN users u ON o.user_id = u.user_id
        JOIN appointments a ON p.pet_id = a.pet_id
        WHERE a.appointment_date BETWEEN :start_date AND :end_date
        AND a.status = 'completed'
        GROUP BY p.pet_id
        ORDER BY visit_count DESC
        LIMIT 10
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $analytics['frequent_patients'] = $stmt->fetchAll();
    
    // Age distribution
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 1 THEN 'Less than 1 year'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 1 AND 3 THEN '1-3 years'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 4 AND 7 THEN '4-7 years'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 8 AND 12 THEN '8-12 years'
                ELSE 'Over 12 years'
            END as age_group,
            COUNT(*) as count
        FROM pets
        WHERE is_active = 1
        AND date_of_birth IS NOT NULL
        GROUP BY age_group
        ORDER BY 
            CASE age_group
                WHEN 'Less than 1 year' THEN 1
                WHEN '1-3 years' THEN 2
                WHEN '4-7 years' THEN 3
                WHEN '8-12 years' THEN 4
                ELSE 5
            END
    ");
    $stmt->execute();
    $analytics['age_distribution'] = $stmt->fetchAll();
    
    return $analytics;
}

// 4. Medical Records Analytics
function getMedicalAnalytics($pdo, $startDate, $endDate) {
    $analytics = [];
    
    // Common diagnoses
    $stmt = $pdo->prepare("
        SELECT 
            diagnosis,
            COUNT(*) as count
        FROM medical_records
        WHERE visit_date BETWEEN :start_date AND :end_date
        AND diagnosis IS NOT NULL AND diagnosis != ''
        GROUP BY diagnosis
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $analytics['common_diagnoses'] = $stmt->fetchAll();
    
    // Follow-up rate
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN follow_up_required = 1 THEN 1 ELSE 0 END) as follow_ups_required,
            SUM(CASE WHEN follow_up_required = 1 AND follow_up_date IS NOT NULL THEN 1 ELSE 0 END) as follow_ups_scheduled
        FROM medical_records
        WHERE visit_date BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $analytics['follow_up_stats'] = $stmt->fetch();
    
    return $analytics;
}

// Get report data based on type
switch ($reportType) {
    case 'overview':
        $reportData = getOverviewStats($pdo, $startDate, $endDate);
        break;
    case 'appointments':
        $reportData = getAppointmentAnalytics($pdo, $startDate, $endDate);
        break;
    case 'patients':
        $reportData = getPatientAnalytics($pdo, $startDate, $endDate);
        break;
    case 'medical':
        $reportData = getMedicalAnalytics($pdo, $startDate, $endDate);
        break;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Report specific styles */
        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex-grow: 1;
            padding: 2rem;
            background: #f5f7fa;
            min-height: 100vh;
        }

        /* Report Header */
        .report-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .report-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .report-title h1 {
            font-size: 2rem;
            color: var(--dark-color);
            margin: 0;
            font-weight: 700;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background: #17a294;
            transform: translateY(-1px);
        }

        /* Report Filters */
        .report-filters {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .filter-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        .filter-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(29, 186, 168, 0.1);
        }

        .filter-select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.875rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(29, 186, 168, 0.1);
        }

        .apply-filter-btn {
            padding: 0.5rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .apply-filter-btn:hover {
            background: #17a294;
        }

        /* Report Tabs */
        .report-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .report-tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            color: #6b7280;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: -2px;
        }

        .report-tab:hover {
            color: var(--primary-color);
        }

        .report-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .stat-change {
            font-size: 0.875rem;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.negative {
            color: #ef4444;
        }

        /* Chart Containers */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .chart-container.full-width {
            grid-column: 1 / -1;
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        /* Table Styles */
        .report-table {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            overflow-x: auto;
        }

        .table-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
            color: var(--dark-color);
        }

        tr:hover {
            background: #f9fafb;
        }

        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .no-data-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .report-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .report-filters {
                flex-direction: column;
                width: 100%;
            }

            .filter-group {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .chart-grid {
                grid-template-columns: 1fr;
            }

            .report-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../../includes/sidebar-staff.php'; ?>
        <?php include '../../includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Report Header -->
            <div class="report-header">
                <div class="report-title">
                    <h1>Reports & Analytics</h1>
                    <div class="export-buttons">
                        <button class="export-btn" onclick="exportToPDF()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                            Export PDF
                        </button>
                        <button class="export-btn" onclick="exportToExcel()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                                <polyline points="13 2 13 9 20 9"/>
                            </svg>
                            Export Excel
                        </button>
                    </div>
                </div>

                <!-- Report Filters -->
                <form method="GET" class="report-filters">
                    <div class="filter-group">
                        <label class="filter-label">Report Type</label>
                        <select name="report_type" class="filter-select">
                            <option value="overview" <?php echo $reportType === 'overview' ? 'selected' : ''; ?>>Overview</option>
                            <option value="appointments" <?php echo $reportType === 'appointments' ? 'selected' : ''; ?>>Appointments</option>
                            <option value="patients" <?php echo $reportType === 'patients' ? 'selected' : ''; ?>>Patients</option>
                            <option value="medical" <?php echo $reportType === 'medical' ? 'selected' : ''; ?>>Medical Records</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="filter-input">
                    </div>
                    <button type="submit" class="apply-filter-btn">Apply Filters</button>
                </form>
            </div>

            <!-- Report Content -->
            <?php if ($reportType === 'overview'): ?>
                <!-- Overview Report -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Appointments</div>
                        <div class="stat-value"><?php echo $reportData['appointments']['total_appointments']; ?></div>
                        <div class="stat-change">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                            <?php echo $reportData['appointments']['completed_appointments']; ?> completed
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">New Patients</div>
                        <div class="stat-value"><?php echo $reportData['new_patients']; ?></div>
                        <div class="stat-change">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="19" y1="8" x2="19" y2="14"/>
                                <line x1="22" y1="11" x2="16" y2="11"/>
                            </svg>
                            This period
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Clients</div>
                        <div class="stat-value"><?php echo $reportData['active_clients']; ?></div>
                        <div class="stat-change">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            With appointments
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Cancellation Rate</div>
                        <div class="stat-value">
                            <?php 
                                $cancellationRate = $reportData['appointments']['total_appointments'] > 0 
                                    ? round(($reportData['appointments']['cancelled_appointments'] / $reportData['appointments']['total_appointments']) * 100, 1)
                                    : 0;
                                echo $cancellationRate . '%';
                            ?>
                        </div>
                        <div class="stat-change <?php echo $cancellationRate > 10 ? 'negative' : ''; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <?php if ($cancellationRate > 10): ?>
                                    <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                                    <polyline points="17 18 23 18 23 12"/>
                                <?php else: ?>
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                    <polyline points="17 6 23 6 23 12"/>
                                <?php endif; ?>
                            </svg>
                            <?php echo $reportData['appointments']['cancelled_appointments']; ?> cancelled
                        </div>
                    </div>
                </div>

                <!-- Quick Summary Table -->
                <div class="report-table">
                    <h3 class="table-title">Period Summary</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Count</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Completed Appointments</td>
                                <td><?php echo $reportData['appointments']['completed_appointments']; ?></td>
                                <td><span style="color: #10b981;">✓ Good</span></td>
                            </tr>
                            <tr>
                                <td>Pending Appointments</td>
                                <td><?php echo $reportData['appointments']['pending_appointments']; ?></td>
                                <td><span style="color: #f59e0b;">⚠ Review</span></td>
                            </tr>
                            <tr>
                                <td>Medical Records Created</td>
                                <td><?php echo $reportData['medical_records']; ?></td>
                                <td><span style="color: #10b981;">✓ On track</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reportType === 'appointments'): ?>
                <!-- Appointments Report -->
                <div class="chart-grid">
                    <!-- Appointments by Type -->
                    <div class="chart-container">
                        <h3 class="chart-title">Appointments by Type</h3>
                        <div class="chart-wrapper">
                            <canvas id="appointmentsByTypeChart"></canvas>
                        </div>
                    </div>

                    <!-- Appointments by Day of Week -->
                    <div class="chart-container">
                        <h3 class="chart-title">Appointments by Day of Week</h3>
                        <div class="chart-wrapper">
                            <canvas id="appointmentsByDayChart"></canvas>
                        </div>
                    </div>

                    <!-- Daily Appointment Trend -->
                    <div class="chart-container full-width">
                        <h3 class="chart-title">Daily Appointment Trend</h3>
                        <div class="chart-wrapper">
                            <canvas id="dailyTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Busiest Hours -->
                    <div class="chart-container">
                        <h3 class="chart-title">Busiest Hours</h3>
                        <div class="chart-wrapper">
                            <canvas id="busiestHoursChart"></canvas>
                        </div>
                    </div>
                </div>

            <?php elseif ($reportType === 'patients'): ?>
                <!-- Patients Report -->
                <div class="chart-grid">
                    <!-- Patients by Species -->
                    <div class="chart-container">
                        <h3 class="chart-title">Patients by Species</h3>
                        <div class="chart-wrapper">
                            <canvas id="patientsBySpeciesChart"></canvas>
                        </div>
                    </div>

                    <!-- Age Distribution -->
                    <div class="chart-container">
                        <h3 class="chart-title">Age Distribution</h3>
                        <div class="chart-wrapper">
                            <canvas id="ageDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Most Frequent Patients -->
                <div class="report-table">
                    <h3 class="table-title">Most Frequent Patients</h3>
                    <?php if (!empty($reportData['frequent_patients'])): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Pet Name</th>
                                    <th>Species</th>
                                    <th>Owner</th>
                                    <th>Visits</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['frequent_patients'] as $patient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient['pet_name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['species']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['owner_name']); ?></td>
                                        <td><?php echo $patient['visit_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-icon">📊</div>
                            <p>No patient data available for this period.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($reportType === 'medical'): ?>
                <!-- Medical Records Report -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Medical Records</div>
                        <div class="stat-value"><?php echo $reportData['follow_up_stats']['total_records']; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Follow-ups Required</div>
                        <div class="stat-value"><?php echo $reportData['follow_up_stats']['follow_ups_required']; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Follow-ups Scheduled</div>
                        <div class="stat-value"><?php echo $reportData['follow_up_stats']['follow_ups_scheduled']; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Follow-up Rate</div>
                        <div class="stat-value">
                            <?php 
                                $followUpRate = $reportData['follow_up_stats']['total_records'] > 0 
                                    ? round(($reportData['follow_up_stats']['follow_ups_required'] / $reportData['follow_up_stats']['total_records']) * 100, 1)
                                    : 0;
                                echo $followUpRate . '%';
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Common Diagnoses -->
                <div class="report-table">
                    <h3 class="table-title">Common Diagnoses</h3>
                    <?php if (!empty($reportData['common_diagnoses'])): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Diagnosis</th>
                                    <th>Occurrences</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalDiagnoses = array_sum(array_column($reportData['common_diagnoses'], 'count'));
                                foreach ($reportData['common_diagnoses'] as $diagnosis): 
                                    $percentage = $totalDiagnoses > 0 ? round(($diagnosis['count'] / $totalDiagnoses) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($diagnosis['diagnosis']); ?></td>
                                        <td><?php echo $diagnosis['count']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div style="width: 100px; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                                                    <div style="width: <?php echo $percentage; ?>%; height: 100%; background: var(--primary-color);"></div>
                                                </div>
                                                <span><?php echo $percentage; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-icon">🏥</div>
                            <p>No medical record data available for this period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Chart.js Scripts -->
    <script>
        // Chart default configuration
        Chart.defaults.font.family = "'Inter', -apple-system, sans-serif";
        Chart.defaults.color = '#374151';

        <?php if ($reportType === 'appointments' && !empty($reportData)): ?>
        // Appointments by Type Chart
        const appointmentsByTypeCtx = document.getElementById('appointmentsByTypeChart').getContext('2d');
        new Chart(appointmentsByTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($reportData['by_type'], 'type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($reportData['by_type'], 'count')); ?>,
                    backgroundColor: [
                        '#1DBAA8',
                        '#ffac36',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        padding: 20
                    }
                }
            }
        });

        // Appointments by Day Chart
        const appointmentsByDayCtx = document.getElementById('appointmentsByDayChart').getContext('2d');
        new Chart(appointmentsByDayCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($reportData['by_day'], 'day_name')); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode(array_column($reportData['by_day'], 'count')); ?>,
                    backgroundColor: '#1DBAA8',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Daily Trend Chart
        const dailyTrendCtx = document.getElementById('dailyTrendChart').getContext('2d');
        new Chart(dailyTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($reportData['daily_trend'], 'appointment_date')); ?>,
                datasets: [{
                    label: 'Total Appointments',
                    data: <?php echo json_encode(array_column($reportData['daily_trend'], 'total')); ?>,
                    borderColor: '#ffac36',
                    backgroundColor: 'rgba(29, 186, 168, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column($reportData['daily_trend'], 'completed')); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Cancelled',
                    data: <?php echo json_encode(array_column($reportData['daily_trend'], 'cancelled')); ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        padding: 20
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Busiest Hours Chart
        const busiestHoursCtx = document.getElementById('busiestHoursChart').getContext('2d');
        new Chart(busiestHoursCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($hour) {
                    return ($hour['hour'] < 12 ? ($hour['hour'] ?: 12) . ' AM' : 
                           ($hour['hour'] == 12 ? 12 : $hour['hour'] - 12) . ' PM');
                }, $reportData['by_hour'])); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode(array_column($reportData['by_hour'], 'count')); ?>,
                    backgroundColor: '#1DBAA8',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if ($reportType === 'patients' && !empty($reportData)): ?>
        // Patients by Species Chart
        const patientsBySpeciesCtx = document.getElementById('patientsBySpeciesChart').getContext('2d');
        new Chart(patientsBySpeciesCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($reportData['by_species'], 'species')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($reportData['by_species'], 'count')); ?>,
                    backgroundColor: [
                        '#1DBAA8',
                        '#ffac36',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        padding: 20
                    }
                }
            }
        });

        // Age Distribution Chart
        const ageDistributionCtx = document.getElementById('ageDistributionChart').getContext('2d');
        new Chart(ageDistributionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($reportData['age_distribution'], 'age_group')); ?>,
                datasets: [{
                    label: 'Number of Pets',
                    data: <?php echo json_encode(array_column($reportData['age_distribution'], 'count')); ?>,
                    backgroundColor: '#1DBAA8',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Export functions
        function exportToPDF() {
            alert('PDF export functionality would be implemented here.');
            // In a real implementation, you would use a library like jsPDF or server-side PDF generation
        }

        function exportToExcel() {
            alert('Excel export functionality would be implemented here.');
            // In a real implementation, you would use a library like SheetJS or server-side Excel generation
        }
    </script>
</body>
</html>