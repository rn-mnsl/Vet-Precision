<?php
require_once '../../config/init.php';

// Authentication & permissions
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: ../../login.php');
    exit();
}

$pageTitle = 'Client Management - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
            width: 600px;
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

        .btn-secondary {
            background: white;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--light-color);
            border-color: var(--text-light);
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

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
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

        .action-edit {
            color: var(--primary-color);
            background: rgba(255, 107, 107, 0.1);
        }

        .action-edit:hover {
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

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: var(--transition-base);
        }

        .modal-close:hover {
            background: var(--light-color);
            color: var(--text-dark);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: var(--light-color);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            transition: var(--transition-base);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .form-required {
            color: var(--danger-color);
        }

        /* Loading State */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Responsive Design */
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-responsive {
                display: none;
            }

            .mobile-cards {
                display: block;
            }

            .pagination-container {
                flex-direction: column;
                gap: 1rem;
            }

            .pagination-controls {
                justify-content: center;
            }
        }

        .mobile-cards {
            display: none;
        }

        .client-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .client-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .client-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .client-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .client-info-item {
            display: flex;
            flex-direction: column;
        }

        .client-info-label {
            font-size: 0.75rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .client-info-value {
            font-size: 0.875rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .client-actions {
            display: flex;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-light);
        }

        @media (max-width: 768px) {
            .table-responsive {
                display: none;
            }

            .mobile-cards {
                display: block;
            }

            .client-actions .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include '../../includes/sidebar-staff.php'; ?>
        <?php include '../../includes/navbar.php'; ?>
        
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Client Management</h1>
                <p>Manage your clients and their information</p>
            </div>

            <!-- Controls Section -->
            <div class="controls-section">
                <div class="controls-row">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search clients..." id="searchInput">
                    </div>
                    <button class="btn btn-primary" onclick="createClient()">
                        <i class="fas fa-plus"></i>
                        Add New Client
                    </button>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table"></i> Clients List</h3>
                </div>
                <div class="card-body">
                    <!-- Desktop Table -->
                    <div class="table-responsive">
                        <table class="table" id="patientsTable">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user"></i> Name</th>
                                    <th><i class="fas fa-envelope"></i> Email</th>
                                    <th><i class="fas fa-circle"></i> Status</th>
                                    <th><i class="fas fa-calendar"></i> Created</th>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- Table data will be loaded here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="mobile-cards" id="mobileCards">
                        <!-- Mobile cards will be loaded here -->
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination-container">
                    <div class="pagination-info" id="paginationInfo">
                        <!-- Pagination info will be updated here -->
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Pagination controls will be generated here -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Client</h2>
                <button type="button" class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="clientForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                First Name <span class="form-required">*</span>
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Last Name <span class="form-required">*</span>
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter last name" required>
                        </div>
                    </div>

                    <div class="form-group form-group-full">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter phone number">
                    </div>

                    <div class="form-group form-group-full">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" placeholder="Enter address">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" placeholder="Enter city">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="is_active" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitButton">
                        <i class="fas fa-save"></i>
                        <span id="submitButtonText">Create Client</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global state & config
        let currentPage = 1;
        const entriesPerPage = 10; // Fixed at 10 entries per page
        let totalRecords = 0;
        let searchTimeout;

        const API_URL = 'ajax/owners_handler.php';

        // DOM elements
        const tableBody = document.getElementById('tableBody');
        const mobileCards = document.getElementById('mobileCards');
        const searchInput = document.getElementById('searchInput');
        const paginationControls = document.getElementById('paginationControls');
        const paginationInfo = document.getElementById('paginationInfo');
        const clientModal = document.getElementById('clientModal');
        const clientForm = document.getElementById('clientForm');
        const modalTitle = document.getElementById('modalTitle');
        const modalSubmitButton = document.getElementById('modalSubmitButton');
        const submitButtonText = document.getElementById('submitButtonText');

        // Data fetching & rendering
        async function fetchPatients() {
            const searchTerm = searchInput.value;
            const url = new URL(API_URL, window.location.href);
            url.searchParams.append('action', 'fetch');
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);
            url.searchParams.append('search', searchTerm);

            try {
                // Show loading state
                tableBody.innerHTML = `<tr><td colspan="7" class="empty-state"><i class="fas fa-spinner fa-spin"></i><br>Loading...</td></tr>`;
                
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                
                if (result.success) {
                    totalRecords = result.totalRecords;
                    renderTable(result.data);
                    renderMobileCards(result.data);
                    updatePaginationInfo();
                    renderPaginationControls();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Failed to fetch patients:', error);
                showError('Error loading data. Please try again.');
            }
        }

// Update the renderTable function (around line 950)
function renderTable(patients) {
    if (patients.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state">
                    <i class="fas fa-users"></i>
                    <br>No clients found
                    <br><small>Try adjusting your search terms</small>
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = patients.map(p => {
        const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim();
        const statusClass = p.is_active == 1 ? 'status-active' : 'status-inactive';
        const statusText = p.is_active == 1 ? 'Active' : 'Inactive';
        const createdAt = new Date(p.user_created_at).toLocaleDateString();

        return `
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 2rem; height: 2rem; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                            ${(p.first_name?.[0] || '') + (p.last_name?.[0] || '')}
                        </div>
                        <span style="font-weight: 600;">${fullName || 'N/A'}</span>
                    </div>
                </td>
                <td>${p.email || 'N/A'}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${createdAt}</td>
                <td class="action-links">
                    <a href="#" class="action-link action-edit" onclick="event.preventDefault(); editClient(${p.user_id})">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="#" class="action-link action-delete" onclick="event.preventDefault(); deleteClient(${p.user_id})">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </td>
            </tr>
        `;
    }).join('');
}

// Update the renderMobileCards function to also remove phone from mobile view (around line 1000)
function renderMobileCards(patients) {
    if (patients.length === 0) {
        mobileCards.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <br>No clients found
                <br><small>Try adjusting your search terms</small>
            </div>
        `;
        return;
    }

    mobileCards.innerHTML = patients.map(p => {
        const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim();
        const statusClass = p.is_active == 1 ? 'status-active' : 'status-inactive';
        const statusText = p.is_active == 1 ? 'Active' : 'Inactive';
        const createdAt = new Date(p.user_created_at).toLocaleDateString();

        return `
            <div class="client-card">
                <div class="client-card-header">
                    <h3 class="client-name">${fullName || 'N/A'}</h3>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
                
                <div class="client-info">
                    <div class="client-info-item">
                        <span class="client-info-label">Email</span>
                        <span class="client-info-value">${p.email || 'N/A'}</span>
                    </div>
                    <div class="client-info-item">
                        <span class="client-info-label">Created</span>
                        <span class="client-info-value">${createdAt}</span>
                    </div>
                </div>
                
                <div class="client-actions">
                    <button class="btn btn-sm btn-primary" onclick="editClient(${p.user_id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteClient(${p.user_id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;
    }).join('');
}
// Update the showError function to use the correct colspan (around line 1040)
function showError(message) {
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" class="empty-state">
                <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
                <br>${message}
            </td>
        </tr>
    `;
    mobileCards.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
            <br>${message}
        </div>
    `;
}

        // Pagination functions
        function updatePaginationInfo() {
            const start = totalRecords === 0 ? 0 : (currentPage - 1) * entriesPerPage + 1;
            const end = Math.min(currentPage * entriesPerPage, totalRecords);
            paginationInfo.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;
        }

        function renderPaginationControls() {
            const totalPages = Math.ceil(totalRecords / entriesPerPage);
            
            if (totalPages <= 1) {
                paginationControls.innerHTML = '';
                return;
            }

            const links = [];

            // Previous button
            const prevClass = currentPage === 1 ? 'disabled' : '';
            links.push(`
                <a href="#" class="pagination-link ${prevClass}" onclick="goToPage(${currentPage - 1}, event)">
                    <i class="fas fa-chevron-left"></i>
                </a>
            `);

            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === currentPage ? 'active' : '';
                links.push(`
                    <a href="#" class="pagination-link ${activeClass}" onclick="goToPage(${i}, event)">
                        ${i}
                    </a>
                `);
            }

            // Next button
            const nextClass = currentPage === totalPages ? 'disabled' : '';
            links.push(`
                <a href="#" class="pagination-link ${nextClass}" onclick="goToPage(${currentPage + 1}, event)">
                    <i class="fas fa-chevron-right"></i>
                </a>
            `);

            paginationControls.innerHTML = links.join('');
        }

        function goToPage(page, event) {
            if (event) event.preventDefault();
            
            const totalPages = Math.ceil(totalRecords / entriesPerPage);
            if (page === currentPage || page < 1 || page > totalPages) {
                return;
            }
            
            currentPage = page;
            fetchPatients();
        }

        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                fetchPatients();
            }, 300);
        }

        // Modal functions
        function openModal() {
            clientModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            clientModal.classList.remove('show');
            document.body.style.overflow = '';
            clientForm.reset();
            clientForm.removeAttribute('data-editing-id');
        }

        function createClient() {
            clientForm.reset();
            modalTitle.innerHTML = '<i class="fas fa-plus"></i> Add New Client';
            submitButtonText.textContent = 'Create Client';
            document.getElementById('is_active').value = '1';
            openModal();
        }

        async function editClient(userId) {
            const client = await getClientById(userId);
            if (!client) return showNotification("Could not retrieve client data.", 'error');
            
            clientForm.reset();
            modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Client';
            submitButtonText.textContent = 'Save Changes';
            
            document.getElementById('user_id').value = client.user_id;
            document.getElementById('first_name').value = client.first_name || '';
            document.getElementById('last_name').value = client.last_name || '';
            document.getElementById('email').value = client.email || '';
            document.getElementById('phone').value = client.phone || '';
            document.getElementById('address').value = client.address || '';
            document.getElementById('city').value = client.city || '';
            document.getElementById('is_active').value = client.is_active;
            
            clientForm.setAttribute('data-editing-id', userId);
            openModal();
        }

        async function getClientById(userId) {
            try {
                const response = await fetch(`${API_URL}?action=fetch&search=&limit=${totalRecords || 1000}`);
                const result = await response.json();
                return result.success ? result.data.find(p => p.user_id === userId) : null;
            } catch (error) {
                console.error('Error fetching client:', error);
                return null;
            }
        }

        async function deleteClient(userId) {
            if (!confirm("Are you sure you want to deactivate this client?")) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', userId);
            
            try {
                const response = await fetch(API_URL, { method: 'POST', body: formData });
                const result = await response.json();
                
                showNotification(result.message, result.success ? 'success' : 'error');
                
                if (result.success) {
                    if (tableBody.children.length === 1 && currentPage > 1) {
                        currentPage--;
                    }
                    fetchPatients();
                }
            } catch (error) {
                console.error('Failed to delete client:', error);
                showNotification('An error occurred. Please try again.', 'error');
            }
        }

        // Form submission
        clientForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const formData = new FormData(clientForm);
            formData.append('action', clientForm.hasAttribute('data-editing-id') ? 'update' : 'create');
            
            if (!formData.get('first_name') || !formData.get('last_name')) {
                return showNotification('First Name and Last Name are required.', 'error');
            }

            // Show loading state
            modalSubmitButton.classList.add('loading');
            modalSubmitButton.disabled = true;
            submitButtonText.textContent = 'Saving...';

            try {
                const response = await fetch(API_URL, { method: 'POST', body: formData });
                const result = await response.json();
                
                showNotification(result.message, result.success ? 'success' : 'error');
                
                if (result.success) {
                    closeModal();
                    fetchPatients();
                }
            } catch (error) {
                console.error('Form submission error:', error);
                showNotification('An error occurred while saving.', 'error');
            } finally {
                modalSubmitButton.classList.remove('loading');
                modalSubmitButton.disabled = false;
                submitButtonText.textContent = clientForm.hasAttribute('data-editing-id') ? 'Save Changes' : 'Create Client';
            }
        });

        // Notification system
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            document.querySelectorAll('.notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-md);
                color: white;
                font-weight: 600;
                z-index: 9999;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 20rem;
                box-shadow: var(--shadow-lg);
            `;
            
            if (type === 'success') {
                notification.style.background = 'var(--success-color)';
            } else if (type === 'error') {
                notification.style.background = 'var(--danger-color)';
            } else {
                notification.style.background = 'var(--primary-color)';
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Animate out
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            fetchPatients();
            searchInput.addEventListener('input', handleSearch);
            
            // Modal close on backdrop click
            clientModal.addEventListener('click', (e) => {
                if (e.target === clientModal) {
                    closeModal();
                }
            });
            
            // Escape key to close modal
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && clientModal.classList.contains('show')) {
                    closeModal();
                }
            });
        });

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
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